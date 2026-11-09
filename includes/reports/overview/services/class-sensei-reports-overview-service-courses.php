<?php
/**
 * File containing the Sensei_Reports_Overview_Service_Courses class.
 *
 * @package sensei
 */

use Sensei\Internal\Services\Grading_Stats_Service_Interface;
use Sensei\Internal\Services\Progress_Aggregation_Service_Interface;
use Sensei\Internal\Services\Progress_Query_Service_Factory;
use Sensei\Internal\Services\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Courses overview service class.
 *
 * @since 4.4.1
 */
class Sensei_Reports_Overview_Service_Courses {
	/**
	 * Grading statistics service.
	 *
	 * @var Grading_Stats_Service_Interface|null
	 */
	private ?Grading_Stats_Service_Interface $grading_stats_service = null;

	/**
	 * Progress aggregation service.
	 *
	 * @var Progress_Aggregation_Service_Interface|null
	 */
	private ?Progress_Aggregation_Service_Interface $aggregation_service = null;

	/**
	 * Create a courses overview service with its dependencies.
	 *
	 * @internal
	 * @since 4.27.0
	 *
	 * @param Grading_Stats_Service_Interface        $grading_stats_service Grading statistics service.
	 * @param Progress_Aggregation_Service_Interface $aggregation_service   Progress aggregation service.
	 * @return self
	 */
	public static function create_with_dependencies( Grading_Stats_Service_Interface $grading_stats_service, Progress_Aggregation_Service_Interface $aggregation_service ): self {
		$instance                        = new self();
		$instance->grading_stats_service = $grading_stats_service;
		$instance->aggregation_service   = $aggregation_service;

		return $instance;
	}

	/**
	 * Get total average progress value for courses.
	 *
	 * @since  4.4.1
	 * @access public
	 *
	 * @param array $course_ids Courses ids.
	 * @return float total average progress value for all the courses.
	 */
	public function get_total_average_progress( array $course_ids ): float {
		if ( empty( $course_ids ) ) {
			return 0.0;
		}

		$course_average_progress = $this->get_average_progress_by_course( $course_ids );

		return ceil( array_sum( $course_average_progress ) / count( $course_ids ) );
	}

	/**
	 * Get the average lesson progress grouped by course.
	 *
	 * @since 4.27.0
	 *
	 * @param int[] $course_ids Course IDs.
	 * @return float[] Average progress keyed by course ID.
	 */
	public function get_average_progress_by_course( array $course_ids ): array {
		if ( empty( $course_ids ) ) {
			return array();
		}

		$lessons_by_course = $this->get_lessons_in_courses( $course_ids );

		// Limit completion counts to the lessons belonging to the courses in this report.
		$all_lesson_ids = array();
		foreach ( $lessons_by_course as $course_lessons ) {
			$all_lesson_ids = array_merge( $all_lesson_ids, $course_lessons );
		}
		$all_lesson_ids = array_unique( $all_lesson_ids );

		$progress_args             = array( 'exclude_user_login_prefixes' => Utils::REPORTS_EXCLUDED_USER_LOGIN_PREFIXES );
		$lessons_completions       = $this->get_lessons_completions( $all_lesson_ids, $progress_args );
		$student_count_per_courses = $this->get_students_count_in_courses( $course_ids, $progress_args );
		$course_average_progress   = array();

		foreach ( $course_ids as $course_id ) {
			if ( ! isset( $lessons_by_course[ $course_id ] ) || ! isset( $student_count_per_courses[ $course_id ] ) ) {
				continue;
			}
			// Get lessons in the course.
			$lessons = $lessons_by_course[ $course_id ];
			if ( empty( $lessons ) ) {
				continue;
			}

			// Get students count.
			$students_count = $student_count_per_courses[ $course_id ]->students_count;
			if ( ! $students_count ) {
				continue;
			}

			// Get all completed lessons for all the students.
			$completed_count = array_reduce(
				$lessons,
				function ( $carry, $lesson ) use ( $lessons_completions ) {
					if ( ! isset( $lessons_completions[ $lesson ] ) ) {
						return $carry;
					}
					$carry += $lessons_completions[ $lesson ]->completion_count;
					return $carry;
				},
				0
			);

			$course_average_progress[ $course_id ] = (float) ( $completed_count / ( $students_count * count( $lessons ) ) * 100 );
		}

		return $course_average_progress;
	}

	/**
	 * Get eligible completion counts keyed by requested course ID.
	 *
	 * @since 4.27.0
	 *
	 * @param int[] $course_ids Requested course IDs.
	 * @return array<int, int> Completion counts including zero for courses without completions.
	 */
	public function get_completion_counts_by_course( array $course_ids ): array {
		if ( empty( $course_ids ) ) {
			return array();
		}

		if ( has_filter( 'sensei_analysis_course_completions' ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- _deprecated_hook handles its own output.
			_deprecated_hook( 'sensei_analysis_course_completions', '4.27.0', '', __( 'This filter is no longer used. Course completion counts now use the progress aggregation service.', 'sensei-lms' ) );
		}

		$counts = $this->get_aggregation_service()->count_statuses_by_post(
			$course_ids,
			array( 'exclude_user_login_prefixes' => Utils::REPORTS_EXCLUDED_USER_LOGIN_PREFIXES )
		);
		$result = array();
		foreach ( $course_ids as $course_id ) {
			$result[ (int) $course_id ] = $counts[ (int) $course_id ]['complete'] ?? 0;
		}

		return $result;
	}

	/**
	 * Get the average grade of the courses.
	 *
	 * @since 4.4.1
	 * @access public
	 *
	 * @param array $course_ids Courses ids to filter by.
	 * @return double Average grade of all courses.
	 */
	public function get_courses_average_grade( array $course_ids ) {
		if ( empty( $course_ids ) ) {
			return 0;
		}

		$requested_courses = array();
		foreach ( Utils::get_progress_post_id_map( $course_ids, 'course' ) as $requested_id => $stored_id ) {
			// Count shared original progress once, retaining the first requested course ID.
			$requested_courses[ $stored_id ] = $requested_courses[ $stored_id ] ?? $requested_id;
		}
		$course_averages = $this->get_average_grades_by_course( array_values( $requested_courses ) );

		return $course_averages ? array_sum( $course_averages ) / count( $course_averages ) : 0.0;
	}

	/**
	 * Get unrounded course grade averages keyed by requested course ID.
	 *
	 * Courses without eligible grades are omitted; a zero grade is included.
	 *
	 * @since 4.27.0
	 *
	 * @param int[] $course_ids Requested course IDs.
	 * @return array<int, float> Average grades before display rounding.
	 */
	public function get_average_grades_by_course( array $course_ids ): array {
		$averages = array();
		foreach ( $this->get_grade_totals_by_course( $course_ids ) as $course_id => $totals ) {
			if ( $totals['count'] > 0 && $totals['sum'] >= 0 ) {
				$averages[ $course_id ] = $totals['sum'] / $totals['count'];
			}
		}

		return $averages;
	}

	/**
	 * Get eligible grade counts and sums keyed by requested course ID.
	 *
	 * Includes published and private lessons.
	 *
	 * @since 4.27.0
	 *
	 * @param int[] $course_ids Requested course IDs.
	 * @return array<int, array{count:int, sum:float}> Grade totals for each requested course.
	 */
	public function get_grade_totals_by_course( array $course_ids ): array {
		if ( empty( $course_ids ) ) {
			return array();
		}

		if ( has_filter( 'sensei_analysis_course_percentage' ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- _deprecated_hook handles its own output.
			_deprecated_hook( 'sensei_analysis_course_percentage', '4.27.0', '', __( 'This filter is no longer used. Course grades now use the grading statistics service.', 'sensei-lms' ) );
		}

		$course_ids        = array_map( 'intval', $course_ids );
		$totals            = array_fill_keys(
			$course_ids,
			array(
				'count' => 0,
				'sum'   => 0.0,
			)
		);
		$lessons_by_course = $this->get_lessons_in_courses( $course_ids );
		$lesson_ids        = array_unique( array_merge( ...array_values( $lessons_by_course ) ) );
		$lesson_id_map     = Utils::get_progress_post_id_map( $lesson_ids, 'lesson' );

		// Resolve translated lessons to the original IDs used to store grades.
		foreach ( $lessons_by_course as $course_id => $lessons ) {
			$stored_lessons = array();
			foreach ( $lessons as $lesson_id ) {
				$stored_lessons[] = $lesson_id_map[ $lesson_id ];
			}
			$lessons_by_course[ $course_id ] = array_unique( $stored_lessons );
		}

		$quiz_question_meta = $this->get_quiz_question_meta_by_lesson( array_unique( array_values( $lesson_id_map ) ) );

		// Courses without quiz questions display N/A, even when historical grades remain.
		foreach ( $lessons_by_course as $course_id => $lessons ) {
			$has_quiz_questions = false;
			foreach ( $lessons as $lesson_id ) {
				if ( ! empty( $quiz_question_meta[ $lesson_id ] ) ) {
					$has_quiz_questions = true;
					break;
				}
			}

			if ( ! $has_quiz_questions ) {
				unset( $lessons_by_course[ $course_id ] );
			}
		}

		// Free temporary arrays before loading grade totals for all lessons.
		unset( $lesson_ids, $lesson_id_map, $quiz_question_meta );

		if ( empty( $lessons_by_course ) ) {
			return $totals;
		}

		$lesson_totals = $this->get_grading_stats_service()->get_grade_totals_by_lesson(
			array_unique( array_merge( ...array_values( $lessons_by_course ) ) ),
			array( 'exclude_user_login_prefixes' => Utils::REPORTS_EXCLUDED_USER_LOGIN_PREFIXES )
		);

		foreach ( $lessons_by_course as $course_id => $lessons ) {
			foreach ( $lessons as $lesson_id ) {
				$totals[ $course_id ]['count'] += $lesson_totals[ $lesson_id ]['count'] ?? 0;
				$totals[ $course_id ]['sum']   += $lesson_totals[ $lesson_id ]['sum'] ?? 0.0;
			}
		}

		return $totals;
	}

	/**
	 * Get average days to completion by courses.
	 *
	 * @since 4.4.1
	 * @access public
	 *
	 * @param array $course_ids Courses ids to filter by.
	 * @return float Average of rounded per-course completion days.
	 */
	public function get_average_days_to_completion( array $course_ids ): float {
		$course_averages = $this->get_average_days_to_completion_by_course( $course_ids );

		return $course_averages ? array_sum( $course_averages ) / count( $course_averages ) : 0.0;
	}

	/**
	 * Get rounded completion-day averages grouped by requested course ID.
	 *
	 * @since 4.27.0
	 *
	 * @param int[] $course_ids Course IDs.
	 * @return array<int, float> Rounded completion days keyed by requested course ID.
	 */
	public function get_average_days_to_completion_by_course( array $course_ids ): array {
		if ( empty( $course_ids ) ) {
			return array();
		}

		return $this->get_aggregation_service()->get_average_days_to_completion_by_course(
			$course_ids,
			array( 'exclude_user_login_prefixes' => Utils::REPORTS_EXCLUDED_USER_LOGIN_PREFIXES )
		);
	}

	/**
	 * Get total of enrollments
	 *
	 * @since  4.15.1
	 * @param array $course_ids Courses ids to filter by.
	 *
	 * @return int total of enrollments
	 */
	public function get_total_enrollments( $course_ids ): int {
		if ( empty( $course_ids ) ) {
			return 0;
		}

		$total_grouped_by_course = $this->get_students_count_in_courses(
			$course_ids,
			array( 'exclude_user_login_prefixes' => Utils::REPORTS_EXCLUDED_USER_LOGIN_PREFIXES )
		);

		if ( empty( $total_grouped_by_course ) ) {
			return 0;
		}

		$to_total = function ( $acc, $current ) {
			return $acc + $current->students_count;
		};

		return array_reduce( $total_grouped_by_course, $to_total, 0 );
	}

	/**
	 * Get completions for the requested lessons.
	 *
	 * @since  4.4.1
	 *
	 * @param int[] $lesson_ids Lesson IDs.
	 * @param array $args       Optional query filters for get_lesson_completion_counts().
	 * @return array lessons completions.
	 */
	private function get_lessons_completions( array $lesson_ids, array $args = array() ): array {
		if ( empty( $lesson_ids ) ) {
			return array();
		}

		$counts = $this->get_aggregation_service()
			->get_lesson_completion_counts( $lesson_ids, $args );

		// Keep the object fields used by the existing progress calculation.
		$result = array();
		foreach ( $counts as $lesson_id => $completion_count ) {
			$result[ $lesson_id ] = (object) array(
				'lesson_id'        => $lesson_id,
				'completion_count' => $completion_count,
			);
		}

		return $result;
	}

	/**
	 * Get lessons grouped by courses.
	 *
	 * @since  4.4.1
	 *
	 * @param int[] $course_ids The list of course IDs.
	 * @return array<int, int[]> Lesson IDs keyed by requested course ID.
	 */
	private function get_lessons_in_courses( array $course_ids ): array {
		global $wpdb;
		// Look up lessons on the course resolved by the progress-ID filter so they match its stored progress.
		$course_id_map   = Utils::get_progress_post_id_map( $course_ids, 'course' );
		$course_ids      = array_values( $course_id_map );
		$report_statuses = Utils::get_reports_post_status_sql();

		$query = "SELECT pm.meta_value as course_id, pm.post_id as lesson_id
			FROM {$wpdb->postmeta} pm
			INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
			WHERE pm.meta_value IN ( " . implode( ',', $course_ids ) . " )
			AND pm.meta_key = '_lesson_course'
			AND p.post_type = 'lesson'
			AND p.post_status IN ( {$report_statuses} )";

		$results = $wpdb->get_results( $query ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- Safe direct SQL; course IDs are integers from the progress-ID map.

		$lessons_by_course = array();
		foreach ( $results as $result ) {
			$lessons_by_course[ (int) $result->course_id ][] = (int) $result->lesson_id;
		}

		return Utils::map_results_to_requested_post_ids( $lessons_by_course, $course_id_map );
	}

	/**
	 * Read whether each lesson has quiz questions, without loading unrelated metadata.
	 *
	 * @param int[] $lesson_ids Original lesson IDs.
	 * @return array<int, bool> Metadata values cast to booleans, keyed by lesson ID; missing metadata is omitted.
	 */
	private function get_quiz_question_meta_by_lesson( array $lesson_ids ): array {
		global $wpdb;

		if ( empty( $lesson_ids ) ) {
			return array();
		}

		$ids = implode( ',', array_map( 'intval', $lesson_ids ) );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- IDs are integers; quiz metadata is read once for this selection.
		$metadata = $wpdb->get_results( "SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE post_id IN ($ids) AND meta_key = '_quiz_has_questions' ORDER BY meta_id" );

		$quiz_question_meta = array();
		foreach ( $metadata as $meta ) {
			if ( ! array_key_exists( (int) $meta->post_id, $quiz_question_meta ) ) {
				$quiz_question_meta[ (int) $meta->post_id ] = (bool) maybe_unserialize( $meta->meta_value );
			}
		}

		return $quiz_question_meta;
	}

	/**
	 * Get students count by courses.
	 *
	 * @since  4.4.1
	 *
	 * @param int[] $course_ids The array of course IDs.
	 * @param array $args       Optional query filters for count_statuses_by_post().
	 * @return array students in courses.
	 */
	private function get_students_count_in_courses( array $course_ids, array $args = array() ): array {
		if ( empty( $course_ids ) ) {
			return array();
		}

		$by_post = $this->get_aggregation_service()
			->count_statuses_by_post( $course_ids, $args );

		// Enrollment totals count only started or completed courses, as before.
		$result = array();
		foreach ( $by_post as $course_id => $statuses ) {
			$result[ $course_id ] = (object) array(
				'course_id'      => $course_id,
				'students_count' => ( $statuses['in-progress'] ?? 0 ) + ( $statuses['complete'] ?? 0 ),
			);
		}

		return $result;
	}

	/**
	 * Get the injected grading statistics service or create and retain the default.
	 *
	 * @return Grading_Stats_Service_Interface
	 */
	private function get_grading_stats_service(): Grading_Stats_Service_Interface {
		if ( null === $this->grading_stats_service ) {
			$this->grading_stats_service = ( new Progress_Query_Service_Factory( Sensei()->progress_storage_configuration ) )->create_grading_stats_service();
		}

		return $this->grading_stats_service;
	}

	/**
	 * Keep older constructor calls working when no aggregation service was supplied.
	 *
	 * @return Progress_Aggregation_Service_Interface
	 */
	private function get_aggregation_service(): Progress_Aggregation_Service_Interface {
		if ( null === $this->aggregation_service ) {
			$this->aggregation_service = ( new Progress_Query_Service_Factory( Sensei()->progress_storage_configuration ) )->create_aggregation_service();
		}

		return $this->aggregation_service;
	}
}
