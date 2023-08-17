<?php
/**
 * File containing the class \Sensei\Student_Progress\Quiz_Progress\Repositories\Tables_Based_Quiz_Progress_Repository.
 *
 * @package sensei
 */

namespace Sensei\Internal\Student_Progress\Quiz_Progress\Repositories;

use DateTimeImmutable;
use DateTimeZone;
use Sensei\Internal\Student_Progress\Quiz_Progress\Models\Quiz_Progress;
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
	 * @return Quiz_Progress
	 */
	public function create( int $quiz_id, int $user_id ): Quiz_Progress {
		$current_datetime = new DateTimeImmutable( 'now', new DateTimeZone( 'UTC' ) );
		$date_format      = 'Y-m-d H:i:s';
		$this->wpdb->insert(
			$this->wpdb->prefix . 'sensei_lms_progress',
			[
				'post_id'        => $quiz_id,
				'user_id'        => $user_id,
				'parent_post_id' => null,
				'type'           => 'quiz',
				'status'         => Quiz_Progress::STATUS_IN_PROGRESS,
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

		return new Quiz_Progress(
			$id,
			$quiz_id,
			$user_id,
			Quiz_Progress::STATUS_IN_PROGRESS,
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
	 * @return Quiz_Progress
	 */
	public function get( int $quiz_id, int $user_id ): ?Quiz_Progress {
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

		return new Quiz_Progress(
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
	 * @param Quiz_Progress $quiz_progress Quiz progress.
	 */
	public function save( Quiz_Progress $quiz_progress ): void {
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
	 * @param Quiz_Progress $quiz_progress Quiz progress.
	 */
	public function delete( Quiz_Progress $quiz_progress ): void {
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
}
