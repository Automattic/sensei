<?php
/**
 * Sensei REST API: Sensei_REST_API_Course_Theme_Controller class.
 *
 * @package sensei-lms
 * @since   $$next-version$$
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly.

/**
 * A REST controller for installing the Course theme.
 *
 * @since $$next-version$$
 *
 * @see   WP_REST_Controller
 */
class Sensei_REST_API_Course_Theme_Controller extends WP_REST_Controller {
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
	protected $rest_base = 'course-theme';

	/**
	 * Sensei_REST_API_Course_Theme_Controller constructor.
	 *
	 * @param string $namespace Routes namespace.
	 */
	public function __construct( $namespace ) {
		$this->namespace = $namespace;
	}

	/**
	 * Register the REST API endpoints for the Course theme.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			$this->rest_base . '/install',
			[
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'install_course_theme' ],
					'permission_callback' => [ $this, 'can_user_manage_themes' ],
				],
			]
		);
	}

	/**
	 * Check user permission for managing themes.
	 *
	 * @param WP_REST_Request $request WordPress request object.
	 *
	 * @return bool|WP_Error Whether the user can manage themes.
	 */
	public function can_user_manage_themes( WP_REST_Request $request ) {
		if (
			! current_user_can( 'install_themes' )
			|| ! current_user_can( 'switch_themes' )
		) {
			return new WP_Error(
				'rest_cannot_manage_themes',
				__( 'Sorry, you are not allowed to manage themes for this site.', 'sensei-lms' ),
				[ 'status' => rest_authorization_required_code() ]
			);
		}

		return true;
	}

	/**
	 * Install theme. If the theme is already installed, just activate it.
	 *
	 * @access private
	 *
	 * @param WP_REST_Request $request The request.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function install_course_theme( WP_REST_Request $request ) {
		// $json_params = $request->get_json_params();
		// $plugin_slug = $json_params['plugin'];

		// $plugin_to_install = array_values(
		// 	array_filter(
		// 		Sensei_Extensions::instance()->get_extensions_and_woocommerce( 'plugin' ),
		// 		function( $plugin ) use ( $plugin_slug ) {
		// 			return $plugin->product_slug === $plugin_slug;
		// 		}
		// 	)
		// )[0];

		// try {
		// 	if ( ! $plugin_to_install->is_installed ) {
		// 		Sensei_Plugins_Installation::instance()->install_plugin( $plugin_slug );
		// 	}
		// 	wp_clean_plugins_cache();
		// 	Sensei_Plugins_Installation::instance()->activate_plugin( $plugin_slug, $plugin_to_install->plugin_file );
		// } catch ( Exception $e ) {
		// 	return new WP_Error(
		// 		'sensei_extensions_install_error',
		// 		$e->getMessage()
		// 	);
		// }

		// $installed_plugins = array_filter(
		// 	Sensei_Extensions::instance()->get_extensions_and_woocommerce( 'plugin' ),
		// 	function( $plugin ) use ( $plugin_slug ) {
		// 		return $plugin->product_slug === $plugin_slug;
		// 	}
		// );

		// return $this->create_extensions_response( $installed_plugins, 'completed' );

		error_log( 'Hello!' );

		return new WP_REST_Response( 'ok' );
	}

}
