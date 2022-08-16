<?php
/**
 * File containing the Submission_Comments_Repository class.
 *
 * @package sensei
 */

namespace Sensei\Quiz_Submission\Repositories;

use Sensei\Quiz_Submission\Models\Submission;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Submission_Comments_Repository.
 *
 * @since $$next-version$$
 */
class Submission_Comments_Repository implements Submission_Repository_Interface {
	/**
	 * Creates a new quiz submission.
	 *
	 * @param int $quiz_id The quiz ID.
	 * @param int $user_id The user ID.
	 *
	 * @return Submission The course progress.
	 */
	public function create( int $quiz_id, int $user_id ): Submission {
		// $lesson_id     = Sensei()->quiz->get_lesson_id( $quiz_id );
		// $lesson_status = Sensei_Utils::user_lesson_status( $lesson_id, $user_id );
		//
		// // TODO: Maybe throw an exception if the lesson status is missing.
		// $comment_id = $lesson_status ? $lesson_status->comment_ID : Sensei_Utils::user_start_lesson( $user_id, $lesson_id );
		//
		// return new Submission( $comment_id, $quiz_id, $user_id );
	}

	/**
	 * Get or create a new quiz submission if it doesn't exist.
	 *
	 * @param int $quiz_id The quiz ID.
	 * @param int $user_id The user ID.
	 *
	 * @return Submission The course progress.
	 */
	public function get_or_create( int $quiz_id, int $user_id ): Submission {
		$submission = $this->get( $quiz_id, $user_id );

		if ( $submission ) {
			return $submission;
		}

		return $this->create( $quiz_id, $user_id );
	}

	/**
	 * Gets a course progress.
	 *
	 * @param int $quiz_id The quiz ID.
	 * @param int $user_id The user ID.
	 *
	 * @return Submission|null The quiz submission.
	 */
	public function get( int $quiz_id, int $user_id ): ?Submission {
		// TODO: Implement get() method.
	}

	/**
	 * Checks if a course progress exists.
	 *
	 * @param int $quiz_id The quiz ID.
	 * @param int $user_id The user ID.
	 *
	 * @return bool Whether the quiz submission exists.
	 */
	public function has( int $quiz_id, int $user_id ): bool {
		// TODO: Implement has() method.
	}

	/**
	 * Save quiz submission.
	 *
	 * @param Submission $submission The quiz submission.
	 */
	public function save( Submission $submission ): void {
		// TODO: use update_lesson_status()
	}
}
