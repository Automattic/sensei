<?php
/**
 * File containing the Email_Seeder_Data class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Emails;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Email_Seeder_Data class.
 *
 * Contains all available email data.
 *
 * @internal
 *
 * @since $$next-version$$
 */
class Email_Seeder_Data {

	/**
	 * Email data.
	 *
	 * @var array
	 */
	private $emails;

	/**
	 * Get all available emails with corresponding data.
	 *
	 * @internal
	 *
	 * @return array
	 */
	public function get_email_data(): array {
		if ( ! empty( $this->emails ) ) {
			return $this->emails;
		}

		$this->emails = [
			'quiz_graded'              => [
				'types'       => [ 'student' ],
				'subject'     => __( 'Quiz Qraded - [lesson:name]', 'sensei-lms' ),
				'description' => __( 'Quiz Graded', 'sensei-lms' ),
				'content'     => '<!-- wp:pattern {"slug":"sensei-emails/footer"} /-->',
			],
			'course_completed'         => [
				'types'       => [ 'student' ],
				'subject'     => __( '[student:displayname] completed [course:name]', 'sensei-lms' ),
				'description' => __( 'Course Complete', 'sensei-lms' ),
				'content'     => '<!-- wp:pattern {"slug":"sensei-emails/footer"} /-->',
			],
			'student_starts_course'    => [
				'types'       => [ 'teacher' ],
				'subject'     => __( '[student:displayname] started [course:name]', 'sensei-lms' ),
				'description' => __( 'Course Started', 'sensei-lms' ),
				'content'     => $this->student_starts_course_content(),
			],
			'student_completes_course' => [
				'types'       => [ 'teacher' ],
				'subject'     => __( '[student:displayname] completed [course:name]', 'sensei-lms' ),
				'description' => __( 'Course Completed', 'sensei-lms' ),
				'content'     => '<!-- wp:pattern {"slug":"sensei-lms/student-completes-course"} /-->',
			],
			'student_completes_lesson' => [
				'types'       => [ 'teacher' ],
				'subject'     => __( '[student:displayname] completed [lesson:name]', 'sensei-lms' ),
				'description' => __( 'Lesson Completed', 'sensei-lms' ),
				'content'     => '<!-- wp:pattern {"slug":"sensei-emails/footer"} /-->',
			],
			'student_submits_quiz'     => [
				'types'       => [ 'teacher' ],
				'subject'     => __( '[student:displayname] has submitted a quiz', 'sensei-lms' ),
				'description' => __( 'Quiz Submitted', 'sensei-lms' ),
				'content'     => '<!-- wp:pattern {"slug":"sensei-emails/footer"} /-->',
			],
			'student_sends_message'    => [
				'types'       => [ 'teacher' ],
				'subject'     => __( '[student:displayname] - [subject:displaysubject]', 'sensei-lms' ),
				'description' => __( 'Student Sent Message', 'sensei-lms' ),
				'content'     => '<!-- wp:pattern {"slug":"sensei-emails/footer"} /-->',
			],
			'new_course_assigned'      => [
				'types'       => [ 'teacher' ],
				'subject'     => __( 'New Course Assigned: [course:name]', 'sensei-lms' ),
				'description' => __( 'Course Assigned', 'sensei-lms' ),
				'content'     => '<!-- wp:pattern {"slug":"sensei-emails/footer"} /-->',
			],
			'new_message_reply'        => [
				'types'       => [ 'student', 'teacher' ],
				'subject'     => __( '[author:displayname] - [subject:displaysubject]', 'sensei-lms' ),
				'description' => __( 'Message Reply Received', 'sensei-lms' ),
				'content'     => '<!-- wp:pattern {"slug":"sensei-emails/footer"} /-->',
			],
			'content_drip'             => [
				'types'       => [ 'student' ],
				'subject'     => __( 'Get ready - [lesson:name] - starts [date:dtext]', 'sensei-lms' ),
				'description' => __( 'Lessons Available (Content Drip)', 'sensei-lms' ),
				'content'     => '<!-- wp:pattern {"slug":"sensei-emails/footer"} /-->',
			],
			'course_expiration_today'  => [
				'types'       => [ 'student' ],
				'subject'     => __( '[course:name] expires [date:dtext]!', 'sensei-lms' ),
				'description' => __( 'Course Expiration - Today', 'sensei-lms' ),
				'content'     => '<!-- wp:pattern {"slug":"sensei-emails/footer"} /-->',
			],
			'course_expiration_3_days' => [
				'types'       => [ 'student' ],
				'subject'     => __( '[course:name] expires [date:dtext]!', 'sensei-lms' ),
				'description' => __( 'Course Expiration - in 3 days', 'sensei-lms' ),
				'content'     => '<!-- wp:pattern {"slug":"sensei-emails/footer"} /-->',
			],
			'course_expiration_7_days' => [
				'types'       => [ 'student' ],
				'subject'     => __( '[course:name] expires [date:dtext]!', 'sensei-lms' ),
				'description' => __( 'Course Expiration - in 7 days', 'sensei-lms' ),
				'content'     => '<!-- wp:pattern {"slug":"sensei-emails/footer"} /-->',
			],
		];

		return $this->emails;
	}

	/**
	 * Get the content for the student_starts_course email.
	 *
	 * @return string
	 */
	private function student_starts_course_content(): string {
		return '<!-- wp:post-title {"style":{"typography":{"textTransform":"capitalize","fontStyle":"normal","fontWeight":"700","fontSize":"40px"},"color":{"text":"#151515"}},"fontFamily":"inter"} /-->
<!-- wp:group {"style":{"color":{"background":"#e6e6e6","text":"#404040"},"spacing":{"padding":{"top":"40px","right":"40px","bottom":"40px","left":"40px"},"blockGap":"0px","margin":{"top":"48px","bottom":"0"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group has-text-color has-background" style="color:#404040;background-color:#e6e6e6;margin-top:48px;margin-bottom:0;padding-top:40px;padding-right:40px;padding-bottom:40px;padding-left:40px"><!-- wp:paragraph {"style":{"color":{"text":"#0b0b0b"},"typography":{"fontSize":"32px","fontStyle":"normal","fontWeight":"600"},"spacing":{"margin":{"top":"0","right":"0","bottom":"0","left":"0"}}},"fontFamily":"inter"} -->
<p class="has-text-color has-inter-font-family" style="color:#0b0b0b;margin-top:0;margin-right:0;margin-bottom:0;margin-left:0;font-size:32px;font-style:normal;font-weight:600"><strong>[student:displayname]</strong></p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"style":{"color":{"text":"#030303"},"typography":{"fontSize":"16px"},"spacing":{"padding":{"top":"0","right":"0","bottom":"0","left":"0"},"margin":{"top":"24px","right":"0","bottom":"0","left":"0"}}}} -->
<p class="has-text-color" style="color:#030303;margin-top:24px;margin-right:0;margin-bottom:0;margin-left:0;padding-top:0;padding-right:0;padding-bottom:0;padding-left:0;font-size:16px"><strong>Course Name</strong></p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"style":{"spacing":{"margin":{"top":"0","right":"0","bottom":"0","left":"0"}},"typography":{"fontSize":"16px"}}} -->
<p style="margin-top:0;margin-right:0;margin-bottom:0;margin-left:0;font-size:16px">[course:name]</p>
<!-- /wp:paragraph -->
<!-- wp:buttons {"style":{"spacing":{"margin":{"top":"40px"}}},"fontFamily":"inter"} -->
<div class="wp-block-buttons has-inter-font-family" style="margin-top:40px"><!-- wp:button {"style":{"typography":{"fontStyle":"normal","fontWeight":"400","textTransform":"capitalize","fontSize":"16px"},"border":{"radius":"3px"},"spacing":{"padding":{"top":"16px","right":"20px","bottom":"16px","left":"20px"}},"color":{"background":"#090909","text":"#fafafa"}},"className":"has-inter-font-family","fontFamily":"inter"} -->
<div class="wp-block-button has-custom-font-size has-inter-font-family" style="font-size:16px;font-style:normal;font-weight:400;text-transform:capitalize"><a class="wp-block-button__link has-text-color has-background wp-element-button" style="border-radius:3px;color:#fafafa;background-color:#090909;padding-top:16px;padding-right:20px;padding-bottom:16px;padding-left:20px">Manage students</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group -->';
	}
}
