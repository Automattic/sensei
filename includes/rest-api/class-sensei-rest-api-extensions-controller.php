<?php
/**
 * Sensei REST API: Sensei_REST_API_Extensions_Controller class.
 *
 * @package sensei-lms
 * @since   3.11.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly.

/**
 * A REST controller for Sensei related extensions.
 *
 * @since 3.11.0
 *
 * @see   WP_REST_Controller
 */
class Sensei_REST_API_Extensions_Controller extends WP_REST_Controller {
	/**
	 * Routes namespace.
	 *
	 * @var string
	 */
	protected $namespace;

	/**
	 * Routes prefix.
	 *
	 * @var string
	 */
	protected $rest_base = 'sensei-extensions';

	/**
	 * Sensei_REST_API_Extensions_Controller constructor.
	 *
	 * @param string $namespace Routes namespace.
	 */
	public function __construct( $namespace ) {
		$this->namespace = $namespace;
	}

	/**
	 * Register the REST API endpoints for extensions.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			$this->rest_base,
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_extensions' ],
					'permission_callback' => [ $this, 'can_user_manage_plugins' ],
					'args'                => [
						'installed'  => [
							'type'              => 'bool',
							'required'          => false,
							'sanitize_callback' => function( $param ) {
								return (bool) $param;
							},
						],
						'has_update' => [
							'type'              => 'bool',
							'required'          => false,
							'sanitize_callback' => function( $param ) {
								return (bool) $param;
							},
						],
						'type'       => [
							'type'              => 'string',
							'required'          => false,
							'sanitize_callback' => function( $param ) {
								if ( 'plugin' === $param || 'theme' === $param ) {
									return $param;
								}

								return null;
							},
						],
					],
				],
				'schema' => [ $this, 'get_extensions_schema' ],
			]
		);

		register_rest_route(
			$this->namespace,
			$this->rest_base . '/install',
			[
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'install_extension' ],
					'permission_callback' => [ $this, 'can_user_manage_plugins' ],
					'args'                => [
						'plugin' => [
							'type'              => 'string',
							'required'          => true,
							'sanitize_callback' => 'sanitize_title',
						],
					],
				],
			]
		);

		register_rest_route(
			$this->namespace,
			$this->rest_base . '/update',
			[
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'update_extensions' ],
					'permission_callback' => [ $this, 'can_user_manage_plugins' ],
					'args'                => [
						'plugins' => [
							'type'              => 'array',
							'required'          => true,
							'sanitize_callback' => function( $param ) {
								if ( ! is_array( $param ) ) {
									$param = [ $param ];
								}

								return array_map(
									function ( $plugin ) {
										return sanitize_title( $plugin );
									},
									$param
								);
							},
						],
					],
				],
			]
		);
	}

	/**
	 * Check user permission for managing plugins.
	 *
	 * @param WP_REST_Request $request WordPress request object.
	 *
	 * @return bool|WP_Error Whether the user can manage extensions.
	 */
	public function can_user_manage_plugins( WP_REST_Request $request ) {
		if ( ! current_user_can( 'activate_plugins' ) || ! current_user_can( 'update_plugins' ) ) {
			return new WP_Error(
				'rest_cannot_view_plugins',
				__( 'Sorry, you are not allowed to manage plugins for this site.', 'sensei-lms' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return true;
	}

	/**
	 * Returns the requested extensions.
	 *
	 * @access private
	 *
	 * @param WP_REST_Request $request The request.
	 *
	 * @return WP_REST_Response The response which contains the extensions.
	 */
	public function get_extensions( WP_REST_Request $request ) : WP_REST_Response {
		$params  = $request->get_params();
		$plugins = Sensei_Extensions::instance()->get_extensions( $params['type'] ?? null );

		$filtered_plugins = array_filter(
			$plugins,
			function( $plugin ) use ( $params ) {
				$should_return = true;

				if ( isset( $params['installed'] ) ) {
					$should_return = $plugin->is_installed === $params['installed'];
				}

				if ( isset( $params['has_update'] ) ) {
					$should_return = isset( $plugin->has_update ) && $plugin->has_update;
				}

				return $should_return;
			}
		);

		return $this->create_extensions_response( $filtered_plugins, 'extensions', true );
	}

	/**
	 * Install extension.
	 *
	 * @access private
	 *
	 * @param WP_REST_Request $request The request.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function install_extension( WP_REST_Request $request ) {
		$json_params    = $request->get_json_params();
		$plugin_slug    = $json_params['plugin'];
		$sensei_plugins = Sensei_Extensions::instance()->get_extensions( 'plugin' );

		$plugin_to_install = array_values(
			array_filter(
				$sensei_plugins,
				function( $plugin ) use ( $plugin_slug ) {
					return $plugin->product_slug === $plugin_slug;
				}
			)
		)[0];

		try {
			Sensei_Plugins_Installation::instance()->install_plugin( $plugin_slug );
			wp_clean_plugins_cache();
			Sensei_Plugins_Installation::instance()->activate_plugin( $plugin_slug, $plugin_to_install->plugin_file );
		} catch ( Exception $e ) {
			return new WP_Error(
				'sensei_extensions_install_error',
				$e->getMessage()
			);
		}

		$installed_plugins = array_filter(
			Sensei_Extensions::instance()->get_extensions( 'plugin' ),
			function( $plugin ) use ( $plugin_slug ) {
				return $plugin->product_slug === $plugin_slug;
			}
		);

		return $this->create_extensions_response( $installed_plugins, 'completed' );
	}

	/**
	 * Update an array of plugins.
	 *
	 * @access private
	 *
	 * @param WP_REST_Request $request The request.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_extensions( WP_REST_Request $request ) {
		$json_params    = $request->get_json_params();
		$plugins_arg    = $json_params['plugins'];
		$sensei_plugins = Sensei_Extensions::instance()->get_extensions( 'plugin' );

		$plugins_to_update = array_filter(
			$sensei_plugins,
			function( $plugin ) use ( $plugins_arg ) {
				return $plugin->is_installed && $plugin->has_update && in_array( $plugin->product_slug, $plugins_arg, true );
			}
		);

		if ( empty( $plugins_to_update ) ) {
			return new WP_Error(
				'sensei_extensions_no_plugins_to_update',
				__( 'No plugins to update found.', 'sensei-lms' ),
				[ 'status' => 404 ]
			);
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

		WP_Filesystem();

		wp_update_plugins();

		$skin     = new WP_Ajax_Upgrader_Skin();
		$upgrader = new Plugin_Upgrader( $skin );
		$result   = $upgrader->bulk_upgrade( wp_list_pluck( $plugins_to_update, 'plugin_file' ) );

		$error = $this->check_for_upgrade_error( $plugins_to_update, $result, $skin, $upgrader );

		if ( is_wp_error( $error ) ) {
			return $error;
		}

		$updated_plugins = array_filter(
			Sensei_Extensions::instance()->get_extensions( 'plugin' ),
			function( $plugin ) use ( $plugins_arg ) {
				return in_array( $plugin->product_slug, $plugins_arg, true );
			}
		);

		return $this->create_extensions_response( $updated_plugins, 'completed' );
	}

	/**
	 * Check if the result of the upgrade has an error. Error handling has been copied from wp_ajax_update_plugin.
	 *
	 * @param array                 $plugins  Plugins which where upgraded.
	 * @param array|WP_Error|false  $result   Result of the upgrade.
	 * @param WP_Ajax_Upgrader_Skin $skin     Upgrader sking.
	 * @param Plugin_Upgrader       $upgrader The upgrader.
	 *
	 * @return bool|WP_Error
	 */
	private function check_for_upgrade_error( array $plugins, $result, WP_Ajax_Upgrader_Skin $skin, Plugin_Upgrader $upgrader ) {
		if ( is_wp_error( $skin->result ) ) {
			return $skin->result;
		}

		if ( $skin->get_errors()->has_errors() ) {
			return new WP_Error(
				'sensei_extensions_plugin_update_failed',
				$skin->get_error_messages()
			);
		}

		if ( is_array( $result ) ) {
			foreach ( $plugins as $plugin ) {
				if ( empty( $result[ $plugin->plugin_file ] ) ) {
					return new WP_Error(
						'sensei_extensions_plugin_update_failed',
						// translators: Placeholder is the name of the plugin that failed.
						sprintf( __( 'Failed to update plugin %s', 'sensei-lms' ), $plugin->title )
					);
				}

				if ( true === $result[ $plugin->plugin_file ] ) {
					return new WP_Error(
						'sensei_extensions_plugin_update_failed',
						$upgrader->strings['up_to_date']
					);
				}
			}
		} else {
			return new WP_Error(
				'sensei_extensions_plugin_update_failed',
				__( 'Plugin update failed.', 'sensei-lms' )
			);
		}

		return false;
	}

	/**
	 * Generate a REST response from an array of plugins.
	 *
	 * @param array   $plugins        The plugins.
	 * @param string  $extensions_key Response key for the extensions array.
	 * @param boolean $full_response  Whether it's creating the response for the main fetch.
	 *
	 * @return WP_REST_Response
	 */
	private function create_extensions_response( array $plugins, string $extensions_key, bool $full_response = false ): WP_REST_Response {
		$wccom_connected = false;

		if ( class_exists( 'WC_Helper_Options' ) ) {
			$auth            = WC_Helper_Options::get( 'auth' );
			$wccom_connected = ! empty( $auth['access_token'] );
		}

		$mapped_plugins = array_map(
			function ( $plugin ) use ( $wccom_connected ) {
				$plugin->price      = html_entity_decode( $plugin->price );
				$plugin->image      = $plugin->image_large;
				$plugin->can_update = empty( $plugin->wccom_product_id ) || ( $wccom_connected && ! $plugin->wccom_expired );
				return $plugin;
			},
			$plugins
		);

		$response_json = [];

		if ( $full_response ) {
			$response_json = [
				'layout'          => Sensei_Extensions::instance()->get_layout(),
				'wccom'           => Sensei_Utils::get_woocommerce_connect_data(),
				'wccom_connected' => $wccom_connected,
			];
		}

		$response_json[ $extensions_key ] = array_values( $mapped_plugins );

		$response = new WP_REST_Response();
		$response->set_data( $response_json );

		return $response;
	}

	/**
	 * Schema for the extensions endpoint.
	 *
	 * @return array Schema object.
	 */
	public function get_extensions_schema() : array {
		return [
			'extensions'      => [
				'type'  => 'array',
				'items' => [
					'type'       => 'object',
					'properties' => [
						'hash'             => [
							'type'        => 'string',
							'description' => 'Product ID.',
						],
						'title'            => [
							'type'        => 'string',
							'description' => 'Extension title.',
						],
						'image'            => [
							'type'        => 'string',
							'description' => 'Extension image.',
						],
						'excerpt'          => [
							'type'        => 'string',
							'description' => 'Extension excerpt',
						],
						'link'             => [
							'type'        => 'string',
							'description' => 'Extension link.',
						],
						'price'            => [
							'type'        => 'string',
							'description' => 'Extension price.',
						],
						'is_featured'      => [
							'type'        => 'boolean',
							'description' => 'Whether its a featured extension.',
						],
						'product_slug'     => [
							'type'        => 'string',
							'description' => 'Extension product slug.',
						],
						'hosted_location'  => [
							'type'        => 'string',
							'description' => 'Where the extension is hosted (dotorg or external)',
						],
						'type'             => [
							'type'        => 'string',
							'description' => 'Whether this is a plugin or a theme',
						],
						'plugin_file'      => [
							'type'        => 'string',
							'description' => 'Main plugin file.',
						],
						'version'          => [
							'type'        => 'string',
							'description' => 'Extension version.',
						],
						'wccom_product_id' => [
							'type'        => 'string',
							'description' => 'WooCommerce.com product ID.',
						],
						'is_installed'     => [
							'type'        => 'boolean',
							'description' => 'Whether the extension is installed.',
						],
						'has_update'       => [
							'type'        => 'boolean',
							'description' => 'Whether the extension has available updates.',
						],
						'wccom_expired'    => [
							'type'        => 'boolean',
							'description' => 'Whether the WC.com subscription is expired.',
						],
					],
				],
			],
			'wccom'           => [
				'type'        => 'object',
				'description' => 'WC.com data.',
			],
			'wccom_connected' => [
				'type'        => 'boolean',
				'description' => 'Whether the site is connected to WC.com.',
			],
			'layout'          => [ $this, 'get_layout_schema' ],
		];
	}

	/**
	 * Schema for the layout endpoint.
	 *
	 * @return array Schema object.
	 */
	private function get_layout_schema() : array {
		return [
			'type'  => 'array',
			'items' => [
				'type'       => 'object',
				'properties' => [
					'key'         => [
						'type'        => 'string',
						'description' => 'Section key.',
					],
					'columns'     => [
						'type'        => 'integer',
						'description' => 'Number of columns to use.',
					],
					'type'        => [
						'type'        => 'string',
						'description' => 'Type of content.',
					],
					'title'       => [
						'type'        => 'string',
						'description' => 'Section title.',
					],
					'description' => [
						'type'        => 'string',
						'description' => 'Description title.',
					],
					'items'       => [
						'type'        => 'array',
						'description' => 'Items to list.',
						'items'       => [
							'type'       => 'object',
							'properties' => [
								'key'           => [
									'type'        => 'string',
									'description' => 'Item key.',
								],
								'extensionSlug' => [
									'type'        => 'string',
									'description' => 'Extension slug.',
								],
								'itemProps'     => [
									'type'        => 'object',
									'description' => 'Props to add to the list item component.',
								],
								'wrapperProps'  => [
									'type'        => 'object',
									'description' => 'Props to add to the wrapper component.',
								],
								'cardProps'     => [
									'type'        => 'object',
									'description' => 'Props to add to the card component.',
								],
							],
						],
					],
				],
			],
		];
	}
}
