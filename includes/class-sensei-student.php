<?php

namespace Sensei;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Student progress logic layer.
 *
 * @package Content
 * @author Automattic
 * @since $next-version
 */
class Student {

	private $user;

	public function __constructor( $user_id ) {

	}

	public function usage() {

		/**
		 * Sensei()->student should only exist on the frontend.
		 *
		 * new Student( $user_id ) should be used in the admin.
		 *
		 *
		 */
		//
		Sensei()->student->course( $course_id );
		Sensei()->student->course()->start();
		Sensei()->student->course()->update();

		//
		Student::quiz()->

		//
		Student::course( $course_id )->
		Current_Student::course()->
		Current_Course()->

		// Repo
		Sensei()->course_progress->get( $course_id, $user_id );

	}

	// Return user id if it's a real user.
	public function get_user_id() {}


	public function course( $course_id ) {

	}

	public function lesson( $lesson_id ) {

	}

	public function quiz( $quiz_id ) {
		return Sensei()->quiz_progress_repository->get( $quiz_id, $this->user_id );
	}

	///


	public function course_start() {}
	public function course_complete() {}
	public function course_restart() {}

	public function lesson_start() {}
	public function lesson_complete() {}
	public function lesson_reset() {}

	public function quiz_submit() {}
	public function quiz_save() {}
	public function quiz_reset() {}


	public function current() {

		/// FRONTEND

		\Sensei_Course::is_user_enrolled();

		\Sensei_Quiz::is_quiz_available();
		\Sensei_Quiz::is_quiz_completed();
		\Sensei_Quiz::save_user_answers();
		\Sensei_Quiz::get_user_answers();
		\Sensei_Quiz::reset_user_lesson_data();
		\Sensei_Quiz::submit_answers_for_grading();
		\Sensei_Quiz::set_user_grades();
		\Sensei_Quiz::get_user_grades();
		\Sensei_Quiz::get_user_answers_feedback();
		\Sensei_Quiz::get_user_quiz_grade();

		\Sensei_Grading::grade_quiz_auto();


		\Sensei_Grading::the_user_status_message();
		\Sensei_Utils::sensei_user_quiz_status_message();

		// \Sensei_Quiz::load_global_quiz_data - Reads everything


		/// ADMIN

		\Sensei_Quiz::get_user_question_answer();
		\Sensei_Quiz::get_user_question_grade();
		\Sensei_Quiz::save_user_answers_feedback();

	}




}
