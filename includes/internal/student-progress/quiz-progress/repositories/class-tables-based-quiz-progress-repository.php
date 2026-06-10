<?php
/**
 * File containing the class \Sensei\Student_Progress\Quiz_Progress\Repositories\Tables_Based_Quiz_Progress_Repository.
 *
 * @package sensei
 */

namespace Sensei\Internal\Student_Progress\Quiz_Progress\Repositories;

use DateTimeImmutable;
use DateTimeZone;
use Sensei\Internal\Cache_Prefix;
use Sensei\Internal\Services\Progress_Storage_Settings;
use Sensei\Internal\Student_Progress\Quiz_Progress\Models\Quiz_Progress_Interface;
use Sensei\Internal\Student_Progress\Quiz_Progress\Models\Tables_Based_Quiz_Progress;
use wpdb;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Tables_Based_Quiz_Progress_Repository
 *
 * @internal
 *
 * @since 4.16.1
 */
class Tables_Based_Quiz_Progress_Repository implements Quiz_Progress_Repository_Interface {
	use Cache_Prefix;

	/**
	 * Cache group for quiz progress.
	 *
	 * @since 4.26.0
	 *
	 * @var string
	 */
	private const CACHE_GROUP = 'sensei_quiz_progress';

	/**
	 * WordPress database object.
	 *
	 * @var wpdb
	 */
	private $wpdb;

	/**
	 * Tables_Based_Quiz_Progress_Repository constructor.
	 *
	 * @internal
	 *
	 * @param wpdb $wpdb WordPress database object.
	 */
	public function __construct( wpdb $wpdb ) {
		$this->wpdb = $wpdb;
	}

	/**
	 * Create a new quiz progress.
	 *
	 * @internal
	 *
	 * @param int $quiz_id Quiz identifier.
	 * @param int $user_id User identifier.
	 * @return Quiz_Progress_Interface
	 */
	public function create( int $quiz_id, int $user_id ): Quiz_Progress_Interface {
		/**
		 * Filter quiz id for quiz progress creation.
		 *
		 * @hook sensei_quiz_progress_create_quiz_id
		 *
		 * @since 4.23.1
		 *
		 * @param {int} $quiz_id Quiz ID.
		 * @return {int} Filtered quiz ID.
		 */
		$quiz_id = (int) apply_filters( 'sensei_quiz_progress_create_quiz_id', $quiz_id );

		$current_datetime = new DateTimeImmutable( 'now', new DateTimeZone( 'UTC' ) );
		$date_format      = 'Y-m-d H:i:s';
		$this->wpdb->insert(
			$this->wpdb->prefix . 'sensei_lms_progress',
			[
				'post_id'        => $quiz_id,
				'user_id'        => $user_id,
				'parent_post_id' => null,
				'type'           => 'quiz',
				'status'         => Quiz_Progress_Interface::STATUS_IN_PROGRESS,
				'started_at'     => $current_datetime->format( $date_format ),
				'completed_at'   => null,
				'created_at'     => $current_datetime->format( $date_format ),
				'updated_at'     => $current_datetime->format( $date_format ),
			],
			[
				'%d',
				'%d',
				null,
				'%s',
				'%s',
				'%s',
				null,
				'%s',
				'%s',
			]
		);
		$id = (int) $this->wpdb->insert_id;

		$progress = new Tables_Based_Quiz_Progress(
			$id,
			$quiz_id,
			$user_id,
			Quiz_Progress_Interface::STATUS_IN_PROGRESS,
			$current_datetime,
			null,
			$current_datetime,
			$current_datetime
		);

		if ( $id && Progress_Storage_Settings::is_cache_enabled() ) {
			wp_cache_set( self::get_prefixed_key( $this->get_cache_key( $quiz_id, $user_id ), self::CACHE_GROUP ), $progress, self::CACHE_GROUP );
		}

		return $progress;
	}

	/**
	 * Find a quiz progress by quiz and user identifiers.
	 *
	 * @internal
	 *
	 * @param int $quiz_id Quiz identifier.
	 * @param int $user_id User identifier.
	 * @return Quiz_Progress_Interface|null
	 */
	public function get( int $quiz_id, int $user_id ): ?Quiz_Progress_Interface {
		if ( ! $user_id ) {
			return null;
		}

		/**
		 * Filter quiz id for quiz progress retrieval.
		 *
		 * @hook sensei_quiz_progress_get_quiz_id
		 *
		 * @since 4.23.1
		 *
		 * @param {int} $quiz_id Quiz ID.
		 * @return {int} Filtered quiz ID.
		 */
		$quiz_id = (int) apply_filters( 'sensei_quiz_progress_get_quiz_id', $quiz_id );

		$cache_key = $this->get_cache_key( $quiz_id, $user_id );

		if ( Progress_Storage_Settings::is_cache_enabled() ) {
			$cached = wp_cache_get( self::get_prefixed_key( $cache_key, self::CACHE_GROUP ), self::CACHE_GROUP );
			if ( false !== $cached ) {
				return self::$cache_not_found === $cached ? null : $cached;
			}
		}

		$table_name = $this->wpdb->prefix . 'sensei_lms_progress';
		$query      = $this->wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			'SELECT * FROM ' . $table_name . ' WHERE post_id = %d AND user_id = %d AND type = %s',
			$quiz_id,
			$user_id,
			'quiz'
		);

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$row = $this->wpdb->get_row( $query );
		if ( ! $row ) {
			if ( Progress_Storage_Settings::is_cache_enabled() ) {
				wp_cache_set( self::get_prefixed_key( $cache_key, self::CACHE_GROUP ), self::$cache_not_found, self::CACHE_GROUP );
			}

			return null;
		}

		$timezone = new DateTimeZone( 'UTC' );

		$progress = new Tables_Based_Quiz_Progress(
			(int) $row->id,
			(int) $row->post_id,
			(int) $row->user_id,
			$row->status,
			new DateTimeImmutable( $row->started_at, $timezone ),
			$row->completed_at ? new DateTimeImmutable( $row->completed_at, $timezone ) : null,
			new DateTimeImmutable( $row->created_at, $timezone ),
			new DateTimeImmutable( $row->updated_at, $timezone )
		);

		if ( Progress_Storage_Settings::is_cache_enabled() ) {
			wp_cache_set( self::get_prefixed_key( $cache_key, self::CACHE_GROUP ), $progress, self::CACHE_GROUP );
		}

		return $progress;
	}

	/**
	 * Check if a quiz progress exists.
	 *
	 * @internal
	 *
	 * @param int $quiz_id Quiz identifier.
	 * @param int $user_id User identifier.
	 * @return bool
	 */
	public function has( int $quiz_id, int $user_id ): bool {
		if ( ! $user_id ) {
			return false;
		}

		/**
		 * Filter the quiz ID for a quiz progress we want to check.
		 *
		 * @hook sensei_quiz_progress_has_quiz_id
		 *
		 * @since 4.23.1
		 *
		 * @param {int} $quiz_id Quiz ID.
		 * @return {int} Filtered quiz ID.
		 */
		$quiz_id = (int) apply_filters( 'sensei_quiz_progress_has_quiz_id', $quiz_id );

		$table_name = $this->wpdb->prefix . 'sensei_lms_progress';
		$query      = $this->wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is safe.
			"SELECT COUNT(*) FROM {$table_name} WHERE post_id = %d AND user_id = %d AND type = %s",
			$quiz_id,
			$user_id,
			'quiz'
		);

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return (int) $this->wpdb->get_var( $query ) > 0;
	}

	/**
	 * Save the quiz progress.
	 *
	 * @internal
	 *
	 * @param Quiz_Progress_Interface $quiz_progress Quiz progress.
	 */
	public function save( Quiz_Progress_Interface $quiz_progress ): void {
		$this->assert_tables_based_quiz_progress( $quiz_progress );

		$updated_at = new DateTimeImmutable( 'now', new DateTimeZone( 'UTC' ) );
		$quiz_progress->set_updated_at( $updated_at );

		$date_format = 'Y-m-d H:i:s';
		$this->wpdb->update(
			$this->wpdb->prefix . 'sensei_lms_progress',
			[
				'status'       => $quiz_progress->get_status(),
				// Table columns are stored in UTC.
				'started_at'   => $quiz_progress->get_started_at() ? gmdate( $date_format, $quiz_progress->get_started_at()->getTimestamp() ) : null,
				'completed_at' => $quiz_progress->get_completed_at() ? gmdate( $date_format, $quiz_progress->get_completed_at()->getTimestamp() ) : null,
				'updated_at'   => $quiz_progress->get_updated_at()->format( $date_format ),
			],
			[
				'id' => $quiz_progress->get_id(),
			],
			[
				'%s',
				$quiz_progress->get_started_at() ? '%s' : null,
				$quiz_progress->get_completed_at() ? '%s' : null,
				'%s',
			],
			[
				'%d',
			]
		);

		if ( Progress_Storage_Settings::is_cache_enabled() ) {
			wp_cache_delete( self::get_prefixed_key( $this->get_cache_key( $quiz_progress->get_quiz_id(), $quiz_progress->get_user_id() ), self::CACHE_GROUP ), self::CACHE_GROUP );
		}
	}

	/**
	 * Delete a quiz progress.
	 *
	 * @internal
	 *
	 * @param Quiz_Progress_Interface $quiz_progress Quiz progress.
	 */
	public function delete( Quiz_Progress_Interface $quiz_progress ): void {
		$this->wpdb->delete(
			$this->wpdb->prefix . 'sensei_lms_progress',
			[
				'post_id' => $quiz_progress->get_quiz_id(),
				'user_id' => $quiz_progress->get_user_id(),
				'type'    => 'quiz',
			],
			[
				'%d',
				'%d',
				'%s',
			]
		);

		if ( Progress_Storage_Settings::is_cache_enabled() ) {
			wp_cache_delete( self::get_prefixed_key( $this->get_cache_key( $quiz_progress->get_quiz_id(), $quiz_progress->get_user_id() ), self::CACHE_GROUP ), self::CACHE_GROUP );
		}
	}

	/**
	 * Delete all quiz progress for a quiz.
	 *
	 * @internal
	 *
	 * @param int $quiz_id Quiz identifier.
	 */
	public function delete_for_quiz( int $quiz_id ): void {
		/**
		 * Filter quiz id for quiz progress deletion.
		 *
		 * @hook sensei_quiz_progress_delete_for_quiz_quiz_id
		 *
		 * @since 4.23.1
		 *
		 * @param {int} $quiz_id Quiz ID.
		 * @return {int} Filtered quiz ID.
		 */
		$quiz_id = (int) apply_filters( 'sensei_quiz_progress_delete_for_quiz_quiz_id', $quiz_id );

		$this->wpdb->delete(
			$this->wpdb->prefix . 'sensei_lms_progress',
			[
				'post_id' => $quiz_id,
				'type'    => 'quiz',
			],
			[
				'%d',
				'%s',
			]
		);

		if ( Progress_Storage_Settings::is_cache_enabled() ) {
			self::invalidate_cache_group( self::CACHE_GROUP );
		}
	}

	/**
	 * Delete all quiz progress for a user.
	 *
	 * @internal
	 *
	 * @param int $user_id User identifier.
	 */
	public function delete_for_user( int $user_id ): void {
		$this->wpdb->delete(
			$this->wpdb->prefix . 'sensei_lms_progress',
			[
				'user_id' => $user_id,
				'type'    => 'quiz',
			],
			[
				'%d',
				'%s',
			]
		);

		if ( Progress_Storage_Settings::is_cache_enabled() ) {
			self::invalidate_cache_group( self::CACHE_GROUP );
		}
	}

	/**
	 * Assert that the quiz progress is a Tables_Based_Quiz_Progress.
	 *
	 * @param Quiz_Progress_Interface $quiz_progress Quiz progress.
	 * @throws \InvalidArgumentException If the quiz progress is not a Tables_Based_Quiz_Progress.
	 */
	private function assert_tables_based_quiz_progress( Quiz_Progress_Interface $quiz_progress ): void {
		if ( ! $quiz_progress instanceof Tables_Based_Quiz_Progress ) {
			$actual_type = get_class( $quiz_progress );
			throw new \InvalidArgumentException( esc_html( "Expected Tables_Based_Quiz_Progress, got {$actual_type}." ) );
		}
	}

	/**
	 * Find quiz progress.
	 *
	 * @internal
	 *
	 * @param array $args The arguments.
	 * @return Quiz_Progress_Interface[]
	 */
	public function find( array $args ): array {
		$quiz_id = $args['quiz_id'] ?? null;
		$user_id = $args['user_id'] ?? null;
		$status  = $args['status'] ?? null;
		$limit   = $args['number'] ?? 100;
		$offset  = $args['offset'] ?? 0;

		$where_clause = array( 'type = %s' );
		$query_params = array( 'quiz' );
		if ( ! empty( $quiz_id ) ) {
			$quiz_id = (array) $quiz_id;
			$quiz_id = array_map( 'intval', $quiz_id );
			$quiz_id = array_map(
				function ( $id ) {
					/**
					 * Filter quiz id for quiz progress retrieval.
					 *
					 * @hook sensei_quiz_progress_find_quiz_id
					 *
					 * @since 4.23.1
					 *
					 * @param {int} $quiz_id Quiz ID.
					 * @return {int} Filtered quiz ID.
					 */
					return (int) apply_filters( 'sensei_quiz_progress_find_quiz_id', $id );
				},
				$quiz_id
			);

			$query_params   = array_merge( $query_params, $quiz_id );
			$where_clause[] = 'post_id IN (' . $this->get_placeholders( $quiz_id ) . ')';
		}

		if ( ! empty( $user_id ) ) {
			$query_params[] = (int) $user_id;
			$where_clause[] = 'user_id = %d';
		}

		if ( ! empty( $status ) ) {
			$query_params   = array_merge( $query_params, (array) $status );
			$where_clause[] = 'status IN (' . $this->get_placeholders( (array) $status ) . ')';
		}

		$table_name = $this->wpdb->prefix . 'sensei_lms_progress';

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$query_string = 'SELECT * FROM ' . $table_name . ' ';
		if ( count( $where_clause ) > 0 ) {
			$query_string .= 'WHERE ' . implode( ' AND ', $where_clause ) . ' ';
		}

		$query_string  .= 'ORDER BY id ASC ';
		$query_string  .= 'LIMIT %d OFFSET %d';
		$query_params[] = $limit;
		$query_params[] = $offset;

		$query = $this->wpdb->prepare(
			$query_string, // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			...$query_params
		);

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$rows = $this->wpdb->get_results( $query );
		if ( ! $rows ) {
			return array();
		}

		$timezone      = new DateTimeZone( 'UTC' );
		$progresses    = array();
		$cache_values  = array();
		$cache_enabled = Progress_Storage_Settings::is_cache_enabled();

		foreach ( $rows as $row ) {
			$progress = new Tables_Based_Quiz_Progress(
				(int) $row->id,
				(int) $row->post_id,
				(int) $row->user_id,
				$row->status,
				$row->started_at ? new DateTimeImmutable( $row->started_at, $timezone ) : null,
				$row->completed_at ? new DateTimeImmutable( $row->completed_at, $timezone ) : null,
				new DateTimeImmutable( $row->created_at, $timezone ),
				new DateTimeImmutable( $row->updated_at, $timezone )
			);

			$progresses[] = $progress;

			if ( $cache_enabled ) {
				$cache_values[ self::get_prefixed_key( $this->get_cache_key( (int) $row->post_id, (int) $row->user_id ), self::CACHE_GROUP ) ] = $progress;
			}
		}

		if ( ! empty( $cache_values ) ) {
			wp_cache_set_multiple( $cache_values, self::CACHE_GROUP );
		}

		return $progresses;
	}

	/**
	 * Get the cache key for a quiz progress.
	 *
	 * @since 4.26.0
	 *
	 * @param int $quiz_id The quiz ID.
	 * @param int $user_id The user ID.
	 * @return string The cache key.
	 */
	private function get_cache_key( int $quiz_id, int $user_id ): string {
		return $quiz_id . '_' . $user_id;
	}

	/**
	 * Return a string of placeholders for the given values.
	 *
	 * @param array $values The values.
	 * @return string The placeholders.
	 */
	private function get_placeholders( array $values ) {
		if ( empty( $values ) ) {
			return '';
		}

		$placeholder  = is_numeric( $values[0] ) ? '%d' : '%s';
		$placeholders = array_fill( 0, count( $values ), $placeholder );

		return implode( ', ', $placeholders );
	}
}
