<?php
/**
 * File containing the Comments_Based_Grading_Listing_Service class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Comments_Based_Grading_Listing_Service.
 *
 * Comments-based implementation of the Grading_Listing_Service_Interface.
 *
 * @internal
 *
 * @since $$next-version$$
 */
class Comments_Based_Grading_Listing_Service implements Grading_Listing_Service_Interface {

	/**
	 * Get lesson progress items for the grading listing.
	 *
	 * @since $$next-version$$
	 *
	 * @param array $args Arguments for the query (see interface).
	 * @return array{ items: Grading_Item[], total_count: int }
	 */
	public function get_lesson_progress_items( array $args ): array {
		// Add a SQL-level filter to exclude lessons where a quiz exists but
		// the student has no quiz answers — there is nothing to grade.
		// This covers both 'complete' (never submitted) and orphaned
		// 'passed'/'graded'/'failed' records with no answer data.
		// In-progress students are kept since they haven't submitted yet.
		$exclusion_filter = function ( array $clauses ): array {
			global $wpdb;
			$clauses['where'] .= " AND NOT ( {$wpdb->comments}.comment_approved != 'in-progress'"
				. " AND EXISTS ( SELECT 1 FROM {$wpdb->postmeta} pm"
				. " WHERE pm.post_id = {$wpdb->comments}.comment_post_ID"
				. " AND pm.meta_key = '_lesson_quiz' AND pm.meta_value > 0 )"
				. " AND NOT EXISTS ( SELECT 1 FROM {$wpdb->commentmeta} cm"
				. " WHERE cm.comment_id = {$wpdb->comments}.comment_ID"
				. " AND cm.meta_key = 'quiz_answers' ) )";
			return $clauses;
		};
		add_filter( 'comments_clauses', $exclusion_filter );

		// WP_Comment_Query doesn't support SQL_CALC_FOUND_ROWS, so run
		// a separate count query first with no limit/offset.
		$total_count = \Sensei_Utils::sensei_check_for_activity(
			array_merge(
				$args,
				[
					'count'  => true,
					'offset' => 0,
					'number' => 0,
				]
			)
		);

		// If the requested offset is beyond the total (e.g. in case a search
		// threw off the pagination), snap back to the last valid page.
		$offset = $args['offset'] ?? 0;
		$number = $args['number'] ?? 10;
		if ( $number > 0 && $total_count < $offset ) {
			$new_paged      = floor( $total_count / $number );
			$args['offset'] = $new_paged * $number;
		}

		$statuses = \Sensei_Utils::sensei_check_for_activity( $args, true );

		remove_filter( 'comments_clauses', $exclusion_filter );

		// sensei_check_for_activity returns a single object when there is
		// exactly one result — normalize to an array.
		if ( ! is_array( $statuses ) ) {
			$statuses = [ $statuses ];
		}

		$items = [];
		foreach ( $statuses as $comment ) {
			// sensei_check_for_activity can return false when no results are
			// found; skip anything that isn't a real comment.
			if ( ! $comment instanceof \WP_Comment ) {
				continue;
			}

			$grade_value = get_comment_meta( $comment->comment_ID, 'grade', true );

			$items[] = new Grading_Item(
				$comment->comment_approved,
				(int) $comment->user_id,
				(int) $comment->comment_post_ID,
				$comment->comment_date,
				'' !== $grade_value ? (float) $grade_value : null
			);
		}

		return [
			'items'       => $items,
			'total_count' => (int) $total_count,
		];
	}
}
