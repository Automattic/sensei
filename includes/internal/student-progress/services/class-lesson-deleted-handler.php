<?php
/**
 * File containing the Lesson_Deleted_Handler class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Student_Progress\Services;

use Sensei\Internal\Persistence_Exception;
use Sensei\Internal\Quiz_Submission\Submission\Repositories\Submission_Repository_Interface;
use Sensei\Internal\Student_Progress\Lesson_Progress\Repositories\Lesson_Progress_Repository_Interface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Lesson_Deleted_Handler.
 *
 * @internal
 *
 * @since 4.16.1
 */
class Lesson_Deleted_Handler {
	/**
	 * Lesson_Progress_Repository_Interface instance.
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
	 * Lesson_Deleted_Handler constructor.
	 *
	 * @param Lesson_Progress_Repository_Interface $lesson_progress_repository Lesson progress repository.
	 * @param Submission_Repository_Interface      $quiz_submission_repository Quiz submission repository.
	 */
	public function __construct( Lesson_Progress_Repository_Interface $lesson_progress_repository, Submission_Repository_Interface $quiz_submission_repository ) {
		$this->lesson_progress_repository = $lesson_progress_repository;
		$this->quiz_submission_repository = $quiz_submission_repository;
	}

	/**
	 * Adds hooks to handle lesson deletion.
	 */
	public function init(): void {
		add_filter( 'pre_delete_post', array( $this, 'delete_quiz_data' ), 10, 2 );
		add_action( 'deleted_post', [ $this, 'handle' ], 10, 2 );
	}

	/**
	 * Clean quiz data before WordPress removes the lesson and its quiz.
	 *
	 * @since 4.28.0
	 *
	 * @param mixed    $delete Whether an earlier filter has handled deletion, or null to continue.
	 * @param \WP_Post $post   The post being permanently deleted.
	 * @return mixed False if quiz cleanup failed; otherwise the original filter value.
	 */
	public function delete_quiz_data( $delete, $post ) {
		if ( null !== $delete || 'lesson' !== $post->post_type ) {
			return $delete;
		}

		$quiz_id = (int) Sensei()->lesson->lesson_quizzes( (int) $post->ID );
		if ( ! $quiz_id ) {
			return $delete;
		}

		try {
			$this->quiz_submission_repository->delete_for_quiz( $quiz_id );
		} catch ( Persistence_Exception $exception ) {
			$exception->log( 'deleting a lesson' );
			return false;
		}

		return $delete;
	}

	/**
	 * Handles the lesson deletion.
	 *
	 * @param int     $lesson_id The lesson ID.
	 * @param WP_Post $post The post object.
	 */
	public function handle( int $lesson_id, $post ): void {
		if ( ! $post || 'lesson' !== $post->post_type ) {
			return;
		}
		$this->lesson_progress_repository->delete_for_lesson( $lesson_id );
	}
}

