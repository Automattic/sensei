<?php
// This file contains material which is the pre-existing property of Psycle Interactive Limited.
// Copyright (c) 2014 Psycle Interactive. All rights reserved.

// Contains Filters and Actions specifically for Sensei 

/**
 * Adjust the text on the Archive of Courses
 * 
 * @param type $text
 * @return type
 */
function imperial_sensei_new_courses_text( $text ) {
	return __( 'Courses', 'psycle' );
}
add_filter( 'sensei_new_courses_text', 'imperial_sensei_new_courses_text' );

/**
 * Edit the 'View Lesson Quiz' button text
 */
function imperial_sensei_view_quiz_button( $text ) {
	return __( 'View Course Quiz', 'psycle' );
}
add_filter( 'sensei_view_quiz_text', 'imperial_sensei_view_quiz_button' );
add_filter( 'sensei_view_lesson_quiz_text', 'imperial_sensei_view_quiz_button' );


/**
 * Filter the size of the image used in Quiz questions
 */
function imperial_sensei_question_image_size( $size, $question_id ) {
	$size = 'full';
	return $size;
}
add_filter( 'sensei_question_image_size', 'imperial_sensei_question_image_size', 10, 2 );

/**
 * Edit the 'Start taking this Course' button text
 */
function imperial_sensei_start_course_button( $text ) {
	return __( 'Start Course', 'psycle' );
}
add_filter( 'sensei_start_course_text', 'imperial_sensei_start_course_button' );

/**
 * Edit the 'Time' text for Courses
 */
function imperial_sensei_length_text( $text ) {
	return '<span class="icon icon-activity"></span>' . $text;
}
add_filter( 'sensei_length_text', 'imperial_sensei_length_text' );

/**
 * Remove the Course title from the single course screen, as it's already shown in the header
 *
 * @global type $woothemes_sensei
 */
function imperial_remove_course_title() {
	global $woothemes_sensei;
	remove_action( 'sensei_course_single_title', array( $woothemes_sensei->frontend, 'sensei_single_title' ), 10 );
}
add_action( 'wp_loaded', 'imperial_remove_course_title' );

/**
 * Add fallback for older Quizzes to remove the double 'quiz' in the title
 *
 * @param type $title
 * @param type $id
 */
function imperial_sensei_quiz_titles_fallback( $title ) {
	$title = str_ireplace( 'quiz quiz', 'Quiz', $title );
	return $title;
}
add_filter( 'the_title', 'imperial_sensei_quiz_titles_fallback', 10 );
add_filter( 'wp_title', 'imperial_sensei_quiz_titles_fallback', 10 );

/**
 * Change the text shown on the 'Complete Lesson' button
 *
 * @param type $text
 * @return type
 */
function imperial_sensei_complete_lesson_button( $text ) {
	return __( 'Complete', 'woothemes-sensei' );
}
add_filter( 'sensei_complete_lesson_text', 'imperial_sensei_complete_lesson_button' );

/**
 * Change the text shown on the 'Reset Lesson' button
 *
 * @param type $text
 * @return type
 */
function imperial_sensei_reset_lesson_button( $text ) {
	return __( 'Reset', 'woothemes-sensei' );
}
add_filter( 'sensei_reset_lesson_text', 'imperial_sensei_reset_lesson_button' );

/**
 * Remove various elements of Sensei elements, sometimes moving them elsewhere
 */
function imperial_sensei_adjust_various_elements() {
	global $woothemes_sensei, $sensei_modules;
	remove_action( 'sensei_lesson_back_link', array( $sensei_modules, 'back_to_module_link' ), 9, 1 );
	remove_action( 'sensei_lesson_archive_main_content', array( $sensei_modules, 'module_back_to_course_link' ), 50);
	remove_action( 'sensei_lesson_back_link', array( $woothemes_sensei->frontend, 'sensei_lesson_back_to_course_link' ), 10, 1 );
	remove_action( 'sensei_lesson_meta_extra', array( $woothemes_sensei->frontend, 'lesson_tags_display' ), 10, 1 );
	remove_action( 'sensei_pagination', array( $sensei_modules, 'module_navigation_links' ), 11 );
	remove_action( 'sensei_pagination', array( $woothemes_sensei->frontend, 'sensei_output_content_pagination' ), 10 );
	remove_action( 'sensei_complete_lesson', array( $woothemes_sensei->frontend, 'sensei_complete_lesson' ) );
	remove_action( 'sensei_complete_lesson_button', array( $woothemes_sensei->frontend, 'sensei_complete_lesson_button' ) );
	remove_action( 'sensei_reset_lesson_button', array( $woothemes_sensei->frontend, 'sensei_reset_lesson_button' ) );
	remove_action( 'sensei_quiz_back_link', array( $woothemes_sensei->frontend, 'sensei_quiz_back_to_lesson_link' ), 10, 1 );
	remove_filter( 'the_title', array( $woothemes_sensei->frontend, 'sensei_lesson_preview_title' ), 10, 2 ); 
}
add_action( 'wp_loaded', 'imperial_sensei_adjust_various_elements' );

/**
 * An adjustment of Sensei Frontend => sensei_output_comments() allowing Staff to view comments
 */
function imperial_sensei_output_comments_to_staff() {
	global $woothemes_sensei, $view_lesson, $user_taking_course;
	// Staff can already view the Course, just can't see the comments because they are not "on" the Course
	if ( imperial()->is_staff( wp_get_current_user() ) ) {
		$user_taking_course = true;
	}
}
add_action( 'sensei_comments', 'imperial_sensei_output_comments_to_staff', 5 );

/**
 * When user is added to a course, add them to course group.
 *
 * @param $uid int
 *   User ID.
 * @param $pid int
 *   Course post ID.
 */
function imperial_sensei_user_course_start_action( $uid, $pid ) {
	$post = get_post( $pid );
	// We require minimal info to check
	$code = strtoupper( $post->__get($post->post_type . '_code') );
	$year = strtoupper( $post->__get($post->post_type . '_year') );
	// Minimal data check...
	if ( empty($code) || empty($year) ) {
		return;
	}
	// Get the course group
	$slug = sprintf( '%s - %s - group', $code, $year );
	$group_id = BP_Groups_Group::get_id_from_slug( sanitize_title( $slug ) );
	if ( $group_id ) {
		groups_join_group( $group_id, $uid );
	}
}
add_action( 'sensei_user_course_start', 'imperial_sensei_user_course_start_action', 10, 2 );

/**
 * Filters the Sensei check on frontend to ensure that Staff can 'see' courses like Students
 * 
 * @global type $current_user
 * @param boolean $access
 * @return boolean
 */
function imperial_sensei_staff_access( $access ) {
	global $current_user;
	if ( !is_admin() && imperial()->is_staff( $current_user ) ) {
		$access = true;
	}
	return $access;
}
add_filter( 'sensei_all_access', 'imperial_sensei_staff_access' );


/**
 * Filters the questions that are autogradable to add more question types
 * 
 * @param type $autogradable_question_types
 */
function imperial_sensei_autogradable_question_types( $autogradable_question_types ) {
	$autogradable_question_types[] = 'gap-fill';
	return $autogradable_question_types;
}
add_filter( 'sensei_autogradable_question_types', 'imperial_sensei_autogradable_question_types' );

/**
 * Filters the grade produced for a question allowing auto grading more question types
 * 
 * @param type $autogradable_question_types
 */
function imperial_sensei_grade_question_auto( $question_grade, $question_id, $question_type, $answer ) {
	switch( $question_type ) {
		case 'gap-fill' :
			$right_answer = get_post_meta( $question_id, '_question_right_answer', true );
			if( 0 == get_magic_quotes_gpc() ) {
				$answer = wp_unslash( $answer );
			}
			$gapfill_array = explode( '|', $right_answer );
			// Check that the 'gap' is "exactly" equal to the given answer
			if ( trim(strtolower($gapfill_array[1])) == trim(strtolower($answer)) ) {
				$question_grade = get_post_meta( $question_id, '_question_grade', true );
				if ( empty($question_grade) ) {
					$question_grade = 1;
				}
			}
			break;
	}
	return $question_grade;
}
add_filter( 'sensei_grade_question_auto', 'imperial_sensei_grade_question_auto', 10, 4 );

/**
 * Filters to add addition Quiz Settings Options in the admin. Filter applied in
 * /custom/plugins/woo-themes/sensei/classes/class-woothemes-sensei-lesson.php
 * @param array of settings
 * @return array of settings - add in new settings 
 */
function imperial_sensei_quiz_settings($settings){
	$quizAttemptClass= '';
	foreach($settings as $setting){
		if( $setting['id'] != 'enable_quiz_reset') {
			continue;
		}
		if( $setting['checked'] != 'on') {
			$quizAttemptClass = 'hidden';
		}
		break;
	}

	//Add the number of attempts the student is allowed
	$settings[] = array(
		'id'          => 'quiz_attempts',
		'label'       => __( 'Quiz attempts', 'woothemes-sensei' ),
		'description' => __( 'The number of times the student can submit their answers. If set to 0 no restriction is applied.', 'woothemes-sensei' ),
		'type'        => 'number',
		'default'     => 0,
		'required'    => 1,
		'min'         => 0,
		'class'       => $quizAttemptClass,
		
	);

	//Add a field to allow the admin to apply a time constraint on the student to complete the quiz
	$settings[] = array(
		'id'          => 'time_limit',
		'label'       => __( 'Time limit - minutes', 'woothemes-sensei' ),
		'description' => __( 'How long the user has to complete the quiz in minutes. If set to 0 no time constraints will be applied.', 'woothemes-sensei' ),
		'type'        => 'number',
		'default'     => 0,
		'required'    => 1,
		'min'         => 0,
	);

	//Add a password to the quiz, note this should be plain text as they may need to print off the password
	//in the examination (They need easy access to it)
	$settings[] = array(
		'id'          => 'quiz_password',
		'label'       => __( 'Quiz entry password', 'woothemes-sensei' ),
		'description' => __( 'Add a password to access the quiz questions. If you leave it blank the student will not require a password.', 'woothemes-sensei' ),
		'type'        => 'text',
		'default'     => '',
		'required'    => 0,
		'class'       => 'regular-text',
	);

	return $settings;
}
add_filter( 'sensei_quiz_settings' , 'imperial_sensei_quiz_settings' );

/**
 * Restricts the total number of attempts for a single Quiz
 * 
 * @uses 'sensei_complete_quiz' to trigger the code
 */
function imperial_sensei_restrict_quiz_attempts() {
	global $post, $woothemes_sensei, $current_user;

	$quiz_id = $post->ID;
	$lesson_id = $woothemes_sensei->frontend->data->quiz_lesson;
	$reset_quiz_allowed = $woothemes_sensei->frontend->data->reset_quiz_allowed;

	// Check we have a lesson (should always have 1), and if resets allowed
	if ( !is_int($lesson_id) || !$reset_quiz_allowed ) {
		return;
	}

	$max_attempts = get_post_meta( $quiz_id,'_quiz_attempts', true);
	// Check for missing entry, or entry of 0, thus no restriction
	if ( empty($max_attempts) ) {
		return;
	}
	// Get users current attempt number
	$args = array(
		'post_id' => $lesson_id,
		'user_id' => $current_user->ID,
		'type' => 'sensei_lesson_start',
		'field' => 'comment_ID',
	);
	$comment_id = WooThemes_Sensei_Utils::sensei_get_activity_value( $args );
	$current_attempts = absint( get_comment_meta( $comment_id, '_quiz_attempts', true ) );

	// Save data for later in template
	$woothemes_sensei->frontend->data->reset_quiz_attempts = $max_attempts;
	$woothemes_sensei->frontend->data->reset_quiz_attempts_used = $current_attempts;

	// Remove the ability to reset the Quiz now we've hit the limit
	if( $max_attempts <= $current_attempts ) {
		remove_action( 'sensei_quiz_action_buttons', array( $woothemes_sensei->frontend, 'sensei_quiz_action_buttons' ), 10 );
	}
}
add_action( 'sensei_complete_quiz','imperial_sensei_restrict_quiz_attempts' );

/**
 * Track the number of attempts a user has made for a Quiz
 * 
 * @uses 'sensei_user_quiz_submitted' on quiz submission to trigger
 * @param type $current_user_id
 * @param type $quiz_id
 * @param type $quiz_grade_type
 * @param type $grade
 */
function imperial_sensei_track_quiz_attempts( $user_id, $quiz_id, $quiz_grade_type, $grade) {
	$reset_allowed = get_post_meta( $quiz_id, '_enable_quiz_reset', true );
	$max_attempts = get_post_meta( $quiz_id,'_quiz_attempts', true);

	if ( $reset_allowed && $max_attempts ) {
		$lesson_id = get_post_meta( $quiz_id, '_quiz_lesson', true );
		// Get users current attempt number
		$args = array(
			'post_id' => $lesson_id,
			'user_id' => $user_id,
			'type' => 'sensei_lesson_start',
			'field' => 'comment_ID',
		);
		$comment_id = WooThemes_Sensei_Utils::sensei_get_activity_value( $args );
		if ( !$comment_id ) {
			return; // oops
		}
		$current_attempts = absint( get_comment_meta( $comment_id, $attempt_key = '_quiz_attempts', true ) );

		// Increment and store
		$current_attempts++;
		update_comment_meta( $comment_id, $attempt_key, $current_attempts );
	}
}
add_action( 'sensei_user_quiz_submitted', 'imperial_sensei_track_quiz_attempts', 10, 4 );

/**
 * Cannot set a cookie within imperial_sensei_check_quiz_password() normally as the headers will have already been sent,
 * so detect when a quiz password is being submitted and buffer the output
 */
function imperial_sensei_detect_quiz_password_submission() {
	if ( isset( $_POST['sensei_quiz_password_form'] ) ) {
		ob_start();
	}
}
//add_action( 'parse_request', 'imperial_sensei_detect_quiz_password_submission' );

/**
 * Restricts the total number of attempts for a single Quiz
 * 
 * @uses 'sensei_complete_quiz' to trigger the code
 */
function imperial_sensei_check_quiz_password() {
	global $post, $woothemes_sensei, $current_user;

	$quiz_id = $post->ID;
	$lesson_id = $woothemes_sensei->frontend->data->quiz_lesson;
	$quiz_password = trim( get_post_meta( $quiz_id, '_quiz_password', true ) );

	// Check we have a lesson (should always have 1) and password etc
	if ( !is_int($lesson_id) || empty( $quiz_password ) || empty( $_POST['quiz_password'] ) || !wp_verify_nonce( $_POST['_wpnonce'], 'quiz-password-' . $quiz_id ) ) {
		return;
	}
	require_once ABSPATH . 'wp-includes/class-phpass.php';
	$hasher = new PasswordHash( 8, true );

	$expire = apply_filters( 'post_password_expires', time() + 10 * DAY_IN_SECONDS );
	setcookie( 'wp-quizpass_' . COOKIEHASH, $hasher->HashPassword( wp_unslash( $_POST['quiz_password'] ) ), $expire, COOKIEPATH );

	wp_safe_redirect( add_query_arg( array( 'message' => 1 ), wp_get_referer() ) );
	exit();
}
add_action( 'sensei_complete_quiz','imperial_sensei_check_quiz_password' );

/**
 * Track and restrict quiz based on a time limit
 * 
 * @uses 'sensei_quiz_header' to trigger the code rather than 'sensei_complete_quiz' because of conflicts with Quiz password
 */
function imperial_sensei_track_quiz_time_limit() {
	global $post, $woothemes_sensei, $current_user;

	$quiz_id = $post->ID;
	$lesson_id = $woothemes_sensei->frontend->data->quiz_lesson;
	$time_limit = absint( get_post_meta( $quiz_id, '_time_limit', true ) );

	// Check we have a lesson (should always have 1) and time limit etc
	if ( !is_int($lesson_id) || !$time_limit ) {
		return;
	}
	$now = current_time('timestamp', 1); // Using GMT timezone (BAD!)
	$time_limit = $time_limit * 60; // Time limit is in minutes, convert to seconds

	// Get users current start time
	$args = array(
		'post_id' => $lesson_id,
		'user_id' => $current_user->ID,
		'type' => 'sensei_lesson_start',
	);
	$comment = WooThemes_Sensei_Utils::sensei_check_for_activity( $args, true );
	if ( is_array($comment) ) {
		$comment = array_shift( $comment );
	}
	if ( !$comment ) {
		// No existing start time, so record it!
		WooThemes_Sensei_Utils::sensei_start_lesson( $lesson_id, $current_user->ID );
		$comment = WooThemes_Sensei_Utils::sensei_check_for_activity( $args, true );
		if ( is_array($comment) ) {
			$comment = array_shift( $comment );
		}
	}
	$start = strtotime( $comment->comment_date_gmt );

	// Save data for later in template
	$woothemes_sensei->frontend->data->quiz_start_time = $start;
	$woothemes_sensei->frontend->data->quiz_time_limit = $time_limit;

	// Hit the time limit, remove the buttons if the page was loaded after the limit
	if( $now >= ($start + $time_limit) ) {
		remove_action( 'sensei_quiz_action_buttons', array( $woothemes_sensei->frontend, 'sensei_quiz_action_buttons' ), 10 );
	}
}
add_action( 'sensei_quiz_header', 'imperial_sensei_track_quiz_time_limit' );

