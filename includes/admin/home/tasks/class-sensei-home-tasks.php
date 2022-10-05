<?php
/**
 * File containing the Sensei_Home_Tasks class.
 *
 * @package sensei-lms
 * @since $$next-version$$
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Sensei_Home_Tasks class.
 *
 * @since $$next-version$$
 */
class Sensei_Home_Tasks {

	/**
	 * The list of actual tasks.
	 *
	 * @var Sensei_Home_Task[]
	 */
	private $items;

	/**
	 * Class constructor.
	 *
	 * @param Sensei_Home_Task[] $items The actual tasks.
	 */
	public function __construct( array $items ) {
		$this->items = $items;
	}

	/**
	 * The actual tasks.
	 *
	 * @return Sensei_Home_Task[]
	 */
	public function get_items(): array {
		return $this->items;
	}
}
