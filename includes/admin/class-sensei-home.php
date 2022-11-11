<?php
/**
 * File containing Sensei_Home class.
 *
 * @package Sensei\Admin
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Sensei_Home class.
 *
 * All functionality pertaining to Sensei Home page.
 *
 * @since 4.8.0
 */
final class Sensei_Home {
	const SCREEN_ID                  = 'toplevel_page_sensei';
	const DISMISS_TASKS_NONCE_ACTION = 'sensei-lms-dismiss-tasks';
	const DISMISS_TASKS_OPTION       = 'sensei_home_tasks_dismissed';

	/**
	 * Instance of class.
	 *
	 * @var self
	 */
	private static $instance;

	/**
	 * The Sensei Home Notices instance.
	 *
	 * @var Sensei_Home_Notices
	 */
	private $notices;

	/**
	 * Sensei Home Quick Links provider.
	 *
	 * @var Sensei_Home_Quick_Links_Provider
	 */
	private $quick_links_provider;

	/**
	 * Sensei Home Help provider.
	 *
	 * @var Sensei_Home_Help_Provider
	 */
	private $help_provider;

	/**
	 * Sensei Home Promo Banner provider.
	 *
	 * @var Sensei_Home_Promo_Banner_Provider
	 */
	private $promo_provider;

	/**
	 * Sensei Home Tasks provider.
	 *
	 * @var Sensei_Home_Tasks_Provider
	 */
	private $tasks_provider;

	/**
	 * Sensei Home News provider.
	 *
	 * @var Sensei_Home_News_Provider
	 */
	private $news_provider;

	/**
	 * Sensei Home Guides provider.
	 *
	 * @var Sensei_Home_Guides_Provider
	 */
	private $guides_provider;

	/**
	 * Sensei Notices provider.
	 *
	 * @var Sensei_Home_Notices_Provider
	 */
	private $notices_provider;

	/**
	 * Home constructor. Prevents other instances from being created outside `Sensei_Home::instance()`.
	 */
	private function __construct() {
		$remote_data_api            = new Sensei_Home_Remote_Data_API( 'sensei-lms', SENSEI_LMS_VERSION );
		$this->notices              = new Sensei_Home_Notices( $remote_data_api, self::SCREEN_ID );
		$this->notices_provider     = new Sensei_Home_Notices_Provider( Sensei_Admin_Notices::instance(), self::SCREEN_ID );
		$this->quick_links_provider = new Sensei_Home_Quick_Links_Provider();
		$this->help_provider        = new Sensei_Home_Help_Provider();
		$this->promo_provider       = new Sensei_Home_Promo_Banner_Provider();
		$this->tasks_provider       = new Sensei_Home_Tasks_Provider();
		$this->news_provider        = new Sensei_Home_News_Provider( $remote_data_api );
		$this->guides_provider      = new Sensei_Home_Guides_Provider( $remote_data_api );
	}

	/**
	 * Gets a REST API controller for Sensei Home.
	 *
	 * @param string $namespace The REST API namespace.
	 *
	 * @return Sensei_REST_API_Home_Controller
	 */
	public function get_rest_api_controller( $namespace ) {
		return new Sensei_REST_API_Home_Controller(
			$namespace,
			$this->quick_links_provider,
			$this->help_provider,
			$this->promo_provider,
			$this->tasks_provider,
			$this->news_provider,
			$this->guides_provider,
			$this->notices_provider
		);
	}

	/**
	 * Initializes the class and adds all filters and actions related to Sensei Home.
	 *
	 * @since 4.8.0
	 */
	public function init() {
		$this->notices->init();

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_action( 'wp_ajax_sensei_home_tasks_dismiss', [ $this, 'handle_tasks_dismiss' ] );
	}

	/**
	 * Enqueues admin scripts when needed on different screens.
	 *
	 * @since 4.8.0
	 * @access private
	 */
	public function enqueue_admin_assets() {
		$screen = get_current_screen();

		if ( self::SCREEN_ID === $screen->id ) {
			Sensei()->assets->enqueue( 'sensei-home', 'home/index.js', [], true );
			Sensei()->assets->enqueue( 'sensei-home-style', 'home/home.css', [ 'sensei-wp-components' ] );
			Sensei()->assets->enqueue( 'sensei-dismiss-notices', 'js/admin/sensei-notice-dismiss.js', [] );
			Sensei()->assets->preload_data( [ '/sensei-internal/v1/sensei-extensions?type=plugin', '/sensei-internal/v1/home' ] );

			$this->localize_script();
		}
	}

	/**
	 * Localize Home script.
	 *
	 * @since 4.8.0
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

		$data['dismiss_tasks_nonce'] = wp_create_nonce( self::DISMISS_TASKS_NONCE_ACTION );

		$data['dismissNoticesNonce'] = null;

		if ( class_exists( 'Sensei_Admin_Notices' ) ) {
			$data['dismissNoticesNonce'] = wp_create_nonce( Sensei_Admin_Notices::DISMISS_NOTICE_NONCE_ACTION );
		}

		$data['tasks_dismissed'] = get_option( self::DISMISS_TASKS_OPTION );

		$data['setupSampleCourseNonce'] = wp_create_nonce( 'sensei-home' );

		wp_localize_script(
			'sensei-home',
			'sensei_home',
			$data
		);
	}

	/**
	 * Get notices count.
	 *
	 * @return int Notices count.
	 */
	private function get_notices_count() {
		return $this->notices_provider->get_badge_count();
	}

	/**
	 * Adds the menu item for the Home page.
	 *
	 * @since  4.8.0
	 *
	 * @access private
	 */
	public function add_admin_menu_item() {
		$menu_cap = Sensei_Admin::get_top_menu_capability();

		$notices_html  = '';
		$notices_count = $this->get_notices_count();

		if ( $notices_count > 0 ) {
			$notices_html = ' <span class="awaiting-mod">' . (int) $notices_count . '</span>';
		}

		add_submenu_page(
			'sensei',
			__( 'Sensei LMS Home', 'sensei-lms' ),
			__( 'Home', 'sensei-lms' ) . $notices_html,
			$menu_cap,
			'sensei',
			[ $this, 'render' ],
			0
		);
	}

	/**
	 * Renders Sensei Home.
	 *
	 * @since  4.8.0
	 * @access private
	 */
	public function render() {
		require __DIR__ . '/views/html-admin-page-home.php';
	}
	/**
	 * Handle tasks dismissal.
	 *
	 * @access private
	 */
	public function handle_tasks_dismiss() {
		check_ajax_referer( self::DISMISS_TASKS_NONCE_ACTION );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( '', '', 403 );
		}

		update_option( self::DISMISS_TASKS_OPTION, 1, false );
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
