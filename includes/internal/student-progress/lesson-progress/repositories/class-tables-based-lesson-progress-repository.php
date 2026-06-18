<?php
/**
 * File containing the class \Sensei\Student_Progress\Lesson_ProgressRepositories\Tables_Based_Lesson_Progress_Repository.
 *
 * @package sensei
 */

namespace Sensei\Internal\Student_Progress\Lesson_Progress\Repositories;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use Sensei\Internal\Cache_Prefix;
use Sensei\Internal\Services\Progress_Storage_Settings;
use Sensei\Internal\Student_Progress\Lesson_Progress\Models\Lesson_Progress_Interface;
use Sensei\Internal\Student_Progress\Lesson_Progress\Models\Tables_Based_Lesson_Progress;
use wpdb;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Tables_Based_Lesson_Progress_Repository
 *
 * @internal
 *
 * @since 4.16.1
 */
class Tables_Based_Lesson_Progress_Repository implements Lesson_Progress_Repository_Interface {
	use Cache_Prefix;

	/**
	 * Cache group for lesson progress.
	 *
	 * @since 4.26.0
	 *
	 * @var string
	 */
	private const CACHE_GROUP = 'sensei_lesson_progress';

	/**
	 * WordPress database object.
	 *
	 * @var wpdb
	 */
	private $wpdb;

	/**
	 * Tables_Based_Lesson_Progress_Repository constructor.
	 *
	 * @internal
	 *
	 * @param wpdb $wpdb WordPress database object.
	 */
	public function __construct( wpdb $wpdb ) {
		$this->wpdb = $wpdb;
	}

	/**
	 * Creates a new lesson progress.
	 *
	 * @internal
	 *
	 * @param int $lesson_id The lesson ID.
	 * @param int $user_id The user ID.
	 *
	 * @return Lesson_Progress_Interface The lesson progress.
	 */
	public function create( int $lesson_id, int $user_id ): Lesson_Progress_Interface {
		/**
		 * Filter lesson id for lesson progress creation.
		 *
		 * @hook sensei_lesson_progress_create_lesson_id
		 *
		 * @since 4.23.1
		 *
		 * @param {int} $lesson_id The lesson ID.
		 * @return {int} Filtered lesson ID.
		 */
		$lesson_id = (int) apply_filters( 'sensei_lesson_progress_create_lesson_id', $lesson_id );

		$current_datetime = new DateTimeImmutable( 'now', new DateTimeZone( 'UTC' ) );
		$date_format      = 'Y-m-d H:i:s';
		$this->wpdb->insert(
			$this->wpdb->prefix . 'sensei_lms_progress',
			[
				'post_id'        => $lesson_id,
				'user_id'        => $user_id,
				'parent_post_id' => null,
				'type'           => 'lesson',
				'status'         => Lesson_Progress_Interface::STATUS_IN_PROGRESS,
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

		$progress = new Tables_Based_Lesson_Progress(
			$id,
			$lesson_id,
			$user_id,
			Lesson_Progress_Interface::STATUS_IN_PROGRESS,
			$current_datetime,
			null,
			$current_datetime,
			$current_datetime
		);

		if ( $id && Progress_Storage_Settings::is_cache_enabled() ) {
			wp_cache_set( self::get_prefixed_key( $this->get_cache_key( $lesson_id, $user_id ), self::CACHE_GROUP ), $progress, self::CACHE_GROUP );
		}

		return $progress;
	}

	/**
	 * Finds a lesson progress by lesson and user.
	 *
	 * @internal
	 *
	 * @param int $lesson_id The lesson ID.
	 * @param int $user_id The user ID.
	 *
	 * @return Lesson_Progress_Interface|null The lesson progress or null if not found.
	 */
	public function get( int $lesson_id, int $user_id ): ?Lesson_Progress_Interface {
		/**
		 * Filter lesson id for lesson progress creation.
		 *
		 * @hook sensei_lesson_progress_get_lesson_id
		 *
		 * @since 4.23.1
		 *
		 * @param {int} $lesson_id The lesson ID.
		 * @return {int} Filtered lesson ID.
		 */
		$lesson_id = (int) apply_filters( 'sensei_lesson_progress_get_lesson_id', $lesson_id );

		$cache_key = $this->get_cache_key( $lesson_id, $user_id );

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
			$lesson_id,
			$user_id,
			'lesson'
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

		$progress = new Tables_Based_Lesson_Progress(
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
	 * Check if a lesson progress exists.
	 *
	 * @internal
	 *
	 * @param int $lesson_id The lesson ID.
	 * @param int $user_id The user ID.
	 * @return bool
	 */
	public function has( int $lesson_id, int $user_id ): bool {
		/**
		 * Filter the lesson ID for a lesson progress we want to check.
		 *
		 * @hook sensei_lesson_progress_has_lesson_id
		 *
		 * @since 4.23.1
		 *
		 * @param {int} $lesson_id The lesson ID.
		 * @return {int} Filtered lesson ID.
		 */
		$lesson_id = (int) apply_filters( 'sensei_lesson_progress_has_lesson_id', $lesson_id );

		$table_name = $this->wpdb->prefix . 'sensei_lms_progress';
		$query      = $this->wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is safe.
			"SELECT COUNT(*) FROM {$table_name} WHERE post_id = %d AND user_id = %d AND type = %s",
			$lesson_id,
			$user_id,
			'lesson'
		);

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return (int) $this->wpdb->get_var( $query ) > 0;
	}

	/**
	 * Save the lesson progress.
	 *
	 * @internal
	 *
	 * @param Lesson_Progress_Interface $lesson_progress The lesson progress.
	 */
	public function save( Lesson_Progress_Interface $lesson_progress ): void {
		$this->assert_tables_based_lesson_progress( $lesson_progress );

		$updated_at = new DateTimeImmutable( 'now', new DateTimeZone( 'UTC' ) );
		$lesson_progress->set_updated_at( $updated_at );

		$date_format = 'Y-m-d H:i:s';
		$this->wpdb->update(
			$this->wpdb->prefix . 'sensei_lms_progress',
			[
				'status'       => $lesson_progress->get_status(),
				// Table columns are stored in UTC.
				'started_at'   => $lesson_progress->get_started_at() ? gmdate( $date_format, $lesson_progress->get_started_at()->getTimestamp() ) : null,
				'completed_at' => $lesson_progress->get_completed_at() ? gmdate( $date_format, $lesson_progress->get_completed_at()->getTimestamp() ) : null,
				'updated_at'   => $lesson_progress->get_updated_at()->format( $date_format ),
			],
			[
				'id' => $lesson_progress->get_id(),
			],
			[
				'%s',
				$lesson_progress->get_started_at() ? '%s' : null,
				$lesson_progress->get_completed_at() ? '%s' : null,
				'%s',
			],
			[
				'%d',
			]
		);

		if ( Progress_Storage_Settings::is_cache_enabled() ) {
			wp_cache_delete( self::get_prefixed_key( $this->get_cache_key( $lesson_progress->get_lesson_id(), $lesson_progress->get_user_id() ), self::CACHE_GROUP ), self::CACHE_GROUP );
		}
	}

	/**
	 * Delete the lesson progress.
	 *
	 * @internal
	 *
	 * @param Lesson_Progress_Interface $lesson_progress The lesson progress.
	 */
	public function delete( Lesson_Progress_Interface $lesson_progress ): void {
		$this->wpdb->delete(
			$this->wpdb->prefix . 'sensei_lms_progress',
			[
				'post_id' => $lesson_progress->get_lesson_id(),
				'user_id' => $lesson_progress->get_user_id(),
				'type'    => 'lesson',
			],
			[
				'%d',
				'%d',
				'%s',
			]
		);

		if ( Progress_Storage_Settings::is_cache_enabled() ) {
			wp_cache_delete( self::get_prefixed_key( $this->get_cache_key( $lesson_progress->get_lesson_id(), $lesson_progress->get_user_id() ), self::CACHE_GROUP ), self::CACHE_GROUP );
		}
	}

	/**
	 * Delete all lesson progress for a lesson.
	 *
	 * @internal
	 *
	 * @param int $lesson_id The lesson ID.
	 */
	public function delete_for_lesson( int $lesson_id ): void {
		/**
		 * Filter lesson id for lesson progress deletion.
		 *
		 * @hook sensei_lesson_progress_delete_for_lesson_lesson_id
		 *
		 * @since 4.23.1
		 *
		 * @param {int} $lesson_id The lesson ID.
		 * @return {int} Filtered lesson ID.
		 */
		$lesson_id = (int) apply_filters( 'sensei_lesson_progress_delete_for_lesson_lesson_id', $lesson_id );

		$this->wpdb->delete(
			$this->wpdb->prefix . 'sensei_lms_progress',
			[
				'post_id' => $lesson_id,
				'type'    => 'lesson',
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
	 * Delete all lesson progress for a user.
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
				'type'    => 'lesson',
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
	 * Returns the number of started lessons for a user in a course.
	 * The number of started lessons is the same as the number of lessons that have a progress record.
	 *
	 * @internal
	 *
	 * @param int $course_id The course ID.
	 * @param int $user_id The user ID.
	 * @return int
	 */
	public function count( int $course_id, int $user_id ): int {
		if ( ! $user_id ) {
			return 0;
		}

		/**
		 * Filter course id for lesson progress counting.
		 *
		 * @hook sensei_lesson_progress_count_course_id
		 *
		 * @since 4.23.1
		 *
		 * @param {int} $course_id The course ID.
		 * @return {int} Filtered course ID.
		 */
		$course_id = (int) apply_filters( 'sensei_lesson_progress_count_course_id', $course_id );

		$lesson_ids = Sensei()->course->course_lessons( $course_id, 'publish', 'ids' );

		if ( empty( $lesson_ids ) ) {
			return 0;
		}

		$clean_lesson_ids = implode( ',', esc_sql( $lesson_ids ) );

		$table_name = $this->wpdb->prefix . 'sensei_lms_progress';
		$query      = $this->wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			'SELECT COUNT(*) FROM ' . $table_name . ' WHERE post_id IN (' . $clean_lesson_ids . ') AND user_id = %d AND type = %s',
			$user_id,
			'lesson'
		);

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$count = $this->wpdb->get_var( $query );

		return (int) $count;
	}

	/**
	 * Asserts that the lesson progress is a Tables_Based_Lesson_Progress.
	 *
	 * @param Lesson_Progress_Interface $lesson_progress The lesson progress.
	 * @throws InvalidArgumentException If the lesson progress is not a Tables_Based_Lesson_Progress.
	 */
	private function assert_tables_based_lesson_progress( Lesson_Progress_Interface $lesson_progress ): void {
		if ( ! $lesson_progress instanceof Tables_Based_Lesson_Progress ) {
			$actual_type = get_class( $lesson_progress );
			throw new InvalidArgumentException( esc_html( "Expected Tables_Based_Lesson_Progress, got {$actual_type}." ) );
		}
	}

	/**
	 * Find lesson progress.
	 *
	 * @internal
	 *
	 * @param array $args The arguments.
	 * @return Lesson_Progress_Interface[]
	 */
	public function find( array $args ): array {
		$lesson_id = $args['lesson_id'] ?? null;
		$user_id   = $args['user_id'] ?? null;
		$status    = $args['status'] ?? null;
		$limit     = $args['number'] ?? 100;
		$offset    = $args['offset'] ?? 0;

		$where_clause = array( 'type = %s' );
		$query_params = array( 'lesson' );
		if ( ! empty( $lesson_id ) ) {
			$lesson_ids = array_map( 'intval', (array) $lesson_id );
			$lesson_ids = array_map(
				function ( $lesson_id ) {
					/**
					 * Filter lesson id for lesson progress creation.
					 *
					 * @hook sensei_lesson_progress_find_lesson_id
					 *
					 * @since 4.23.1
					 *
					 * @param {int} $lesson_id The lesson ID.
					 * @return {int} Filtered lesson ID.
					 */
					return (int) apply_filters( 'sensei_lesson_progress_find_lesson_id', $lesson_id );
				},
				$lesson_ids
			);

			$query_params   = array_merge( $query_params, $lesson_ids );
			$where_clause[] = 'post_id IN (' . $this->get_placeholders( $lesson_ids ) . ')';
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
		$lesson_progresses = array();
		$cache_values      = array();
		$cache_enabled     = Progress_Storage_Settings::is_cache_enabled();

		foreach ( $rows as $row ) {
			$progress = new Tables_Based_Lesson_Progress(
				(int) $row->id,
				(int) $row->post_id,
				(int) $row->user_id,
				$row->status,
				$row->started_at ? new DateTimeImmutable( $row->started_at, $timezone ) : null,
				$row->completed_at ? new DateTimeImmutable( $row->completed_at, $timezone ) : null,
				new DateTimeImmutable( $row->created_at, $timezone ),
				new DateTimeImmutable( $row->updated_at, $timezone )
			);

			$lesson_progresses[] = $progress;

			if ( $cache_enabled ) {
				$cache_values[ self::get_prefixed_key( $this->get_cache_key( (int) $row->post_id, (int) $row->user_id ), self::CACHE_GROUP ) ] = $progress;
			}
		}

		if ( ! empty( $cache_values ) ) {
			wp_cache_set_multiple( $cache_values, self::CACHE_GROUP );
		}

		return $lesson_progresses;
	}

	/**
	 * Get the cache key for a lesson progress.
	 *
	 * @since 4.26.0
	 *
	 * @param int $lesson_id The lesson ID.
	 * @param int $user_id   The user ID.
	 * @return string The cache key.
	 */
	private function get_cache_key( int $lesson_id, int $user_id ): string {
		return $lesson_id . '_' . $user_id;
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
