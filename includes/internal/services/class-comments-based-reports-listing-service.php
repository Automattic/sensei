<?php
/**
 * File containing the Comments_Based_Reports_Listing_Service class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Services;

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
 * @since 4.26.0
 */
class Comments_Based_Reports_Listing_Service implements Reports_Listing_Service_Interface {

	/**
	 * Get paginated users' progress on a specific lesson.
	 *
	 * @since 4.26.0
	 *
	 * @param array $args Arguments for the query (see interface).
	 * @return array{ items: Reports_Item[], total_count: int }
	 */
	public function get_lesson_students( array $args ): array {
		return $this->query_activity( $args, 'lesson', 'grade' );
	}

	/**
	 * Get paginated users' progress on a specific course.
	 *
	 * @since 4.26.0
	 *
	 * @param array $args Arguments for the query (see interface).
	 * @return array{ items: Reports_Item[], total_count: int }
	 */
	public function get_course_students( array $args ): array {
		return $this->query_activity( $args, 'course', 'percent' );
	}

	/**
	 * Get lesson progress for one user in a course.
	 *
	 * @since 4.26.0
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
	 * @since 4.26.0
	 *
	 * @param array $args Arguments for the query (see interface).
	 * @return array{ items: Reports_Item[], total_count: int }
	 */
	public function get_user_courses( array $args ): array {
		return $this->query_activity( $args, 'course', 'percent' );
	}

	/**
	 * Run a paginated activity query and map results to Reports_Item objects.
	 *
	 * @param array  $args      Activity args documented by Reports_Listing_Service_Interface.
	 * @param string $type      Progress post type: 'lesson' or 'course'.
	 * @param string $meta_kind Numeric meta field to read: 'grade' or 'percent'.
	 * @return array{ items: Reports_Item[], total_count: int }
	 */
	private function query_activity( array $args, string $type, string $meta_kind ): array {
		global $wpdb;

		$requested_post_id = null;

		// WPML stores progress against the original post, while the report shows the requested translation.
		if ( isset( $args['post_id'] ) ) {
			$requested_post_id = (int) $args['post_id'];
			$post_id_map       = Utils::get_progress_post_id_map( array( $requested_post_id ), $type );
			$args['post_id']   = $post_id_map[ $requested_post_id ];
		}

		// Exclude guest and preview users from both the pagination total and the returned rows.
		$excluded_user_ids      = Utils::get_user_ids_by_login_prefixes( $wpdb, Utils::REPORTS_EXCLUDED_USER_LOGIN_PREFIXES );
		$args['author__not_in'] = array_values( array_unique( array_merge( (array) ( $args['author__not_in'] ?? array() ), $excluded_user_ids ) ) );

		// Count all eligible comments so pagination totals do not depend on the requested page.
		$count_args          = $args;
		$count_args['count'] = true;
		unset( $count_args['offset'], $count_args['number'] );

		$total_count = $this->query_report_comments( $count_args );

		// An out-of-range page request shows the last available page.
		$offset = $args['offset'] ?? 0;
		$number = $args['number'] ?? 0;
		if ( $number > 0 && (int) $total_count > 0 && $offset >= (int) $total_count ) {
			$last_page      = max( 0, (int) ceil( $total_count / $number ) - 1 );
			$args['offset'] = $last_page * $number;
		}

		/**
		 * Fetch the requested page using the same filters as the count.
		 *
		 * @var \WP_Comment[] $statuses
		 */
		$statuses = $this->query_report_comments( $args );

		$items = array();
		foreach ( $statuses as $comment ) {
			if ( ! $comment instanceof \WP_Comment ) {
				continue;
			}

			$items[] = $this->item_from_comment( $comment, $meta_kind, $requested_post_id );
		}

		return array(
			'items'       => $items,
			'total_count' => (int) $total_count,
		);
	}

	/**
	 * Query report comments without WPML language filtering.
	 *
	 * @param array $args Comment query arguments.
	 * @return int|array<array-key, int|\WP_Comment> Comment count or rows.
	 */
	private function query_report_comments( array $args ) {
		$disable_language_filter = static function (): bool {
			return false;
		};
		add_filter( 'wpml_is_comment_query_filtered', $disable_language_filter, 10, 0 );

		try {
			return get_comments( $args );
		} finally {
			remove_filter( 'wpml_is_comment_query_filtered', $disable_language_filter, 10 );
		}
	}

	/**
	 * Build a Reports_Item from a WP_Comment row.
	 *
	 * @param \WP_Comment $comment   The activity comment.
	 * @param string      $meta_kind         Numeric meta field to read: 'grade' or 'percent'.
	 * @param int|null    $requested_post_id Requested post ID, if progress is stored under another ID.
	 * @return Reports_Item
	 */
	private function item_from_comment( \WP_Comment $comment, string $meta_kind, ?int $requested_post_id = null ): Reports_Item {
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
			$requested_post_id ?? (int) $comment->comment_post_ID,
			(int) $comment->user_id,
			$comment->comment_approved,
			$start_date ? $start_date : null,
			$comment->comment_date ? $comment->comment_date : null,
			$grade,
			$percent
		);
	}
}
