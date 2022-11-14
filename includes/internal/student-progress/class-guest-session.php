<?php

/**
 * File containing the Guest_Learner class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Student_Progress;

use Sensei\Internal\Student_Progress\Course_Progress\Repositories\Session_Based_Course_Progress_Repository;
use Sensei\Internal\Student_Progress\Lesson_Progress\Repositories\Session_Based_Lesson_Progress_Repository;
use Sensei\Internal\Student_Progress\Quiz_Progress\Repositories\Session_Based_Quiz_Progress_Repository;

/**
 * Session-based student progress mode for guests or course previews.
 *
 * @internal
 *
 * @since $$next-version$$
 */
class Guest_Session {


	public function __construct() {

		add_action( 'init', [ $this, 'init' ] );

	}

	private function init() {
		if ( ! $this->can_start_guest_session() ) {
			return;
		}

		// TODO Use some other implementation than PHP sessions?
		if ( ! session_id() ) {
			session_start();
		}

		if ( isset( $_GET['start-guest-session'] ) ) {
			$this->start_guest_session();
		}

		if ( isset( $_GET['end-guest-session'] ) ) {
			$this->end_guest_session();
		}

		if ( $this->is_guest_session() ) {

			// Student progress repositories.
			Sensei()->course_progress_repository = new Session_Based_Course_Progress_Repository();
			Sensei()->lesson_progress_repository = new Session_Based_Lesson_Progress_Repository();
			Sensei()->quiz_progress_repository   = new Session_Based_Quiz_Progress_Repository();

//			add_filter( 'determine_current_user', function() {
//				return 1;
//			} );

			add_filter( 'sensei_is_enrolled', '__return_true' );
			add_filter( 'sensei_is_login_required', '__return_false' );
			add_filter( 'sensei_grade_question_auto', [ $this, 'grade_question' ], 10, 4 );
		}
	}

	public function can_start_guest_session() {
		$course_id = \Sensei_Utils::get_current_course();

		$is_teacher = is_user_logged_in() && current_user_can( get_post_type_object( 'course' )->cap->edit_post, $course_id );
		$is_guest   = ! is_user_logged_in() && $this->is_guest_course( $course_id );

		return $is_teacher || $is_guest;

	}

	public function is_guest_course( $course_id ) {
		return true || get_post_meta( $course_id, '_guest_access', true );
	}

	private function is_guest_session() {
		return isset( $_SESSION['guest-learner'] );
	}

	private function start_guest_session() {
		$_SESSION['guest-learner'] = true;
	}

	private function end_guest_session() {
		unset( $_SESSION['guest-learner'] );
	}

	private function grade_question( $question_grade, $question_id, $question_type, $answer ) {
		if ( false !== $question_grade ) {
			return $question_grade;
		}

		// Always pass questions that can't be autograded.
		return Sensei()->question->get_question_grade( $question_id );
	}

}
