<?php
/**
 * File containing the Aggregate_Table_Reading_Course_Progress_Repository class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Student_Progress\Course_Progress\Repositories;

use Sensei\Internal\Student_Progress\Course_Progress\Models\Course_Progress;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Aggregate_Table_Reading_Course_Progress_Repository.
 *
 * @internal
 *
 * @since $$next_version$$
 */
class Aggregate_Table_Reading_Course_Progress_Repository implements Course_Progress_Repository_Interface {
	private $comments_based_repository;
	private $tables_based_repository;

	public function __construct(
		Comments_Based_Course_Progress_Repository $comments_based_repository,
		Tables_Based_Course_Progress_Repository $tables_based_repository,
	) {
		$this->comments_based_repository = $comments_based_repository;
		$this->tables_based_repository   = $tables_based_repository;
	}

	public function create(int $course_id, int $user_id): Course_Progress {
		$this->comments_based_repository->create( $course_id, $user_id );
		return $this->tables_based_repository->create( $course_id, $user_id );
	}

	public function get(int $course_id, int $user_id): ?Course_Progress {
		return $this->tables_based_repository->get( $course_id, $user_id );
	}

	public function has(int $course_id, int $user_id): bool {
		return $this->tables_based_repository->has( $course_id, $user_id );
	}

	public function save(Course_Progress $course_progress): void {
		$this->tables_based_repository->save( $course_progress );
		$comments_based_progress = $this->comments_based_repository->get( $course_progress->get_course_id(), $course_progress->get_user_id() );
		if ( ! $comments_based_progress ) {
			$comments_based_progress = $this->comments_based_repository->create( $course_progress->get_course_id(), $course_progress->get_user_id() );
		}
		$updated_comments_based_progress = new Course_Progress(
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

	public function delete(Course_Progress $course_progress): void {
		$this->tables_based_repository->delete( $course_progress );
		$this->comments_based_repository->delete( $course_progress );
	}

	public function delete_for_course(int $course_id): void {
		$this->tables_based_repository->delete_for_course( $course_id );
		$this->comments_based_repository->delete_for_course( $course_id );
	}

	public function delete_for_user(int $user_id): void {
		$this->tables_based_repository->delete_for_user( $user_id );
		$this->comments_based_repository->delete_for_user( $user_id );
	}

}
