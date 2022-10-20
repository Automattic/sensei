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
	 * @return array|null
	 */
	public function get() {
		if ( ! current_user_can( 'manage_sensei' ) ) {
			return null;
		}

		return [
			'items'        => $this->get_tasks(),
			'site'         => $this->get_site(),
			'course'       => $this->get_course(),
			'is_completed' => (bool) get_option( self::COMPLETED_TASKS_OPTION_KEY, false ),
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
			new Sensei_Home_Task_Publish_First_Course(),
		];

		if ( Sensei_Home_Task_Sell_Course_With_WooCommerce::is_active() ) {
			$core_tasks[] = new Sensei_Home_Task_Sell_Course_With_WooCommerce();
		}

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
			'done'     => $task->is_completed(),
		];
	}

	/**
	 * Return the site information needed for the task list component.
	 *
	 * @return array The site info, including title and image (which is the custom logo) URL.
	 */
	private function get_site() {
		$custom_logo_id = get_theme_mod( 'custom_logo' );
		$image          = wp_get_attachment_image_src( $custom_logo_id, 'full' );
		return [
			'title' => get_bloginfo( 'name' ),
			'image' => $image ? $image[0] : null,
		];
	}

	/**
	 * Return the course information needed for the task list component, including title and image (which is the
	 * featured image) for that course. Please note that the data for the demo course is never returned by this method.
	 *
	 * @return array|null The course information, including title, the permalink and the URL for the featured image.
	 */
	private function get_course() {
		global $wpdb;
		$cache_key   = 'home/metadata/tasks/course';
		$cache_group = 'sensei/temporary';
		$result      = wp_cache_get( $cache_key, $cache_group );
		if ( false === $result ) {
			$prefix = $wpdb->esc_like( Sensei_Data_Port_Manager::SAMPLE_COURSE_SLUG );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Safe-ish and rare query.
			$post_id = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_type='course' AND post_status IN ('publish', 'draft') AND post_name NOT LIKE %s ORDER BY post_status='published' DESC, ID ASC LIMIT 1", "{$prefix}%" ) );
			if ( null === $post_id ) {
				$result = null;
			} else {
				$image  = get_the_post_thumbnail_url( $post_id, 'full' );
				$result = [
					'title'     => get_the_title( $post_id ),
					'permalink' => get_permalink( $post_id ),
					'image'     => $image ? $image : null,
				];
				wp_cache_set( $cache_key, $result, $cache_group, 60 );
			}
		}
		return $result;
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
