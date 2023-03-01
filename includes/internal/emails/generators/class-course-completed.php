<?php
/**
 * File containing the Course_Completed class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Emails;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Course_Completed
 *
 * @internal
 *
 * @since $$next-version$$
 */
class Course_Completed extends Email_Generators_Abstract {
	/**
	 * Identifier of the email.
	 *
	 * @var string
	 */
	const IDENTIFIER_NAME = 'course_completed';

	/**
	 * Initialize the email hooks.
	 *
	 * @access public
	 * @since $$next-version$$
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

		$student    = new \WP_User( $student_id );
		$recipient  = stripslashes( $student->user_email );
		$result_url = \Sensei()->course_results->get_permalink( $course_id ) ?? '';

		$this->send_email_action(
			[
				$recipient => [
					'student:id'          => $student_id,
					'student:displayname' => $student->display_name,
					'course:id'           => $course_id,
					'course:name'         => get_the_title( $course_id ),
					'certificate:url'     => esc_url( \Sensei_Course::get_course_completed_page_url( $course_id ) ?? '' ),
					'results:url'         => esc_url( $result_url ),
				],
			]
		);
	}
}
