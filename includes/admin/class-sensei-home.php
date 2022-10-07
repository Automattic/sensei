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
 * @since $$next-version$$
 */
final class Sensei_Home {
	const SCREEN_ID = 'course_page_sensei-home';

	/**
	 * Instance of class.
	 *
	 * @var self
	 */
	private static $instance;

	/**
	 * Home constructor. Prevents other instances from being created outside `Sensei_Home::instance()`.
	 */
	private function __construct() {
		$this->remote_data_api = new Sensei_Home_Remote_Data_API( 'sensei-lms' );
		$this->notices         = new Sensei_Home_Notices( $this->remote_data_api );
	}

	/**
	 * Gets the remote data API.
	 *
	 * @return Sensei_Home_Remote_Data_API
	 */
	public function get_remote_data_api() {
		return $this->remote_data_api;
	}

	/**
	 * Initializes the class and adds all filters and actions related to Sensei Home.
	 *
	 * @since $$next-version$$
	 */
	public function init() {
		$this->notices->init();

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	/**
	 * Enqueues admin scripts when needed on different screens.
	 *
	 * @since $$next-version$$
	 * @access private
	 */
	public function enqueue_admin_assets() {
		$screen = get_current_screen();

		if ( self::SCREEN_ID === $screen->id ) {
			Sensei()->assets->enqueue( 'sensei-home', 'home/index.js', [], true );
			Sensei()->assets->enqueue( 'sensei-home-style', 'home/home.css', [ 'sensei-wp-components' ] );
			Sensei()->assets->preload_data( [ '/sensei-internal/v1/sensei-extensions?type=plugin' ] );

			$this->localize_script();
		}
	}

	/**
	 * Localize Home script.
	 *
	 * @since $$next-version$$
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

		wp_localize_script(
			'sensei-home',
			'sensei_home',
			$data
		);
	}

	/**
	 * Get updates count.
	 *
	 * @return int Updates count.
	 */
	private function get_has_update_count() {
		$extensions = Sensei_Extensions::instance()->get_extensions( 'plugin' );

		return count(
			array_filter(
				array_column( $extensions, 'has_update' )
			)
		);
	}

	/**
	 * Adds the menu item for the Home page.
	 *
	 * @since  $$next-version$$
	 *
	 * @access private
	 */
	public function add_admin_menu_item() {
		$updates_html = '';
		$updates      = $this->get_has_update_count();

		if ( $updates > 0 ) {
			$updates_html = ' <span class="awaiting-mod">' . esc_html( $updates ) . '</span>';
		}

		add_submenu_page(
			'edit.php?post_type=course',
			__( 'Sensei LMS Home', 'sensei-lms' ),
			__( 'Home', 'sensei-lms' ) . $updates_html,
			'install_plugins',
			'sensei-home',
			[ $this, 'render' ],
			0
		);
	}

	/**
	 * Renders Sensei Home.
	 *
	 * @since  $$next-version$$
	 * @access private
	 */
	public function render() {
		require __DIR__ . '/views/html-admin-page-home.php';
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
