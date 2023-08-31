<?php
/**
 * File containing the User_Deleted_Handler class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Student_Progress\Services;

use Sensei\Internal\Student_Progress\Course_Progress\Repositories\Course_Progress_Repository_Interface;
use Sensei\Internal\Student_Progress\Lesson_Progress\Repositories\Lesson_Progress_Repository_Interface;
use Sensei\Internal\Student_Progress\Quiz_Progress\Repositories\Quiz_Progress_Repository_Interface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles the user deletion.
 *
 * @since 4.16.1
 */
class User_Deleted_Handler {

	/**
	 * Course progress repository.
	 *
	 * @var Course_Progress_Repository_Interface
	 */
	private $course_progress_repository;

	/**
	 * Lesson progress repository.
	 *
	 * @var Lesson_Progress_Repository_Interface
	 */
	private $lesson_progress_repository;

	/**
	 * Quiz progress repository.
	 *
	 * @var Quiz_Progress_Repository_Interface
	 */
	private $quiz_progress_repository;

	/**
	 * User_Deleted_Handler constructor.
	 *
	 * @param Course_Progress_Repository_Interface $course_progress_repository Course progress repository.
	 * @param Lesson_Progress_Repository_Interface $lesson_progress_repository Lesson progress repository.
	 * @param Quiz_Progress_Repository_Interface   $quiz_progress_repository   Quiz progress repository.
	 */
	public function __construct( Course_Progress_Repository_Interface $course_progress_repository, Lesson_Progress_Repository_Interface $lesson_progress_repository, Quiz_Progress_Repository_Interface $quiz_progress_repository ) {
		$this->course_progress_repository = $course_progress_repository;
		$this->lesson_progress_repository = $lesson_progress_repository;
		$this->quiz_progress_repository   = $quiz_progress_repository;
	}

	/**
	 * Adds hooks.
	 */
	public function init() {
		add_action( 'deleted_user', [ $this, 'handle' ], 10, 1 );
	}

	/**
	 * Handles the user deletion.
	 *
	 * @param int $user_id User ID.
	 */
	public function handle( $user_id ) {
		$this->course_progress_repository->delete_for_user( $user_id );
		$this->lesson_progress_repository->delete_for_user( $user_id );
		$this->quiz_progress_repository->delete_for_user( $user_id );
	}
}

