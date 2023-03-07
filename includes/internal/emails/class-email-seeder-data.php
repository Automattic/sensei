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
			'course_created'           => [
				'types'       => [ 'teacher' ],
				'subject'     => __( 'Course created by [teacher:displayname]', 'sensei-lms' ),
				'description' => __( 'Course Created', 'sensei-lms' ),
				'content'     => '<!-- wp:pattern {"slug":"sensei-lms/course-created"} /-->',
			],
			'quiz_graded'              => [
				'types'       => [ 'student' ],
				'subject'     => __( 'Quiz Graded - [lesson:name]', 'sensei-lms' ),
				'description' => __( 'Quiz Graded', 'sensei-lms' ),
				'content'     => '<!-- wp:pattern {"slug":"sensei-lms/quiz-graded"} /-->',
			],
			'course_completed'         => [
				'types'       => [ 'student' ],
				'subject'     => __( 'You have completed [course:name]', 'sensei-lms' ),
				'description' => __( 'Course Complete', 'sensei-lms' ),
				'content'     => '<!-- wp:pattern {"slug":"sensei-lms/course-completed"} /-->',
			],
			'student_starts_course'    => [
				'types'       => [ 'teacher' ],
				'subject'     => __( '[student:displayname] started [course:name]', 'sensei-lms' ),
				'description' => __( 'Course Started', 'sensei-lms' ),
				'content'     => '<!-- wp:pattern {"slug":"sensei-lms/student-starts-course"} /-->',
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
				'content'     => '<!-- wp:pattern {"slug":"sensei-lms/student-completes-lesson"} /-->',
			],
			'student_submits_quiz'     => [
				'types'       => [ 'teacher' ],
				'subject'     => __( '[student:displayname] has submitted a quiz', 'sensei-lms' ),
				'description' => __( 'Quiz Submitted', 'sensei-lms' ),
				'content'     => '<!-- wp:pattern {"slug":"sensei-emails/student-submits-quiz"} /-->',
			],
			'student_sends_message'    => [
				'types'       => [ 'teacher' ],
				'subject'     => __( '[student:displayname] - [subject:displaysubject]', 'sensei-lms' ),
				'description' => __( 'Student Sent Message', 'sensei-lms' ),
				'content'     => '<!-- wp:pattern {"slug":"sensei-lms/student-sends-message"} /-->',
			],
			'new_course_assigned'      => [
				'types'       => [ 'teacher' ],
				'subject'     => __( 'New Course Assigned: [course:name]', 'sensei-lms' ),
				'description' => __( 'Course Assigned', 'sensei-lms' ),
				'content'     => '<!-- wp:pattern {"slug":"sensei-lms/new-course-assigned"} /-->',
			],
			'student_message_reply'    => [
				'types'       => [ 'student' ],
				'subject'     => __( '[teacher:displayname] - [subject:displaysubject]', 'sensei-lms' ),
				'description' => __( 'Message Reply Received', 'sensei-lms' ),
				'content'     => '<!-- wp:pattern {"slug":"sensei-lms/student-message-reply"} /-->',
			],
			'teacher_message_reply'    => [
				'types'       => [ 'teacher' ],
				'subject'     => __( '[student:displayname] - [subject:displaysubject]', 'sensei-lms' ),
				'description' => __( 'Message Reply Received', 'sensei-lms' ),
				'content'     => '<!-- wp:pattern {"slug":"sensei-lms/teacher-message-reply"} /-->',
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
}
