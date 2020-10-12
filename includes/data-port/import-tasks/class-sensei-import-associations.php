<?php
/**
 * File containing the Sensei_Import_Associations class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * This class handles the late associations that were set up during import.
 */
class Sensei_Import_Associations
	extends Sensei_Data_Port_Task
	implements Sensei_Data_Port_Task_Interface {

	const BATCH_SIZE           = 50;
	const TASK_KEY             = 'associations';
	const STATE_TOTAL_TASKS    = 'total';
	const STATE_LESSON_MODULES = 'lesson-modules';
	const STATE_COURSE_LESSONS = 'course-lessons';

	/**
	 * Total sub-tasks to complete once this task starts.
	 *
	 * @var int
	 */
	private $total_tasks;

	/**
	 * Lesson and modules that need to be associated.
	 *
	 * @var array
	 */
	private $lesson_modules = [];

	/**
	 * Courses and lessons that need to be associated.
	 *
	 * @var array
	 */
	private $course_lessons = [];

	/**
	 * Number remaining in the current batch.
	 *
	 * @var int
	 */
	private $batch_remaining;

	/**
	 * Sensei_Import_Associations constructor.
	 *
	 * @param Sensei_Data_Port_Job $job
	 */
	public function __construct( Sensei_Data_Port_Job $job ) {
		parent::__construct( $job );

		$task_state = $this->get_job()->get_state( self::TASK_KEY );

		$this->total_tasks    = isset( $task_state[ self::STATE_TOTAL_TASKS ] ) ? $task_state[ self::STATE_TOTAL_TASKS ] : null;
		$this->lesson_modules = isset( $task_state[ self::STATE_LESSON_MODULES ] ) ? $task_state[ self::STATE_LESSON_MODULES ] : [];
		$this->course_lessons = isset( $task_state[ self::STATE_COURSE_LESSONS ] ) ? $task_state[ self::STATE_COURSE_LESSONS ] : [];
	}

	/**
	 * Run this task.
	 */
	public function run() {
		if ( ! isset( $this->total_tasks ) ) {
			$this->total_tasks = $this->get_remaining_tasks();
		}

		$this->batch_remaining = self::BATCH_SIZE;

		foreach ( $this->course_lessons as $course_id => $args ) {
			if ( $this->batch_remaining <= 0 ) {
				return;
			}

			$this->handle_course_lessons( $course_id, $args );
			unset( $this->course_lessons[ $course_id ] );
		}

		foreach ( $this->lesson_modules as $lesson_id => $args ) {
			if ( $this->batch_remaining <= 0 ) {
				return;
			}

			$this->handle_lesson_module( $lesson_id, $args );
			unset( $this->lesson_modules[ $lesson_id ] );
		}
	}

	/**
	 * Handle associating a course to a its lessons.
	 *
	 * @param int   $course_id Course post ID.
	 * @param array $args      Arguments from when this task was enqueued.
	 */
	private function handle_course_lessons( $course_id, $args ) {
		$this->batch_remaining--;

		list( $lessons_str, $line_number, $post_title ) = $args;

		$new_lessons = array_unique( Sensei_Data_Port_Utilities::split_list_safely( $lessons_str ) );
		if ( null === $new_lessons ) {
			return;
		}

		$add_warning_helper = function( $message, $code ) use ( $line_number, $course_id, $post_title ) {
			$model_key = Sensei_Import_Course_Model::MODEL_KEY;
			$this->get_job()->add_line_warning(
				$model_key,
				$line_number,
				$message,
				[
					'line'        => $line_number,
					'type'        => $model_key,
					'post_id'     => $course_id,
					'entry_title' => $post_title,
					'code'        => $code,
				]
			);
		};

		$current_lessons = $this->get_current_lesson_ids( $course_id );
		$order_index     = 1;
		$module_indices  = [];
		$lesson_order    = [];
		$all_lessons     = [];
		foreach ( $new_lessons as $lesson_ref ) {
			$lesson_id = $this->get_job()->translate_import_id( Sensei_Data_Port_Lesson_Schema::POST_TYPE, $lesson_ref );
			if ( empty( $lesson_id ) ) {
				$add_warning_helper(
					// translators: Placeholder is the reference to a lesson which did not exist.
					sprintf( __( 'Lesson does not exist: %s.', 'sensei-lms' ), $lesson_ref ),
					'sensei_data_port_course_lesson_not_found'
				);

				continue;
			}

			// Check to see if this lesson was set to multiple courses in the import.
			$current_lesson_course_id = get_post_meta( $lesson_id, '_lesson_course', true );
			if (
				$current_lesson_course_id
				&& intval( $current_lesson_course_id ) !== intval( $course_id )
				&& $this->get_job()->was_imported( Sensei_Data_Port_Course_Schema::POST_TYPE, $current_lesson_course_id )
			) {
				$add_warning_helper(
					// translators: Placeholder is the lesson reference (e.g. "id:44").
					sprintf( __( 'The lesson "%s" can only be associated with one course at a time.', 'sensei-lms' ), $lesson_ref ),
					'sensei_data_port_lesson_multiple_course'
				);

				continue;
			}

			// Add the lesson to the course.
			update_post_meta( $lesson_id, '_lesson_course', $course_id );

			$module_term_id = $this->get_lesson_module( $lesson_id );
			if ( $module_term_id ) {
				if ( ! isset( $module_indices[ $module_term_id ] ) ) {
					$module_indices[ $module_term_id ] = 0;
				} else {
					$module_indices[ $module_term_id ]++;
				}

				update_post_meta( $lesson_id, '_order_module_' . $module_term_id, $module_indices[ $module_term_id ] );
			} else {
				$lesson_order[] = $lesson_id;
				update_post_meta( $lesson_id, '_order_' . $course_id, $order_index );

				$order_index++;
			}

			$all_lessons[] = $lesson_id;
		}

		update_post_meta( $course_id, '_lesson_order', implode( ',', $lesson_order ) );

		// Remove lessons on the course that weren't included in the course import.
		$old_lessons = array_diff( $current_lessons, $all_lessons );
		foreach ( $old_lessons as $lesson_id ) {
			delete_post_meta( $lesson_id, '_lesson_course' );
		}
	}

	/**
	 * Get the lesson's module ID.
	 *
	 * @param int $lesson_id The lesson post ID.
	 *
	 * @return false|int
	 */
	private function get_lesson_module( $lesson_id ) {
		$module_term_id = false;

		// Make sure any related module associations are handled.
		if ( isset( $this->lesson_modules[ $lesson_id ] ) ) {
			$module_term_id = $this->handle_lesson_module( $lesson_id, $this->lesson_modules[ $lesson_id ] );
			unset( $this->lesson_modules[ $lesson_id ] );
		}

		if ( ! $module_term_id ) {
			$module_term = Sensei()->modules->get_lesson_module( $lesson_id );
			if ( $module_term ) {
				$module_term_id = $module_term->term_id;
			}
		}

		return $module_term_id;
	}

	/**
	 * Get the current lesson IDs for a course.
	 *
	 * @param int $course_id Course post ID.
	 *
	 * @return int[]
	 */
	private function get_current_lesson_ids( $course_id ) {
		$post_args = [
			'post_type'        => 'lesson',
			'posts_per_page'   => -1,
			'post_status'      => 'any',
			'suppress_filters' => 0,
			'fields'           => 'ids',
			'meta_query'       => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Only during import.
				[
					'key'   => '_lesson_course',
					'value' => $course_id,
				],
			],
		];

		return get_posts( $post_args );
	}

	/**
	 * Handle lesson module association.
	 *
	 * @param int   $lesson_id Lesson post ID.
	 * @param array $args      Association task arguments.
	 *
	 * @return false|int Module term ID if successful, false if not.
	 */
	private function handle_lesson_module( $lesson_id, $args ) {
		$this->batch_remaining--;

		list( $module_ref, $line_number, $post_title ) = $args;

		$lesson_id = (int) $lesson_id;

		$add_warning_helper = function( $message, $code ) use ( $line_number, $lesson_id, $post_title ) {
			$model_key = Sensei_Import_Lesson_Model::MODEL_KEY;

			$this->get_job()->add_line_warning(
				$model_key,
				$line_number,
				$message,
				[
					'line'        => $line_number,
					'type'        => $model_key,
					'post_id'     => $lesson_id,
					'entry_title' => $post_title,
					'code'        => $code,
				]
			);
		};

		$course_id = Sensei()->lesson->get_course_id( $lesson_id );
		if ( ! $course_id ) {
			$add_warning_helper(
				// translators: Placeholder is reference to module.
				sprintf( __( 'Unable to set the lesson module to "%s" because it does not have a course associated with it.', 'sensei-lms' ), $module_ref ),
				'sensei_data_port_lesson_module_no_course'
			);

			return false;
		}

		$term = Sensei_Data_Port_Utilities::get_module_for_course( $module_ref, $course_id );
		if ( is_wp_error( $term ) ) {
			$add_warning_helper( $term->get_error_message(), $term->get_error_code() );

			return false;
		}

		$result = wp_set_object_terms( $lesson_id, [ $term->term_id ], 'module' );
		if ( is_wp_error( $result ) ) {
			$add_warning_helper( $result->get_error_message(), $result->get_error_code() );

			return false;
		}

		return $term->term_id;
	}

	/**
	 * Enqueue a module to be assigned to a lesson.
	 *
	 * @param int    $lesson_id   Lesson post ID.
	 * @param string $module      Module name.
	 * @param int    $line_number Line number for the lesson.
	 * @param string $post_title  Import line post title.
	 */
	public function add_lesson_module( $lesson_id, $module, $line_number, $post_title ) {
		$this->lesson_modules[ $lesson_id ] = [
			$module,
			$line_number,
			$post_title,
		];
	}

	/**
	 * Enqueue lessons to be associated with a course.
	 *
	 * @param int    $course_id   Course post ID.
	 * @param string $lessons     String list of references to lessons.
	 * @param int    $line_number Line number for the lesson.
	 * @param string $post_title  Import line post title.
	 */
	public function add_course_lessons( $course_id, $lessons, $line_number, $post_title ) {
		$this->course_lessons[ $course_id ] = [
			$lessons,
			$line_number,
			$post_title,
		];
	}

	/**
	 * Returns true if the task is completed.
	 *
	 * @return boolean
	 */
	public function is_completed() {
		return isset( $this->total_tasks ) && 0 === $this->get_remaining_tasks();
	}

	/**
	 * Returns the completion ratio of this task. The ration has the following format:
	 *
	 * {
	 *
	 *     @type integer $completed  Number of completed actions.
	 *     @type integer $total      Number of total actions.
	 * }
	 *
	 * @return array
	 */
	public function get_completion_ratio() {
		$artificial_task_size = 10;
		$completed            = 0;

		if ( isset( $this->total_tasks ) ) {
			if ( 0 === $this->total_tasks ) {
				$completed = $artificial_task_size;
			} else {
				$remaining = $this->get_remaining_tasks();
				$completed = floor( ( ( $this->total_tasks - $remaining ) / $this->total_tasks ) * $artificial_task_size );
			}
		}

		return [
			'completed' => $completed,
			'total'     => $artificial_task_size,
		];
	}

	/**
	 * Get the number of remaining tasks.
	 *
	 * @return int
	 */
	private function get_remaining_tasks() {
		return count( $this->lesson_modules ) + count( $this->course_lessons );
	}

	/**
	 * Save the current task's state.
	 */
	public function save_state() {
		$this->get_job()->set_state(
			self::TASK_KEY,
			[
				self::STATE_TOTAL_TASKS    => $this->total_tasks,
				self::STATE_LESSON_MODULES => $this->lesson_modules,
				self::STATE_COURSE_LESSONS => $this->course_lessons,
			]
		);
	}
}
