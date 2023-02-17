<?php
/**
 * File containing the Email_Generator class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Emails;

use function Sensei_WC_Paid_Courses\E2e_Test_Data\Helpers\create_post;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Email_Generator
 *
 * @package Sensei\Internal\Emails
 */
class Email_Generator {

	/**
	 * Initialize the class and add hooks.
	 *
	 * @internal
	 */
	public function init(): void {
		add_action( 'sensei_user_course_start', [ $this, 'student_started_course_mail_to_teacher' ], 10, 2 );
	}

	/**
	 * Send email to teacher when a student starts a course.
	 *
	 * @param int $student_id The student ID.
	 * @param int $course_id  The course ID.
	 *
	 * @access private
	 */
	public function student_started_course_mail_to_teacher( $student_id, $course_id ) {

		$this->temp_create_email();

		$email_name = 'student_started_course_to_teacher';
		$course     = get_post( $course_id );

		if ( ! $course || 'publish' !== $course->post_status ) {
			return;
		}

		$teacher   = new \WP_User( $course->post_author );
		$student   = new \WP_User( $student_id );
		$recipient = stripslashes( $teacher->user_email );

		$this->send_email_action(
			$email_name,
			[
				$recipient => [
					'student:displayname' => $student->display_name,
					'course.name'         => $course->post_title,
				],
			]
		);
	}

	/**
	 * Invokes the sensei_send_html_email action.
	 *
	 * @param string $email_name    The email name.
	 * @param array  $replacements  The replacements.
	 *
	 * @access private
	 */
	private function send_email_action( $email_name, $replacements ) {
		/**
		 * Send HTML email.
		 *
		 * @since $$next-version$$
		 *
		 * @param string $email_name    The email name.
		 * @param array  $replacements  The replacements.
		 */
		do_action( 'sensei_send_html_email', $email_name, $replacements );
	}

	/**
	 * Temporarily create the email post if it doesn't exist.
	 *
	 * @access private
	 */
	private function temp_create_email() {
		$email_post = get_posts(
			[
				'post_type'      => Email_Post_Type::POST_TYPE,
				'posts_per_page' => 1,
				'meta_query'     => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					[
						'key'   => Email_Sender::EMAIL_ID_META_KEY,
						'value' => 'student_started_course_to_teacher',
					],
				],
			]
		);

		if ( $email_post ) {
			return;
		}

		$id = wp_insert_post(
			[
				'post_type'    => Email_Post_Type::POST_TYPE,
				'post_title'   => '[student:displayname] started [course.name]',
				'post_content' => '<!-- wp:post-title {"style":{"typography":{"textTransform":"capitalize","fontStyle":"normal","fontWeight":"700","fontSize":"40px"},"color":{"text":"#151515"}},"fontFamily":"inter"} /-->

<!-- wp:group {"style":{"color":{"background":"#e6e6e6","text":"#404040"},"spacing":{"padding":{"top":"40px","right":"40px","bottom":"40px","left":"40px"},"blockGap":"0px","margin":{"top":"48px","bottom":"0"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group has-text-color has-background" style="color:#404040;background-color:#e6e6e6;margin-top:48px;margin-bottom:0;padding-top:40px;padding-right:40px;padding-bottom:40px;padding-left:40px"><!-- wp:paragraph {"style":{"color":{"text":"#0b0b0b"},"typography":{"fontSize":"32px","fontStyle":"normal","fontWeight":"600"},"spacing":{"margin":{"top":"0","right":"0","bottom":"0","left":"0"}}},"fontFamily":"inter"} -->
<p class="has-text-color has-inter-font-family" style="color:#0b0b0b;margin-top:0;margin-right:0;margin-bottom:0;margin-left:0;font-size:32px;font-style:normal;font-weight:600"><strong>[student:displayname]</strong></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"style":{"color":{"text":"#030303"},"typography":{"fontSize":"16px"},"spacing":{"padding":{"top":"0","right":"0","bottom":"0","left":"0"},"margin":{"top":"24px","right":"0","bottom":"0","left":"0"}}}} -->
<p class="has-text-color" style="color:#030303;margin-top:24px;margin-right:0;margin-bottom:0;margin-left:0;padding-top:0;padding-right:0;padding-bottom:0;padding-left:0;font-size:16px"><strong>Course Name</strong></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"style":{"spacing":{"margin":{"top":"0","right":"0","bottom":"0","left":"0"}},"typography":{"fontSize":"16px"}}} -->
<p style="margin-top:0;margin-right:0;margin-bottom:0;margin-left:0;font-size:16px">[course.name]</p>
<!-- /wp:paragraph -->

<!-- wp:buttons {"style":{"spacing":{"margin":{"top":"40px"}}},"fontFamily":"inter"} -->
<div class="wp-block-buttons has-inter-font-family" style="margin-top:40px"><!-- wp:button {"style":{"typography":{"fontStyle":"normal","fontWeight":"400","textTransform":"capitalize","fontSize":"16px"},"border":{"radius":"3px"},"spacing":{"padding":{"top":"16px","right":"20px","bottom":"16px","left":"20px"}},"color":{"background":"#090909","text":"#fafafa"}},"className":"has-inter-font-family","fontFamily":"inter"} -->
<div class="wp-block-button has-custom-font-size has-inter-font-family" style="font-size:16px;font-style:normal;font-weight:400;text-transform:capitalize"><a class="wp-block-button__link has-text-color has-background wp-element-button" style="border-radius:3px;color:#fafafa;background-color:#090909;padding-top:16px;padding-right:20px;padding-bottom:16px;padding-left:20px">Manage students</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group -->',
				'post_status'  => 'publish',
			]
		);
		update_post_meta( $id, Email_Sender::EMAIL_ID_META_KEY, 'student_started_course_to_teacher' );
		update_post_meta( $id, '_sensei_email_type', 'teacher' );
	}
}
