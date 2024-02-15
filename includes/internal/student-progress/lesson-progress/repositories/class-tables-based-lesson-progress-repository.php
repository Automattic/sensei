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
use Sensei\Internal\Student_Progress\Lesson_Progress\Models\Lesson_Progress_Interface;
use Sensei\Internal\Student_Progress\Lesson_Progress\Models\Tables_Based_Lesson_Progress;
use wpdb;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Tables_Based_Lesson_Progress_Repository
 *
 * @intenal
 *
 * @since 4.16.1
 */
class Tables_Based_Lesson_Progress_Repository implements Lesson_Progress_Repository_Interface {
	/**
	 * WordPress database object.
	 *
	 * @var wpdb
	 */
	private $wpdb;

	/**
	 * Tables_Based_Course_Progress_Repository constructor.
	 *
	 * @intenal
	 *
	 * @param wpdb $wpdb WordPress database object.
	 */
	public function __construct( wpdb $wpdb ) {
		$this->wpdb = $wpdb;
	}

	/**
	 * Creates a new lesson progress.
	 *
	 * @intenal
	 *
	 * @param int $lesson_id The lesson ID.
	 * @param int $user_id The user ID.
	 *
	 * @return Lesson_Progress_Interface The lesson progress.
	 */
	public function create( int $lesson_id, int $user_id ): Lesson_Progress_Interface {
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

		return new Tables_Based_Lesson_Progress(
			$id,
			$lesson_id,
			$user_id,
			Lesson_Progress_Interface::STATUS_IN_PROGRESS,
			$current_datetime,
			null,
			$current_datetime,
			$current_datetime
		);
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
			return null;
		}

		$timezone = new DateTimeZone( 'UTC' );

		return new Tables_Based_Lesson_Progress(
			(int) $row->id,
			(int) $row->post_id,
			(int) $row->user_id,
			$row->status,
			$row->started_at ? new DateTimeImmutable( $row->started_at, $timezone ) : null,
			$row->completed_at ? new DateTimeImmutable( $row->completed_at, $timezone ) : null,
			new DateTimeImmutable( $row->created_at, $timezone ),
			new DateTimeImmutable( $row->updated_at, $timezone )
		);
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
		$table_name = $this->wpdb->prefix . 'sensei_lms_progress';
		$query      = $this->wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			'SELECT COUNT(*) FROM ' . $table_name . ' WHERE post_id = %d AND user_id = %d AND type = %s',
			$lesson_id,
			$user_id,
			'lesson'
		);

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$count = (int) $this->wpdb->get_var( $query );

		return $count > 0;
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
				'started_at'   => $lesson_progress->get_started_at() ? $lesson_progress->get_started_at()->format( $date_format ) : null,
				'completed_at' => $lesson_progress->get_completed_at() ? $lesson_progress->get_completed_at()->format( $date_format ) : null,
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
	}

	/**
	 * Delete all lesson progress for a lesson.
	 *
	 * @internal
	 *
	 * @param int $lesson_id The lesson ID.
	 */
	public function delete_for_lesson( int $lesson_id ): void {
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
			$query_params   = array_merge( $query_params, (array) $lesson_id );
			$where_clause[] = 'post_id IN (' . $this->get_placeholders( (array) $lesson_id ) . ')';
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

		$timezone = new DateTimeZone( 'UTC' );

		$lesson_progresses = array();
		foreach ( $rows as $row ) {
			$lesson_progresses[] = new Tables_Based_Lesson_Progress(
				(int) $row->id,
				(int) $row->post_id,
				(int) $row->user_id,
				$row->status,
				$row->started_at ? new DateTimeImmutable( $row->started_at, $timezone ) : null,
				$row->completed_at ? new DateTimeImmutable( $row->completed_at, $timezone ) : null,
				new DateTimeImmutable( $row->created_at, $timezone ),
				new DateTimeImmutable( $row->updated_at, $timezone )
			);
		}

		return $lesson_progresses;
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
