<?php
/**
 * File containing Sensei_Home_Promo_Banner_Provider class.
 *
 * @package sensei-lms
 * @since   4.8.0
 */

/**
 * Class that generates all the information relevant to the promotional banner in the Sensei Home screen.
 */
class Sensei_Home_Promo_Banner_Provider {

	/**
	 * Returns all the information for the promotional banner.
	 *
	 * @return array
	 */
	public function get(): array {

		return [
			/**
			 * Filter to disable the promotional banner in Sensei Home.
			 *
			 * @hook sensei_home_promo_banner_show
			 * @since 4.8.0
			 *
			 * @param {bool} $show_promo_banner True if promotional banner must be shown.
			 *
			 * @return {bool}
			 */
			'is_visible' => apply_filters( 'sensei_home_promo_banner_show', true ),
		];
	}
}
