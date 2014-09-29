<?php
// This file contains material which is the pre-existing property of Psycle Interactive Limited.
// Copyright (c) 2014 Psycle Interactive. All rights reserved.

// This file contains the primary changes to Sensei, the deep rooted aspects. More generic filters (and actions) are in the sensei_filters.php file

/**
 * Modifies the Sensei content types, changing menu positions and icons
 * 
 * @param type $post_type
 * @param type $args
 */
function imperial_modify_sensei_content_type( $post_type, $args ) {
	global $wp_post_types;
	if ( 'course' == $post_type ) {
//		$args = $wp_post_types[ 'course' ];
		// Adjust the args...
		$args->show_in_menu = true;
		$args->show_in_admin_bar = true;
		$args->menu_position = 52;
		$args->menu_icon = 'dashicons-book-alt';
		$args->has_archive = false;
//		error_log( print_r($args, true));
		// ... and re-save back
		$wp_post_types[ $post_type ] = $args;
	}
	elseif ( 'lesson' == $post_type ) {
		// Adjust the args...
		$args->menu_icon = 'dashicons-format-aside';
		$labels = get_object_vars( $args->labels );
		foreach ( $labels as $key => $label ) {
			$labels[$key] = str_replace( array('Lessons', 'lessons', 'Lesson\'s', 'lesson\'s', 'Lesson', 'lesson'), array('Activities', 'activities', 'Activities', 'activities', 'Activity', 'activity'), $label );
		}
		$args->labels = (object) $labels;
		$args->label = 'Activities';
		// ... and re-save back
		$wp_post_types[ $post_type ] = $args;
	}
} // END imperial_modify_sensei_content_type()
add_action( 'registered_post_type', 'imperial_modify_sensei_content_type', 10, 2 ); // Just after Sensei

/**
 * Customise the Sensei Settings page, remove (and add) options
 * 
 * @param type $fields
 * @return type
 */
function imperial_sensei_settings_fields( $fields ) {
	unset( $fields['course_page'] );
	return $fields;
}
//add_filter( 'sensei_settings_fields', 'imperial_sensei_settings_fields' );

/**
 * Modifies the Sensei taxonomies
 */
function imperial_modify_sensei_taxonomy( $taxonomy, $object_type, $args ) {
	global $wp_taxonomies;
	if ( 'course-category' == $taxonomy ) {
		// Adjust the args...
//		$args['show_ui'] = false;
		$args['show_in_menu'] = false;
		$args['show_in_nav_menus'] = false;
		// ... and re-save back
		$wp_taxonomies[ 'course-category' ] = (object) $args;
	}
	if ( 'lesson-tag' == $taxonomy ) {
		// Adjust the args...
		$labels = get_object_vars( $args['labels'] );
		foreach ( $labels as $key => $label ) {
			$labels[$key] = str_replace( array('Lessons', 'lessons', 'Lesson\'s', 'lesson\'s', 'Lesson', 'lesson'), array('Activities', 'activities', 'Activities', 'activities', 'Activity', 'activity'), $label );
		}
		$args['labels'] = (object) $labels;
		$args['label'] = 'Activity Tags';
		// ... and re-save back
		$wp_taxonomies[ $taxonomy ] = (object) $args;
	}
//	unset($wp_taxonomies[ 'course-category' ]);
} // END imperial_modify_sensei_taxonomy()
add_action( 'registered_taxonomy', 'imperial_modify_sensei_taxonomy', 10, 3 );


/**
 * Adjust various Sensei admin menus such as permissions, removing some...
 * 
 * @global type $woothemes_sensei
 */
function imperial_sensei_menu_removals() {
	global $woothemes_sensei, $menu;
	// Don't need this category
//	remove_action( 'init', array( $woothemes_sensei->post_types, 'setup_course_category_taxonomy' ), 100 );
	// Don't need to add Course Categories to the 'Lessons' menu
//	remove_action( 'admin_menu', array( $woothemes_sensei->post_types, 'sensei_admin_menu_items' ), 10 );
	// 'Courses' isn't part of 'Lessons' now
	remove_action( 'admin_head', array( $woothemes_sensei->admin, 'admin_menu_highlight' ) );
	
	// Allow lower level users to access the Sensei Analysis and Grading screens (remove now, re-add later)
//	remove_action( 'admin_menu', array( $woothemes_sensei->admin, 'admin_menu' ), 10 );
//	remove_action( 'admin_menu', array( $woothemes_sensei->analysis, 'analysis_admin_menu' ), 10);
//	remove_action( 'admin_menu', array( $woothemes_sensei->grading, 'grading_admin_menu' ), 10);
//	remove_action( 'admin_menu', array( $woothemes_sensei->learners, 'learners_admin_menu' ), 10);

}
add_action( 'init', 'imperial_sensei_menu_removals', 5 ); // Higher priority to remove later actions

/**
 * Adjust various Sensei admin menus such as permissions, adding some...
 * 
 * @global type $woothemes_sensei
 */
function imperial_sensei_menu_additions() {
	global $woothemes_sensei, $menu;
	if ( current_user_can( 'publish_courses' ) ) {
		// These 3 used to use 'manage_options' as the capability!?
		$menu[] = array( '', 'read', 'separator-sensei', '', 'wp-menu-separator sensei' );
		$main_page = add_menu_page( __( 'Sensei', 'woothemes-sensei' ), __( 'Sensei', 'woothemes-sensei' ), 'publish_courses', 'sensei' , array( $woothemes_sensei->analysis, 'analysis_page' ) , '', '50' );
		$analysis_page = add_submenu_page( 'sensei', __('Analysis', 'woothemes-sensei'),  __('Analysis', 'woothemes-sensei') , 'publish_courses', 'sensei_analysis', array( $woothemes_sensei->analysis, 'analysis_page' ) );
		$grading_page = add_submenu_page( 'sensei', __('Grading', 'woothemes-sensei'),  __('Grading', 'woothemes-sensei') , 'publish_courses', 'sensei_grading', array( $woothemes_sensei->grading, 'grading_page' ) );
	}

}
//add_action( 'admin_menu', 'imperial_sensei_menu_additions', 10 );

/**
 * Adjusts the CSS used for Sensei
 * 
 * @global type $woothemes_sensei
 */
function imperial_sensei_css() {
	global $woothemes_sensei;
	$imp = imperial();
	wp_enqueue_style( $woothemes_sensei->token . '-global-overrides', $imp->css_url( 'sensei-global.css' ), '', $woothemes_sensei->version, 'screen' );
}
add_action( 'admin_enqueue_scripts', 'imperial_sensei_css' );

/**
 * Filter the title of Quizzes stored against Lessons so that a duplicate 'quiz Quiz' doesn't appear
 * 
 * @param type $data
 * @param type $postarr
 * @return type
 */
function imperial_sensei_quiz_titles( $data, $postarr ) {
	if ( false !== stristr($data['post_title'], 'quiz quiz') ) {
		$data['post_title'] = str_ireplace( 'quiz quiz', 'Quiz', $data['post_title'] );
	}
	return $data;
}
add_filter( 'wp_insert_post_data', 'imperial_sensei_quiz_titles', 10, 2 );

/**
 * Update the overall Sensei lesson status, switching between 'in-progress', 'complete'
 * 
 * @param type $user_id
 * @param type $lesson_id
 */
function imperial_sensei_user_lesson_status( $user_id, $lesson_id ) {
	global $wpdb, $woothemes_sensei, $wp_current_filter;
error_log(__FUNCTION__);

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
		$existing_status = $wpdb->get_var( $wpdb->prepare( "SELECT comment_content FROM $wpdb->comments WHERE comment_post_ID = %d AND user_id = %d AND comment_type = %s ", $lesson_id, $user_id, 'sensei_lesson_status' ) );
		// TO FIX: Sometimes 'sensei_user_lesson_end' triggers after the grading of a Quiz, so don't duplicate the status update
		if ( !in_array( $existing_status, array( 'in-progress', 'ungraded' ) ) ) {
			return; // nothing to do
		}
	}

	if ( !empty( $status ) ) {
		$args = array(
				'user_id' => $user_id,
				'post_id' => $lesson_id,
				'data' => $status,
				'type' => 'sensei_lesson_status', /* FIELD SIZE 20 */
				'action' => 'update' // Important to only have 1 entry
			);

error_log(" => Logging lesson status of '$status' for user $user_id on lesson $lesson_id, called from " .print_r($wp_current_filter, true));
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
 */
function imperial_sensei_user_lesson_quiz_status( $user_id, $quiz_id, $grade, $passmark, $quiz_grade_type = 'auto' ) {
	global $wpdb, $wp_current_filter;
error_log(__FUNCTION__);

	$status = '';
	$metadata = array();
	// Check if the lesson has questions...
	$lesson_id = get_post_meta( $quiz_id, '_quiz_lesson', true );
	$has_questions = get_post_meta( $lesson_id, '_quiz_has_questions', true );
	// ...if the lesson doesn't have questions the ultimate status is simple
	if ( !$has_questions ) {
		$status = 'complete'; // No quiz set
	}
	else if( !doing_action('sensei_user_quiz_submitted') ) {
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
	elseif ( is_wp_error( $grade ) || 'auto' != $quiz_grade_type ) {
		$status = 'ungraded'; // Quiz is manually graded and this was a user submission via 'sensei_user_quiz_submitted'
	}

	if ( !empty( $status ) ) {
		$existing_args = array(
				'user_id' => $user_id,
				'post_id' => $lesson_id,
				'type' => 'sensei_lesson_status', /* FIELD SIZE 20 */
			);
		$existing_status = WooThemes_Sensei_Utils::sensei_check_for_activity( $existing_args, true );
		if ( is_array( $existing_status ) ) {
			$existing_status = $existing_status[0];
		}
		// This is quicker than going through WooThemes_Sensei_Utils::sensei_check_for_activity()
//		$existing_status = $wpdb->get_var( $wpdb->prepare( "SELECT comment_content FROM $wpdb->comments WHERE comment_post_ID = %d AND user_id = %d AND comment_type = %s ", $lesson_id, $user_id, 'sensei_lesson_status' ) );
		$args = array(
				'user_id' => $user_id,
				'post_id' => $lesson_id,
				'data' => $status,
				'type' => 'sensei_lesson_status', /* FIELD SIZE 20 */
				'action' => 'update', // Important to only have 1 entry
			);

		// Don't update the time, it should stay as what the user last set
		if ( 'ungraded' == $existing_status->comment_content ) {
			$args['keep_time'] = true;
		}

error_log(" => Logging lesson status of '$status' for user $user_id on lesson $lesson_id, called from " .print_r($wp_current_filter, true));
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
add_action( 'sensei_user_quiz_grade', 'imperial_sensei_user_lesson_quiz_status', 10, 5 );
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
	global $woothemes_sensei;
error_log(__FUNCTION__);

	$status = 'in-progress';
	$metadata = array();
	$course_id = get_post_meta( $lesson_id, '_lesson_course', $single = true );
	$course_completion = $woothemes_sensei->settings->settings[ 'course_completion' ];
	// If the lesson 'completed' then update the overall Course percentage
	if ( !in_array( $lesson_status, array( 'in-progress', 'ungraded' ) ) ) {
error_log(" => processing percentage");
		// Now effectively copy utils::user_completed_course() but track the overall percentage
		$lessons_completed = $total_lessons = 0;
		$lesson_status_args = array(
				'user_id' => $user_id,
				'type' => 'sensei_lesson_status', /* FIELD SIZE 20 */
			);
		// Grab all of this Courses' lessons, looping through each...
		$lesson_ids = $woothemes_sensei->post_types->course->course_lessons( $course_id, 'publish', 'ids' );
		$total_lessons = count( $lesson_ids );
			// ...if course completion not set to 'passed', and all lessons are complete or graded, 
			// ......then all lessons are 'passed'
			// ...else if course completion is set to 'passed', check if each lesson has questions...
			// ......if no questions yet the status is 'complete'
			// .........then the lesson is 'passed'
			// ......else if questions check the lesson status has a grade and that the grade is greater than the lesson passmark
			// .........then the lesson is 'passed'
			// ...if all lessons 'passed' then update the course status to complete
		foreach( $lesson_ids as $lesson_id ) {
			// The below checks if a lesson is fully completed, though should be Utils::user_completed_lesson()
			// WP get_comments() doesn't allow multiple post_IDs, so check each one? (or have custom SQL?)
			$lesson_status_args['post_id'] = $lesson_id;
			$this_lesson_status = WooThemes_Sensei_Utils::sensei_check_for_activity( $lesson_status_args, true );
			if ( is_array( $this_lesson_status ) ) {
				$this_lesson_status = $this_lesson_status[0];
			}
			// If lessons are complete without needing quizzes to be passed
			if ( 'passed' != $course_completion ) {
				switch ( $this_lesson_status->comment_content ) {
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
				switch ( $this_lesson_status->comment_content ) {
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
error_log(__FUNCTION__);

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
error_log(__FUNCTION__);

	if ( !empty($status) ) {
		$args = array(
				'user_id'   => $user_id,
				'post_id'   => $course_id,
				'data'      => $status,
				'type'      => 'sensei_course_status', /* FIELD SIZE 20 */
				'action'    => 'update',// Update the existing status...
				'keep_time' => true, // ...but don't change the existing timestamp
			);
		switch( $status ) {
			case 'in-progress' :
				unset( $args['keep_time'] ); // Keep updating what's happened
				break;
		}

error_log(" => Logging course status of '$status' for user $user_id on course $course_id, called from " .print_r($wp_current_filter, true));
		$comment_id = WooThemes_Sensei_Utils::sensei_log_activity( $args );
		if ( $comment_id && !empty($metadata) ) {
			foreach( $metadata as $key => $value ) {
				update_comment_meta( $comment_id, $key, $value );
			}
		}
		do_action( 'sensei_course_status_updated', $status, $user_id, $course_id, $comment_id );
	}
}

/**
 * Change the meta boxes used across Sensei
 */
function imperial_modify_sensei_meta_boxes() {
	global $woothemes_sensei;

	$token = 'lesson';
	remove_meta_box( 'lesson-prerequisite', $token, 'side' );
	add_meta_box( 'lesson-prerequisite', __( 'Lesson Prerequisite', 'woothemes-sensei' ), 'imperial_lesson_prerequisite_meta_box_content_restricted_to_course', $token, 'side', 'default' );
}
add_action( 'admin_init', 'imperial_modify_sensei_meta_boxes' );

/**
 * Copy of the Lesson::lesson_prerequisite_meta_box_content() function but modified to restrict to the current Course
 * @global type $post
 */
function imperial_lesson_prerequisite_meta_box_content_restricted_to_course() {
	global $post;
	$token = 'lesson';
	// Get existing post meta
	$select_lesson_prerequisite = get_post_meta( $post->ID, '_lesson_prerequisite', true );
	$lesson_course = get_post_meta( $post->ID, '_lesson_course', true );
	// Get the Lesson Posts
	$post_args = array(	'post_type' 		=> 'lesson',
						'numberposts' 		=> -1,
						'orderby'         	=> 'title',
						'order'           	=> 'ASC',
						'exclude' 			=> $post->ID,
						'suppress_filters' 	=> 0,
						'meta_query' => array(
							array(
								'key' => '_lesson_course',
								'value' => $lesson_course,
							)
						),
						);
	$posts_array = get_posts( $post_args );
	// Build the HTML to Output
	$html = '';
	$html .= '<input type="hidden" name="' . esc_attr( 'woo_' . $token . '_noonce' ) . '" id="' . esc_attr( 'woo_' . $token . '_noonce' ) . '" value="' . esc_attr( wp_create_nonce( plugin_basename(__FILE__) ) ) . '" />';
	if ( count( $posts_array ) > 0 ) {
		$html .= '<select id="lesson-prerequisite-options" name="lesson_prerequisite" class="chosen_select widefat">' . "\n";
		$html .= '<option value="">' . __( 'None', 'woothemes-sensei' ) . '</option>';
			foreach ($posts_array as $post_item){
				$html .= '<option value="' . esc_attr( absint( $post_item->ID ) ) . '"' . selected( $post_item->ID, $select_lesson_prerequisite, false ) . '>' . esc_html( $post_item->post_title ) . '</option>' . "\n";
			} // End For Loop
		$html .= '</select>' . "\n";
	} else {
		$html .= '<p>' . esc_html( __( 'No lessons exist yet. Please add some first.', 'woothemes-sensei' ) ) . '</p>';
	} // End If Statement
	// Output the HTML
	echo $html;
} // End imperial_lesson_prerequisite_meta_box_content_restricted_to_course()


