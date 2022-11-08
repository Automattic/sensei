<?php
/**
 * File containing the class Course_Deleted_Handler.
 * @package sensei-lms
 */

namespace Sensei\Internal\Student_Progress\Services;

use Sensei\Internal\Student_Progress\Course_Progress\Repositories\Course_Progress_Repository_Interface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles the deletion of a course.
 */
class Course_Deleted_Handler {


	public function __construct( Course_Progress_Repository_Interface $course_progress_repository ) {
		$this->course_progress_repository = $course_progress_repository;
	}

	public function init() {
		add_action( 'deleted_post', [ $this, 'handle' ], 10, 2 );
	}

	public function handle( $course_id, $course ) {
		if ( ! $course || 'course' !== $course->post_type ) {
			return;
		}

		$this->course_progress_repository->delete_for_course( $course_id );
	}
}

