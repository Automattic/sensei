<?php
/**
 * File containing the Course_Completed class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Emails\Generators;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Course_Completed
 *
 * @internal
 *
 * @since 4.12.0
 */
class Course_Completed extends Email_Generators_Abstract {
	/**
	 * Identifier of the email.
	 *
	 * @var string
	 */
	const IDENTIFIER_NAME = 'course_completed';

	/**
	 * Identifier used in usage tracking.
	 *
	 * @var string
	 */
	const USAGE_TRACKING_TYPE = 'learner-completed-course';

	/**
	 * Initialize the email hooks.
	 *
	 * @access public
	 * @since 4.12.0
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'sensei_course_status_updated', [ $this, 'completed_course_mail_to_student' ], 10, 3 );
	}

	/**
	 * Send email to student when a course is completed.
	 *
	 * @param string $status     The status.
	 * @param int    $student_id The learner ID.
	 * @param int    $course_id  The course ID.
	 *
	 * @access private
	 */
	public function completed_course_mail_to_student( $status = 'in-progress', $student_id = 0, $course_id = 0 ) {

		if ( 'complete' !== $status || ! \Sensei_Course::is_user_enrolled( $course_id, $student_id ) ) {
			return;
		}

		$student   = new \WP_User( $student_id );
		$recipient = stripslashes( $student->user_email );

		$this->send_email_action(
			[
				$recipient => [
					'student:id'          => $student_id,
					'student:displayname' => $student->display_name,
					'course:id'           => $course_id,
					'course:name'         => get_the_title( $course_id ),
					'completed:url'       => esc_url(
						\Sensei_Course::get_course_completed_page_url( $course_id ) ?? get_permalink( $course_id )
					),
				],
			]
		);
	}
}
