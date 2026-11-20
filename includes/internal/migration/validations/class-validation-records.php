<?php
/**
 * File containing the Validation_Records class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Migration\Validations;

use Generator;
use RuntimeException;
use Sensei\Internal\Services\Progress_Storage_Settings;
use WP_Comment;
use WP_Comment_Query;
use WP_Query;

/**
 * Enumerates persisted records by primary key in bounded batches.
 *
 * @internal
 * @since $$next-version$$
 */
class Validation_Records {
	private const BATCH_SIZE = 200;

	/**
	 * Read stored values during validation, restoring normal caching afterward.
	 *
	 * @since $$next-version$$
	 *
	 * @param callable $validation The validation to run.
	 * @return void
	 */
	public static function with_fresh_reads( callable $validation ): void {
		$disable_cache     = static function (): bool {
			return false;
		};
		$uncached_posts    = static function ( WP_Query $query ): void {
			$query->set( 'cache_results', false );
		};
		$uncached_comments = static function ( WP_Comment_Query $query ): void {
			$query->query_vars['cache_results'] = false;
		};
		$fresh_comment     = static function ( WP_Comment $comment ): ?WP_Comment {
			global $wpdb;

			$rows = self::query( 'SELECT * FROM %i WHERE comment_ID = %d', array( $wpdb->comments, (int) $comment->comment_ID ), 'comment' );
			/**
			 * WordPress accepts raw comment rows as constructor input.
			 *
			 * @psalm-suppress ArgumentTypeCoercion
			 */
			return $rows ? new WP_Comment( $rows[0] ) : null;
		};
		$fresh_metadata    = array( self::class, 'filter_metadata' );
		$refresh_quiz_post = static function ( int $quiz_id ): int {
			// The comments repositories resolve the lesson through the quiz post's parent.
			wp_cache_delete( $quiz_id, 'posts' );
			return $quiz_id;
		};

		// Keep the repositories' normalization rules while bypassing cached values and query results.
		add_filter( 'sensei_hpps_cache_enabled', $disable_cache, PHP_INT_MAX );
		add_action( 'pre_get_posts', $uncached_posts, PHP_INT_MAX );
		add_action( 'pre_get_comments', $uncached_comments, PHP_INT_MAX );
		add_filter( 'get_comment', $fresh_comment, PHP_INT_MAX );
		add_filter( 'get_comment_metadata', $fresh_metadata, PHP_INT_MAX, 5 );
		add_filter( 'get_post_metadata', $fresh_metadata, PHP_INT_MAX, 5 );
		add_filter( 'sensei_quiz_progress_get_quiz_id', $refresh_quiz_post, PHP_INT_MAX );
		add_filter( 'sensei_quiz_submission_get_quiz_id', $refresh_quiz_post, PHP_INT_MAX );
		Progress_Storage_Settings::reset_cache_enabled();

		try {
			$validation();
		} finally {
			remove_filter( 'sensei_hpps_cache_enabled', $disable_cache, PHP_INT_MAX );
			remove_action( 'pre_get_posts', $uncached_posts, PHP_INT_MAX );
			remove_action( 'pre_get_comments', $uncached_comments, PHP_INT_MAX );
			remove_filter( 'get_comment', $fresh_comment, PHP_INT_MAX );
			remove_filter( 'get_comment_metadata', $fresh_metadata, PHP_INT_MAX );
			remove_filter( 'get_post_metadata', $fresh_metadata, PHP_INT_MAX );
			remove_filter( 'sensei_quiz_progress_get_quiz_id', $refresh_quiz_post, PHP_INT_MAX );
			remove_filter( 'sensei_quiz_submission_get_quiz_id', $refresh_quiz_post, PHP_INT_MAX );
			Progress_Storage_Settings::reset_cache_enabled();
		}
	}

	/**
	 * Get the comment records of a given activity type.
	 *
	 * @since $$next-version$$
	 *
	 * @param string $comment_type The Sensei activity comment type.
	 * @return Generator<int, object>
	 */
	public static function get_comments( string $comment_type ): Generator {
		global $wpdb;

		return self::read(
			'SELECT comment_ID AS id, comment_post_ID AS post_id, user_id
			FROM %i
			WHERE comment_type = %s AND comment_ID > %d
			ORDER BY comment_ID
			LIMIT %d',
			array( $wpdb->comments, $comment_type ),
			$comment_type . ' comments'
		);
	}

	/**
	 * Get the HPPS progress records of a given type.
	 *
	 * @since $$next-version$$
	 *
	 * @param string $type The progress type.
	 * @return Generator<int, object>
	 */
	public static function get_progress_rows( string $type ): Generator {
		global $wpdb;

		return self::read(
			'SELECT id, post_id, user_id
			FROM %i
			WHERE type = %s AND id > %d
			ORDER BY id
			LIMIT %d',
			array( $wpdb->prefix . 'sensei_lms_progress', $type ),
			$type . ' progress table'
		);
	}

	/**
	 * Get the HPPS quiz submission records.
	 *
	 * @since $$next-version$$
	 *
	 * @return Generator<int, object>
	 */
	public static function get_submission_rows(): Generator {
		global $wpdb;

		return self::read(
			'SELECT id, quiz_id AS post_id, user_id, final_grade, created_at, updated_at
			FROM %i
			WHERE id > %d
			ORDER BY id
			LIMIT %d',
			array( $wpdb->prefix . 'sensei_lms_quiz_submissions' ),
			'quiz submission table'
		);
	}

	/**
	 * Get the HPPS answer rows whose submission no longer exists.
	 *
	 * @since $$next-version$$
	 *
	 * @return Generator<int, object>
	 */
	public static function get_answers_without_submissions(): Generator {
		global $wpdb;

		return self::read(
			'SELECT answers.id, answers.submission_id, answers.question_id
			FROM %i AS answers
			LEFT JOIN %i AS submissions ON submissions.id = answers.submission_id
			WHERE submissions.id IS NULL AND answers.id > %d
			ORDER BY answers.id
			LIMIT %d',
			array(
				$wpdb->prefix . 'sensei_lms_quiz_answers',
				$wpdb->prefix . 'sensei_lms_quiz_submissions',
			),
			'quiz answers table'
		);
	}

	/**
	 * Get the HPPS grade rows whose answer no longer exists.
	 *
	 * @since $$next-version$$
	 *
	 * @return Generator<int, object>
	 */
	public static function get_grades_without_answers(): Generator {
		global $wpdb;

		return self::read(
			'SELECT grades.id, grades.answer_id, grades.question_id
			FROM %i AS grades
			LEFT JOIN %i AS answers ON answers.id = grades.answer_id
			WHERE answers.id IS NULL AND grades.id > %d
			ORDER BY grades.id
			LIMIT %d',
			array(
				$wpdb->prefix . 'sensei_lms_quiz_grades',
				$wpdb->prefix . 'sensei_lms_quiz_answers',
			),
			'quiz grades table'
		);
	}

	/**
	 * Read metadata without the WordPress object cache.
	 *
	 * @internal
	 * @since $$next-version$$
	 *
	 * @param mixed  $value     The value supplied by earlier metadata filters.
	 * @param int    $object_id The object ID.
	 * @param string $key       The metadata key.
	 * @param bool   $single    Whether the caller requested one value.
	 * @param string $type      The metadata type, post or comment.
	 * @return mixed The value expected by WordPress's metadata filter.
	 */
	public static function filter_metadata( $value, int $object_id, string $key, bool $single, string $type ) {
		global $wpdb;

		$table = 'post' === $type ? $wpdb->postmeta : $wpdb->commentmeta;
		$args  = array( $table, $type . '_id', $object_id );
		$sql   = 'SELECT meta_key, meta_value FROM %i WHERE %i = %d';
		if ( '' !== $key ) {
			$sql   .= ' AND meta_key = %s';
			$args[] = $key;
		}
		$rows = self::query( $sql . ' ORDER BY meta_id', $args, $type . ' metadata' );

		if ( '' === $key ) {
			$metadata = array();
			foreach ( $rows as $row ) {
				$metadata[ $row->meta_key ][] = $row->meta_value;
			}
			return $single ? array( $metadata ) : $metadata;
		}

		$values = array();
		foreach ( $rows as $row ) {
			$values[] = maybe_unserialize( $row->meta_value );
		}

		// WordPress unwraps the first array entry for a single-value metadata read.
		return $values ? $values : ( $single ? '' : array() );
	}

	/**
	 * Read stored records and report database read failures.
	 *
	 * @param string $query  The prepared statement template.
	 * @param array  $args   Placeholder values.
	 * @param string $source The storage source being read.
	 * @return object[] Stored records.
	 * @throws RuntimeException If the records cannot be read.
	 */
	private static function query( string $query, array $args, string $source ): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared -- Validation must read stored values rather than caches.
		$rows = $wpdb->get_results( $wpdb->prepare( $query, $args ) );
		if ( $wpdb->last_error || ! is_array( $rows ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- The source is a fixed identifier.
			throw new RuntimeException( 'Could not read ' . $source . ' during validation.' );
		}
		return $rows;
	}

	/**
	 * Read a query whose last two arguments are the primary key cursor and limit.
	 *
	 * @param string $query   The prepared statement template.
	 * @param array  $args    The arguments preceding the cursor and limit.
	 * @param string $source  The storage source being scanned.
	 * @return Generator<int, object>
	 * @throws RuntimeException If a batch cannot be read.
	 */
	private static function read( string $query, array $args, string $source ): Generator {
		$last_id = 0;

		do {
			$rows = self::query( $query, array_merge( $args, array( $last_id, self::BATCH_SIZE ) ), $source );

			$row_count = count( $rows );
			foreach ( $rows as $row ) {
				$last_id = (int) $row->id;
				yield $row;
			}
		} while ( self::BATCH_SIZE === $row_count );
	}
}
