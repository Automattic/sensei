<?php
/**
 * File containing the Sensei_Home_Task_Customize_Course_Theme class.
 *
 * @package sensei-lms
 * @since 4.10.0
 */

/**
 * Sensei_Home_Task_Customize_Course_Theme class.
 *
 * @since 4.10.0
 */
class Sensei_Home_Task_Customize_Course_Theme implements Sensei_Home_Task {
	const CUSTOMIZED_COURSE_THEME_OPTION_KEY = 'sensei_home_task_visited_course_theme_customizer';

	/**
	 * The ID for the task.
	 *
	 * @return string
	 */
	public static function get_id(): string {
		return 'customize-course-theme';
	}

	/**
	 * Number used to sort in frontend.
	 *
	 * @return int
	 */
	public function get_priority(): int {
		return 400;
	}

	/**
	 * Task title.
	 *
	 * @return string
	 */
	public function get_title(): string {
		return __( 'Customize your lesson template', 'sensei-lms' );
	}

	/**
	 * Task url.
	 *
	 * @return string
	 */
	public function get_url(): ?string {
		return add_query_arg(
			[
				'canvas' => 'edit',
			],
			Sensei_Course_Theme::get_learning_mode_fse_url()
		);
	}

	/**
	 * Whether the task is completed or not.
	 *
	 * @return bool
	 */
	public function is_completed(): bool {
		return (bool) get_option( self::CUSTOMIZED_COURSE_THEME_OPTION_KEY, false );
	}

	/**
	 * Mark the task as completed.
	 *
	 * @return void
	 */
	public static function mark_completed() {
		update_option( self::CUSTOMIZED_COURSE_THEME_OPTION_KEY, true, false );
		sensei_log_event( 'home_task_complete', [ 'type' => self::get_id() ] );
	}

	/**
	 * Test if this task should be active or not.
	 *
	 * @return bool Whether the task should be active or not.
	 */
	public static function is_active() {
		return 'course' === wp_get_theme()->get_template();
	}
}
