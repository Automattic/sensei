<?php
/**
 * File containing the Sensei_Home_Promo_Banner class.
 *
 * @package sensei-lms
 * @since $$next-version$$
 */

/**
 * Sensei_Home_Promo_Banner class.
 *
 * @since $$next-version$$
 */
class Sensei_Home_Promo_Banner {

	/**
	 * Whether the promotional banner must be visible or not.
	 *
	 * @var boolean
	 */
	private $visible;

	/**
	 * Constructor for Sensei_Home_Promo_Banner.
	 *
	 * @param bool $visible Whether the promo must be visible or not.
	 */
	public function __construct( bool $visible ) {
		$this->visible = $visible;
	}

	/**
	 * Whether the promo must be visible or not.
	 *
	 * @return bool
	 */
	public function is_visible(): bool {
		return $this->visible;
	}
}
