<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Sensei Onboarding Class
 * All onboarding functionality
 *
 * @package Sensei
 * @author  Automattic
 * @since   3.1.0
 */
class Sensei_Onboarding {

	public $page_slug;

	public function __construct() {

		$this->page_slug = 'sensei_onboarding';

		if ( is_admin() ) {
			add_action( 'admin_menu', array ( $this, 'admin_menu' ), 20 );
			if ( isset( $_GET['page'] ) && ( $_GET['page'] == $this->page_slug ) ) {

				add_action(
					'admin_print_scripts',
					function() {
						Sensei()->assets->enqueue( 'sensei-onboarding', 'onboarding/onboarding.js', [], true );
					}
				);

				add_action(
					'admin_print_styles',
					function() {
						Sensei()->assets->enqueue( 'sensei-onboarding', 'onboarding/onboarding.css', [ 'wp-components' ] );
					}
				);
			}
		}

	}

	public function admin_menu() {
		if ( current_user_can( 'manage_sensei' ) ) {
			add_submenu_page( 'sensei', __( 'Onboarding', 'sensei-lms' ), __( 'Onboarding', 'sensei-lms' ), 'manage_sensei', $this->page_slug, array ( $this, 'onboarding_page' ) );
		}

	}
	}


	public function onboarding_page() {
		?>
		<div id="sensei-onboarding-page">

		</div>
		<?php
	}

}
