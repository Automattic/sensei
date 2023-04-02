<?php
/**
 * File containing the New_Course_Assigned class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Emails\Generators;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class New_Course_Assigned
 *
 * @internal
 *
 * @since 4.12.0
 */
class New_Course_Assigned extends Email_Generators_Abstract {
	/**
	 * Identifier of the email.
	 *
	 * @var string
	 */
	const IDENTIFIER_NAME = 'new_course_assigned';

	/**
	 * Identifier used in usage tracking.
	 *
	 * @var string
	 */
	const USAGE_TRACKING_TYPE = 'teacher-assigned-course';

	/**
	 * Initialize the email hooks.
	 *
	 * @access public
	 * @since 4.12.0
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'sensei_course_new_teacher_assigned', [ $this, 'course_new_teacher_assigned_email' ], 10, 2 );
	}

	/**
	 * Send email to teacher when a course is assigned to them.
	 *
	 * @param int $teacher_id Teacher ID.
	 * @param int $course_id  Course ID.
	 *
	 * @access private
	 */
	public function course_new_teacher_assigned_email( $teacher_id, $course_id ) {

		if ( 'course' !== get_post_type( $course_id ) || ! get_userdata( $teacher_id ) ) {
			return;
		}

		// If new user is the same as the current logged-in user, they don't need an email.
		if ( get_current_user_id() === $teacher_id ) {
			return;
		}

		$teacher   = new \WP_User( $teacher_id );
		$recipient = stripslashes( $teacher->user_email );

		// Course edit link.
		$edit_link = esc_url(
			add_query_arg(
				array(
					'post'   => $course_id,
					'action' => 'edit',
				),
				admin_url( 'post.php' )
			)
		);

		$this->send_email_action(
			[
				$recipient => [
					'teacher:displayname' => $teacher->display_name,
					'course:name'         => get_the_title( $course_id ),
					'editcourse:url'      => esc_url( $edit_link ),
				],
			]
		);
	}
}
