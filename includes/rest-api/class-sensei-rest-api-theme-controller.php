<?php
/**
 * Sensei REST API: Sensei_REST_API_Theme_Controller class.
 *
 * @package sensei-lms
 * @since   4.10.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly.

/**
 * A REST controller for installing themes.
 *
 * @since 4.10.0
 *
 * @see   WP_REST_Controller
 */
class Sensei_REST_API_Theme_Controller extends WP_REST_Controller {
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
	protected $rest_base = 'themes';

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
					'callback'            => [ $this, 'install_theme' ],
					'permission_callback' => [ $this, 'can_user_manage_themes' ],
					'args'                => [
						'theme' => [
							'required' => true,
							'type'     => 'string',
						],
					],
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
	public function install_theme( WP_REST_Request $request ) {
		$json_params    = $request->get_json_params();
		$theme_slug     = $json_params['theme'];
		$allowed_themes = Sensei_Extensions::instance()->get_extensions( 'theme' );

		// Get the info for the theme to install.
		$filtered_themes = array_values(
			array_filter(
				$allowed_themes,
				function( $theme ) use ( $theme_slug ) {
					return $theme->product_slug === $theme_slug;
				}
			)
		);

		if ( empty( $filtered_themes ) ) {
			return new WP_Error(
				'sensei_theme_invalid',
				// translators: Placeholder is the theme slug.
				sprintf( __( 'Invalid theme `%s`.', 'sensei-lms' ), $theme_slug ),
				[ 'status' => 400 ]
			);
		}

		$theme_to_install = $filtered_themes[0];

		// If the theme is not already installed, install it.
		if ( ! $theme_to_install->is_installed ) {
			include_once ABSPATH . '/wp-admin/includes/admin.php';
			include_once ABSPATH . '/wp-admin/includes/theme-install.php';
			include_once ABSPATH . '/wp-admin/includes/theme.php';
			include_once ABSPATH . '/wp-admin/includes/class-wp-upgrader.php';
			include_once ABSPATH . '/wp-admin/includes/class-theme-upgrader.php';

			$api = themes_api(
				'theme_information',
				[
					'slug'   => $theme_slug,
					'fields' => [
						'sections' => false,
					],
				]
			);

			if ( is_wp_error( $api ) ) {
				return new WP_Error(
					'sensei_theme_api_error',
					// translators: Placeholder is the theme slug.
					sprintf( __( 'The requested theme `%s` could not be installed. Theme API call failed.', 'sensei-lms' ), $theme_slug ),
					[ 'status' => 400 ]
				);
			}

			$upgrader = new \Theme_Upgrader( new \Automatic_Upgrader_Skin() );
			$result   = $upgrader->install( $api->download_link );

			if ( is_wp_error( $result ) || is_null( $result ) ) {
				return new \WP_Error(
					'sensei_theme_install_error',
					sprintf(
						// translators: Placeholder is the theme slug.
						__( 'The requested theme `%s` could not be installed.', 'sensei-lms' ),
						$theme_slug
					),
					500
				);
			}
		}

		// Switch to the theme.
		switch_theme( $theme_slug );

		return new WP_REST_Response( 'ok' );
	}

}
