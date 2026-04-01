<?php
/**
 * File containing the Comments_Based_Analysis_Listing_Service class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Comments_Based_Analysis_Listing_Service.
 *
 * Comments-based implementation of the Analysis_Listing_Service_Interface.
 *
 * @internal
 *
 * @since $$next-version$$
 */
class Comments_Based_Analysis_Listing_Service implements Analysis_Listing_Service_Interface {

	/**
	 * WordPress database object.
	 *
	 * @var \wpdb
	 */
	private \wpdb $wpdb;

	/**
	 * Constructor.
	 *
	 * @since $$next-version$$
	 *
	 * @param \wpdb $wpdb WordPress database object.
	 */
	public function __construct( \wpdb $wpdb ) {
		$this->wpdb = $wpdb;
	}

	/**
	 * Get paginated users' progress on a specific lesson.
	 *
	 * @since $$next-version$$
	 *
	 * @param array $args Arguments for the query (see interface).
	 * @return array{ items: Analysis_Item[], total_count: int }
	 */
	public function get_lesson_students( array $args ): array {
		$activity_args = array(
			'post_id' => $args['lesson_id'],
			'type'    => 'sensei_lesson_status',
			'number'  => $args['per_page'] ?? 0,
			'offset'  => $args['offset'] ?? 0,
			'orderby' => $args['orderby'] ?? '',
			'order'   => $args['order'] ?? 'ASC',
			'status'  => 'any',
		);

		if ( ! empty( $args['search'] ) ) {
			$user_args       = array(
				'search' => '*' . $args['search'] . '*',
				'fields' => 'ID',
			);
			$learners_search = new \WP_User_Query( $user_args );

			$activity_args['user_id'] = (array) $learners_search->get_results();
		}

		$total_count = \Sensei_Utils::sensei_check_for_activity(
			array_merge(
				$activity_args,
				array(
					'count'  => true,
					'offset' => 0,
					'number' => 0,
				)
			)
		);

		if ( (int) $total_count > 0 && $activity_args['offset'] >= (int) $total_count ) {
			$number                  = $activity_args['number'] ? $activity_args['number'] : 1;
			$new_paged               = floor( (int) $total_count / $number );
			$activity_args['offset'] = (int) ( $new_paged * $number );
		}

		$statuses = \Sensei_Utils::sensei_check_for_activity( $activity_args, true );
		if ( ! is_array( $statuses ) ) {
			$statuses = array( $statuses );
		}

		$items = array();
		foreach ( $statuses as $comment ) {
			if ( ! $comment instanceof \WP_Comment ) {
				continue;
			}

			$start_date = get_comment_meta( $comment->comment_ID, 'start', true );
			$grade_raw  = get_comment_meta( $comment->comment_ID, 'grade', true );

			$items[] = new Analysis_Item(
				(int) $comment->comment_post_ID,
				(int) $comment->user_id,
				$comment->comment_approved,
				$start_date ? $start_date : null,
				$comment->comment_date ? $comment->comment_date : null,
				'' !== $grade_raw ? (float) $grade_raw : null,
				null
			);
		}

		return array(
			'items'       => $items,
			'total_count' => (int) $total_count,
		);
	}

	/**
	 * Get paginated users' progress on a specific course.
	 *
	 * @since $$next-version$$
	 *
	 * @param array $args Arguments for the query (see interface).
	 * @return array{ items: Analysis_Item[], total_count: int }
	 */
	public function get_course_students( array $args ): array {
		$activity_args = array(
			'post_id' => $args['course_id'],
			'type'    => 'sensei_course_status',
			'number'  => $args['per_page'] ?? 0,
			'offset'  => $args['offset'] ?? 0,
			'orderby' => $args['orderby'] ?? '',
			'order'   => $args['order'] ?? 'ASC',
			'status'  => 'any',
		);

		if ( ! empty( $args['search'] ) ) {
			$user_args       = array(
				'search' => '*' . $args['search'] . '*',
				'fields' => 'ID',
			);
			$learners_search = new \WP_User_Query( $user_args );

			$activity_args['user_id'] = (array) $learners_search->get_results();
		}

		// Apply start date range filter.
		$meta_query_conditions = array();
		if ( ! empty( $args['start_date_from'] ) ) {
			$meta_query_conditions[] = array(
				'key'     => 'start',
				'value'   => $args['start_date_from'],
				'compare' => '>=',
				'type'    => 'DATE',
			);
		}
		if ( ! empty( $args['start_date_to'] ) ) {
			$meta_query_conditions[] = array(
				'key'     => 'start',
				'value'   => $args['start_date_to'],
				'compare' => '<=',
				'type'    => 'DATE',
			);
		}
		if ( ! empty( $meta_query_conditions ) ) {
			$activity_args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Required for date range filtering.
				'relation' => 'AND',
				$meta_query_conditions,
			);
		}

		$total_count = \Sensei_Utils::sensei_check_for_activity(
			array_merge(
				$activity_args,
				array(
					'count'  => true,
					'offset' => 0,
					'number' => 0,
				)
			)
		);

		if ( (int) $total_count > 0 && $activity_args['offset'] >= (int) $total_count ) {
			$number                  = $activity_args['number'] ? $activity_args['number'] : 1;
			$new_paged               = floor( (int) $total_count / $number );
			$activity_args['offset'] = (int) ( $new_paged * $number );
		}

		$statuses = \Sensei_Utils::sensei_check_for_activity( $activity_args, true );
		if ( ! is_array( $statuses ) ) {
			$statuses = array( $statuses );
		}

		$items = array();
		foreach ( $statuses as $comment ) {
			if ( ! $comment instanceof \WP_Comment ) {
				continue;
			}

			$start_date  = get_comment_meta( $comment->comment_ID, 'start', true );
			$percent_raw = get_comment_meta( $comment->comment_ID, 'percent', true );

			$items[] = new Analysis_Item(
				(int) $comment->comment_post_ID,
				(int) $comment->user_id,
				$comment->comment_approved,
				$start_date ? $start_date : null,
				$comment->comment_date ? $comment->comment_date : null,
				null,
				'' !== $percent_raw ? (float) $percent_raw : null
			);
		}

		return array(
			'items'       => $items,
			'total_count' => (int) $total_count,
		);
	}

	/**
	 * Get lesson progress for one user in a course.
	 *
	 * @since $$next-version$$
	 *
	 * @param int $course_id Course post ID.
	 * @param int $user_id   User ID.
	 * @return Analysis_Item[] One item per lesson.
	 */
	public function get_user_lesson_progress( int $course_id, int $user_id ): array {
		$lessons = Sensei()->course->course_lessons( $course_id, 'any', 'ids' );

		$items = array();
		foreach ( $lessons as $lesson_id ) {
			$lesson_args   = array(
				'post_id' => $lesson_id,
				'user_id' => $user_id,
				'type'    => 'sensei_lesson_status',
				'status'  => 'any',
			);
			$lesson_status = \Sensei_Utils::sensei_check_for_activity( $lesson_args, true );

			if ( empty( $lesson_status ) || ! $lesson_status instanceof \WP_Comment ) {
				$items[ $lesson_id ] = null;
				continue;
			}

			$start_date = get_comment_meta( $lesson_status->comment_ID, 'start', true );
			$grade_raw  = get_comment_meta( $lesson_status->comment_ID, 'grade', true );

			$items[ $lesson_id ] = new Analysis_Item(
				(int) $lesson_status->comment_post_ID,
				(int) $lesson_status->user_id,
				$lesson_status->comment_approved,
				$start_date ? $start_date : null,
				$lesson_status->comment_date ? $lesson_status->comment_date : null,
				'' !== $grade_raw ? (float) $grade_raw : null,
				null
			);
		}

		return $items;
	}

	/**
	 * Get paginated course progress for a specific user.
	 *
	 * @since $$next-version$$
	 *
	 * @param array $args Arguments for the query (see interface).
	 * @return array{ items: Analysis_Item[], total_count: int }
	 */
	public function get_user_courses( array $args ): array {
		$activity_args = array(
			'user_id' => $args['user_id'],
			'type'    => 'sensei_course_status',
			'number'  => $args['per_page'] ?? 0,
			'offset'  => $args['offset'] ?? 0,
			'orderby' => $args['orderby'] ?? '',
			'order'   => $args['order'] ?? 'ASC',
			'status'  => 'any',
		);

		if ( ! empty( $args['post_author'] ) ) {
			$activity_args['post_author'] = (int) $args['post_author'];
		}

		$total_count = \Sensei_Utils::sensei_check_for_activity(
			array_merge(
				$activity_args,
				array(
					'count'  => true,
					'offset' => 0,
					'number' => 0,
				)
			)
		);

		if ( (int) $total_count > 0 && $activity_args['offset'] >= (int) $total_count ) {
			$number                  = $activity_args['number'] ? $activity_args['number'] : 1;
			$new_paged               = floor( (int) $total_count / $number );
			$activity_args['offset'] = (int) ( $new_paged * $number );
		}

		$statuses = \Sensei_Utils::sensei_check_for_activity( $activity_args, true );
		if ( ! is_array( $statuses ) ) {
			$statuses = array( $statuses );
		}

		$items = array();
		foreach ( $statuses as $comment ) {
			if ( ! $comment instanceof \WP_Comment ) {
				continue;
			}

			$start_date  = get_comment_meta( $comment->comment_ID, 'start', true );
			$percent_raw = get_comment_meta( $comment->comment_ID, 'percent', true );

			$items[] = new Analysis_Item(
				(int) $comment->comment_post_ID,
				(int) $comment->user_id,
				$comment->comment_approved,
				$start_date ? $start_date : null,
				$comment->comment_date ? $comment->comment_date : null,
				null,
				'' !== $percent_raw ? (float) $percent_raw : null
			);
		}

		return array(
			'items'       => $items,
			'total_count' => (int) $total_count,
		);
	}

	/**
	 * Get per-lesson aggregate stats for a course overview.
	 *
	 * @since $$next-version$$
	 *
	 * @param int $course_id Course post ID.
	 * @return array[] Array of associative arrays with keys: lesson_id, student_count, completion_count, average_grade.
	 */
	public function get_lesson_aggregates( int $course_id ): array {
		$lessons = Sensei()->course->course_lessons( $course_id, 'any', 'ids' );

		$aggregates = array();
		foreach ( $lessons as $lesson_id ) {
			// Count all students.
			$student_count = \Sensei_Utils::sensei_check_for_activity(
				array(
					'post_id' => $lesson_id,
					'type'    => 'sensei_lesson_status',
					'status'  => 'any',
				)
			);

			// Count completions.
			$completion_count = \Sensei_Utils::sensei_check_for_activity(
				array(
					'post_id' => $lesson_id,
					'type'    => 'sensei_lesson_status',
					'status'  => array( 'complete', 'graded', 'passed', 'failed' ),
					'count'   => true,
				)
			);

			// Average grade.
			$average_grade = null;
			if ( false !== \Sensei_Lesson::lesson_quiz_has_questions( $lesson_id ) ) {
				$grade_args = array(
					'post_id'  => $lesson_id,
					'type'     => 'sensei_lesson_status',
					'status'   => array( 'graded', 'passed', 'failed' ),
					'meta_key' => 'grade', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Required for grade aggregation.
				);
				add_filter( 'comments_clauses', array( 'Sensei_Utils', 'comment_total_sum_meta_value_filter' ) );
				$lesson_grades = \Sensei_Utils::sensei_check_for_activity( $grade_args, true );
				remove_filter( 'comments_clauses', array( 'Sensei_Utils', 'comment_total_sum_meta_value_filter' ) );

				$grade_count   = ! empty( $lesson_grades->total ) ? $lesson_grades->total : 1;
				$grade_total   = ! empty( $lesson_grades->meta_sum ) ? (float) $lesson_grades->meta_sum : 0;
				$average_grade = \Sensei_Utils::quotient_as_absolute_rounded_number( $grade_total, $grade_count, 2 );
			}

			$aggregates[ $lesson_id ] = array(
				'lesson_id'        => $lesson_id,
				'student_count'    => (int) $student_count,
				'completion_count' => (int) $completion_count,
				'average_grade'    => $average_grade,
			);
		}

		return $aggregates;
	}
}
