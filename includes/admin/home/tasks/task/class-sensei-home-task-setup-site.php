<?php
/**
 * File containing the Sensei_Home_Task_Setup_Site class.
 *
 * @package sensei-lms
 * @since $$next-version$$
 */

/**
 * Sensei_Home_Task_Setup_Site class.
 *
 * @since $$next-version$$
 */
class Sensei_Home_Task_Setup_Site implements Sensei_Home_Task {
	/**
	 * Task title.
	 *
	 * @return string
	 */
	public function get_title(): string {
		return __( 'Set up Course Site', 'sensei-lms' );
	}

	/**
	 * Task url.
	 *
	 * @return string
	 */
	public function get_url(): ?string {
		return admin_url( 'admin.php?page=sensei_setup_wizard' );
	}

	/**
	 * Task image.
	 *
	 * @return string|null
	 */
	public function get_image(): ?string {
		// TODO Add image path.
		return null;
	}

	/**
	 * Whether the task is completed or not.
	 *
	 * @return bool
	 */
	public function is_completed(): bool {
		return true;
	}
}
