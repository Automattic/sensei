<?php
/**
 * File containing Sensei_Home_Sensei_Pro_Promo_Provider class.
 *
 * @package sensei-lms
 * @since   $$next-version$$
 */

/**
 * Class responsible for returning if the promo for Sensei Pro must be shown or not in the Sensei Home screen.
 */
class Sensei_Home_Sensei_Pro_Promo_Provider {

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
	 * Returns a single boolean representing whether the promo must be shown or not.
	 *
	 * @return boolean
	 */
	public function get(): bool {
		$is_pro_loaded = $this->pro_detector->is_loaded();
		return ! $is_pro_loaded;
	}
}
