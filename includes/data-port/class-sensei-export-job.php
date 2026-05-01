<?php
/**
 * File containing the Sensei_Export_Job class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * This class represents a data export job.
 */
class Sensei_Export_Job extends Sensei_Data_Port_Job {
	const CONTENT_TYPES_STATE_KEY = 'content_types';
	const SELECTIONS_STATE_KEY    = 'selections';
	const RESOLVED_IDS_STATE_KEY  = 'resolved_ids';
	const MODE_STATE_KEY          = 'mode';

	const MODE_BY_COURSE    = 'by_course';
	const MODE_BY_FILE_TYPE = 'by_file_type';

	/**
	 * The array of the export tasks.
	 *
	 * @var Sensei_Data_Port_Task_Interface[]
	 */
	private $tasks;

	/**
	 * Sensei_Export_Job constructor.
	 *
	 * @param string $job_id Unique job id.
	 * @param string $json   A json string to restore internal state from.
	 */
	public function __construct( $job_id, $json = '' ) {
		parent::__construct( $job_id, $json );

		if ( null === $this->results ) {
			$this->results = self::get_default_results();
		}
	}

	/**
	 * Get the tasks of this export job.
	 *
	 * @return Sensei_Data_Port_Task_Interface[]
	 */
	public function get_tasks() {
		if ( ! isset( $this->tasks ) ) {
			$this->tasks = [];
			$task_class  = [
				'course'   => Sensei_Export_Courses::class,
				'lesson'   => Sensei_Export_Lessons::class,
				'question' => Sensei_Export_Questions::class,
			];

			foreach ( $this->get_content_types() as $type ) {
				if ( isset( $task_class[ $type ] ) ) {
					$this->tasks[ $type ] = $this->initialize_task( $task_class[ $type ] );
				}
			}

			if ( class_exists( 'ZipArchive' ) ) {
				$this->tasks['package'] = $this->initialize_task( Sensei_Export_Package::class );
			}
		}

		return $this->tasks;
	}


	/**
	 * Get the configuration for expected files.
	 *
	 * @return array
	 */
	public static function get_file_config() {
		return [
			'course'   => [],
			'lesson'   => [],
			'question' => [],
			'package'  => [],
		];
	}

	/**
	 * Check if a job is ready to be started.
	 *
	 * @return bool
	 */
	public function is_ready() {
		return true;
	}


	/**
	 * Get the result counts for each model.
	 */
	public function get_result_counts() {
	}

	/**
	 * Get the default results array.
	 *
	 * @return array
	 */
	public static function get_default_results() {
		return [];
	}

	/**
	 * Set the content types to be exported.
	 *
	 * @param string[] $content_types Content types.
	 */
	public function set_content_types( $content_types ) {
		$this->set_state( self::CONTENT_TYPES_STATE_KEY, $content_types );
	}

	/**
	 * Get the content types to be exported.
	 *
	 * @return array
	 */
	public function get_content_types() {
		return $this->get_state( self::CONTENT_TYPES_STATE_KEY );
	}

	/**
	 * Set the export mode.
	 *
	 * @since $$next-version$$
	 *
	 * @param string $mode One of MODE_BY_COURSE or MODE_BY_FILE_TYPE.
	 */
	public function set_mode( $mode ) {
		if ( ! in_array( $mode, array( self::MODE_BY_COURSE, self::MODE_BY_FILE_TYPE ), true ) ) {
			$mode = self::MODE_BY_FILE_TYPE;
		}
		$this->set_state( self::MODE_STATE_KEY, $mode );
	}

	/**
	 * Get the export mode.
	 *
	 * Defaults to MODE_BY_FILE_TYPE for jobs that predate the mode field
	 * so existing-job behaviour matches the historical "literal types"
	 * shape (no cascade unless explicitly configured).
	 *
	 * @since $$next-version$$
	 *
	 * @return string
	 */
	public function get_mode() {
		$mode = $this->get_state( self::MODE_STATE_KEY );
		if ( ! in_array( $mode, array( self::MODE_BY_COURSE, self::MODE_BY_FILE_TYPE ), true ) ) {
			return self::MODE_BY_FILE_TYPE;
		}
		return $mode;
	}

	/**
	 * Set the per-type item selections to be exported.
	 *
	 * Each value is an array of post IDs. An empty array means "export all of that type".
	 *
	 * @since $$next-version$$
	 *
	 * @param array $selections Per-type ID arrays keyed by 'course', 'lesson', 'question'.
	 */
	public function set_selections( $selections ) {
		$normalized = array();
		foreach ( array( 'course', 'lesson', 'question' ) as $type ) {
			if ( isset( $selections[ $type ] ) && is_array( $selections[ $type ] ) ) {
				$normalized[ $type ] = array_values( array_unique( array_map( 'intval', $selections[ $type ] ) ) );
			} else {
				$normalized[ $type ] = array();
			}
		}

		$this->set_state( self::SELECTIONS_STATE_KEY, $normalized );
	}

	/**
	 * Get the per-type item selections to be exported.
	 *
	 * @since $$next-version$$
	 *
	 * @return array Per-type ID arrays. Empty array for a type means "export all".
	 */
	public function get_selections() {
		$selections = $this->get_state( self::SELECTIONS_STATE_KEY );

		if ( ! is_array( $selections ) ) {
			$selections = array();
		}

		return array_merge(
			array(
				'course'   => array(),
				'lesson'   => array(),
				'question' => array(),
			),
			$selections
		);
	}

	/**
	 * Resolve cascaded IDs from the configured selections and persist them.
	 *
	 * For each type, the resolved ID list is the union of the user's explicit
	 * selection and any IDs cascaded from a parent selection (courses cascade
	 * to their lessons and the questions used by those lessons; lessons cascade
	 * to the questions used by their quizzes). An empty resolved list for a
	 * type means "export all of that type".
	 *
	 * @since $$next-version$$
	 */
	public function resolve_export_ids() {
		$selections = $this->get_selections();
		$mode       = $this->get_mode();

		$course_ids   = $selections['course'];
		$lesson_ids   = $selections['lesson'];
		$question_ids = $selections['question'];

		$has_course_selection   = ! empty( $course_ids );
		$has_lesson_selection   = ! empty( $lesson_ids );
		$has_question_selection = ! empty( $question_ids );

		if ( self::MODE_BY_COURSE === $mode ) {
			// Cascade: courses → lessons.
			if ( $has_course_selection ) {
				$cascaded_lessons = $this->get_lessons_for_courses( $course_ids );
				if ( $has_lesson_selection ) {
					$lesson_ids = array_values( array_unique( array_merge( $lesson_ids, $cascaded_lessons ) ) );
				} else {
					$lesson_ids = $cascaded_lessons;
				}
			}

			// Cascade: lessons (explicit + cascaded) → questions.
			if ( $has_course_selection || $has_lesson_selection ) {
				$cascaded_questions = $this->get_questions_for_lessons( $lesson_ids );
				if ( $has_question_selection ) {
					$question_ids = array_values( array_unique( array_merge( $question_ids, $cascaded_questions ) ) );
				} else {
					$question_ids = $cascaded_questions;
				}
			}

			$resolved = array(
				'course'   => $has_course_selection ? $course_ids : array(),
				'lesson'   => ( $has_course_selection || $has_lesson_selection ) ? $lesson_ids : array(),
				'question' => ( $has_course_selection || $has_lesson_selection || $has_question_selection ) ? $question_ids : array(),
			);
		} else {
			// MODE_BY_FILE_TYPE: literal interpretation. Each CSV contains
			// exactly the items the user picked for it; no cross-type cascade.
			$resolved = array(
				'course'   => $has_course_selection ? $course_ids : array(),
				'lesson'   => $has_lesson_selection ? $lesson_ids : array(),
				'question' => $has_question_selection ? $question_ids : array(),
			);
		}

		$this->set_state( self::RESOLVED_IDS_STATE_KEY, $resolved );
	}

	/**
	 * Get the resolved post IDs for a content type.
	 *
	 * Empty array means "export all of that type".
	 *
	 * @since $$next-version$$
	 *
	 * @param string $type Content type ('course', 'lesson', 'question').
	 *
	 * @return int[]
	 */
	public function get_resolved_ids( $type ) {
		$resolved = $this->get_state( self::RESOLVED_IDS_STATE_KEY );

		if ( ! is_array( $resolved ) || empty( $resolved[ $type ] ) ) {
			return array();
		}

		return $resolved[ $type ];
	}

	/**
	 * Get all lesson IDs that belong to the given courses.
	 *
	 * @param int[] $course_ids Course IDs.
	 *
	 * @return int[]
	 */
	private function get_lessons_for_courses( $course_ids ) {
		if ( empty( $course_ids ) ) {
			return array();
		}

		$query = new WP_Query(
			array(
				'post_type'      => 'lesson',
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Resolved once at job start.
				'meta_query'     => array(
					array(
						'key'     => '_lesson_course',
						'value'   => array_map( 'intval', $course_ids ),
						'compare' => 'IN',
					),
				),
			)
		);

		return array_map( 'intval', $query->posts );
	}

	/**
	 * Get all question IDs used by the quizzes of the given lessons.
	 *
	 * @param int[] $lesson_ids Lesson IDs.
	 *
	 * @return int[]
	 */
	private function get_questions_for_lessons( $lesson_ids ) {
		if ( empty( $lesson_ids ) ) {
			return array();
		}

		$question_ids = array();
		foreach ( $lesson_ids as $lesson_id ) {
			$quiz_id = Sensei()->lesson->lesson_quizzes( $lesson_id );
			if ( ! $quiz_id ) {
				continue;
			}

			$ids = Sensei_Utils::lesson_quiz_questions(
				$quiz_id,
				array(
					'fields' => 'ids',
				)
			);

			foreach ( $ids as $id ) {
				$question_ids[] = (int) $id;
			}
		}

		return array_values( array_unique( $question_ids ) );
	}

	/**
	 * Type order in the logs.
	 *
	 * @return array
	 */
	public function get_log_type_order() {
		return [ 'course', 'lesson', 'question' ];
	}
}
