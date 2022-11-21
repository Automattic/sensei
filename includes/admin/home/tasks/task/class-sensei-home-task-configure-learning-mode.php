<?php
/**
 * File containing the Sensei_Home_Task_Configure_Learning_Mode class.
 *
 * @package sensei-lms
 * @since 4.8.0
 */

/**
 * Sensei_Home_Task_Configure_Learning_Mode class.
 *
 * @since 4.8.0
 */
class Sensei_Home_Task_Configure_Learning_Mode implements Sensei_Home_Task {

	/**
	 * The ID for the task.
	 *
	 * @return string
	 */
	public static function get_id(): string {
		return 'configure-learning-mode';
	}

	/**
	 * Number used to sort in frontend.
	 *
	 * @return int
	 */
	public function get_priority(): int {
		return 300;
	}

	/**
	 * Task title.
	 *
	 * @return string
	 */
	public function get_title(): string {
		return __( 'Configure learning mode', 'sensei-lms' );
	}

	/**
	 * Task url.
	 *
	 * @return string
	 */
	public function get_url(): ?string {
		return admin_url( 'admin.php?page=sensei-settings#appearance-settings' );
	}

	/**
	 * Whether the task is completed or not.
	 *
	 * @return bool
	 */
	public function is_completed(): bool {
		$visited_settings_sections = get_option( Sensei_Settings::VISITED_SECTIONS_OPTION_KEY, [] );
		return in_array( 'appearance-settings', $visited_settings_sections, true );
	}
}
