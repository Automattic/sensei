<?php
/**
 * File containing the Lesson_Deleted_Handler class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Student_Progress\Services;

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
	 * Lesson_Deleted_Handler constructor.
	 *
	 * @param Lesson_Progress_Repository_Interface $lesson_progress_repository Lesson progress repository.
	 */
	public function __construct( Lesson_Progress_Repository_Interface $lesson_progress_repository ) {
		$this->lesson_progress_repository = $lesson_progress_repository;
	}

	/**
	 * Adds hooks to handle lesson deletion.
	 */
	public function init(): void {
		add_action( 'deleted_post', [ $this, 'handle' ], 10, 2 );
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

