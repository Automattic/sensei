<?php
/**
 * File containing the class Aggregate_Course_Progress_Repository.
 *
 * @package sensei
 */

namespace Sensei\Internal\Student_Progress\Course_Progress\Repositories;

use Sensei\Internal\Student_Progress\Course_Progress\Models\Course_Progress;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Aggregate_Course_Progress_Repository.
 *
 * Aggregate repository is an intermediate repository that delegates the calls to the appropriate repository implementation.
 *
 * @internal
 *
 * @since 4.16.1
 */
class Aggregate_Course_Progress_Repository implements Course_Progress_Repository_Interface {
	/**
	 * Comments based course progress repository implementation.
	 *
	 * @var Comments_Based_Course_Progress_Repository
	 */
	private $comments_based_repository;

	/**
	 * Tables based course progress repository implementation.
	 *
	 * @var Tables_Based_Course_Progress_Repository
	 */
	private $tables_based_repository;

	/**
	 * The flag if the tables based implementation is available for use.
	 *
	 * @var bool
	 */
	private $use_tables;

	/**
	 * Aggregate_Course_Progress_Repository constructor.
	 *
	 * @internal
	 *
	 * @param Comments_Based_Course_Progress_Repository $comments_based_repository Comments based course progress repository implementation.
	 * @param Tables_Based_Course_Progress_Repository   $tables_based_repository  Tables based course progress repository implementation.
	 * @param bool                                      $use_tables  The flag if the tables based implementation is available for use.
	 */
	public function __construct( Comments_Based_Course_Progress_Repository $comments_based_repository, Tables_Based_Course_Progress_Repository $tables_based_repository, bool $use_tables ) {
		$this->comments_based_repository = $comments_based_repository;
		$this->tables_based_repository   = $tables_based_repository;
		$this->use_tables                = $use_tables;
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
		$progress = $this->comments_based_repository->create( $course_id, $user_id );
		if ( $this->use_tables ) {
			$this->tables_based_repository->create( $course_id, $user_id );
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
	 * @return Course_Progress|null The course progress or null if it does not exist.
	 */
	public function get( int $course_id, int $user_id ): ?Course_Progress {
		return $this->comments_based_repository->get( $course_id, $user_id );
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
		return $this->comments_based_repository->has( $course_id, $user_id );
	}

	/**
	 * Save course progress.
	 *
	 * @internal
	 *
	 * @param Course_Progress $course_progress The course progress.
	 */
	public function save( Course_Progress $course_progress ): void {
		$this->comments_based_repository->save( $course_progress );
		if ( $this->use_tables ) {
			$tables_based_progress = $this->tables_based_repository->get( $course_progress->get_course_id(), $course_progress->get_user_id() );
			if ( $tables_based_progress ) {
				$started_at = null;
				if ( $course_progress->get_started_at() ) {
					$started_at = new \DateTimeImmutable( '@' . $course_progress->get_started_at()->getTimestamp() );
				}
				$completed_at = null;
				if ( $course_progress->get_completed_at() ) {
					$completed_at = new \DateTimeImmutable( '@' . $course_progress->get_completed_at()->getTimestamp() );
				}

				$progress_to_save = new Course_Progress(
					$tables_based_progress->get_id(),
					$tables_based_progress->get_course_id(),
					$tables_based_progress->get_user_id(),
					$course_progress->get_status(),
					$started_at,
					$completed_at,
					$tables_based_progress->get_created_at(),
					$tables_based_progress->get_updated_at()
				);
				$this->tables_based_repository->save( $progress_to_save );
			}
		}
	}

	/**
	 * Deletes a course progress.
	 *
	 * @internal
	 *
	 * @param Course_Progress $course_progress The course progress.
	 */
	public function delete( Course_Progress $course_progress ): void {
		$this->comments_based_repository->delete( $course_progress );
		if ( $this->use_tables ) {
			$this->tables_based_repository->delete( $course_progress );
		}
	}

	/**
	 * Deletes all course progress for a course.
	 *
	 * @internal
	 *
	 * @param int $course_id The course ID.
	 */
	public function delete_for_course( int $course_id ): void {
		$this->comments_based_repository->delete_for_course( $course_id );
		if ( $this->use_tables ) {
			$this->tables_based_repository->delete_for_course( $course_id );
		}
	}

	/**
	 * Deletes all course progress for a user.
	 *
	 * @internal
	 *
	 * @param int $user_id The user ID.
	 */
	public function delete_for_user( int $user_id ): void {
		$this->comments_based_repository->delete_for_user( $user_id );
		if ( $this->use_tables ) {
			$this->tables_based_repository->delete_for_user( $user_id );
		}
	}
}
