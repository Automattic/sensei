<?php
/**
 * File containing the class \Sensei\Student_Progress\Course_Progress\Repositories\Tables_Based_Course_Progress_Repository.
 *
 * @package sensei
 */

namespace Sensei\Internal\Student_Progress\Course_Progress\Repositories;

use DateTimeImmutable;
use DateTimeZone;
use Sensei\Internal\Cache_Prefix;
use Sensei\Internal\Services\Progress_Storage_Settings;
use Sensei\Internal\Student_Progress\Course_Progress\Models\Course_Progress_Interface;
use Sensei\Internal\Student_Progress\Course_Progress\Models\Tables_Based_Course_Progress;
use wpdb;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Tables_Based_Course_Progress_Repository
 *
 * @internal
 *
 * @since 4.16.1
 */
class Tables_Based_Course_Progress_Repository implements Course_Progress_Repository_Interface {
	use Cache_Prefix;

	/**
	 * Cache group for course progress.
	 *
	 * @since 4.26.0
	 *
	 * @var string
	 */
	private const CACHE_GROUP = 'sensei_course_progress';

	/**
	 * WordPress database object.
	 *
	 * @var wpdb
	 */
	private $wpdb;

	/**
	 * Tables_Based_Course_Progress_Repository constructor.
	 *
	 * @internal
	 *
	 * @param wpdb $wpdb WordPress database object.
	 */
	public function __construct( wpdb $wpdb ) {
		$this->wpdb = $wpdb;
	}

	/**
	 * Creates a new course progress.
	 *
	 * @internal
	 *
	 * @param int $course_id The course ID.
	 * @param int $user_id The user ID.
	 * @return Course_Progress_Interface The course progress.
	 */
	public function create( int $course_id, int $user_id ): Course_Progress_Interface {
		/**
		 * Filter the course ID for a created course progress.
		 *
		 * @hook sensei_course_progress_create_course_id
		 *
		 * @since 4.23.1
		 *
		 * @param {int} $course_id The course ID.
		 * @return {int} Filtered course ID.
		 */
		$course_id = (int) apply_filters( 'sensei_course_progress_create_course_id', $course_id );

		$current_datetime = new DateTimeImmutable( 'now', new DateTimeZone( 'UTC' ) );
		$date_format      = 'Y-m-d H:i:s';
		$this->wpdb->insert(
			$this->wpdb->prefix . 'sensei_lms_progress',
			[
				'post_id'        => $course_id,
				'user_id'        => $user_id,
				'parent_post_id' => null,
				'type'           => 'course',
				'status'         => Course_Progress_Interface::STATUS_IN_PROGRESS,
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

		$progress = new Tables_Based_Course_Progress(
			$id,
			$course_id,
			$user_id,
			Course_Progress_Interface::STATUS_IN_PROGRESS,
			$current_datetime,
			null,
			$current_datetime,
			$current_datetime
		);

		if ( $id && Progress_Storage_Settings::is_cache_enabled() ) {
			wp_cache_set( self::get_prefixed_key( $this->get_cache_key( $course_id, $user_id ), self::CACHE_GROUP ), $progress, self::CACHE_GROUP );
		}

		return $progress;
	}

	/**
	 * Gets a course progress.
	 *
	 * @internal
	 *
	 * @param int $course_id The course ID.
	 * @param int $user_id The user ID.
	 * @return Course_Progress_Interface|null The course progress or null if it does not exist.
	 */
	public function get( int $course_id, int $user_id ): ?Course_Progress_Interface {
		/**
		 * Filter the course ID for a course progress we want to get.
		 *
		 * @hook sensei_course_progress_get_course_id
		 *
		 * @since 4.23.1
		 *
		 * @param {int} $course_id The course ID.
		 * @return {int} Filtered course ID.
		 */
		$course_id = (int) apply_filters( 'sensei_course_progress_get_course_id', $course_id );

		$cache_key = $this->get_cache_key( $course_id, $user_id );

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
			$course_id,
			$user_id,
			'course'
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

		$progress = new Tables_Based_Course_Progress(
			(int) $row->id,
			(int) $row->post_id,
			(int) $row->user_id,
			$row->status,
			$row->started_at ? new DateTimeImmutable( $row->started_at, $timezone ) : null,
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
	 * Checks if a course progress exists.
	 *
	 * @internal
	 *
	 * @param int $course_id The course ID.
	 * @param int $user_id The user ID.
	 * @return bool Whether the course progress exists.
	 */
	public function has( int $course_id, int $user_id ): bool {
		/**
		 * Filter the course ID for a course progress we want to check.
		 *
		 * @hook sensei_course_progress_has_course_id
		 *
		 * @since 4.23.1
		 *
		 * @param {int} $course_id The course ID.
		 * @return {int} Filtered course ID.
		 */
		$course_id = (int) apply_filters( 'sensei_course_progress_has_course_id', $course_id );

		$table_name = $this->wpdb->prefix . 'sensei_lms_progress';
		$query      = $this->wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is safe.
			"SELECT COUNT(*) FROM {$table_name} WHERE post_id = %d AND user_id = %d AND type = %s",
			$course_id,
			$user_id,
			'course'
		);

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return (int) $this->wpdb->get_var( $query ) > 0;
	}

	/**
	 * Save course progress.
	 *
	 * @internal
	 *
	 * @param Course_Progress_Interface $course_progress The course progress.
	 */
	public function save( Course_Progress_Interface $course_progress ): void {
		$this->assert_tables_based_course_progress( $course_progress );

		$date_format = 'Y-m-d H:i:s';

		$updated_at = new DateTimeImmutable( 'now', new DateTimeZone( 'UTC' ) );
		$course_progress->set_updated_at( $updated_at );

		$this->wpdb->update(
			$this->wpdb->prefix . 'sensei_lms_progress',
			[
				'status'       => $course_progress->get_status(),
				// Table columns are stored in UTC.
				'started_at'   => $course_progress->get_started_at() ? gmdate( $date_format, $course_progress->get_started_at()->getTimestamp() ) : null,
				'completed_at' => $course_progress->get_completed_at() ? gmdate( $date_format, $course_progress->get_completed_at()->getTimestamp() ) : null,
				'updated_at'   => $course_progress->get_updated_at()->format( $date_format ),
			],
			[
				'id' => $course_progress->get_id(),
			],
			[
				'%s',
				$course_progress->get_started_at() ? '%s' : null,
				$course_progress->get_completed_at() ? '%s' : null,
				'%s',
			],
			[
				'%d',
			]
		);

		if ( Progress_Storage_Settings::is_cache_enabled() ) {
			wp_cache_delete( self::get_prefixed_key( $this->get_cache_key( $course_progress->get_course_id(), $course_progress->get_user_id() ), self::CACHE_GROUP ), self::CACHE_GROUP );
		}
	}

	/**
	 * Delete course progress.
	 *
	 * @internal
	 *
	 * @param Course_Progress_Interface $course_progress The course progress.
	 */
	public function delete( Course_Progress_Interface $course_progress ): void {
		$this->wpdb->delete(
			$this->wpdb->prefix . 'sensei_lms_progress',
			[
				'post_id' => $course_progress->get_course_id(),
				'user_id' => $course_progress->get_user_id(),
				'type'    => 'course',
			],
			[
				'%d',
				'%d',
				'%s',
			]
		);

		if ( Progress_Storage_Settings::is_cache_enabled() ) {
			wp_cache_delete( self::get_prefixed_key( $this->get_cache_key( $course_progress->get_course_id(), $course_progress->get_user_id() ), self::CACHE_GROUP ), self::CACHE_GROUP );
		}
	}

	/**
	 * Delete course progress for a given course.
	 *
	 * @internal
	 *
	 * @param int $course_id The course ID.
	 */
	public function delete_for_course( int $course_id ): void {
		/**
		 * Filter the course ID for a course progress we want to delete.
		 *
		 * @hook sensei_course_progress_delete_for_course_course_id
		 *
		 * @since 4.23.1
		 *
		 * @param {int} $course_id The course ID.
		 * @return {int} Filtered course ID.
		 */
		$course_id = (int) apply_filters( 'sensei_course_progress_delete_for_course_course_id', $course_id );

		$this->wpdb->delete(
			$this->wpdb->prefix . 'sensei_lms_progress',
			[
				'post_id' => $course_id,
				'type'    => 'course',
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
	 * Delete course progress for a given user.
	 *
	 * @internal
	 *
	 * @param int $user_id The user ID.
	 */
	public function delete_for_user( int $user_id ): void {
		$this->wpdb->delete(
			$this->wpdb->prefix . 'sensei_lms_progress',
			[
				'user_id' => $user_id,
				'type'    => 'course',
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
	 * Assert that the course progress is a Tables_Based_Course_Progress.
	 *
	 * @param Course_Progress_Interface $course_progress The course progress.
	 * @throws \InvalidArgumentException If the course progress is not a Tables_Based_Course_Progress.
	 */
	private function assert_tables_based_course_progress( Course_Progress_Interface $course_progress ): void {
		if ( ! $course_progress instanceof Tables_Based_Course_Progress ) {
			$actual_type = get_class( $course_progress );
			throw new \InvalidArgumentException( esc_html( "Expected Tables_Based_Course_Progress, got {$actual_type}." ) );
		}
	}

	/**
	 * Find course progress.
	 *
	 * @internal
	 *
	 * @param array $args The arguments.
	 * @return Course_Progress_Interface[] The course progress.
	 */
	public function find( array $args ): array {
		$course_id = $args['course_id'] ?? null;
		$user_id   = $args['user_id'] ?? null;
		$status    = $args['status'] ?? null;
		$limit     = $args['number'] ?? 100;
		$offset    = $args['offset'] ?? 0;

		$where_clause = array( 'type = %s' );
		$query_params = array( 'course' );
		if ( ! empty( $course_id ) ) {
			$course_ids = array_map( 'intval', (array) $course_id );
			$course_ids = array_map(
				function ( $course_id ): int {
					/**
					 * Filter the course ID for a course progress we want to find.
					 *
					 * @hook sensei_course_progress_find_course_id
					 *
					 * @since 4.23.1
					 *
					 * @param {int} $course_id The course ID.
					 * @return {int} Filtered course ID.
					 */
					return (int) apply_filters( 'sensei_course_progress_find_course_id', $course_id );
				},
				$course_ids
			);

			$query_params   = array_merge( $query_params, $course_ids );
			$where_clause[] = 'post_id IN (' . $this->get_placeholders( $course_ids ) . ')';
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

		$timezone          = new DateTimeZone( 'UTC' );
		$course_progresses = array();
		$cache_values      = array();
		$cache_enabled     = Progress_Storage_Settings::is_cache_enabled();

		foreach ( $rows as $row ) {
			$progress = new Tables_Based_Course_Progress(
				(int) $row->id,
				(int) $row->post_id,
				(int) $row->user_id,
				$row->status,
				$row->started_at ? new DateTimeImmutable( $row->started_at, $timezone ) : null,
				$row->completed_at ? new DateTimeImmutable( $row->completed_at, $timezone ) : null,
				new DateTimeImmutable( $row->created_at, $timezone ),
				new DateTimeImmutable( $row->updated_at, $timezone )
			);

			$course_progresses[] = $progress;

			if ( $cache_enabled ) {
				$cache_values[ self::get_prefixed_key( $this->get_cache_key( (int) $row->post_id, (int) $row->user_id ), self::CACHE_GROUP ) ] = $progress;
			}
		}

		if ( ! empty( $cache_values ) ) {
			wp_cache_set_multiple( $cache_values, self::CACHE_GROUP );
		}

		return $course_progresses;
	}

	/**
	 * Get the cache key for a course progress.
	 *
	 * @since 4.26.0
	 *
	 * @param int $course_id The course ID.
	 * @param int $user_id   The user ID.
	 * @return string The cache key.
	 */
	private function get_cache_key( int $course_id, int $user_id ): string {
		return $course_id . '_' . $user_id;
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
