<?php
/**
 * File containing the Student_Completes_Course class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Emails\Generators;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Student_Completes_Course
 *
 * @internal
 *
 * @since $$next-version$$
 */
class Course_Created extends Email_Generators_Abstract {
	/**
	 * Identifier of the email.
	 *
	 * @var string
	 */
	const IDENTIFIER_NAME = 'course_created';

	/**
	 * Initialize the email hooks.
	 *
	 * @access public
	 * @since $$next-version$$
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'transition_post_status', [ $this, 'course_created_to_admin' ], 10, 3 );
	}

	/**
	 * Send email to admin when a teacher created a course.
	 *
	 * @param string   $new_status New status.
	 * @param string   $old_status Old status.
	 * @param \WP_Post $post       The post.
	 *
	 * @access private
	 */
	public function course_created_to_admin( $new_status, $old_status, $post ) {
		$course_id = $post->ID;

		if ( 'publish' === $old_status || 'course' !== get_post_type( $course_id ) || 'auto-draft' === $new_status
			|| 'trash' === $new_status || 'draft' === $new_status ) {
			return;
		}

		$course     = get_post( $course_id );
		$teacher_id = $course->post_author;
		$teacher    = new \WP_User( $course->post_author );
		$recipient  = get_option( 'admin_email', true );

		// Don't send if the course is created by admin.
		if ( $recipient === $teacher->user_email || current_user_can( 'manage_options' ) ) {
			return;
		}

		$manage_url = esc_url(
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
					'teacher:id'          => (int) $teacher_id,
					'teacher:displayname' => $teacher->display_name,
					'course:id'           => (int) $course_id,
					'course:name'         => get_the_title( $course_id ),
					'manage:course'       => $manage_url,
				],
			]
		);
	}
}
