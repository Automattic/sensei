<?php
/**
 * File containing the Lesson_Progress_Removal class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Student_Progress\Services;

use Sensei\Internal\Persistence_Exception;
use Sensei\Internal\Quiz_Submission\Submission\Repositories\Submission_Repository_Interface;
use Sensei\Internal\Student_Progress\Lesson_Progress\Repositories\Lesson_Progress_Repository_Interface;
use Sensei\Internal\Student_Progress\Quiz_Progress\Repositories\Tables_Based_Quiz_Progress_Repository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Remove a student's lesson progress and associated quiz data.
 *
 * @internal
 * @since 4.28.0
 */
class Lesson_Progress_Removal {
	/**
	 * Lesson progress repository.
	 *
	 * @var Lesson_Progress_Repository_Interface
	 */
	private $lesson_progress_repository;

	/**
	 * Quiz submission repository.
	 *
	 * @var Submission_Repository_Interface
	 */
	private $quiz_submission_repository;

	/**
	 * Repository for quiz progress stored separately from lesson progress.
	 *
	 * @var Tables_Based_Quiz_Progress_Repository|null
	 */
	private $tables_based_quiz_progress_repository;

	/**
	 * Constructor.
	 *
	 * @param Lesson_Progress_Repository_Interface       $lesson_progress_repository            Lesson progress repository.
	 * @param Submission_Repository_Interface            $quiz_submission_repository             Quiz submission repository.
	 * @param Tables_Based_Quiz_Progress_Repository|null $tables_based_quiz_progress_repository Tables repository when synchronization is enabled.
	 */
	public function __construct(
		Lesson_Progress_Repository_Interface $lesson_progress_repository,
		Submission_Repository_Interface $quiz_submission_repository,
		?Tables_Based_Quiz_Progress_Repository $tables_based_quiz_progress_repository
	) {
		$this->lesson_progress_repository            = $lesson_progress_repository;
		$this->quiz_submission_repository            = $quiz_submission_repository;
		$this->tables_based_quiz_progress_repository = $tables_based_quiz_progress_repository;
	}

	/**
	 * Delete a student's lesson progress and any remaining quiz data.
	 *
	 * @since 4.28.0
	 *
	 * @param int $lesson_id The lesson ID.
	 * @param int $user_id   The student ID.
	 * @param int $quiz_id   The associated quiz ID, or zero if there is no quiz.
	 * @throws Persistence_Exception If required data cannot be deleted.
	 */
	public function remove_for_student( int $lesson_id, int $user_id, int $quiz_id ): void {
		if ( $quiz_id ) {
			$this->quiz_submission_repository->delete_for_student( $quiz_id, $user_id );

			if ( $this->tables_based_quiz_progress_repository ) {
				$this->tables_based_quiz_progress_repository->delete_for_student( $quiz_id, $user_id );
			}
		}

		// Delete lesson progress last so the lesson comment stays available if quiz cleanup fails.
		$lesson_progress    = $this->lesson_progress_repository->get( $lesson_id, $user_id );
		$progress_lesson_id = $lesson_progress ? $lesson_progress->get_lesson_id() : $lesson_id;
		$this->lesson_progress_repository->delete_for_student( $progress_lesson_id, $user_id );
	}
}
