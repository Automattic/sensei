<?php
/**
 * File containing Sensei_Home_Tasks_Provider class.
 *
 * @package sensei-lms
 * @since   4.8.0
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
	 * Tells if the WP hooks were attached or not.
	 *
	 * @var bool
	 */
	private static $attached_hooks = false;

	/**
	 * The option name that wp-calypso automatically fetches to use
	 * as a reference for Launchpad tasks' complete statuses.
	 *
	 * @var string
	 */
	const CALYPSO_LAUNCHPAD_STATUSES_NAME = 'launchpad_checklist_tasks_statuses';

	/**
	 * Class constructor.
	 */
	public function __construct() {
		$this->attach_tasks_statuses_hooks();
	}

	/**
	 * Returns the Tasks.
	 *
	 * @return array
	 */
	public function get(): array {
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

		if ( Sensei_Home_Task_Pro_Upsell::is_active() ) {
			$core_tasks[] = new Sensei_Home_Task_Pro_Upsell();
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
		 * @since 4.8.0
		 *
		 * @hook sensei_home_tasks
		 *
		 * @param    {array[]} $tasks                A dictionary of tasks indexed by task ID.
		 * @property {string} `$tasks[]['id']`       The task ID. Must be unique.
		 * @property {string} `$tasks[]['title']`    The task title.
		 * @property {int}    `$tasks[]['priority']` Number used in frontend to sort tasks in ascending order.
		 * @property {string} `$tasks[]['url']`      Optional. Destination URL for users when clicking on the task.
		 * @property {string} `$tasks[]['image']`    Optional. Source url or path for the featured image when this task is the first pending one.
		 * @property {bool}   `$tasks[]['done']`     Whether the task is considered done or not.
		 * @return {array} Filtered tasks.
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
			// Title is persisted with encoded specialchars. We need to decode so that consumer decides what to do with it.
			'title' => wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ),
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
				$post   = get_post( $post_id );
				$image  = get_the_post_thumbnail_url( $post_id, 'full' );
				$result = [
					'title'     => $post->post_title,
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

	/**
	 * Attaches required hooks.
	 */
	private function attach_tasks_statuses_hooks() {
		// Attach the hooks only once.
		if ( self::$attached_hooks ) {
			return;
		}

		self::$attached_hooks = true;

		add_action( 'save_post_course', [ $this, 'log_course_completion_tasks' ], 10, 3 );

		// Attach some hooks only for Atomic sites.
		if ( ! Sensei_Utils::is_atomic_platform() ) {
			return;
		}

		// Attach the update_tasks_statuses method to filters and actions
		// that can affect the status of the Sensei Home tasks.
		add_action( 'save_post_course', [ $this, 'update_tasks_statuses' ] );
		add_action( 'wp_ajax_sensei_settings_section_visited', [ $this, 'update_tasks_statuses' ] );
	}

	/**
	 * Updates the tasks_statuses in wp options
	 */
	public function update_tasks_statuses() {
		$tasks_statuses = [];

		foreach ( $this->get_tasks() as $task ) {
			// Convert the names of the tasks into snake case.
			$task_name                    = str_replace( '-', '_', $task['id'] );
			$tasks_statuses[ $task_name ] = $task['done'];
		}

		// Overwrite the existing values with the new ones before updating.
		$tasks_statuses = array_merge( get_option( self::CALYPSO_LAUNCHPAD_STATUSES_NAME, [] ), $tasks_statuses );
		update_option( self::CALYPSO_LAUNCHPAD_STATUSES_NAME, $tasks_statuses );
	}

	/**
	 * Logs completion of the "Create your first course" and "Publish your first course" tasks.
	 *
	 * @internal
	 *
	 * @since 4.20.1
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 * @param bool    $update  Whether this is an existing post being updated.
	 */
	public function log_course_completion_tasks( $post_id, $post, $update ) {
		if ( ! $update ) {
			return;
		}

		if ( (bool) get_option( self::COMPLETED_TASKS_OPTION_KEY, false ) ) {
			return;
		}

		// Handle "Create your first course" task completion.
		if ( 'draft' === $post->post_status ) {
			$create_first_course_task = new Sensei_Home_Task_Create_First_Course();

			if ( ! $create_first_course_task->is_completed() ) {
				sensei_log_event( 'home_task_complete', [ 'type' => $create_first_course_task::get_id() ] );
				update_option( Sensei_Home_Task_Create_First_Course::CREATED_FIRST_COURSE_OPTION_KEY, 1, false );
			}
		} elseif ( 'publish' === $post->post_status ) { // Handle "Publish your first course" task completion.
			$publish_first_course_task = new Sensei_Home_Task_Publish_First_Course();

			// Log task completion event if this is the first published course.
			if ( ! $publish_first_course_task->is_completed() ) {
				sensei_log_event( 'home_task_complete', [ 'type' => $publish_first_course_task::get_id() ] );
				update_option( Sensei_Home_Task_Publish_First_Course::PUBLISHED_FIRST_COURSE_OPTION_KEY, 1, false );
			}
		}
	}
}
