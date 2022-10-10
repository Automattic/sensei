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

	const COMPLETED_TASKS_OPTION_KEY = 'sensei_home_tasks_list_is_completed';

	/**
	 * Returns the Tasks.
	 *
	 * @return array
	 */
	public function get(): array {
		return [
			'items'        => $this->get_tasks(),
			'is_completed' => get_option( self::COMPLETED_TASKS_OPTION_KEY, false ),
		];
	}

	/**
	 * Actual logic to decide what tasks have to be returned.
	 *
	 * @return array[]
	 */
	private function get_tasks(): array {
		// TODO Implement the logic for this.
		$core_tasks = [
			new Sensei_Home_Task_Setup_Site(),
			new Sensei_Home_Task_Create_First_Course(),
			new Sensei_Home_Task_Configure_Learning_Mode(),
		];

		$tasks = [];
		/**
		 * Each one of the core tasks.
		 *
		 * @var Sensei_Home_Task $core_task
		 */
		foreach ( $core_tasks as $core_task ) {
			$tasks[ $core_task::get_id() ] = $this->map_task( $core_task );
		}

		/**
		 * Filters the list of tasks that will be later displayed in the Sensei Home header.
		 *
		 * @since $$next-version$$
		 *
		 * @param array $tasks {
		 *  A dictionary of tasks indexed by task ID.
		 *
		 *  @type string $id The task ID. Must be unique.
		 *  @type string $title The task title.
		 *  @type int $priority Number used in frontend to sort tasks in ascending order.
		 *  @type string $url Optional. Destination URL for users when clicking on the task.
		 *  @type string $image Optional. Source url or path for the featured image when this task is the first pending one.
		 *  @type bool $done Whether the task is considered done or not.
		 * }
		 */
		return apply_filters( 'sensei_home_tasks', $tasks );
	}

	/**
	 * Maps a specific Sensei_Home_Task to a basic array structure. Will execute the code in `is_completed` for all entries.
	 *
	 * @param Sensei_Home_Task $task The actual task to map.
	 * @return array
	 */
	private function map_task( Sensei_Home_Task $task ): array {
		return [
			'id'       => $task::get_id(),
			'title'    => $task->get_title(),
			'priority' => $task->get_priority(),
			'url'      => $task->get_url(),
			'image'    => $task->get_image(),
			'done'     => $task->is_completed(),
		];
	}

	/**
	 * Mark tasks list as completed.
	 *
	 * @param bool $completed Whether the task list must be marked as completed or uncompleted.
	 */
	public function mark_as_completed( $completed = true ) {
		update_option( self::COMPLETED_TASKS_OPTION_KEY, $completed, false );
	}
}
