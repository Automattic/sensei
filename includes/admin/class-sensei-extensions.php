<?php
/**
 * File containing Sensei_Extensions class.
 *
 * @package Sensei\Admin
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Sensei_Extensions class.
 *
 * All functionality pertaining to the admin area's extension directory.
 *
 * @since 2.0.0
 */
final class Sensei_Extensions {
	const SENSEILMS_PRODUCTS_API_BASE_URL = 'https://senseilms.com/wp-json/senseilms-products/1.0';

	/**
	 * Instance of class.
	 *
	 * @var self
	 */
	private static $instance;

	/**
	 * Courses constructor. Prevents other instances from being created outside of `Sensei_Extensions::instance()`.
	 */
	private function __construct() {}

	/**
	 * Initializes the class and adds all filters and actions related to the extension directory.
	 *
	 * @since 2.0.0
	 */
	public function init() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_action( 'admin_menu', array( $this, 'add_admin_menu_item' ), 60 );
	}

	/**
	 * Enqueues admin scripts when needed on different screens.
	 *
	 * @since  2.0.0
	 * @access private
	 */
	public function enqueue_admin_assets() {
		$screen = get_current_screen();

		if ( in_array( $screen->id, [ 'sensei-lms_page_sensei-extensions' ], true ) ) {
			Sensei()->assets->enqueue( 'sensei-extensions', 'extensions/index.js', [], true );
			Sensei()->assets->enqueue( 'sensei-extensions-style', 'extensions/extensions.css', [ 'sensei-wp-components' ] );
			Sensei()->assets->preload_data( [ '/sensei-internal/v1/sensei-extensions?type=plugin' ] );

			$this->localize_script();
		}
	}

	/**
	 * Localize extensions script.
	 *
	 * @since 3.11.0
	 */
	private function localize_script() {
		$data = array(
			'connectUrl' => add_query_arg(
				array(
					'page'              => 'wc-addons',
					'section'           => 'helper',
					'wc-helper-connect' => 1,
					'wc-helper-nonce'   => wp_create_nonce( 'connect' ),
				),
				admin_url( 'admin.php' )
			),
		);

		if ( ! Sensei_Utils::is_woocommerce_installed() ) {
			$data['installUrl'] = self_admin_url( 'plugin-install.php?s=woocommerce&tab=search&type=term&plugin_details=woocommerce' );
		} elseif ( ! Sensei_Utils::is_woocommerce_active() ) {
			$plugin_file         = 'woocommerce/woocommerce.php';
			$data['activateUrl'] = wp_nonce_url(
				add_query_arg(
					array(
						'action' => 'activate',
						'plugin' => $plugin_file,
					),
					self_admin_url( 'plugins.php' )
				),
				'activate-plugin_' . $plugin_file
			);
		}

		wp_localize_script(
			'sensei-extensions',
			'sensei_extensions',
			$data
		);
	}

	/**
	 * Call API to get Sensei extensions.
	 *
	 * @since  2.0.0
	 * @since  3.1.0 The method is public.
	 *
	 * @param  string $type                  Product type ('plugin' or 'theme').
	 * @param  string $category              Category to fetch (null = all).
	 * @param  string $additional_query_args Additional query arguments.
	 * @return array
	 */
	public function get_extensions( $type = null, $category = null, $additional_query_args = [] ) {
		$extension_request_key = md5( $type . '|' . $category . '|' . determine_locale() . '|' . wp_json_encode( $additional_query_args ) );
		$extensions            = get_transient( 'sensei_extensions_' . $extension_request_key );

		if ( false === $extensions ) {
			$url = add_query_arg(
				[
					array_merge(
						[
							'category' => $category,
							'type'     => $type,
							'lang'     => determine_locale(),
						],
						$additional_query_args
					),
				],
				self::SENSEILMS_PRODUCTS_API_BASE_URL . '/search'
			);

			$raw_extensions = wp_safe_remote_get( $url );
			if ( ! is_wp_error( $raw_extensions ) ) {
				$json       = json_decode( wp_remote_retrieve_body( $raw_extensions ) );
				$extensions = isset( $json->products ) ? $json->products : [];

				set_transient( 'sensei_extensions_' . $extension_request_key, $extensions, DAY_IN_SECONDS );
			}
		}

		if ( 'plugin' === $type ) {
			return $this->add_installed_extensions_properties( $extensions );
		}

		return $extensions;
	}

	/**
	 * Map the extensions array, adding the installed properties.
	 *
	 * @param array $extensions Extensions.
	 *
	 * @return array Extensions with installed properties.
	 */
	private function add_installed_extensions_properties( $extensions ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';

		$installed_plugins = get_plugins();

		$wccom_subscriptions = [];

		if ( class_exists( 'WC_Helper_Options' ) ) {
			$wccom_subscriptions = WC_Helper::get_subscriptions();
		}

		// Includes installed version, whether it has update and WC.com metadata.
		$extensions = array_map(
			function( $extension ) use ( $installed_plugins, $wccom_subscriptions ) {
				$extension->is_installed = isset( $installed_plugins[ $extension->plugin_file ] );

				if ( $extension->is_installed ) {
					$extension->installed_version = $installed_plugins[ $extension->plugin_file ]['Version'];
					$extension->has_update        = isset( $extension->version ) && version_compare( $extension->version, $extension->installed_version, '>' );
				}

				if ( isset( $extension->wccom_product_id ) ) {
					foreach ( $wccom_subscriptions as $wccom_subscription ) {
						if ( (int) $extension->wccom_product_id === $wccom_subscription['product_id'] ) {
							$extension->wccom_expired = $wccom_subscription['expired'];

							if ( ! $extension->wccom_expired ) {
								break;
							}
						}
					}
				}

				return $extension;
			},
			$extensions
		);

		return $extensions;
	}

	/**
	 * Get extensions page layout.
	 *
	 * @since 3.11.0
	 *
	 * @return array
	 */
	public function get_layout() {
		$transient_key    = implode( '_', [ 'sensei_extensions_layout', determine_locale() ] );
		$extension_layout = get_transient( $transient_key );
		if ( false === $extension_layout ) {
			$raw_layout = wp_safe_remote_get(
				add_query_arg(
					[ 'lang' => determine_locale() ],
					self::SENSEILMS_PRODUCTS_API_BASE_URL . '/layout'
				)
			);

			if ( ! is_wp_error( $raw_layout ) ) {
				$json             = json_decode( wp_remote_retrieve_body( $raw_layout ) );
				$extension_layout = isset( $json->layout ) ? $json->layout : [];
				set_transient( $transient_key, $extension_layout, DAY_IN_SECONDS );
			}
		}

		return $extension_layout;
	}

	/**
	 * Get updates count.
	 *
	 * @return int Updates count.
	 */
	private function get_has_update_count() {
		$extensions = $this->get_extensions( 'plugin' );

		return count(
			array_filter(
				array_column( $extensions, 'has_update' )
			)
		);
	}

	/**
	 * Get installed Sensei plugins.
	 *
	 * @param bool $only_woo Only include WooCommerce.com extensions.
	 *
	 * @return array
	 */
	public function get_installed_plugins( $only_woo = false ) {
		$extensions = $this->get_extensions( 'plugin' );

		return array_filter(
			$extensions,
			function( $extension ) use ( $only_woo ) {
				if (
					empty( $extension->installed_version )
					|| ( $only_woo && empty( $extension->wccom_product_id ) )
				) {
					return false;
				}

				return true;
			}
		);
	}

	/**
	 * Adds the menu item for the Extensions page.
	 *
	 * @since  2.0.0
	 * @access private
	 */
	public function add_admin_menu_item() {
		$updates_html = '';
		$updates      = $this->get_has_update_count();

		if ( $updates > 0 ) {
			$updates_html = ' <span class="awaiting-mod">' . esc_html( $updates ) . '</span>';
		}

		add_submenu_page(
			'sensei',
			__( 'Sensei LMS Extensions', 'sensei-lms' ),
			__( 'Extensions', 'sensei-lms' ) . $updates_html,
			'install_plugins',
			'sensei-extensions',
			[ $this, 'render' ]
		);
	}

	/**
	 * Renders the extensions page.
	 *
	 * @since  2.0.0
	 * @access private
	 */
	public function render() {
		// phpcs:ignore WordPress.Security.NonceVerification
		$tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : null;

		sensei_log_event(
			'extensions_view',
			[ 'view' => $tab ? $tab : '_all' ]
		);

		echo '<div id="sensei-extensions-page" class="sensei-extensions-page"></div>';
	}

	/**
	 * Fetches an instance of the class.
	 *
	 * @return self
	 */
	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

}
