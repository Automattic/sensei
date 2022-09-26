<?php
/**
 * File containing Sensei_Home_Help_Provider class.
 *
 * @package sensei-lms
 * @since   $$next-version$$
 */

/**
 * Class responsible for generating the Help structure for Sensei Home screen.
 */
class Sensei_Home_Help_Provider {

	/**
	 * Return a list of categories which each contain multiple help items.
	 *
	 * @return Sensei_Home_Help_Category[]
	 */
	public function get(): array {
		return [
			new Sensei_Home_Help_Category(
				__( 'Get the most out of Sensei', 'sensei-lms' ),
				[
					new Sensei_Home_Help_Item( __( 'Sensei Documentation', 'sensei-lms' ), 'https://senseilms.com/documentation/' ),
					new Sensei_Home_Help_Item( __( 'Support forums', 'sensei-lms' ), 'https://wordpress.org/support/plugin/sensei-lms/' ),
					$this->create_contact_support_item(),
				]
			),
		];
	}

	/**
	 * Generates the item to contact support.
	 *
	 * @return Sensei_Home_Help_Item
	 */
	private function create_contact_support_item() {
		$url        = null;
		$extra_link = null;
		if ( apply_filters( 'sensei_home_create_support_upgrade_cta_display', true ) ) {
			$extra_link = new Sensei_Home_Help_Extra_Link( __( 'Upgrade to Sensei Pro', 'sensei-lms' ), 'https://senseilms.com/pricing/' );
		} else {
			$url = 'https://senseilms.com/contact/';
		}
		return new Sensei_Home_Help_Item( __( 'Create a support ticket', 'sensei-lms' ), $url, 'lock', $extra_link );
	}

}
