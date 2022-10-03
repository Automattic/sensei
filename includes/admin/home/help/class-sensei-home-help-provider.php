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
					$this->create_support_ticket_item(),
				]
			),
		];
	}

	/**
	 * Generates the item to create a support ticket or a CTA if Sensei Pro is not installed.
	 *
	 * @return Sensei_Home_Help_Item
	 */
	private function create_support_ticket_item() {
		$url        = null;
		$extra_link = null;
		$icon       = null;

		/**
		 * Filter to disable upsell to Sensei Pro in Sensei Home's action to create support tickets.
		 *
		 * @hook sensei_home_support_ticket_creation_upsell_show
		 * @since $$next-version$$
		 *
		 * @param {bool} $show_upsell True if upsell must be shown.
		 *
		 * @return {bool}
		 */
		if ( apply_filters( 'sensei_home_support_ticket_creation_upsell_show', true ) ) {
			$extra_link = new Sensei_Home_Help_Extra_Link( __( 'Upgrade to Sensei Pro', 'sensei-lms' ), 'https://senseilms.com/pricing/' );
			$icon       = 'lock';
		} else {
			$url = 'https://senseilms.com/contact/';
		}

		return new Sensei_Home_Help_Item( __( 'Create a support ticket', 'sensei-lms' ), $url, $icon, $extra_link );
	}

}
