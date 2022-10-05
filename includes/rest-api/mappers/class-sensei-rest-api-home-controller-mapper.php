<?php
/**
 * File containing Sensei_REST_API_Home_Controller_Mapper class.
 *
 * @package sensei-lms
 * @since   $$next-version$$
 */

/**
 * Class responsible for mapping Sensei Home domain classes to REST structure.
 */
class Sensei_REST_API_Home_Controller_Mapper {

	/**
	 * Maps a Sensei_Home_Tasks to a basic array structure to be used as response for the REST API.
	 *
	 * @param Sensei_Home_Tasks $tasks The tasks structure.
	 *
	 * @return array
	 */
	public function map_tasks( Sensei_Home_Tasks $tasks ): array {
		$mapped = [];
		foreach ( $tasks->get_items() as $task ) {
			$mapped[ $task->get_id() ] = $this->map_task( $task );
		}

		return [
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
			'items' => apply_filters( 'sensei_home_tasks', $mapped ),
		];
	}

	/**
	 * Maps a specific Sensei_Home_Task to a basic array structure. Will execute the code in `is_completed` for all entries.
	 *
	 * @param Sensei_Home_Task $task The actual task to map.
	 * @return array
	 */
	private function map_task( Sensei_Home_Task $task ): array {
		return [
			'id'       => $task->get_id(),
			'title'    => $task->get_title(),
			'priority' => $task->get_priority(),
			'url'      => $task->get_url(),
			'image'    => $task->get_image(),
			'done'     => $task->is_completed(),
		];
	}
}
