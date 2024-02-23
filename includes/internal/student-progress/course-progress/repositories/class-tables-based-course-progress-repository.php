<?php
/**
 * File containing the class \Sensei\Student_Progress\Course_Progress\Repositories\Tables_Based_Course_Progress_Repository.
 *
 * @package sensei
 */

namespace Sensei\Internal\Student_Progress\Course_Progress\Repositories;

use DateTimeImmutable;
use DateTimeZone;
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

		return new Tables_Based_Course_Progress(
			$id,
			$course_id,
			$user_id,
			Course_Progress_Interface::STATUS_IN_PROGRESS,
			$current_datetime,
			null,
			$current_datetime,
			$current_datetime
		);
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
			return null;
		}

		$timezone = new DateTimeZone( 'UTC' );

		return new Tables_Based_Course_Progress(
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
	 * Checks if a course progress exists.
	 *
	 * @internal
	 *
	 * @param int $course_id The course ID.
	 * @param int $user_id The user ID.
	 * @return bool Whether the course progress exists.
	 */
	public function has( int $course_id, int $user_id ): bool {
		$table_name = $this->wpdb->prefix . 'sensei_lms_progress';
		$query      = $this->wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			'SELECT COUNT(*) FROM ' . $table_name . ' WHERE post_id = %d AND user_id = %d AND type = %s',
			$course_id,
			$user_id,
			'course'
		);

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$count = (int) $this->wpdb->get_var( $query );

		return $count > 0;
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
				'started_at'   => $course_progress->get_started_at() ? $course_progress->get_started_at()->format( $date_format ) : null,
				'completed_at' => $course_progress->get_completed_at() ? $course_progress->get_completed_at()->format( $date_format ) : null,
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
	}

	/**
	 * Delete course progress for a given course.
	 *
	 * @internal
	 *
	 * @param int $course_id The course ID.
	 */
	public function delete_for_course( int $course_id ): void {
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
			$query_params   = array_merge( $query_params, (array) $course_id );
			$where_clause[] = 'post_id IN (' . $this->get_placeholders( (array) $course_id ) . ')';
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

		$course_progresses = array();
		foreach ( $rows as $row ) {
			$course_progresses[] = new Tables_Based_Course_Progress(
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

		return $course_progresses;
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
