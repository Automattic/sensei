<?php
/**
 * File containing the Sensei_Home_Task_Configure_Learning_Mode class.
 *
 * @package sensei-lms
 * @since $$next-version$$
 */

/**
 * Sensei_Home_Task_Configure_Learning_Mode class.
 *
 * @since $$next-version$$
 */
class Sensei_Home_Task_Configure_Learning_Mode implements Sensei_Home_Task {

	/**
	 * The ID for the task.
	 *
	 * @return string
	 */
	public function get_id(): string {
		return 'configure-learning-mode';
	}

	/**
	 * Number used to sort in frontend.
	 *
	 * @return int
	 */
	public function get_priority(): int {
		return 200;
	}

	/**
	 * Task title.
	 *
	 * @return string
	 */
	public function get_title(): string {
		return __( 'Configure Learning Mode', 'sensei-lms' );
	}

	/**
	 * Task url.
	 *
	 * @return string
	 */
	public function get_url(): ?string {
		return admin_url( 'edit.php?post_type=course&page=sensei-settings#appearance-settings' );
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
		$visited_settings_sections = get_site_option( Sensei_Settings::VISITED_SECTIONS_OPTION_KEY, [] );
		return in_array( 'appearance-settings', $visited_settings_sections, true );
	}
}
