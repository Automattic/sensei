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
class Sensei_Home_Task_Setup_First_Course implements Sensei_Home_Task {
	/**
	 * Task title.
	 *
	 * @return string
	 */
	public function get_title(): string {
		return __( 'Create your first Course', 'sensei-lms' );
	}

	/**
	 * Task url.
	 *
	 * @return string
	 */
	public function get_url(): ?string {
		return admin_url( 'edit.php?post_type=course' );
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
		// Completed if there is any created course.
		return Sensei()->course->course_count( 'any' ) > 0;
	}
}
