<?php
/**
 * File containing the class \Sensei\Student_Progress\Lesson_ProgressRepositories\Tables_Based_Lesson_Progress_Repository.
 *
 * @package sensei
 */

namespace Sensei\Internal\Student_Progress\Lesson_Progress\Repositories;

use DateTimeImmutable;
use DateTimeZone;
use Sensei\Internal\Student_Progress\Lesson_Progress\Models\Lesson_Progress;
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
	 * @return Lesson_Progress The lesson progress.
	 */
	public function create( int $lesson_id, int $user_id ): Lesson_Progress {
		$current_datetime = new DateTimeImmutable( 'now', new DateTimeZone( 'UTC' ) );
		$date_format      = 'Y-m-d H:i:s';
		$this->wpdb->insert(
			$this->wpdb->prefix . 'sensei_lms_progress',
			[
				'post_id'        => $lesson_id,
				'user_id'        => $user_id,
				'parent_post_id' => null,
				'type'           => 'lesson',
				'status'         => Lesson_Progress::STATUS_IN_PROGRESS,
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

		return new Lesson_Progress(
			$id,
			$lesson_id,
			$user_id,
			Lesson_Progress::STATUS_IN_PROGRESS,
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
	 * @return Lesson_Progress|null The lesson progress or null if not found.
	 */
	public function get( int $lesson_id, int $user_id ): ?Lesson_Progress {
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

		return new Lesson_Progress(
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
	 * @param Lesson_Progress $lesson_progress The lesson progress.
	 */
	public function save( Lesson_Progress $lesson_progress ): void {
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
	 * @param Lesson_Progress $lesson_progress The lesson progress.
	 */
	public function delete( Lesson_Progress $lesson_progress ): void {
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
}
