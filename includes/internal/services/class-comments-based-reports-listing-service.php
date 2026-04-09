<?php
/**
 * File containing the Comments_Based_Reports_Listing_Service class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Services;

use Sensei\Internal\Student_Progress\Quiz_Progress\Models\Quiz_Progress_Interface;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Comments_Based_Reports_Listing_Service.
 *
 * Comments-based implementation of the Reports_Listing_Service_Interface.
 *
 * @internal
 *
 * @since $$next-version$$
 */
class Comments_Based_Reports_Listing_Service implements Reports_Listing_Service_Interface {

	/**
	 * Get paginated users' progress on a specific lesson.
	 *
	 * @since $$next-version$$
	 *
	 * @param array $args Arguments for the query (see interface).
	 * @return array{ items: Reports_Item[], total_count: int }
	 */
	public function get_lesson_students( array $args ): array {
		return $this->query_activity( $args, 'grade' );
	}

	/**
	 * Get paginated users' progress on a specific course.
	 *
	 * @since $$next-version$$
	 *
	 * @param array $args Arguments for the query (see interface).
	 * @return array{ items: Reports_Item[], total_count: int }
	 */
	public function get_course_students( array $args ): array {
		return $this->query_activity( $args, 'percent' );
	}

	/**
	 * Get lesson progress for one user in a course.
	 *
	 * @since $$next-version$$
	 *
	 * @param array $args Arguments for the query (see interface).
	 * @return Reports_Item|null
	 */
	public function get_user_lesson_progress( array $args ): ?Reports_Item {
		$lesson_status = \Sensei_Utils::sensei_check_for_activity( $args, true );

		if ( empty( $lesson_status ) || ! $lesson_status instanceof \WP_Comment ) {
			return null;
		}

		return $this->item_from_comment( $lesson_status, 'grade' );
	}

	/**
	 * Get paginated course progress for a specific user.
	 *
	 * @since $$next-version$$
	 *
	 * @param array $args Arguments for the query (see interface).
	 * @return array{ items: Reports_Item[], total_count: int }
	 */
	public function get_user_courses( array $args ): array {
		return $this->query_activity( $args, 'percent' );
	}

	/**
	 * Get aggregate stats for a single lesson.
	 *
	 * @since $$next-version$$
	 *
	 * @param int $lesson_id Lesson post ID.
	 * @return array{ student_count: int, completion_count: int, average_grade: float|null }
	 */
	public function get_lesson_aggregate( int $lesson_id ): array {
		$student_count = \Sensei_Utils::sensei_check_for_activity(
			array(
				'post_id' => $lesson_id,
				'type'    => 'sensei_lesson_status',
				'status'  => 'any',
			)
		);

		$completion_count = \Sensei_Utils::sensei_check_for_activity(
			array(
				'post_id' => $lesson_id,
				'type'    => 'sensei_lesson_status',
				'status'  => Reports_Item::COMPLETED_STATUSES,
				'count'   => true,
			)
		);

		$average_grade = null;
		if ( false !== \Sensei_Lesson::lesson_quiz_has_questions( $lesson_id ) ) {
			$grade_args = array(
				'post_id'  => $lesson_id,
				'type'     => 'sensei_lesson_status',
				'status'   => array(
					Quiz_Progress_Interface::STATUS_GRADED,
					Quiz_Progress_Interface::STATUS_PASSED,
					Quiz_Progress_Interface::STATUS_FAILED,
				),
				'meta_key' => 'grade', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Required for grade aggregation.
			);
			add_filter( 'comments_clauses', array( 'Sensei_Utils', 'comment_total_sum_meta_value_filter' ) );
			$lesson_grades = \Sensei_Utils::sensei_check_for_activity( $grade_args, true );
			remove_filter( 'comments_clauses', array( 'Sensei_Utils', 'comment_total_sum_meta_value_filter' ) );

			if ( is_object( $lesson_grades ) ) {
				$grade_count   = ! empty( $lesson_grades->total ) ? $lesson_grades->total : 1;
				$grade_total   = ! empty( $lesson_grades->meta_sum ) ? (float) $lesson_grades->meta_sum : 0;
				$average_grade = \Sensei_Utils::quotient_as_absolute_rounded_number( $grade_total, $grade_count, 2 );
			}
		}

		return array(
			'student_count'    => (int) $student_count,
			'completion_count' => (int) $completion_count,
			'average_grade'    => $average_grade,
		);
	}

	/**
	 * Run a paginated activity query and map results to Reports_Item objects.
	 *
	 * @param array  $args      Activity args (see interface).
	 * @param string $meta_kind Numeric meta field to read: 'grade' or 'percent'.
	 * @return array{ items: Reports_Item[], total_count: int }
	 */
	private function query_activity( array $args, string $meta_kind ): array {
		$total_count = \Sensei_Utils::sensei_check_for_activity(
			array_merge(
				$args,
				array(
					'count'  => true,
					'offset' => 0,
					'number' => 0,
				)
			)
		);

		$offset = $args['offset'] ?? 0;
		$number = $args['number'] ?? 0;
		if ( $number > 0 && (int) $total_count > 0 && $offset >= (int) $total_count ) {
			$last_page      = max( 0, (int) ceil( $total_count / $number ) - 1 );
			$args['offset'] = $last_page * $number;
		}

		$statuses = \Sensei_Utils::sensei_check_for_activity( $args, true );
		if ( ! is_array( $statuses ) ) {
			$statuses = array( $statuses );
		}

		$items = array();
		foreach ( $statuses as $comment ) {
			if ( ! $comment instanceof \WP_Comment ) {
				continue;
			}
			$items[] = $this->item_from_comment( $comment, $meta_kind );
		}

		return array(
			'items'       => $items,
			'total_count' => (int) $total_count,
		);
	}

	/**
	 * Build a Reports_Item from a WP_Comment row.
	 *
	 * @param \WP_Comment $comment   The activity comment.
	 * @param string      $meta_kind Numeric meta field to read: 'grade' or 'percent'.
	 * @return Reports_Item
	 */
	private function item_from_comment( \WP_Comment $comment, string $meta_kind ): Reports_Item {
		$start_date = get_comment_meta( (int) $comment->comment_ID, 'start', true );
		$grade      = null;
		$percent    = null;

		if ( 'grade' === $meta_kind ) {
			$grade_raw = get_comment_meta( (int) $comment->comment_ID, 'grade', true );
			$grade     = '' !== $grade_raw ? (float) $grade_raw : null;
		} else {
			$percent_raw = get_comment_meta( (int) $comment->comment_ID, 'percent', true );
			$percent     = '' !== $percent_raw ? (float) $percent_raw : null;
		}

		return new Reports_Item(
			(int) $comment->comment_post_ID,
			(int) $comment->user_id,
			$comment->comment_approved,
			$start_date ? $start_date : null,
			$comment->comment_date ? $comment->comment_date : null,
			$grade,
			$percent
		);
	}
}
