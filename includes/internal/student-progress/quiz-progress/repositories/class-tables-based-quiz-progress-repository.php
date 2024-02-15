<?php
/**
 * File containing the class \Sensei\Student_Progress\Quiz_Progress\Repositories\Tables_Based_Quiz_Progress_Repository.
 *
 * @package sensei
 */

namespace Sensei\Internal\Student_Progress\Quiz_Progress\Repositories;

use DateTimeImmutable;
use DateTimeZone;
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
	 * Create a new quiz progress.
	 *
	 * @internal
	 *
	 * @param int $quiz_id Quiz identifier.
	 * @param int $user_id User identifier.
	 * @return Quiz_Progress_Interface
	 */
	public function create( int $quiz_id, int $user_id ): Quiz_Progress_Interface {
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

		return new Tables_Based_Quiz_Progress(
			$id,
			$quiz_id,
			$user_id,
			Quiz_Progress_Interface::STATUS_IN_PROGRESS,
			$current_datetime,
			null,
			$current_datetime,
			$current_datetime
		);
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
			return null;
		}

		$timezone = new DateTimeZone( 'UTC' );

		return new Tables_Based_Quiz_Progress(
			(int) $row->id,
			(int) $row->post_id,
			(int) $row->user_id,
			$row->status,
			new DateTimeImmutable( $row->started_at, $timezone ),
			$row->completed_at ? new DateTimeImmutable( $row->completed_at, $timezone ) : null,
			new DateTimeImmutable( $row->created_at, $timezone ),
			new DateTimeImmutable( $row->updated_at, $timezone )
		);
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
		$table_name = $this->wpdb->prefix . 'sensei_lms_progress';
		$query      = $this->wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			'SELECT COUNT(*) FROM ' . $table_name . ' WHERE post_id = %d AND user_id = %d AND type = %s',
			$quiz_id,
			$user_id,
			'quiz'
		);

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$count = (int) $this->wpdb->get_var( $query );

		return $count > 0;
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
				'started_at'   => $quiz_progress->get_started_at() ? $quiz_progress->get_started_at()->format( $date_format ) : null,
				'completed_at' => $quiz_progress->get_completed_at() ? $quiz_progress->get_completed_at()->format( $date_format ) : null,
				'updated_at'   => $quiz_progress->get_updated_at()->format( $date_format ),
			],
			[
				'id' => $quiz_progress->get_id(),
			],
			[
				'%s',
				'%s',
				$quiz_progress->get_completed_at() ? '%s' : null,
				'%s',
			],
			[
				'%d',
			]
		);
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
	}

	/**
	 * Delete all quiz progress for a quiz.
	 *
	 * @internal
	 *
	 * @param int $quiz_id Quiz identifier.
	 */
	public function delete_for_quiz( int $quiz_id ): void {
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
			$query_params   = array_merge( $query_params, (array) $quiz_id );
			$where_clause[] = 'post_id IN (' . $this->get_placeholders( (array) $quiz_id ) . ')';
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
			$course_progresses[] = new Tables_Based_Quiz_Progress(
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
