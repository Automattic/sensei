<?php
/**
 * Extensions REST API.
 *
 * @package Sensei\Admin
 * @since   3.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Sensei Extensions REST API endpoints.
 *
 * @package Sensei\Admin
 * @author  Automattic
 * @since   3.6.0
 */
class Sensei_REST_API_Extensions_Controller extends \WP_REST_Controller {

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
	protected $rest_base = 'extensions';

	/**
	 * Sensei_REST_API_Extensions_Controller constructor.
	 *
	 * @param string $namespace Routes namespace.
	 */
	public function __construct( $namespace ) {
		$this->namespace = $namespace;
	}

	/**
	 * Register the REST API endpoints for the Extensions page.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			$this->rest_base . '/extensions-install',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'extensions_install_status' ],
					'permission_callback' => [ $this, 'can_user_install_plugins' ],
					'args'                => [
						'slug' => [
							'required' => true,
							'type'     => 'string',
						],
					],
				],
				[
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => [ $this, 'extensions_install' ],
					'permission_callback' => [ $this, 'can_user_install_plugins' ],
					'args'                => [
						'slug' => [
							'required' => true,
							'type'     => 'string',
						],
					],
				],
			]
		);
	}

	/**
	 * Check user permission for install plugins.
	 *
	 * @return bool Whether the user can install plugins.
	 */
	public function can_user_install_plugins() {
		return current_user_can( 'manage_sensei' ) && current_user_can( 'install_plugins' );
	}

	/**
	 * Submit features installation step.
	 *
	 * @param array $params Form data.
	 *
	 * @return array|WP_Error Installation status.
	 */
	public function extensions_install( $params ) {

		$extension = Sensei_Extensions::instance()->get_extension( $params['slug'] );

		if ( ! $extension ) {
			return new WP_Error(
				'sensei_extension_not_found',
				__( 'Sensei extension not found.', 'sensei-lms' ),
				[ 'status' => 404 ]
			);
		}
		Sensei_Plugins_Installation::instance()->install_plugins( [ $extension ] );

		return $this->extensions_install_status( $params );
	}

	/**
	 * Submit features installation step.
	 *
	 * @param array $params Form data.
	 *
	 * @return array Installation status.
	 */
	public function extensions_install_status( $params ) {
		return Sensei_Plugins_Installation::instance()->get_plugin_install_status( $params['slug'] );
	}

}
