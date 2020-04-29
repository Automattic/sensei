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
	 * Sensei_Onboarding constructor.
	 */
	public function __construct() {

		$this->page_slug = 'sensei_onboarding';

		if ( is_admin() ) {
			add_action( 'admin_menu', [ $this, 'admin_menu' ], 20 );
			add_action( 'current_screen', [ $this, 'add_onboarding_help_tab' ] );
			add_filter( 'woocommerce_screen_ids', [ $this, 'filter_woocommerce_screen_ids' ], 11 );

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
	 * Register an Onboarding submenu.
	 */
	public function admin_menu() {
		if ( current_user_can( 'manage_sensei' ) ) {
			add_submenu_page(
				'sensei',
				__( 'Onboarding', 'sensei-lms' ),
				__( 'Onboarding', 'sensei-lms' ),
				'manage_sensei',
				$this->page_slug,
				[ $this, 'onboarding_page' ]
			);
		}

	}


	/**
	 * Render app container for Onboarding Wizard.
	 */
	public function onboarding_page() {

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
	 * @access private
	 */
	public function add_onboarding_help_tab() {
		$screen                 = get_current_screen();
		$setup_wizard_completed = get_option( 'sensei_setup_wizard_completed' );
		$link_track_event       = 'sensei_setup_wizard_click';

		if ( ! $screen || ! $this->should_show_help_screen( $screen->id ) || $setup_wizard_completed ) {
			return;
		}

		$screen->add_help_tab(
			[
				'id'      => 'sensei_lms_onboarding_tab',
				'title'   => __( 'Setup wizard', 'sensei-lms' ),
				'content' =>
					'<h2>' . __( 'Sensei LMS Onboarding', 'sensei-lms' ) . '</h2>' .
					'<h3>' . __( 'Setup Wizard', 'sensei-lms' ) . '</h3>' .
					'<p>' . __( 'If you need to access the setup wizard again, please click on the button bellow.', 'sensei-lms' ) . '</p>' .
					'<p><a href="' . admin_url( 'admin.php?page=' . $this->page_slug ) . '" class="button button-primary" data-sensei-log-event="' . $link_track_event . '">' . __( 'Setup wizard', 'sensei-lms' ) . '</a></p>',
			]
		);
	}

	/**
	 * Filter the woocommerce screens to prevent show the WooCommerce
	 * help tab together with the onboarding tab.
	 *
	 * @access private
	 *
	 * @param string[] $screen_ids List of screen IDs considered as WooCommerce screen.
	 *
	 * @return $screen_ids Filtered screens.
	 */
	public function filter_woocommerce_screen_ids( $screen_ids ) {
		return array_filter(
			$screen_ids,
			function( $screen_id ) {
				return ! $this->should_show_help_screen( $screen_id );
			}
		);
	}
}
