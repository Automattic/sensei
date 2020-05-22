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
	 * Finishes replying to the client, but keeps the process running for further (async) code execution.
	 *
	 * @see https://core.trac.wordpress.org/ticket/41358
	 */
	private function close_http_connection() {
		// Only 1 PHP process can access a session object at a time, close this so the next request isn't kept waiting.
		if ( session_id() ) {
			session_write_close();
		}

		wc_set_time_limit( 0 );

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
		$new_installations  = [];

		foreach ( $plugins_to_install as $plugin ) {
			if ( false === array_search( $plugin->product_slug, array_column( $installing_plugins, 'product_slug' ), true ) ) {
				$installing_plugins[] = $plugin;

				$this->deferred_actions[] = [
					'func' => [ $this, 'background_installer' ],
					'args' => [ $plugin ],
				];
			}
		}

		set_transient( self::INSTALLING_PLUGINS_TRANSIENT, $installing_plugins, DAY_IN_SECONDS );
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

		// Get filename if it is the complete path.
		$plugin_file = $this->get_file_name_from_path( $plugin_file );

		if ( ! isset( $installed_plugins[ $plugin_file ] ) ) {
			return false;
		}

		return is_plugin_active( $installed_plugins[ $plugin_file ] );
	}

	/**
	 * Save error
	 *
	 * @param string $slug    Plugin slug.
	 * @param string $message Error message.
	 */
	private function save_error( $slug, $message ) {
		$installing_plugins = $this->get_installing_plugins();
		$key                = array_search( $slug, array_column( $installing_plugins, 'product_slug' ), true );

		if ( false !== $key ) {
			$installing_plugins[ $key ]->error = $message;
		}

		set_transient( self::INSTALLING_PLUGINS_TRANSIENT, $installing_plugins, DAY_IN_SECONDS );
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
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
			require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

			WP_Filesystem();

			$skin              = new Automatic_Upgrader_Skin();
			$upgrader          = new WP_Upgrader( $skin );
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
				// Suppress feedback.
				ob_start();

				try {
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
						throw new Exception( $plugin_information->get_error_message() );
					}

					$package  = $plugin_information->download_link;
					$download = $upgrader->download_package( $package );

					if ( is_wp_error( $download ) ) {
						throw new Exception( $download->get_error_message() );
					}

					$working_dir = $upgrader->unpack_package( $download, true );

					if ( is_wp_error( $working_dir ) ) {
						throw new Exception( $working_dir->get_error_message() );
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

					if ( is_wp_error( $result ) ) {
						throw new Exception( $result->get_error_message() );
					}

					$activate = true;

				} catch ( Exception $e ) {
					$error   = true;
					$message = sprintf(
						// translators: 1: plugin name, 2: error message.
						__( '%1$s could not be installed (%2$s).', 'sensei-lms' ),
						$plugin_title,
						$e->getMessage()
					);

					$this->save_error( $plugin_slug, $message );
				}

				// Discard feedback.
				ob_end_clean();
			}

			wp_clean_plugins_cache();

			// Activate this thing.
			if ( $activate ) {
				try {
					$result = activate_plugin( $installed ? $installed_plugins[ $plugin_file ] : $plugin_slug . '/' . $plugin_file );

					if ( is_wp_error( $result ) ) {
						throw new Exception( $result->get_error_message() );
					}
				} catch ( Exception $e ) {
					$error   = true;
					$message = sprintf(
						// translators: 1: plugin name.
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

			// Remove from transient when complete.
			$installing_plugins = $this->get_installing_plugins();

			if ( ! empty( $installing_plugins ) ) {
				$installing_plugins = array_filter(
					$installing_plugins,
					function( $plugin ) use ( $plugin_slug ) {
						$plugin->product_slug !== $plugin_slug;
					}
				);

				set_transient( self::INSTALLING_PLUGINS_TRANSIENT, $installing_plugins, DAY_IN_SECONDS );
			}
		}
	}
}
