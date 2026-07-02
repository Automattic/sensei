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
	 * Prefix of the user meta key that flags the welcome email as already sent for a course.
	 *
	 * The blog prefix is prepended and the course ID appended to build the full key, e.g.
	 * `wp_sensei_course_welcome_email_sent_123`, so the flag is scoped per site on multisite.
	 *
	 * @since $$next-version$$
	 *
	 * @var string
	 */
	const META_PREFIX_WELCOME_SENT = 'sensei_course_welcome_email_sent_';

	/**
	 * Build the user meta key used to flag the welcome email as sent for a course.
	 *
	 * The key is blog-prefixed so it stays scoped to the current site on multisite,
	 * matching how course enrolment results are stored.
	 *
	 * @since $$next-version$$
	 *
	 * @param int $course_id The course ID.
	 * @return string The user meta key.
	 */
	public static function get_welcome_sent_meta_key( int $course_id ): string {
		global $wpdb;

		return $wpdb->get_blog_prefix() . self::META_PREFIX_WELCOME_SENT . $course_id;
	}

	/**
	 * Initialize the email hooks.
	 *
	 * @since 4.12.0
	 *
	 * @return void
	 */
	public function init() {
		$this->maybe_add_action( 'sensei_course_enrolment_status_changed', [ $this, 'welcome_to_course_for_student' ], 10, 3 );

		// Send welcome email on the day the student gets access to the course.
		$this->maybe_add_action( 'sensei_pro_course_access_start_student_email_send', [ $this, 'welcome_to_course_for_student' ], 10, 2 );
	}

	/**
	 * Send email to student when they are enrolled in a course.
	 *
	 * @access private
	 *
	 * @param int  $student_id  The student ID.
	 * @param int  $course_id   The course ID.
	 * @param bool $is_enrolled Whether the student is enrolled in the course.
	 */
	public function welcome_to_course_for_student( $student_id, $course_id, $is_enrolled = true ) {
		if ( ! $is_enrolled ) {
			return;
		}

		$course = get_post( $course_id );
		if ( ! $course || 'publish' !== $course->post_status ) {
			return;
		}

		// Never send the welcome email to the same student for the same course more than once.
		if ( $this->has_welcome_email_been_sent( $student_id, $course_id ) ) {
			return;
		}

		// Safeguard: don't re-welcome students who already have history in the course.
		if (
			\Sensei_Utils::user_completed_course( $course_id, $student_id )
			|| \Sensei_Utils::user_started_lesson_count( $course_id, $student_id ) > 0
		) {
			return;
		}

		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		$original_course_id = apply_filters( 'wpml_original_element_id', null, $course_id, 'post_course' );

		// Prevent sending emails for the copy courses created by WPML for translations.
		if ( $original_course_id && intval( $original_course_id ) !== $course_id ) {
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

		$this->mark_welcome_email_sent( $student_id, $course_id );
	}

	/**
	 * Check whether the welcome email has already been sent to a student for a course.
	 *
	 * @since $$next-version$$
	 *
	 * @param int $student_id The student ID.
	 * @param int $course_id  The course ID.
	 * @return bool Whether the welcome email has already been sent.
	 */
	private function has_welcome_email_been_sent( int $student_id, int $course_id ): bool {
		return (bool) get_user_meta( $student_id, self::get_welcome_sent_meta_key( $course_id ), true );
	}

	/**
	 * Flag the welcome email as sent to a student for a course.
	 *
	 * @since $$next-version$$
	 *
	 * @param int $student_id The student ID.
	 * @param int $course_id  The course ID.
	 */
	private function mark_welcome_email_sent( int $student_id, int $course_id ): void {
		update_user_meta( $student_id, self::get_welcome_sent_meta_key( $course_id ), gmdate( 'Y-m-d H:i:s' ) );
	}
}
