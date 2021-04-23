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
				'schema' => [ $this, 'get_item_schema' ],
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
		if ( ! current_user_can( 'activate_plugins' ) ) {
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
	 * @param WP_REST_Request $request The request.
	 *
	 * @return WP_REST_Response The response which contains the extensions.
	 */
	public function get_extensions( WP_REST_Request $request ) : WP_REST_Response {
		$params  = $request->get_params();
		$plugins = Sensei_Extensions::instance()->get_extensions( $params['type'] );

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

		$mapped_plugins = array_map(
			function( $plugin ) {
				$plugin->price = html_entity_decode( $plugin->price );

				return $plugin;
			},
			$filtered_plugins
		);

		$response = new WP_REST_Response();
		$response->set_data( array_values( $mapped_plugins ) );

		return $response;
	}

	/**
	 * Schema for the endpoint.
	 *
	 * @return array Schema object.
	 */
	public function get_item_schema() : array {
		return [
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
				],
			],
		];
	}

}
