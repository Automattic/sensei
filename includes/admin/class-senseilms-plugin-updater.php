<?php
/**
 * File containing SenseiLMS_Plugin_Updater class.
 *
 * @package Sensei\Admin
 * @since   4.14.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Manages plugin updates by hooking into WordPress's plugins update system and querying SenseiLMS Store's API.
 *
 * It's a fallback to SenseiLMS_Licensing\SenseiLMS_Plugin_Updater from Sensei Pro. So it works when
 * Sensei Pro is in a legacy version that depends on the activated license.
 *
 * @since 4.14.0
 */
class SenseiLMS_Plugin_Updater {

	const CACHE_KEY_PREFIX = 'senseilms_plugin_updater_info__';
	const CACHE_TTL        = 3600;

	/**
	 * Full qualified name for the plugin. Relative path from plugins directory. Example: 'akismet/akismet.php'.
	 *
	 * @var string
	 */
	private $plugin_full_name;

	/**
	 * Plugin slug name. Example: 'akismet'.
	 *
	 * @var string
	 */
	private $plugin_slug;

	/**
	 * Current version for the plugin. Example: '1.0.0'.
	 *
	 * @var string
	 */
	private $version;

	/**
	 * Private constructor.
	 *
	 * @param string $main_plugin_file_absolute_path Plugin's main file path.
	 * @param string $version                        Plugin version.
	 */
	private function __construct( $main_plugin_file_absolute_path, $version ) {
		$this->plugin_full_name = plugin_basename( $main_plugin_file_absolute_path );
		$this->plugin_slug      = basename( $main_plugin_file_absolute_path, '.php' );
		$this->version          = $version;
	}

	/**
	 * Initialize the plugin updater.
	 */
	public static function init() {
		add_action( 'plugins_loaded', [ __CLASS__, 'plugins_loaded' ] );
	}

	/**
	 * Add hooks after the `plugins_loaded`, so we make sure Sensei Pro was
	 * already loaded.
	 */
	public static function plugins_loaded() {
		// Early return if license and updates are managed by WooCommerce.
		if ( defined( 'SENSEI_COMPAT_PLUGIN' ) && SENSEI_COMPAT_PLUGIN ) {
			return;
		}

		if ( class_exists( '\Sensei_Pro_Interactive_Blocks\Setup_Context' ) ) {
			$instance = new self( SENSEI_IB_PLUGIN_FILE, SENSEI_IB_VERSION );
		} elseif ( class_exists( '\Sensei_Pro\Setup_Context' ) ) {
			$instance = new self( SENSEI_PRO_PLUGIN_FILE, SENSEI_PRO_VERSION );
		} else {
			return;
		}

		add_filter( 'plugins_api', [ $instance, 'get_plugin_info' ], 15, 3 );
		add_filter( 'site_transient_update_plugins', [ $instance, 'maybe_inject_custom_update_to_update_plugins_transient' ], 15 );
		add_action( 'in_plugin_update_message-' . $instance->plugin_full_name, [ $instance, 'invalid_license_update_disclaimer' ] );
	}

	/**
	 * Get plugin information as expected by the `plugins_api` hook.
	 * This will be called to display the details for the updated in the detailed view.
	 *
	 * @param false|object|array $res    Result. As defined per the `plugins_api` hook.
	 * @param string             $action The action being executed. As defined per the `plugins_api` hook.
	 * @param object             $args   The arguments. As defined per the `plugins_api` hook.
	 *
	 * @hooked plugins_api
	 *
	 * @return false|object If other than false is returned the actual call to wordpress.org is not done.
	 */
	public function get_plugin_info( $res, $action, $args ) {
		if (
			'plugin_information' !== $action
			|| $this->plugin_slug !== $args->slug
			|| false !== $res
		) {
			return $res;
		}

		$remote = $this->request_info();
		if ( is_wp_error( $remote ) ) {
			// Early return in case request to SenseiLMS.com failed.
			return $res;
		}

		$res                = new stdClass();
		$res->name          = $remote->name;
		$res->slug          = $remote->slug;
		$res->author        = $remote->author;
		$res->version       = $remote->version;
		$res->requires      = $remote->requires;
		$res->tested        = $remote->tested;
		$res->requires_php  = $remote->requires_php;
		$res->last_updated  = $remote->last_updated;
		$res->sections      = [
			'description'  => $remote->sections->description,
			'installation' => $remote->sections->installation,
			'changelog'    => $remote->sections->changelog,
		];
		$res->download_link = $remote->download_url;
		$res->banners       = [
			'low'  => $remote->banners->{'1x'},
			'high' => $remote->banners->{'2x'},
		];

		Sensei()->assets->enqueue( 'sensei-updater-styles', 'css/senseilms-licensing.css' );

		return $res;
	}

	/**
	 * Potentially injects the details for a new plugin version by checking against the remote server.
	 * This is done by hooking into the `update_plugins` transient by using the `site_transient_update_plugins` hook.
	 *
	 * @param mixed $transient The plugin_update transient.
	 *
	 * @hooked site_transient_update_plugins See reference for `site_transient_transient`.
	 *
	 * @return mixed
	 */
	public function maybe_inject_custom_update_to_update_plugins_transient( $transient ) {

		// Skip empty transients or if it was already set by Sensei Pro.
		if ( empty( $transient ) || isset( $transient->response[ $this->plugin_full_name ] ) ) {
			return $transient;
		}

		$remote = $this->request_info();
		if ( is_wp_error( $remote ) ) {
			// Request failed so do not inject anything into the transient.
			return $transient;
		}

		if (
			$remote
			&& version_compare( $this->version, $remote->version, '<' )
			&& version_compare( get_bloginfo( 'version' ), $remote->requires, '>=' )
			&& version_compare( PHP_VERSION, $remote->requires_php, '>=' )
		) {

			$res                                 = new stdClass();
			$res->slug                           = $remote->slug;
			$res->plugin                         = $this->plugin_full_name;
			$res->new_version                    = $remote->version;
			$res->tested                         = $remote->tested;
			$res->package                        = $remote->download_url;
			$res->icons                          = (array) $remote->icons;
			$transient->response[ $res->plugin ] = $res;
		}
		return $transient;
	}

	/**
	 * Helper function that retrieves the latest version information from the remote server if there is a valid license in the system.
	 * This function caches remote response by using transients.
	 *
	 * @return array|WP_Error
	 */
	private function request_info() {
		$cache_key = self::CACHE_KEY_PREFIX . $this->plugin_slug;
		$remote    = get_transient( $cache_key );

		if ( false === $remote ) {
			// @phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
			$api_url = apply_filters( 'senseilms_licensing_api', 'https://senseilms.com/wp-json' );

			$remote = wp_remote_get(
				add_query_arg(
					[
						'plugin_slug' => $this->plugin_slug,
						'ts'          => time(), // Adding some timestamp to workaround cache issues.
					],
					$api_url . '/plugin-updater/v1/info'
				),
				[
					'timeout' => 10,
					'headers' => [
						'Accept'        => 'application/json',
						'Cache-Control' => 'no-cache',
					],
				]
			);

			// Caching any response.
			set_transient( $cache_key, $remote, self::CACHE_TTL );
		}

		// Check response for errors.
		if (
			is_wp_error( $remote )
			|| 200 !== wp_remote_retrieve_response_code( $remote )
			|| empty( wp_remote_retrieve_body( $remote ) )
		) {
			return new WP_Error( 'remote-error', __( 'Remote answered with an error.', 'sensei-lms' ) );
		}

		// Check response for valid json.
		$response = json_decode( wp_remote_retrieve_body( $remote ) );
		if ( is_null( $response ) ) {
			return new WP_Error( 'invalid-remote-response', __( 'Remote answered with an invalid response.', 'sensei-lms' ) );
		}

		return $response;
	}

	/**
	 * Add update disclaimer for invalid license.
	 *
	 * @since 4.14.0
	 *
	 * @internal
	 */
	public function invalid_license_update_disclaimer() {
		if ( ! class_exists( '\SenseiLMS_Licensing\License_Manager' ) ) {
			return;
		}

		// Checks if Sensei Pro method exists. So it's already being done there.
		if ( class_exists( '\SenseiLMS_Licensing\SenseiLMS_Plugin_Updater' ) && method_exists( '\SenseiLMS_Licensing\SenseiLMS_Plugin_Updater', 'invalid_license_update_disclaimer' ) ) {
			return;
		}

		$license_status = \SenseiLMS_Licensing\License_Manager::get_license_status( $this->plugin_slug );
		if ( ! $license_status['is_valid'] ) {
			printf(
				'<br /><strong>%s</strong>',
				esc_html__( 'Update will be available after you activate your license.', 'sensei-lms' )
			);
		}
	}
}
