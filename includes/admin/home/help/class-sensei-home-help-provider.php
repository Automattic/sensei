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
	 * Sensei Pro detector.
	 *
	 * @var Sensei_Pro_Detector
	 */
	private $pro_detector;

	/**
	 * Class constructor.
	 *
	 * @param Sensei_Pro_Detector $pro_detector The Sensei Pro detector.
	 */
	public function __construct( Sensei_Pro_Detector $pro_detector ) {
		$this->pro_detector = $pro_detector;
	}


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
		if ( $this->pro_detector->is_loaded() ) {
			$url = 'https://senseilms.com/contact/';
		} else {
			$extra_link = new Sensei_Home_Help_Extra_Link( __( 'Upgrade to Sensei Pro', 'sensei-lms' ), 'https://senseilms.com/pricing/' );
		}
		return new Sensei_Home_Help_Item( __( 'Create a support ticket', 'sensei-lms' ), $url, 'lock', $extra_link );
	}

}
