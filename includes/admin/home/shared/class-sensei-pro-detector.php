<?php
/**
 * File containing the Sensei_Pro_Detector class.
 *
 * @package sensei-lms
 * @since   $$next-version$$
 */

/**
 * Sensei_Pro_Detector class.
 *
 * @since $$next-version$$
 */
class Sensei_Pro_Detector {
	/**
	 * Whether Sensei Pro is loaded or not.
	 *
	 * @return bool
	 */
	public function is_loaded() {
		return apply_filters( 'sensei_is_sensei_pro_active', false );
	}
}
