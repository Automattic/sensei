<?php
/**
 * File containing the Course_Welcome class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Emails\Generators;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Course_Welcome
 *
 * @internal
 *
 * @since 4.12.0
 */
class Course_Welcome extends Email_Generators_Abstract {
	/**
	 * Identifier of the email.
	 *
	 * @var string
	 */
	const IDENTIFIER_NAME = 'course_welcome';

	/**
	 * Identifier used in usage tracking.
	 *
	 * @var string
	 */
	const USAGE_TRACKING_TYPE = 'learner-welcome-course';

	/**
	 * Initialize the email hooks.
	 *
	 * @since 4.12.0
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'sensei_user_course_start', [ $this, 'welcome_to_course_for_student' ], 10, 2 );
	}

	/**
	 * Send email to student when they are enrolled in a course.
	 *
	 * @access private
	 *
	 * @param int $student_id The student ID.
	 * @param int $course_id  The course ID.
	 */
	public function welcome_to_course_for_student( $student_id, $course_id ) {
		$course = get_post( $course_id );
		if ( ! $course || 'publish' !== $course->post_status ) {
			return;
		}

		$student    = new \WP_User( $student_id );
		$teacher_id = $course->post_author;
		$teacher    = new \WP_User( $teacher_id );
		$recipient  = stripslashes( $student->user_email );
		$course_url = get_permalink( $course_id );

		$this->send_email_action(
			[
				$recipient => [
					'teacher:id'          => $teacher->ID,
					'teacher:displayname' => $teacher->display_name,
					'student:id'          => $student->ID,
					'student:displayname' => $student->display_name,
					'course:id'           => $course->ID,
					'course:name'         => $course->post_title,
					'course:url'          => $course_url,
				],
			]
		);
	}
}
