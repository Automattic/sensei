<?php
/**
 * File containing the Quiz_Deleted_Handler class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Student_Progress\Services;

use Sensei\Internal\Student_Progress\Quiz_Progress\Repositories\Quiz_Progress_Repository_Interface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Quiz_Deleted_Handler.
 *
 * @internal
 *
 * @since 4.16.1
 */
class Quiz_Deleted_Handler {
	/**
	 * Quiz_Progress_Repository_Interface instance.
	 *
	 * @var Quiz_Progress_Repository_Interface
	 */
	private $quiz_progress_repository;

	/**
	 * Quiz_Deleted_Handler constructor.
	 *
	 * @param Quiz_Progress_Repository_Interface $quiz_progress_repository Quiz progress repository.
	 */
	public function __construct( Quiz_Progress_Repository_Interface $quiz_progress_repository ) {
		$this->quiz_progress_repository = $quiz_progress_repository;
	}

	/**
	 * Adds action hook for quiz deletion.
	 */
	public function init(): void {
		add_action( 'deleted_post', [ $this, 'handle' ], 10, 2 );
	}

	/**
	 * Handles the quiz deletion.
	 *
	 * @param int     $quiz_id The quiz ID.
	 * @param WP_Post $post The post object.
	 */
	public function handle( int $quiz_id, $post ): void {
		if ( ! $post || 'quiz' !== $post->post_type ) {
			return;
		}
		$this->quiz_progress_repository->delete_for_quiz( $quiz_id );
	}
}

