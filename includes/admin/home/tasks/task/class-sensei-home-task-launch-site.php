<?php
/**
 * File containing the Sensei_Home_Task_Launch_Site class.
 *
 * @package sensei-lms
 * @since $$next-version$$
 */

/**
 * Sensei_Home_Task_Launch_Site class.
 *
 * @since $$next-version$$
 */
class Sensei_Home_Task_Launch_Site implements Sensei_Home_Task {
	/**
	 * The action key.
	 *
	 * @var string
	 */
	const ACTION = 'sensei_home_task_launch_site';

	/**
	 * Initialize the task hooks.
	 */
	public function init() {
		add_action( 'admin_post_' . static::ACTION, [ $this, 'handle_action' ] );
	}

	/**
	 * Handle the task action.
	 */
	function handle_action() {
		check_admin_referer( static::ACTION );

		$blog_id  = (int) Jetpack_Options::get_option( 'id' );
		$response = \Automattic\Jetpack\Connection\Client::wpcom_json_api_request_as_user(
			"/sites/$blog_id/launch",
			'1.1',
			[
				'method' => 'POST',
			],
			null,
			'rest'
		);

		print_r( $response );
	}

	/**
	 * The ID for the task.
	 *
	 * @return string
	 */
	public static function get_id(): string {
		return 'launch-site';
	}

	/**
	 * Number used to sort in frontend.
	 *
	 * @return int
	 */
	public function get_priority(): int {
		return 700;
	}

	/**
	 * Task title.
	 *
	 * @return string
	 */
	public function get_title(): string {
		return __( 'Launch your site', 'sensei-lms' );
	}

	/**
	 * Task url.
	 *
	 * @return string
	 */
	public function get_url(): ?string {
		return add_query_arg(
			[
				'action' => static::ACTION,
			],
			wp_nonce_url( admin_url( 'admin-post.php' ), static::ACTION )
		);
	}

	/**
	 * Whether the task is completed or not.
	 *
	 * @return bool
	 */
	public function is_completed(): bool {
		return false; // TODO: Remove hardcoded value.

		$task_statuses = (array) get_option( 'launchpad_checklist_tasks_statuses' );

		return isset( $task_statuses['site_launched'] ) && $task_statuses['site_launched'];
	}

	/**
	 * Test if this task should be active or not.
	 *
	 * @return bool Whether the task should be active or not.
	 */
	public static function is_active() {
		return true; // TODO: Remove hardcoded value.

		// return Sensei_Utils::is_atomic_platform();
	}
}
