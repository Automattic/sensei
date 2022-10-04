<?php
/**
 * File containing Sensei_Home_Tasks_Provider class.
 *
 * @package sensei-lms
 * @since   $$next-version$$
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class responsible for generating the Tasks structure for Sensei Home screen.
 */
class Sensei_Home_Tasks_Provider {

	/**
	 * Returns the Tasks.
	 *
	 * @return Sensei_Home_Tasks
	 */
	public function get(): Sensei_Home_Tasks {
		return new Sensei_Home_Tasks( $this->calculate_tasks() );
	}

	/**
	 * Actual logic to decide what tasks have to be returned.
	 *
	 * @return Sensei_Home_Task[]
	 */
	private function calculate_tasks(): array {
		// TODO Implement the logic for this.
		return [
			new Sensei_Home_Task_Setup_Site(),
		];
	}

}
