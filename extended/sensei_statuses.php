<?php
// This file contains material which is the pre-existing property of Psycle Interactive Limited.
// Copyright (c) 2014 Psycle Interactive. All rights reserved.

/**
 * As part of the overall shift for the underlying Sensei framework from 9 comment types to just 3, this file
 * covers the first step. Storing the additional 'sensei_lesson_status' and 'sensei_course_status' entries.
 * 
 * Step 2 will be to roll out the changed admin screens to use the new statueses, in addition creating all the statuses
 * from the existing comment data
 * 
 * Step 3 will be to update the rest of Sensei to only use the new statuses, leaving the older entries redundant.
 */

/**
 * Update the overall Sensei lesson status, switching between 'in-progress', 'complete'
 * 
 * @param type $user_id
 * @param type $lesson_id
 */
function imperial_sensei_user_lesson_status( $user_id, $lesson_id ) {
	global $wpdb, $woothemes_sensei, $wp_current_filter;
//error_log(__FUNCTION__);

	$status = '';
	$metadata = array();
	// Mark lesson as in-progress
	if ( doing_action('sensei_user_lesson_start') ) {
		$status = 'in-progress';
		// Store when we started
		$metadata['start'] = current_time('mysql');
	}
	// Potentially mark lesson as complete...
	else if ( doing_action('sensei_user_lesson_end') ) {
		// 'sensei_user_lesson_end' triggers generally after 'sensei_user_quiz_grade', sometimes before and sometimes not at all
		// ...but only if we have no questions
		$has_questions = get_post_meta( $lesson_id, '_quiz_has_questions', true );
		if ( !$has_questions ) {
			$status = 'complete'; // No quiz set
		}

		// This is quicker than going through WooThemes_Sensei_Utils::sensei_check_for_activity()
		$existing_status = $wpdb->get_var( $wpdb->prepare( "SELECT comment_approved FROM $wpdb->comments WHERE comment_post_ID = %d AND user_id = %d AND comment_type = %s ", $lesson_id, $user_id, 'sensei_lesson_status' ) );
		// TO FIX: Sometimes 'sensei_user_lesson_end' triggers after the grading of a Quiz, so don't duplicate the status update
		if ( !in_array( $existing_status, array( 'in-progress', 'ungraded' ) ) ) {
			return; // nothing to do
		}
	}

	if ( !empty( $status ) ) {
		$args = array(
				'user_id' => $user_id,
				'post_id' => $lesson_id,
				'status' => $status,
				'type' => 'sensei_lesson_status', /* FIELD SIZE 20 */
				'action' => 'update' // Important to only have 1 entry
			);

//error_log(" => Logging lesson status of '$status' for user $user_id on lesson $lesson_id, called from " .print_r($wp_current_filter, true));
		$comment_id = WooThemes_Sensei_Utils::sensei_log_activity( $args );
		if ( $comment_id && !empty($metadata) ) {
			foreach( $metadata as $key => $value ) {
				update_comment_meta( $comment_id, $key, $value );
			}
		}
		do_action( 'sensei_lesson_status_updated', $status, $user_id, $lesson_id, $comment_id );
	}
}
add_action( 'sensei_user_lesson_start', 'imperial_sensei_user_lesson_status', 10, 2 );
add_action( 'sensei_user_lesson_end', 'imperial_sensei_user_lesson_status', 10, 2 );

/**
 * Update the overall Sensei lesson status (when called for a quiz), switching between 'ungraded', 'complete', 'graded', 'failed', 'passed'
 * 
 * @param type $user_id
 * @param type $quiz_id
 * @param type $grade
 * @param type $passmark
 * @param type $quiz_grade_type
 */
function imperial_sensei_user_lesson_quiz_status( $user_id, $quiz_id, $grade, $passmark, $quiz_grade_type = 'auto' ) {
	global $wpdb, $wp_current_filter;
//error_log(__FUNCTION__ . ':' .print_r( func_get_args(), true ));

	$status = '';
	$metadata = array();
	// Check if the lesson has questions...
	$lesson_id = get_post_meta( $quiz_id, '_quiz_lesson', true );
	$has_questions = get_post_meta( $lesson_id, '_quiz_has_questions', true );
	// ...if the lesson doesn't have questions the ultimate status is simple
	if ( !$has_questions ) {
		$status = 'complete'; // No quiz set
	}
	elseif ( is_wp_error( $grade ) || 'auto' != $quiz_grade_type ) {
//		error_log( 'MANUAL QUIZ');
		$status = 'ungraded'; // Quiz is manually graded and this was a user submission via 'sensei_user_quiz_submitted'
	}
	else {
		// Quiz has been automatically Graded
		$lesson_quiz_id = get_post_meta( $lesson_id, '_lesson_quiz', true ); // Something we store to save time
		$pass_required = get_post_meta( $lesson_quiz_id, '_pass_required', true );
		if ( $pass_required ) {
			// ...check user has passed
			if ( $passmark <= $grade ) {
				$status = 'passed';
			}
			else {
				$status = 'failed';
			}
		}
		else {
			$status = 'graded'; // Passing quiz not required
		}
		$metadata['grade'] = $grade;
	}

	if ( !empty( $status ) ) {
		$existing_args = array(
				'user_id' => $user_id,
				'post_id' => $lesson_id,
				'status' => 'any',
				'type' => 'sensei_lesson_status', /* FIELD SIZE 20 */
			);
		$existing_status = WooThemes_Sensei_Utils::sensei_check_for_activity( $existing_args, true );
		if ( is_array( $existing_status ) ) {
			$existing_status = $existing_status[0];
		}
		// This is quicker than going through WooThemes_Sensei_Utils::sensei_check_for_activity()
//		$existing_status = $wpdb->get_var( $wpdb->prepare( "SELECT comment_approved FROM $wpdb->comments WHERE comment_post_ID = %d AND user_id = %d AND comment_type = %s ", $lesson_id, $user_id, 'sensei_lesson_status' ) );
		$args = array(
				'user_id' => $user_id,
				'post_id' => $lesson_id,
				'status' => $status,
				'type' => 'sensei_lesson_status', /* FIELD SIZE 20 */
				'action' => 'update', // Important to only have 1 entry
			);

		// Don't update the time, it should stay as what the user last set
		if ( 'ungraded' == $existing_status->comment_approved ) {
			$args['keep_time'] = true;
		}

//error_log(" => Logging lesson status of '$status' for user $user_id on lesson $lesson_id, called from " .print_r($wp_current_filter, true));
		$comment_id = WooThemes_Sensei_Utils::sensei_log_activity( $args );
		if ( $comment_id && !empty($metadata) ) {
			foreach( $metadata as $key => $value ) {
				update_comment_meta( $comment_id, $key, $value );
			}
		}
		do_action( 'sensei_lesson_status_updated', $status, $user_id, $lesson_id, $comment_id );
	}
}
// 'sensei_user_quiz_grade' triggers within frontend::sensei_complete_lesson(), frontend::sensei_complete_quiz() and frontend::sensei_complete_course()
//add_action( 'sensei_user_quiz_grade', 'imperial_sensei_user_lesson_quiz_status', 10, 5 );
// 'sensei_user_quiz_submitted' triggers within frontend::sensei_complete_quiz()
add_action( 'sensei_user_quiz_submitted', 'imperial_sensei_user_lesson_quiz_status', 10, 5 );


/**
 * Update the overall Sensei Course status, switching betweem 'in-progress' and 'complete'
 * 
 * @param type $lesson_status
 * @param type $user_id
 * @param type $lesson_id
 * @param type $comment_id
 */
function imperial_sensei_lesson_status_updated_course( $lesson_status, $user_id, $lesson_id, $comment_id ) { 
	global $woothemes_sensei, $wp_version;
//error_log(__FUNCTION__ . ", $lesson_status, $user_id, $lesson_id, $comment_id");

	$status = 'in-progress';
	$metadata = array();
	$course_id = get_post_meta( $lesson_id, '_lesson_course', $single = true );
	$course_completion = $woothemes_sensei->settings->settings[ 'course_completion' ];
	// If the lesson 'completed' then update the overall Course percentage
	if ( !in_array( $lesson_status, array( 'in-progress', 'ungraded' ) ) ) {
//error_log(" => processing percentage");
		// Now effectively copy utils::user_completed_course() but track the overall percentage
		$lessons_completed = $total_lessons = 0;
		$lesson_status_args = array(
				'user_id' => $user_id,
				'status' => 'any',
				'type' => 'sensei_lesson_status', /* FIELD SIZE 20 */
			);
		// Grab all of this Courses' lessons, looping through each...
		$lesson_ids = $woothemes_sensei->post_types->course->course_lessons( $course_id, 'any', 'ids' );
		$total_lessons = count( $lesson_ids );
			// ...if course completion not set to 'passed', and all lessons are complete or graded, 
			// ......then all lessons are 'passed'
			// ...else if course completion is set to 'passed', check if each lesson has questions...
			// ......if no questions yet the status is 'complete'
			// .........then the lesson is 'passed'
			// ......else if questions check the lesson status has a grade and that the grade is greater than the lesson passmark
			// .........then the lesson is 'passed'
			// ...if all lessons 'passed' then update the course status to complete
//error_log(" => => total lessons: $total_lessons");

		// The below checks if a lesson is fully completed, though maybe should be Utils::user_completed_lesson()
		$all_lesson_statuses = array();
		// In WordPress 4.1 get_comments() allows a single query to cover multiple comment_post_IDs
		if ( version_compare($wp_version, '4.1', '>=') ) {
			$lesson_status_args['post__in'] = $lesson_ids;
			$all_lesson_statuses = WooThemes_Sensei_Utils::sensei_check_for_activity( $lesson_status_args, true );
		}
		// ...otherwise check each one
		else {
			foreach( $lesson_ids as $lesson_id ) {
				$lesson_status_args['post_id'] = $lesson_id;
//				error_log( print_r($lesson_status_args, true));
				$_lesson_status = WooThemes_Sensei_Utils::sensei_check_for_activity( $lesson_status_args, true );
				if ( is_array( $_lesson_status ) ) {
					$_lesson_status = array_shift( $_lesson_status );
				}
				$all_lesson_statuses[] = $_lesson_status;
			}
		}
		foreach( $all_lesson_statuses as $lesson_status ) {
//			error_log( print_r($lesson_status, true));
			// No status??
			if ( empty($lesson_status->comment_approved) ) {
				continue;
			}
			// If lessons are complete without needing quizzes to be passed
			if ( 'passed' != $course_completion ) {
				switch ( $lesson_status->comment_approved ) {
					// A user cannot 'complete' a course if a lesson...
					case 'in-progress': // ...is still in progress
					case 'ungraded': // ...hasn't yet been graded
						break;

					default:
						$lessons_completed++;
						break;
				}
			}
			else {
				switch ( $lesson_status->comment_approved ) {
					case 'complete': // Lesson has no quiz/questions
					case 'graded': // Lesson has quiz, but it's not important what the grade was
					case 'passed': // Lesson has quiz and the user passed
						$lessons_completed++;
						break;

					// A user cannot 'complete' a course if on a lesson...
					case 'failed': // ...a user failed the passmark on a quiz
					default:
						break;
				}
			}
		} // Each lesson
		if ( $lessons_completed == $total_lessons ) {
			$status = 'complete';
		}
//error_log(" => => lessons completed: $lessons_completed");
		// update the overall percentage of the course lessons complete (or graded) compared to 'in-progress' regardless of the above
		$metadata['percent'] = abs( round( ( doubleval( $lessons_completed ) * 100 ) / ( $total_lessons ), 0 ) );
	}
	imperial_sensei_update_course_status( $user_id, $course_id, $status, $metadata );
}
add_action( 'sensei_lesson_status_updated', 'imperial_sensei_lesson_status_updated_course', 10, 4 );

/**
 * Wrapper to start off a 
 * 
 * @param type $user_id
 * @param type $course_id
 * @param type $status
 * @param type $metadata
 */
function imperial_sensei_user_course_status( $user_id, $course_id ) {
//error_log(__FUNCTION__);

	$status = '';
	$metadata = array();
	if ( doing_action('sensei_user_course_start') ) {
		// Mark course as in-progress
		$status = 'in-progress';
		$metadata['start'] = current_time('mysql');
		$metadata['percent'] = 0; // No completed lessons yet
	}
//	elseif ( doing_action('sensei_user_course_end') ) {
//		// Mark course as complete
//		$status = 'complete';
//	}
	imperial_sensei_update_course_status( $user_id, $course_id, $status, $metadata );
}
// 'sensei_user_course_start' triggers within utils::user_start_course() and frontend::sensei_course_start() 
add_action( 'sensei_user_course_start', 'imperial_sensei_user_course_status', 10, 2 );
// 'sensei_user_course_end' triggers within utils::user_completed_course() and frontend::sensei_complete_course() and frontend::sensei_completed_course()
//add_action( 'sensei_user_course_end', 'imperial_sensei_user_course_status', 10, 2 );


/**
 * Sets the actual statuses for the Course
 * 
 * @param type $user_id
 * @param type $course_id
 * @param type $status
 * @param type $metadata
 */
function imperial_sensei_update_course_status( $user_id, $course_id, $status = '', $metadata = array() ) {
	global $wp_current_filter;
//error_log(__FUNCTION__);

	if ( !empty($status) ) {
		$args = array(
				'user_id'   => $user_id,
				'post_id'   => $course_id,
				'status'    => $status,
				'type'      => 'sensei_course_status', /* FIELD SIZE 20 */
				'action'    => 'update',// Update the existing status...
				'keep_time' => true, // ...but don't change the existing timestamp
			);
		switch( $status ) {
			case 'in-progress' :
				unset( $args['keep_time'] ); // Keep updating what's happened
				break;
		}

//error_log(" => Logging course status of '$status' for user $user_id on course $course_id, called from " .print_r($wp_current_filter, true));
		$comment_id = WooThemes_Sensei_Utils::sensei_log_activity( $args );
		if ( $comment_id && !empty($metadata) ) {
			foreach( $metadata as $key => $value ) {
				update_comment_meta( $comment_id, $key, $value );
			}
		}
		do_action( 'sensei_course_status_updated', $status, $user_id, $course_id, $comment_id );
	}
}

