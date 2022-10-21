<?php
/**
 * File containing the class \Sensei\Student_Progress\Course_Progress\Repositories\Tables_Based_Course_Progress_Repository.
 *
 * @package sensei
 */

namespace Sensei\Internal\Student_Progress\Course_Progress\Repositories;

use DateTimeImmutable;
use Sensei\Internal\Student_Progress\Course_Progress\Models\Course_Progress;
use wpdb;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Tables_Based_Course_Progress_Repository
 *
 * @internal
 *
 * @since $$next-version$$
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
	 * @return Course_Progress The course progress.
	 */
	public function create( int $course_id, int $user_id ): Course_Progress {
		$current_datetime = current_datetime();
		$date_format      = 'Y-m-d H:i:s';
		$this->wpdb->insert(
			$this->wpdb->prefix . 'sensei_lms_progress',
			[
				'post_id'        => $course_id,
				'user_id'        => $user_id,
				'parent_post_id' => null,
				'type'           => 'course',
				'status'         => Course_Progress::STATUS_IN_PROGRESS,
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

		return new Course_Progress(
			$id,
			$course_id,
			$user_id,
			Course_Progress::STATUS_IN_PROGRESS,
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
	 * @return Course_Progress|null The course progress or null if it does not exist.
	 */
	public function get( int $course_id, int $user_id ): ?Course_Progress {
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

		return new Course_Progress(
			(int) $row->id,
			(int) $row->post_id,
			(int) $row->user_id,
			$row->status,
			$row->started_at ? new DateTimeImmutable( $row->started_at, wp_timezone() ) : null,
			$row->completed_at ? new DateTimeImmutable( $row->completed_at, wp_timezone() ) : null,
			new DateTimeImmutable( $row->created_at, wp_timezone() ),
			new DateTimeImmutable( $row->updated_at, wp_timezone() )
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
	 * @param Course_Progress $course_progress The course progress.
	 */
	public function save( Course_Progress $course_progress ): void {
		$date_format = 'Y-m-d H:i:s';
		$this->wpdb->update(
			$this->wpdb->prefix . 'sensei_lms_progress',
			[
				'status'       => $course_progress->get_status(),
				'started_at'   => $course_progress->get_started_at() ? $course_progress->get_started_at()->format( $date_format ) : null,
				'completed_at' => $course_progress->get_completed_at() ? $course_progress->get_completed_at()->format( $date_format ) : null,
				'updated_at'   => current_datetime()->format( $date_format ),
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
	 * @param Course_Progress $course_progress The course progress.
	 */
	public function delete( Course_Progress $course_progress ): void {
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
}
