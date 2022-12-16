<?php
/**
 * File containing Sensei_Course_Theme_Lesson class.
 *
 * @package sensei-lms
 * @since 3.15.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Sensei_Course_Theme_Lesson class.
 *
 * @since 3.15.0
 */
class Sensei_Course_Theme_Lesson {
	/**
	 * Instance of class.
	 *
	 * @var self
	 */
	private static $instance;

	/**
	 * Sensei_Course_Theme_Lesson constructor. Prevents other instances from being created outside of `self::instance()`.
	 */
	private function __construct() {}

	/**
	 * Fetches an instance of the class.
	 *
	 * @return self
	 */
	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Initializes the class.
	 */
	public function init() {
		$post_type = get_post_type();
		if ( 'lesson' === $post_type || 'quiz' === $post_type ) {
			$this->maybe_add_lesson_prerequisite_notice();
		} elseif ( 'lesson' !== get_post_type() ) {
			return;
		}

		$this->maybe_add_quiz_results_notice();
		$this->maybe_add_not_enrolled_notice();
	}

	/**
	 * Intercepts the notices and prints them out later via 'sensei-lms/course-theme-notices' block.
	 *
	 * @param array $notice The notice to intercept.
	 */
	public static function intercept_notice( array $notice ) {
		// Do nothing if it is not lesson or quiz post.
		$post_type = get_post_type();
		if ( ! in_array( $post_type, [ 'lesson', 'quiz' ], true ) ) {
			return $notice;
		}

		// Do nothing if learning mode is not used.
		$course_id = \Sensei_Utils::get_current_course();
		if ( ! $course_id || ! Sensei_Course_Theme_Option::has_learning_mode_enabled( $course_id ) ) {
			return $notice;
		}

		// Add the notice to lesson notices.
		$notices = \Sensei_Context_Notices::instance( 'course_theme_lesson_regular' );
		$notices->add_notice( $notice['content'], $notice['content'], null, [], $notice['type'] );

		return null;
	}

	/**
	 * Maybe add lesson quiz results notice.
	 */
	private function maybe_add_quiz_results_notice() {
		$lesson_id = \Sensei_Utils::get_current_lesson();
		$user_id   = get_current_user_id();

		if ( empty( $lesson_id ) || empty( $user_id ) ) {
			return;
		}

		$quiz_permalink = Sensei()->lesson->get_quiz_permalink( $lesson_id );
		// Don't show notice if this is the quiz page.
		if ( empty( $quiz_permalink ) || get_permalink() === $quiz_permalink ) {
			return;
		}

		$notices       = \Sensei_Context_Notices::instance( 'course_theme_lesson_quiz' );
		$quiz_id       = Sensei()->lesson->lesson_quizzes( $lesson_id );
		$user_answers  = Sensei()->quiz->get_user_answers( $lesson_id, $user_id );
		$lesson_status = \Sensei_Utils::user_lesson_status( $lesson_id, $user_id );

		if ( $this->maybe_add_lesson_quiz_progress_notice( $user_answers, $lesson_status, $quiz_id, $notices ) ) {
			return;
		}

		if ( ! Sensei()->lesson->is_quiz_submitted( $lesson_id, $user_id ) ) {
			return;
		}

		$grade            = Sensei_Quiz::get_user_quiz_grade( $lesson_id, $user_id );
		$grade_rounded    = Sensei_Utils::round( $grade, 2 );
		$passmark         = get_post_meta( $quiz_id, '_quiz_passmark', true );
		$passmark_rounded = Sensei_Utils::round( $passmark, 2 );
		$pass_required    = get_post_meta( $quiz_id, '_pass_required', true );

		if ( 'ungraded' === $lesson_status->comment_approved ) {
			$text = __( 'Awaiting grade', 'sensei-lms' );
		} elseif ( 'failed' === $lesson_status->comment_approved ) {
			// translators: Placeholders are the required grade and the actual grade, respectively.
			$text = sprintf( __( 'You require %1$s%% to pass this lesson\'s quiz. Your grade is %2$s%%.', 'sensei-lms' ), '<strong>' . $passmark_rounded . '</strong>', '<strong>' . $grade_rounded . '</strong>' );
		} else {
			// translators: Placeholder is the quiz grade.
			$text = sprintf( __( 'Your Grade: %s%%', 'sensei-lms' ), '<strong class="sensei-course-theme-lesson-quiz-notice__grade">' . $grade_rounded . '</strong>' );
		}

		$actions = [
			[
				'label' => __( 'View quiz', 'sensei-lms' ),
				'url'   => $quiz_permalink,
				'style' => 'link',
			],
		];

		$notices->add_notice( 'lesson_quiz_results', $text, __( 'Quiz completed', 'sensei-lms' ), $actions );
	}

	/**
	 * Maybe add lesson quiz progress notice.
	 *
	 * @param array|false            $user_answers  User answers.
	 * @param object|false           $lesson_status Lesson status.
	 * @param int                    $quiz_id       Quiz ID.
	 * @param Sensei_Context_Notices $notices       Notices instance.
	 *
	 * @return bool Whether notice was added.
	 */
	private function maybe_add_lesson_quiz_progress_notice( $user_answers, $lesson_status, $quiz_id, $notices ) {
		if ( ! $user_answers || empty( $user_answers ) || ! is_array( $user_answers ) || empty( $lesson_status ) || 'in-progress' !== $lesson_status->comment_approved ) {
			return false;
		}

		// Get first unanswered question and filter answers (skip the empty questions).
		$answers_index             = 0;
		$first_unanswered_question = null;
		$filtered_user_answers     = array_filter(
			$user_answers,
			function( $answer ) use ( &$answers_index, &$first_unanswered_question ) {
				if ( '' === $answer && null === $first_unanswered_question ) {
					$first_unanswered_question = $answers_index;
				}
				$answers_index++;

				return '' !== $answer;
			}
		);

		$answered_questions = count( $filtered_user_answers );
		$total_questions    = count( Sensei()->lesson->lesson_quiz_questions( $quiz_id, 'publish' ) );
		$continue_link      = get_permalink( $quiz_id );

		// Set pagination to the continue link, if needed.
		$pagination_settings = json_decode(
			get_post_meta( $quiz_id, '_pagination', true ),
			true
		);
		if ( ! empty( $pagination_settings['pagination_number'] ) && null !== $first_unanswered_question ) {
			$questions_per_page = (int) $pagination_settings['pagination_number'];
			$unanswered_page    = ceil( ( $first_unanswered_question + 1 ) / $questions_per_page );
			$continue_link      = add_query_arg( 'quiz-page', $unanswered_page, $continue_link );
		}

		$actions = [
			[
				'label' => __( 'Continue quiz', 'sensei-lms' ),
				'url'   => $continue_link,
				'style' => 'link',
			],
		];
		// translators: Placeholders are the number of answered questions and the total questions, respectively.
		$text = sprintf( __( '%1$d of %2$d questions complete', 'sensei-lms' ), $answered_questions, $total_questions );

		$notices->add_notice( 'lesson_quiz_results', $text, __( 'Lesson quiz in progress', 'sensei-lms' ), $actions );

		return true;
	}

	/**
	 * Maybe add lesson prerequisite notice.
	 */
	private function maybe_add_lesson_prerequisite_notice() {
		$lesson_id = \Sensei_Utils::get_current_lesson();
		$course_id = Sensei()->lesson->get_course_id( $lesson_id );

		if ( ! Sensei_Course::is_user_enrolled( $course_id ) ) {
			return;
		}

		$user_id             = get_current_user_id();
		$lesson_prerequisite = \Sensei_Lesson::find_first_prerequisite_lesson( $lesson_id, $user_id );

		if ( $lesson_prerequisite > 0 ) {
			$lesson_status = \Sensei_Utils::user_lesson_status( $lesson_prerequisite, $user_id );

			$prerequisite_lesson_link = '<a href="'
				. esc_url( get_permalink( $lesson_prerequisite ) )
				. '" title="'
				// translators: Placeholder is the item title.
				. sprintf( esc_attr__( 'You must first complete: %1$s', 'sensei-lms' ), get_the_title( $lesson_prerequisite ) )
				. '">'
				. esc_html__( 'prerequisites', 'sensei-lms' )
				. '</a>';

			$text = ! empty( $lesson_status ) && 'ungraded' === $lesson_status->comment_approved
				// translators: Placeholder is the link to the prerequisite lesson.
				? sprintf( esc_html__( 'You will be able to view this lesson once the %1$s are completed and graded.', 'sensei-lms' ), $prerequisite_lesson_link )
				// translators: Placeholder is the link to the prerequisite lesson.
				: sprintf( esc_html__( 'Please complete the %1$s to view this lesson content.', 'sensei-lms' ), $prerequisite_lesson_link );

			$notices = \Sensei_Context_Notices::instance( 'course_theme_locked_lesson' );
			$notices->add_notice( 'locked_lesson', $text, __( 'You don\'t have access to this lesson', 'sensei-lms' ), [], 'lock' );
		}
	}

	/**
	 * Maybe add not enrolled notice.
	 *
	 * @return void
	 */
	private function maybe_add_not_enrolled_notice() {
		$lesson_id = \Sensei_Utils::get_current_lesson();
		$course_id = Sensei()->lesson->get_course_id( $lesson_id );

		if ( Sensei_Course::is_user_enrolled( $course_id ) ) {
			return;
		}

		$notices      = \Sensei_Context_Notices::instance( 'course_theme_locked_lesson' );
		$notice_key   = 'locked_lesson';
		$notice_title = __( 'You don\'t have access to this lesson', 'sensei-lms' );
		$notice_icon  = 'lock';

		// Course prerequisite notice.
		if ( ! Sensei_Course::is_prerequisite_complete( $course_id ) ) {
			$notices->add_notice(
				$notice_key,
				Sensei()->course::get_course_prerequisite_message( $course_id ),
				$notice_title,
				[],
				$notice_icon
			);

			return;
		}

		// Logged-out notice.
		if ( ! is_user_logged_in() ) {
			$user_can_register = get_option( 'users_can_register' );

			// Sign in URL.
			$current_link = get_permalink();
			$sign_in_url  = $user_can_register ? sensei_user_registration_url( true, $current_link ) : sensei_user_login_url( $current_link );

			$actions = [
				[
					'label' => __( 'Take course', 'sensei-lms' ),
					'url'   => Sensei()->lesson->get_take_course_url( $course_id ),
					'style' => 'primary',
				],
				[
					'label' => __( 'Sign in', 'sensei-lms' ),
					'url'   => $sign_in_url,
					'style' => 'secondary',
				],
			];

			$notice_text = __( 'Please register or sign in to access the course content.', 'sensei-lms' );

			if ( Sensei_Utils::is_preview_lesson( $lesson_id ) ) {
				$notice_text  = __( 'Register or sign in to take this lesson.', 'sensei-lms' );
				$notice_title = __( 'This is a preview lesson', 'sensei-lms' );
				$notice_icon  = 'eye';
			}

			$notices->add_notice(
				$notice_key,
				$notice_text,
				$notice_title,
				$actions,
				$notice_icon
			);

			return;
		}

		// Not enrolled notice.
		$nonce   = wp_nonce_field( 'woothemes_sensei_start_course_noonce', 'woothemes_sensei_start_course_noonce', false, false );
		$actions = [
			'<form method="POST" action="' . esc_url( get_permalink( $course_id ) ) . '">
				<input type="hidden" name="course_start" value="1" />
				' . $nonce . '
				<button type="submit" class="sensei-course-theme__button is-primary">' . esc_html__( 'Take course', 'sensei-lms' ) . '</button>
			</form>',
		];

		$notice_text = __( 'Please register for this course to access the content.', 'sensei-lms' );

		if ( Sensei_Utils::is_preview_lesson( $lesson_id ) ) {
			$notice_text  = __( 'Register for this course to take this lesson.', 'sensei-lms' );
			$notice_title = __( 'This is a preview lesson', 'sensei-lms' );
			$notice_icon  = 'eye';
		}

		$notices->add_notice(
			$notice_key,
			$notice_text,
			$notice_title,
			$actions,
			$notice_icon
		);
	}
}
