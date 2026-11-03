<?php
/**
 * Real database fault injection for progress tests.
 *
 * @package sensei-tests
 */

use Sensei\Internal\Services\Progress_Storage_Settings;

/**
 * Run test operations with deliberately failing database inserts.
 *
 * Matching INSERT queries target a nonexistent table by default. Temporary
 * repositories and filters are restored afterward; completed writes are not undone.
 */
trait Sensei_Progress_Insert_Failure_Helpers {
	use Sensei_HPPS_Helpers;

	/**
	 * Select a storage mode and fail progress inserts while an operation runs.
	 *
	 * Repository configuration and query filters are restored even if the operation throws.
	 *
	 * @param string      $mode Storage mode: comments-only, comments, or tables.
	 * @param string      $entity Progress type, such as course or lesson.
	 * @param callable    $operation Operation receiving the removable query filter.
	 * @param string|null $failed_store Store to fail: comments or tables; null selects the read store.
	 * @return mixed Operation result. Exceptions propagate to the test.
	 */
	protected function with_failed_progress_insert( string $mode, string $entity, callable $operation, ?string $failed_store = null ) {
		return $this->with_progress_storage_mode(
			$mode,
			function () use ( $mode, $entity, $operation, $failed_store ) {
				$wpdb         = $GLOBALS['wpdb'];
				$failed_store = $failed_store ?? $mode;
				$table        = 'tables' === $failed_store ? $wpdb->prefix . 'sensei_lms_progress' : $wpdb->comments;
				$type         = 'tables' === $failed_store ? "'" . $entity . "'" : 'sensei_' . $entity . '_status';

				return $this->with_failed_insert( $table, $operation, null, $type );
			}
		);
	}

	/**
	 * Remove an existing progress comment just before saving and fail its replacement insert.
	 *
	 * Requires an existing comment for the given post and student. Removing it at
	 * sensei_log_activity_before exercises the save path after progress was read.
	 * Temporary hooks and query filters are removed even if the operation throws.
	 *
	 * @param string      $entity Course or lesson comment type.
	 * @param int         $post_id Course or lesson ID.
	 * @param int         $user_id Student ID.
	 * @param callable    $operation Save operation to exercise.
	 * @param string|null $status Optional status at which to remove the comment.
	 * @return mixed Operation result.
	 */
	protected function with_failed_progress_comment_insert( string $entity, int $post_id, int $user_id, callable $operation, ?string $status = null ) {
		$type           = 'sensei_' . $entity . '_status';
		$comment        = Sensei_Utils::sensei_check_for_activity(
			array(
				'post_id' => $post_id,
				'user_id' => $user_id,
				'type'    => $type,
			),
			true
		);
		$remove_comment = static function ( $args ) use ( $comment, $post_id, $user_id, $type, $status ) {
			if ( $type === $args['type'] && $post_id === $args['post_id'] && $user_id === $args['user_id']
				&& ( null === $status || $status === $args['status'] ) ) {
				wp_delete_comment( $comment->comment_ID, true );
			}
		};
		add_action( 'sensei_log_activity_before', $remove_comment );

		try {
			return $this->with_failed_insert( $GLOBALS['wpdb']->comments, $operation, null, $type );
		} finally {
			remove_action( 'sensei_log_activity_before', $remove_comment );
		}
	}

	/**
	 * Enable progress caching and fail matching inserts while an operation runs.
	 *
	 * Cache filters and settings are restored afterward, and the object cache is flushed.
	 *
	 * @param string      $table Physical table receiving inserts.
	 * @param callable    $operation Operation receiving the removable query filter.
	 * @param string|null $replacement Replacement SQL; null targets a nonexistent table.
	 * @return mixed Operation result.
	 */
	protected function with_failed_cached_insert( string $table, callable $operation, ?string $replacement = null ) {
		add_filter( 'sensei_hpps_cache_enabled', '__return_true' );
		Progress_Storage_Settings::reset_cache_enabled();

		try {
			return $this->with_failed_insert( $table, $operation, $replacement );
		} finally {
			remove_filter( 'sensei_hpps_cache_enabled', '__return_true' );
			Progress_Storage_Settings::reset_cache_enabled();
		}
	}

	/**
	 * Replace matching INSERT queries while an operation runs.
	 *
	 * By default, the replacement INSERT targets a nonexistent table so wpdb reports
	 * a real database failure. The callback receives the query filter and may remove
	 * it to retry. Query filters and database error reporting are restored, and the
	 * object cache is flushed, even if the operation throws.
	 *
	 * @param string      $table Physical table receiving inserts.
	 * @param callable    $operation Operation receiving the removable query filter.
	 * @param string|null $replacement Replacement SQL; null targets a nonexistent table.
	 * @param string|null $type Optional entity value to match in the insert.
	 * @return mixed Operation result.
	 */
	protected function with_failed_insert( string $table, callable $operation, ?string $replacement = null, ?string $type = null ) {
		global $wpdb;
		// Target a missing table by default to cause a real insert failure.
		$replacement     = $replacement ?? 'INSERT INTO sensei_missing_progress_table (id) VALUES (1)';
		$filter          = static function ( $query ) use ( $table, $replacement, $type ) {
			if ( preg_match( '/^INSERT INTO `?' . preg_quote( $table, '/' ) . '`?\s/i', $query )
				&& ( null === $type || false !== strpos( $query, $type ) ) ) {
				return $replacement;
			}
			return $query;
		};
		$suppress_errors = $wpdb->suppress_errors( true );
		add_filter( 'query', $filter );

		try {
			return $operation( $filter );
		} finally {
			remove_filter( 'query', $filter );
			$wpdb->suppress_errors( $suppress_errors );
			wp_cache_flush();
		}
	}
}
