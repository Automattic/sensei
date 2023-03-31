<?php
/**
 * File containing the Student_Completes_Lesson class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Emails\Generators;

use Sensei\Internal\Emails\Email_Repository;
use Sensei\Internal\Student_Progress\Lesson_Progress\Models\Lesson_Progress;
use Sensei\Internal\Student_Progress\Lesson_Progress\Repositories\Lesson_Progress_Repository_Interface;
use Sensei\Internal\Student_Progress\Quiz_Progress\Models\Quiz_Progress;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Student_Completes_Lesson
 *
 * @internal
 *
 * @since 4.12.0
 */
class Student_Completes_Lesson extends Email_Generators_Abstract {
	/**
	 * Lesson progress repository.
	 *
	 * @var Lesson_Progress_Repository_Interface
	 */
	private $lesson_progress_repository;

	/**
	 * Identifier of the email.
	 *
	 * @var string
	 */
	const IDENTIFIER_NAME = 'student_completes_lesson';

	/**
	 * Identifier used in usage tracking.
	 *
	 * @var string
	 */
	const USAGE_TRACKING_TYPE = 'teacher-completed-lesson';

	/**
	 * Email_Generators_Abstract constructor.
	 *
	 * @param Email_Repository                     $repository Email_Repository instance.
	 * @param Lesson_Progress_Repository_Interface $lesson_progress_repository Lesson progress repository.
	 *
	 * @since 4.12.0
	 *
	 * @internal
	 */
	public function __construct(
		Email_Repository $repository,
		Lesson_Progress_Repository_Interface $lesson_progress_repository
	) {
		$this->repository                 = $repository;
		$this->lesson_progress_repository = $lesson_progress_repository;
	}

	/**
	 * Initialize the email hooks.
	 *
	 * @access public
	 * @since 4.12.0
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'sensei_user_lesson_end', [ $this, 'student_completed_lesson_mail_to_teacher' ], 10, 2 );
	}

	/**
	 * Send email to teacher when a student completes a lesson.
	 *
	 * @access private
	 *
	 * @param int $student_id The student ID.
	 * @param int $lesson_id  The lesson ID.
	 */
	public function student_completed_lesson_mail_to_teacher( $student_id, $lesson_id ) {
		$lesson_progress = $this->lesson_progress_repository->get( $lesson_id, $student_id );
		if ( ! $lesson_progress || ! in_array( $lesson_progress->get_status(), [ Lesson_Progress::STATUS_COMPLETE, Quiz_Progress::STATUS_PASSED ], true ) ) {
			return;
		}

		$course_id  = \Sensei()->lesson->get_course_id( $lesson_id );
		$teacher_id = get_post_field( 'post_author', $lesson_id, 'raw' );
		$teacher    = new \WP_User( $teacher_id );
		$recipient  = stripslashes( $teacher->user_email );
		$student    = new \WP_User( $student_id );
		$manage_url = esc_url(
			add_query_arg(
				array(
					'page'      => 'sensei_learners',
					'course_id' => $course_id,
					'lesson_id' => $lesson_id,
					'view'      => 'learners',
				),
				admin_url( 'admin.php' )
			)
		);

		$this->send_email_action(
			[
				$recipient => [
					'student:id'          => (int) $student_id,
					'student:displayname' => $student->display_name,
					'course:id'           => (int) $course_id,
					'course:name'         => get_the_title( $course_id ),
					'lesson:id'           => (int) $lesson_id,
					'lesson:name'         => get_the_title( $lesson_id ),
					'manage:students'     => $manage_url,
				],
			]
		);
	}
}
