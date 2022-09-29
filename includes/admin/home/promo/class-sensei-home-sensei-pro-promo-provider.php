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
	 * Returns a single boolean representing whether the promo must be shown or not.
	 *
	 * @return boolean
	 */
	public function get(): bool {
		return true;
	}
}
