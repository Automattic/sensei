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
	}


	public function onboarding_page() {
		?>
		<div id="sensei-onboarding-page">

		</div>
		<?php
	}

}
