<?php
/**
 * File containing Sensei_Plugins_Installation class.
 *
 * @package Sensei\Admin
 * @since 3.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Sensei Plugins Installation Class.
 *
 * @since 3.1.0
 */
class Sensei_Plugins_Installation {
	const INSTALLING_PLUGINS_TRANSIENT = 'sensei_installing_plugins';

	/**
	 * Actions to be executed after the HTTP response has completed
	 *
	 * @var array
	 */
	private $deferred_actions = [];

	/**
	 * Instance of singleton.
	 *
	 * @var self
	 */
	private static $instance;

	/**
	 * Sensei_Plugins_Installation constructor.
	 *
	 * It's private to prevent other instances from being created outside of `Sensei_Plugins_Installation::instance()`.
	 */
	private function __construct() {
		add_action( 'shutdown', [ $this, 'run_deferred_actions' ] );
	}

	/**
	 * Fetches the instance of the class.
	 *
	 * @return self
	 */
	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Function called after the HTTP request is finished, so it's executed without the client having to wait for it.
	 *
	 * @access private
	 */
	public function run_deferred_actions() {
		if ( empty( $this->deferred_actions ) ) {
			return;
		}

		$this->close_http_connection();
		foreach ( $this->deferred_actions as $action ) {
			$action['func']( ...$action['args'] );
		}
	}

	/**
	 * Wrapper for set_time_limit to see if it is enabled.
	 *
	 * @param int $limit Time limit.
	 */
	private function set_time_limit( $limit = 0 ) {
		if ( function_exists( 'set_time_limit' ) && false === strpos( ini_get( 'disable_functions' ), 'set_time_limit' ) && ! ini_get( 'safe_mode' ) ) { // phpcs:ignore PHPCompatibility.IniDirectives.RemovedIniDirectives.safe_modeDeprecatedRemoved
			@set_time_limit( $limit ); // phpcs:ignore
		}
	}

	/**
	 * Finishes replying to the client, but keeps the process running for further (async) code execution.
	 *
	 * @see https://core.trac.wordpress.org/ticket/41358
	 */
	private function close_http_connection() {
		// Only 1 PHP process can access a session object at a time, close this so the next request isn't kept waiting.
		if ( session_id() ) {
			session_write_close();
		}

		$this->set_time_limit( 0 );

		// fastcgi_finish_request is the cleanest way to send the response and keep the script running, but not every server has it.
		if ( is_callable( 'fastcgi_finish_request' ) ) {
			fastcgi_finish_request();
		} else {
			// Fallback: send headers and flush buffers.
			if ( ! headers_sent() ) {
				header( 'Connection: close' );
			}
			@ob_end_flush(); // phpcs:ignore
			flush();
		}
	}

	/**
	 * Install plugins
	 *
	 * @param stdClass[] $plugins_to_install Plugin objects to install.
	 */
	public function install_plugins( $plugins_to_install ) {
		$installing_plugins = $this->get_installing_plugins();

		foreach ( $plugins_to_install as $plugin ) {
			$key = array_search( $plugin->product_slug, wp_list_pluck( $installing_plugins, 'product_slug' ), true );

			// Add to the queue if it is not there yet, or if it is there with error.
			if ( false === $key || isset( $installing_plugins[ $key ]->error ) ) {
				// Clean error.
				unset( $plugin->error );
				unset( $plugin->status );

				if ( false !== $key ) {
					$installing_plugins[ $key ] = $plugin;
				} else {
					$installing_plugins[] = $plugin;
				}

				$this->deferred_actions[] = [
					'func' => [ $this, 'background_installer' ],
					'args' => [ $plugin ],
				];
			}
		}

		$this->set_installing_plugins( $installing_plugins );
	}

	/**
	 * Get installing plugins.
	 *
	 * @return stdClass[] Installing plugins.
	 */
	public function get_installing_plugins() {
		$installing_plugins = get_transient( self::INSTALLING_PLUGINS_TRANSIENT );

		return $installing_plugins ? $installing_plugins : [];
	}

	/**
	 * Set installing plugins.
	 *
	 * @param stdClass[] $installing_plugins Installing plugins.
	 */
	public function set_installing_plugins( $installing_plugins ) {
		set_transient( self::INSTALLING_PLUGINS_TRANSIENT, $installing_plugins, DAY_IN_SECONDS );
	}

	/**
	 * Get file name from path.
	 *
	 * @param string $path Complete path.
	 *
	 * @return string File name.
	 */
	private function get_file_name_from_path( $path ) {
		$path_array = explode( '/', $path );
		return end( $path_array );
	}

	/**
	 * Get slug from path and associate it with the path.
	 *
	 * @param array  $plugins Associative array of plugin files to paths.
	 * @param string $key     Plugin relative path. Example: woocommerce/woocommerce.php.
	 */
	private function associate_plugin_file( $plugins, $key ) {
		$filename             = $this->get_file_name_from_path( $key );
		$plugins[ $filename ] = $key;
		return $plugins;
	}

	/**
	 * Get installed plugins.
	 *
	 * @return array Installed plugins.
	 */
	private function get_installed_plugins() {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';

		return array_reduce( array_keys( get_plugins() ), [ $this, 'associate_plugin_file' ] );
	}

	/**
	 * Get plugin path if it's installed, otherwise returns null.
	 *
	 * @since 3.7.0
	 *
	 * @param string $plugin_file       Plugin filename or path.
	 * @param array  $installed_plugins Installed plugins. If it's not passed, it will get the installed plugins.
	 *
	 * @return string|null Plugin path or null if plugin is not installed.
	 */
	public function get_installed_plugin_path( $plugin_file, $installed_plugins = null ) {
		if ( ! $installed_plugins ) {
			$installed_plugins = $this->get_installed_plugins();
		}

		// Get filename if it is the complete path.
		$plugin_file = $this->get_file_name_from_path( $plugin_file );

		if ( ! isset( $installed_plugins[ $plugin_file ] ) ) {
			return null;
		}

		return $installed_plugins[ $plugin_file ];
	}

	/**
	 * Check whether plugin is active.
	 *
	 * @param string $plugin_file       Plugin filename or path.
	 * @param array  $installed_plugins Installed plugins. If it's not passed, it will get the installed plugins.
	 *
	 * @return boolean Is plugin active.
	 */
	public function is_plugin_active( $plugin_file, $installed_plugins = null ) {
		if ( ! $installed_plugins ) {
			$installed_plugins = $this->get_installed_plugins();
		}

		$plugin_path = $this->get_installed_plugin_path( $plugin_file );

		if ( null === $plugin_path ) {
			return false;
		}

		return is_plugin_active( $plugin_path );
	}

	/**
	 * Save error
	 *
	 * @param string $slug    Plugin slug.
	 * @param string $message Error message.
	 */
	private function save_error( $slug, $message ) {

		$message = wp_kses( $message, [] );

		$installing_plugins = $this->get_installing_plugins();
		$key                = array_search( $slug, wp_list_pluck( $installing_plugins, 'product_slug' ), true );

		if ( false !== $key ) {
			$installing_plugins[ $key ]->error = $message;
		}

		$this->set_installing_plugins( $installing_plugins );

		sensei_log_event(
			'setup_wizard_features_install_error',
			[
				'slug'  => $slug,
				'error' => $message,
			]
		);
	}

	/**
	 * Complete installation removing the plugin from the transient.
	 *
	 * @param string $slug
	 */
	private function complete_installation( $slug ) {
		$installing_plugins = $this->get_installing_plugins();

		if ( ! empty( $installing_plugins ) ) {
			$installing_plugins = array_filter(
				$installing_plugins,
				function( $plugin ) use ( $slug ) {
					return $plugin->product_slug !== $slug;
				}
			);

			$this->set_installing_plugins( $installing_plugins );
		}

		sensei_log_event(
			'setup_wizard_features_install_success',
			[ 'slug' => $slug ]
		);
	}

	/**
	 * Wrapper to get error message and give the `get_error_data` as fallback.
	 *
	 * @param WP_Error $error
	 *
	 * @return string Error message.
	 */
	private function get_error_message( $error ) {
		if ( $error->get_error_message() ) {
			return $error->get_error_message();
		}

		return $error->get_error_data();
	}

	/**
	 * Install a plugin from WP.org.
	 *
	 * @param stdClass[] $plugin_to_install Plugin information.
	 *
	 * @throws Exception If unable to proceed with plugin installation.
	 */
	private function background_installer( $plugin_to_install ) {
		if ( ! empty( $plugin_to_install->product_slug ) ) {
			$installed_plugins = $this->get_installed_plugins();
			if ( empty( $installed_plugins ) ) {
				$installed_plugins = [];
			}
			$plugin_slug  = $plugin_to_install->product_slug;
			$plugin_title = $plugin_to_install->title;
			$plugin_file  = isset( $plugin_to_install->plugin_file )
				? $this->get_file_name_from_path( $plugin_to_install->plugin_file )
				: $plugin_slug . '.php';

			$installed = false;
			$activate  = false;
			$error     = false;

			// See if the plugin is installed already.
			if ( isset( $installed_plugins[ $plugin_file ] ) ) {
				$installed = true;
				$activate  = ! is_plugin_active( $installed_plugins[ $plugin_file ] );
			}

			// Install this thing!
			if ( ! $installed ) {
				try {
					$this->install_plugin( $plugin_slug );
					$activate = true;
				} catch ( Exception $e ) {
					$error   = true;
					$message = sprintf(
						// translators: Placeholder %1$s is the plugin title, %2$s is the error message.
						__( '%1$s could not be installed (%2$s).', 'sensei-lms' ),
						$plugin_title,
						$e->getMessage()
					);

					$this->save_error( $plugin_slug, $message );
				}
			}

			wp_clean_plugins_cache();

			// Activate this thing.
			if ( $activate ) {
				try {
					$this->activate_plugin( $plugin_slug, $installed ? $installed_plugins[ $plugin_file ] : $plugin_slug . '/' . $plugin_file );
				} catch ( Exception $e ) {
					$error   = true;
					$message = sprintf(
						// translators: Placeholder %1$s is the plugin title, %2$s is the error message.
						__( '%1$s is installed but could not be activated (%2$s).', 'sensei-lms' ),
						$plugin_title,
						$e->getMessage()
					);

					$this->save_error( $plugin_slug, $message );
				}
			}

			if ( $error ) {
				return;
			}

			$this->complete_installation( $plugin_slug );
		}
	}

	/**
	 * Install plugin.
	 *
	 * @param string $plugin_slug Plugin slug.
	 *
	 * @throws Exception When there is an installation error.
	 */
	public function install_plugin( $plugin_slug ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

		WP_Filesystem();

		$skin     = new Automatic_Upgrader_Skin();
		$upgrader = new WP_Upgrader( $skin );

		$plugin_information = plugins_api(
			'plugin_information',
			[
				'slug'   => $plugin_slug,
				'fields' => [
					'short_description' => false,
					'sections'          => false,
					'requires'          => false,
					'rating'            => false,
					'ratings'           => false,
					'downloaded'        => false,
					'last_updated'      => false,
					'added'             => false,
					'tags'              => false,
					'homepage'          => false,
					'donate_link'       => false,
					'author_profile'    => false,
					'author'            => false,
				],
			]
		);

		if ( is_wp_error( $plugin_information ) ) {
			throw new Exception( $this->get_error_message( $plugin_information ) );
		}

		// Suppress feedback.
		ob_start();

		$package  = $plugin_information->download_link;
		$download = $upgrader->download_package( $package );

		if ( is_wp_error( $download ) ) {
			throw new Exception( $this->get_error_message( $download ) );
		}

		$working_dir = $upgrader->unpack_package( $download, true );

		if ( is_wp_error( $working_dir ) ) {
			throw new Exception( $this->get_error_message( $working_dir ) );
		}

		$result = $upgrader->install_package(
			[
				'source'                      => $working_dir,
				'destination'                 => WP_PLUGIN_DIR,
				'clear_destination'           => false,
				'abort_if_destination_exists' => false,
				'clear_working'               => true,
				'hook_extra'                  => [
					'type'   => 'plugin',
					'action' => 'install',
				],
			]
		);

		// Discard feedback.
		ob_end_clean();

		if ( is_wp_error( $result ) ) {
			throw new Exception( $this->get_error_message( $result ) );
		}
	}

	/**
	 * Activate plugin.
	 *
	 * @param string $plugin_slug Plugin slug.
	 * @param string $plugin_file Path to the plugin file relative to the plugins directory.
	 *
	 * @throws Exception When there is an activation error.
	 */
	public function activate_plugin( $plugin_slug, $plugin_file ) {
		// Prevent WC wizard open after installation.
		if ( 'woocommerce' === $plugin_slug ) {
			add_filter( 'pre_set_transient__wc_activation_redirect', '__return_false' );
		}
		$result = activate_plugin( $plugin_file );

		if ( is_wp_error( $result ) ) {
			throw new Exception( $this->get_error_message( $result ) );
		}
	}
}
