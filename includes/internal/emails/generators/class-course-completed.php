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
		$this->maybe_add_action( 'sensei_course_status_updated', [ $this, 'completed_course_mail_to_student' ], 10, 5 );
	}

	/**
	 * Send email to student when a course is completed.
	 *
	 * @param string      $status     The status.
	 * @param int         $student_id The learner ID.
	 * @param int         $course_id  The course ID.
	 * @param int         $comment_id The comment ID.
	 * @param string|null $previous_status The previous status.
	 *
	 * @access private
	 */
	public function completed_course_mail_to_student( $status = 'in-progress', $student_id = 0, $course_id = 0, $comment_id = 0, $previous_status = null ) {

		if ( $this->should_skip_sending( $status, $previous_status, $course_id, $student_id ) ) {
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
					'course:name'         => html_entity_decode( get_the_title( $course_id ) ),
					'completed:url'       => esc_url(
						\Sensei_Course::get_course_completed_page_url( $course_id ) ?? get_permalink( $course_id )
					),
				],
			]
		);
	}

	/**
	 * Returns true if the email should be skipped.
	 *
	 * @param string      $status          The status.
	 * @param string|null $previous_status The previous status.
	 * @param int         $course_id       The course ID.
	 * @param int         $student_id      The learner ID.
	 * @return bool
	 */
	private function should_skip_sending( $status, $previous_status, $course_id, $student_id ) {
		// Skip sending if the status is not complete or if the status was already complete.
		if ( 'complete' !== $status || 'complete' === $previous_status ) {
			return true;
		}

		// Skip sending if the user is not enrolled in the course.
		return ! \Sensei_Course::is_user_enrolled( $course_id, $student_id );
	}
}
