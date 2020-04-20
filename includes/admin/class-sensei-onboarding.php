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


	}


	public function onboarding_page() {
		?>
		<div id="sensei-onboarding-page">

		</div>
		<?php
	}

}
