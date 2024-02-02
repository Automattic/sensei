<?php
/**
 * File containing the Table_Reading_Aggregate_Course_Progress_Repository class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Student_Progress\Course_Progress\Repositories;

use Sensei\Internal\Student_Progress\Course_Progress\Models\Comments_Based_Course_Progress;
use Sensei\Internal\Student_Progress\Course_Progress\Models\Course_Progress_Interface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Table_Reading_Aggregate_Course_Progress_Repository.
 *
 * @internal
 *
 * @since $$next_version$$
 */
class Table_Reading_Aggregate_Course_Progress_Repository implements Course_Progress_Repository_Interface {
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
	 * Constructor for the Table_Reading_Aggregate_Course_Progress_Repository class.
	 *
	 * @param Comments_Based_Course_Progress_Repository $comments_based_repository Comments based course progress repository implementation.
	 * @param Tables_Based_Course_Progress_Repository   $tables_based_repository  Tables based course progress repository implementation.
	 */
	public function __construct(
		Comments_Based_Course_Progress_Repository $comments_based_repository,
		Tables_Based_Course_Progress_Repository $tables_based_repository
	) {
		$this->comments_based_repository = $comments_based_repository;
		$this->tables_based_repository   = $tables_based_repository;
	}

	/**
	 * Creates a new course progress.
	 *
	 * @param int $course_id The course ID.
	 * @param int $user_id The user ID.
	 * @return Course_Progress_Interface The course progress.
	 */
	public function create( int $course_id, int $user_id ): Course_Progress_Interface {
		$this->comments_based_repository->create( $course_id, $user_id );
		return $this->tables_based_repository->create( $course_id, $user_id );
	}

	/**
	 * Gets the course progress for the given course and user.
	 *
	 * @param int $course_id The course ID.
	 * @param int $user_id The user ID.
	 * @return Course_Progress_Interface|null The course progress, or null if it doesn't exist.
	 */
	public function get( int $course_id, int $user_id ): ?Course_Progress_Interface {
		return $this->tables_based_repository->get( $course_id, $user_id );
	}

	/**
	 * Gets the course progress for the given course and user.
	 *
	 * @param int $course_id The course ID.
	 * @param int $user_id The user ID.
	 * @return bool Whether the course progress exists.
	 */
	public function has( int $course_id, int $user_id ): bool {
		return $this->tables_based_repository->has( $course_id, $user_id );
	}

	/**
	 * Save course progress.
	 *
	 * @param Course_Progress_Interface $course_progress The course progress.
	 */
	public function save( Course_Progress_Interface $course_progress ): void {
		$this->tables_based_repository->save( $course_progress );
		$comments_based_progress = $this->comments_based_repository->get( $course_progress->get_course_id(), $course_progress->get_user_id() );
		if ( ! $comments_based_progress ) {
			$comments_based_progress = $this->comments_based_repository->create( $course_progress->get_course_id(), $course_progress->get_user_id() );
		}
		$updated_comments_based_progress = new Comments_Based_Course_Progress(
			$comments_based_progress->get_id(),
			$comments_based_progress->get_course_id(),
			$comments_based_progress->get_user_id(),
			$course_progress->get_status(),
			$course_progress->get_started_at(),
			$course_progress->get_completed_at(),
			$course_progress->get_created_at(),
			$course_progress->get_updated_at()
		);
		$this->comments_based_repository->save( $updated_comments_based_progress );
	}

	/**
	 * Deletes a course progress.
	 *
	 * @param Course_Progress_Interface $course_progress The course progress.
	 */
	public function delete( Course_Progress_Interface $course_progress ): void {
		$this->tables_based_repository->delete( $course_progress );
		$comments_based_progress = $this->comments_based_repository->get( $course_progress->get_course_id(), $course_progress->get_user_id() );
		if ( $comments_based_progress ) {
			$this->comments_based_repository->delete( $comments_based_progress );
		}
	}

	/**
	 * Deletes all course progress.
	 *
	 * @param int $course_id The course ID.
	 */
	public function delete_for_course( int $course_id ): void {
		$this->tables_based_repository->delete_for_course( $course_id );
		$this->comments_based_repository->delete_for_course( $course_id );
	}

	/**
	 * Deletes all course progress for a user.
	 *
	 * @param int $user_id The user ID.
	 */
	public function delete_for_user( int $user_id ): void {
		$this->tables_based_repository->delete_for_user( $user_id );
		$this->comments_based_repository->delete_for_user( $user_id );
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
		return $this->tables_based_repository->find( $args );
	}
}
