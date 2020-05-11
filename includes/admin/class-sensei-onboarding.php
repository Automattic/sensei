<?php
/**
 * Onboarding.
 *
 * @package Sensei\Onboarding
 * @since   1.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Sensei Onboarding Class
 * All onboarding functionality.
 *
 * @package Sensei
 * @author  Automattic
 * @since   3.1.0
 */
class Sensei_Onboarding {

	/**
	 * URL Slug for Onboarding Wizard page
	 *
	 * @var string
	 */
	public $page_slug;

	/**
	 * Creation of Sensei pages.
	 *
	 * @var Sensei_Onboarding_Pages
	 */
	public $pages;

	/**
	 * Sensei_Onboarding constructor.
	 */
	public function __construct() {

		$this->page_slug = 'sensei_onboarding';
		$this->pages     = new Sensei_Onboarding_Pages();

		add_action( 'rest_api_init', [ $this, 'register_rest_api' ] );
		if ( is_admin() ) {

			add_action( 'admin_menu', [ $this, 'admin_menu' ], 20 );
			add_action( 'current_screen', [ $this, 'add_onboarding_help_tab' ] );

			if ( $this->should_prevent_woocommerce_help_tab() ) {
				// Prevent WooCommerce help tab.
				add_filter( 'woocommerce_enable_admin_help_tab', '__return_false' );
			}

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Arguments used for comparison.
			if ( isset( $_GET['page'] ) && ( $_GET['page'] === $this->page_slug ) ) {

				add_action(
					'admin_print_scripts',
					function() {
						Sensei()->assets->enqueue( 'sensei-onboarding', 'onboarding/index.js', [], true );
					}
				);

				add_action(
					'admin_print_styles',
					function() {
						Sensei()->assets->enqueue( 'sensei-onboarding', 'onboarding/style.css', [ 'wp-components' ] );
					}
				);

				add_filter(
					'admin_body_class',
					function( $classes ) {
						$classes .= ' sensei-wp-admin-fullscreen ';
						return $classes;
					}
				);
				add_filter( 'show_admin_bar', '__return_false' );
			}
		}

	}

	/**
	 * Check if should prevent woocommerce help tab or not.
	 *
	 * @return boolean
	 */
	private function should_prevent_woocommerce_help_tab() {
		$post_types_to_prevent = [ 'course', 'lesson', 'sensei_message' ];

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Arguments used for comparison.
		return isset( $_GET['post_type'] ) && (
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Arguments used for comparison.
			in_array( $_GET['post_type'], $post_types_to_prevent, true )
		);
	}

	/**
	 * Register an setup wizard hidden submenu.
	 *
	 * @link https://developer.wordpress.org/reference/functions/add_submenu_page/#comment-445
	 */
	public function admin_menu() {
		if ( current_user_can( 'manage_sensei' ) ) {
			add_submenu_page(
				'options.php',
				__( 'Sensei LMS - Setup Wizard', 'sensei-lms' ),
				__( 'Sensei LMS - Setup Wizard', 'sensei-lms' ),
				'manage_sensei',
				$this->page_slug,
				[ $this, 'setup_wizard_page' ]
			);
		}
	}

	/**
	 * Render app container for setup wizard.
	 */
	public function setup_wizard_page() {

		?>
		<div id="sensei-onboarding-page" class="sensei-onboarding">

		</div>
		<?php
	}

	/**
	 * Check if should show help tab or not.
	 *
	 * @param string $screen_id Screen ID to check if should show the help tab.
	 *
	 * @return boolean
	 */
	private function should_show_help_screen( $screen_id ) {
		return 'edit-course' === $screen_id;
	}

	/**
	 * Add onboarding help tab.
	 *
	 * @param WP_Screen $screen Current screen.
	 *
	 * @access private
	 */
	public function add_onboarding_help_tab( $screen ) {
		$link_track_event = 'setup_wizard_click';

		if ( ! $screen || ! $this->should_show_help_screen( $screen->id ) ) {
			return;
		}

		$screen->add_help_tab(
			[
				'id'      => 'sensei_lms_onboarding_tab',
				'title'   => __( 'Setup wizard', 'sensei-lms' ),
				'content' =>
					'<h2>' . __( 'Sensei LMS Onboarding', 'sensei-lms' ) . '</h2>' .
					'<h3>' . __( 'Setup Wizard', 'sensei-lms' ) . '</h3>' .
					'<p>' . __( 'If you need to access the setup wizard again, please click on the button below.', 'sensei-lms' ) . '</p>' .
					'<p><a href="' . admin_url( 'admin.php?page=' . $this->page_slug ) . '" class="button button-primary" data-sensei-log-event="' . $link_track_event . '">' . __( 'Setup wizard', 'sensei-lms' ) . '</a></p>',
			]
		);
	}
	/**
	 * Register REST API route.
	 */
	public function register_rest_api() {

		register_rest_route(
			'sensei/v1',
			'/onboarding/(?P<page>[a-zA-Z0-9-]+)',
			array(
				'methods'             => [ 'GET', 'POST' ],
				'callback'            => [ $this, 'handle_api_request' ],
				'permission_callback' => [ $this, 'can_user_access_rest_api' ],
			)
		);
	}

	/**
	 * Check user permission for REST API access.
	 *
	 * @return bool Whether the user can access the Onboarding REST API.
	 */
	public function can_user_access_rest_api() {
		return current_user_can( 'manage_sensei' );
	}

	/**
	 * Process onboarding API request.
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return mixed Result for the called endpoint.
	 */
	public function handle_api_request( $request ) {

		$page      = $request->get_param( 'page' );
		$method    = $request->get_method();
		$endpoints = [
			'welcome' => [
				'GET'  => [ $this, 'api_welcome_get' ],
				'POST' => [ $this, 'api_welcome_submit' ],
			],
		];

		if ( ! ( array_key_exists( $page, $endpoints ) && array_key_exists( $method, $endpoints[ $page ] ) ) ) {
			return new WP_Error( 'invalid_page', __( 'Page not found', 'sensei-lms' ), [ 'status' => 404 ] );
		}
		$endpoint = $endpoints[ $page ][ $method ];

		if ( 'POST' === $method ) {
			$data = $request->get_json_params();
			return call_user_func( $endpoint, $data );
		} else {
			return call_user_func( $endpoint );
		}
	}

	/**
	 * Welcome step data.
	 *
	 * @return array Data used on welcome page.
	 */
	public function api_welcome_get() {
		return [
			'usage_tracking' => Sensei()->usage_tracking->get_tracking_enabled(),
		];
	}

	/**
	 * Submit form on welcome step.
	 *
	 * @param array $data Form data.
	 *
	 * @return bool Success.
	 */
	public function api_welcome_submit( $data ) {
		Sensei()->usage_tracking->set_tracking_enabled( (bool) $data['usage_tracking'] );
		$this->pages->create_pages();

		return true;
	}

}
