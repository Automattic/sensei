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
	 * @since 4.26.2
	 *
	 * @var string
	 */
	const META_PREFIX_WELCOME_SENT = 'sensei_course_welcome_email_sent_';

	/**
	 * Welcome-sent flag value written by the upgrade backfill, not a real dispatch. A real dispatch stores a timestamp, so integrations can tell the two apart.
	 *
	 * @since 4.26.2
	 *
	 * @var string
	 */
	const WELCOME_ASSUMED = 'assumed';

	/**
	 * Build the user meta key used to flag the welcome email as sent for a course.
	 *
	 * The key is blog-prefixed so it stays scoped to the current site on multisite,
	 * matching how course enrolment results are stored.
	 *
	 * @since 4.26.2
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
		$this->maybe_add_action( 'sensei_course_enrolment_status_changed', array( $this, 'welcome_to_course_for_student' ), 10, 3 );

		// Send welcome email on the day the student gets access to the course.
		$this->maybe_add_action( 'sensei_pro_course_access_start_student_email_send', array( $this, 'welcome_to_course_on_access_start' ), 10, 2 );

		// Flag the welcome email as sent only once it has actually been dispatched.
		$this->maybe_add_action( 'sensei_email_sent', array( $this, 'mark_welcome_email_sent_on_dispatch' ), 10, 3 );
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

		$this->maybe_send_welcome_email( (int) $student_id, (int) $course_id, true );
	}

	/**
	 * Send email to student on the day their access period starts.
	 *
	 * Access had not started until now, so lesson progress cannot mean the student was
	 * previously active — opening a locked lesson is enough to record it. Only completing
	 * the course proves earlier access.
	 *
	 * @access private
	 *
	 * @since 4.26.2
	 *
	 * @param int $student_id The student ID.
	 * @param int $course_id  The course ID.
	 */
	public function welcome_to_course_on_access_start( $student_id, $course_id ) {
		$this->maybe_send_welcome_email( (int) $student_id, (int) $course_id, false );
	}

	/**
	 * Send the welcome email to a student for a course, if it is warranted.
	 *
	 * @since 4.26.2
	 *
	 * @param int  $student_id            The student ID.
	 * @param int  $course_id             The course ID.
	 * @param bool $started_lessons_count Whether started lessons disqualify the student.
	 */
	private function maybe_send_welcome_email( int $student_id, int $course_id, bool $started_lessons_count ): void {
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
			|| ( $started_lessons_count && \Sensei_Utils::user_started_lesson_count( $course_id, $student_id ) > 0 )
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
			array(
				$recipient => array(
					'teacher:id'          => $teacher->ID,
					'teacher:displayname' => $teacher->display_name,
					'student:id'          => $student->ID,
					'student:displayname' => $student->display_name,
					'course:id'           => $course->ID,
					'course:name'         => $course->post_title,
					'course:url'          => $course_url,
				),
			)
		);
	}

	/**
	 * Flag the welcome email as sent, hooked on `sensei_email_sent`.
	 *
	 * That hook only fires on actual dispatch, so a suppressed email (e.g. course
	 * access has not started yet) is not flagged and can still be delivered later.
	 *
	 * @internal
	 *
	 * @access private
	 *
	 * @since 4.26.2
	 *
	 * @param string $email_name  The email identifier.
	 * @param string $recipient   The recipient email address.
	 * @param array  $replacement The per-recipient replacement values.
	 */
	public function mark_welcome_email_sent_on_dispatch( $email_name, $recipient, $replacement ) {
		if ( self::IDENTIFIER_NAME !== $email_name ) {
			return;
		}

		$student_id = isset( $replacement['student:id'] ) ? (int) $replacement['student:id'] : 0;
		$course_id  = isset( $replacement['course:id'] ) ? (int) $replacement['course:id'] : 0;

		if ( $student_id && $course_id ) {
			$this->mark_welcome_email_sent( $student_id, $course_id );
		}
	}

	/**
	 * Check whether the welcome email has already been sent to a student for a course.
	 *
	 * @since 4.26.2
	 *
	 * @param int $student_id The student ID.
	 * @param int $course_id  The course ID.
	 * @return bool Whether the welcome email has already been sent.
	 */
	private function has_welcome_email_been_sent( int $student_id, int $course_id ): bool {
		return metadata_exists( 'user', $student_id, self::get_welcome_sent_meta_key( $course_id ) );
	}

	/**
	 * Flag the welcome email as sent to a student for a course.
	 *
	 * @since 4.26.2
	 *
	 * @param int $student_id The student ID.
	 * @param int $course_id  The course ID.
	 */
	private function mark_welcome_email_sent( int $student_id, int $course_id ): void {
		update_user_meta( $student_id, self::get_welcome_sent_meta_key( $course_id ), gmdate( 'Y-m-d H:i:s' ) );
	}
}
