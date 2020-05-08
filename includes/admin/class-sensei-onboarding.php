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
	const SUGGEST_SETUP_WIZARD_OPTION = 'sensei_suggest_setup_wizard';

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
			add_action( 'admin_notices', [ $this, 'setup_wizard_notice' ] );
			add_action( 'admin_init', array( $this, 'skip_setup_wizard' ) );
			add_action( 'admin_init', array( $this, 'activation_redirect' ) );
			add_action( 'current_screen', [ $this, 'add_setup_wizard_help_tab' ] );

			// Maybe prevent WooCommerce help tab.
			add_filter( 'woocommerce_enable_admin_help_tab', [ $this, 'should_enable_woocommerce_help_tab' ] );

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
	 * Register the hidden setup wizard submenu.
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
	 * Redirect after first activation.
	 */
	public function activation_redirect() {
		if (
			// Check if activation redirect is needed.
			! get_transient( 'sensei_activation_redirect' )
			// Test whether the context of execution comes from async action scheduler.
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Arguments used for comparison.
			|| ( isset( $_REQUEST['action'] ) && 'as_async_request_queue_runner' === $_REQUEST['action'] )
			// On these pages, or during these events, postpone the redirect.
			|| wp_doing_ajax() || wp_doing_cron() || is_network_admin() || ! current_user_can( 'manage_sensei' )
		) {
			return;
		}

		delete_transient( 'sensei_activation_redirect' );
		$this->redirect_to_setup_wizard();
	}

	/**
	 * Redirect to setup wizard.
	 */
	protected function redirect_to_setup_wizard() {
		wp_safe_redirect( admin_url( 'admin.php?page=' . $this->page_slug ) );
		exit;
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
	 * Check if current screen is selected to display the wizard notice.
	 *
	 * @return boolean
	 */
	private function should_current_page_display_setup_wizard() {
		$screen = get_current_screen();

		if ( false !== strpos( $screen->id, 'sensei-lms_page_sensei' ) ) {
			return true;
		}

		$screens_without_sensei_prefix = [
			'dashboard',
			'plugins',
			'edit-sensei_message',
			'edit-course',
			'edit-course-category',
			'course_page_course-order',
			'edit-module',
			'course_page_module-order',
			'edit-lesson',
			'edit-lesson-tag',
			'lesson_page_lesson-order',
			'edit-question',
			'question',
			'edit-question-category',
		];

		return in_array( $screen->id, $screens_without_sensei_prefix, true );
	}

	/**
	 * Setup wizard notice.
	 *
	 * @access private
	 */
	public function setup_wizard_notice() {
		if (
			! $this->should_current_page_display_setup_wizard()
			|| ! get_option( self::SUGGEST_SETUP_WIZARD_OPTION, 0 )
			|| ! current_user_can( 'manage_sensei' )
		) {
			return;
		}

		$setup_url = admin_url( 'admin.php?page=' . $this->page_slug );

		$skip_url = add_query_arg( 'sensei_skip_setup_wizard', '1' );
		$skip_url = wp_nonce_url( $skip_url, 'sensei_skip_setup_wizard' );
		?>
		<div id="message" class="updated sensei-message sensei-connect">
			<p><?php echo wp_kses_post( __( '<strong>Welcome to Sensei LMS</strong> &#8211; You\'re almost ready to start creating online courses!', 'sensei-lms' ) ); ?></p>

			<p class="submit">
				<a href="<?php echo esc_url( $setup_url ); ?>" class="button-primary">
					<?php esc_html_e( 'Run the Setup Wizard', 'sensei-lms' ); ?>
				</a>

				<a class="button" href="<?php echo esc_url( $skip_url ); ?>">
					<?php esc_html_e( 'Skip setup', 'sensei-lms' ); ?>
				</a>
			</p>
		</div>
		<?php
	}

	/**
	 * Skip setup wizard.
	 *
	 * @access private
	 */
	public function skip_setup_wizard() {
		if (
			isset( $_GET['sensei_skip_setup_wizard'] )
			&& '1' === $_GET['sensei_skip_setup_wizard']
			&& isset( $_GET['_wpnonce'] )
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Don't touch the nonce.
			&& wp_verify_nonce( wp_unslash( $_GET['_wpnonce'] ), 'sensei_skip_setup_wizard' )
			&& current_user_can( 'manage_sensei' )
		) {
			update_option( self::SUGGEST_SETUP_WIZARD_OPTION, 0 );
		}
	}

	/**
	 * Prevent displaying WooCommerce help tab in Sensei admin pages.
	 *
	 * @access private
	 *
	 * @param boolean $allow Allow showing the WooCommerce help tab.
	 *
	 * @return boolean
	 */
	public function should_enable_woocommerce_help_tab( $allow ) {
		$post_types_to_prevent = [ 'course', 'lesson', 'sensei_message' ];

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Arguments used for comparison.
		if ( isset( $_GET['post_type'] ) && in_array( $_GET['post_type'], $post_types_to_prevent, true ) ) {
			return false;
		}

		return $allow;
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
	 * Add setup wizard help tab.
	 *
	 * @param WP_Screen $screen Current screen.
	 *
	 * @access private
	 */
	public function add_setup_wizard_help_tab( $screen ) {
		$link_track_event = 'setup_wizard_click';

		if ( ! $screen || ! $this->should_show_help_screen( $screen->id ) || ! current_user_can( 'manage_sensei' ) ) {
			return;
		}

		$screen->add_help_tab(
			[
				'id'      => 'sensei_lms_setup_wizard_tab',
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
