<?php
/**
 * File containing the class \Sensei\Student_Progress\Lesson_ProgressRepositories\Tables_Based_Lesson_Progress_Repository.
 *
 * @package sensei
 */

namespace Sensei\Student_Progress\Lesson_Progress\Repositories;

use DateTimeImmutable;
use RuntimeException;
use Sensei\Student_Progress\Lesson_Progress\Models\Lesson_Progress;
use wpdb;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Tables_Based_Lesson_Progress_Repository
 *
 * @since $$next-version$$
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
	 * @param wpdb $wpdb WordPress database object.
	 */
	public function __construct( wpdb $wpdb ) {
		$this->wpdb = $wpdb;
	}

	/**
	 * Creates a new lesson progress.
	 *
	 * @param int $lesson_id The lesson ID.
	 * @param int $user_id The user ID.
	 *
	 * @return Lesson_Progress The lesson progress.
	 * @throws RuntimeException When the lesson progress could not be created.
	 */
	public function create( int $lesson_id, int $user_id ): Lesson_Progress {
		$current_datetime = current_datetime();
		$this->wpdb->insert(
			$this->wpdb->prefix . 'sensei_lms_progress',
			[
				'post_id'        => $lesson_id,
				'user_id'        => $user_id,
				'parent_post_id' => null,
				'type'           => 'lesson',
				'status'         => Lesson_Progress::STATUS_IN_PROGRESS,
				'started_at'     => $current_datetime->getTimestamp(),
				'completed_at'   => null,
				'created_at'     => $current_datetime->getTimestamp(),
				'updated_at'     => $current_datetime->getTimestamp(),
			],
			[
				'%d',
				'%d',
				null,
				'%s',
				'%s',
				'%d',
				null,
				'%d',
				'%d',
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

		return new Lesson_Progress(
			(int) $row->id,
			(int) $row->post_id,
			(int) $row->user_id,
			$row->status,
			new DateTimeImmutable( "@{$row->started_at}", wp_timezone() ),
			$row->completed_at ? new DateTimeImmutable( "@{$row->completed_at}", wp_timezone() ) : null,
			new DateTimeImmutable( "@{$row->created_at}", wp_timezone() ),
			new DateTimeImmutable( "@{$row->updated_at}", wp_timezone() )
		);
	}

	/**
	 * Check if a lesson progress exists.
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
	 * @param Lesson_Progress $lesson_progress The lesson progress.
	 */
	public function save( Lesson_Progress $lesson_progress ): void {
		$this->wpdb->update(
			$this->wpdb->prefix . 'sensei_lms_progress',
			[
				'status'       => $lesson_progress->get_status(),
				'started_at'   => $lesson_progress->get_started_at()->getTimestamp(),
				'completed_at' => $lesson_progress->get_completed_at() ? $lesson_progress->get_completed_at()->getTimestamp() : null,
				'updated_at'   => current_datetime()->getTimestamp(),
			],
			[
				'id' => $lesson_progress->get_id(),
			],
			[
				'%s',
				'%d',
				$lesson_progress->get_completed_at() ? '%d' : null,
				'%d',
			],
			[
				'%d',
			]
		);
	}

	/**
	 * Returns the number of started lessons for a user in a course.
	 * The number of started lessons is the same as the number of lessons that have a progress record.
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
