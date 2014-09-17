<?php
// This file contains material which is the pre-existing property of Psycle Interactive Limited.
// Copyright (c) 2014 Psycle Interactive. All rights reserved.

// Contains Filters and Actions specifically for the Admin of Sensei 

/**
 * Adds the form to allow Bulk grading of Quizzes when viewing a single Lesson on the 
 */
function imperial_sensei_bulk_autograde_quizzes_form() {
	if( isset( $_GET['lesson_id'] ) ) {
		$lesson_id = intval( $_GET['lesson_id'] );
		$lesson_title = get_the_title( $lesson_id );
		?>
<form method="POST" class="auto-grade">
	<?php
		wp_nonce_field( $action = 'sensei_bulk_autograde-' . $lesson_id, '_wp_bag_nonce' );
	
		$value = esc_attr__( 'Bulk auto grade', 'woothemes-sensei' );
		$atts = array( 
			'title' => sprintf( __('Bulk auto grade all the submissions for the %s Quiz', 'imperial' ), $lesson_title ),
		);
		submit_button( $value, 'primary', 'bulk-auto-grade', false, $atts ); 
		// add the other GET variables!
		$get_args = array( 'page', 'programme_id', 'course_id', 'lesson_id', 'grading_status' );
		foreach ( $get_args AS $arg ) {
			echo '<input type="hidden" name="' . $arg . '" value="' . esc_attr( $_GET[$arg] ) . '" />';
		}
		?>
	
</form>
	<?php
	}
}

/**
 * Process the Bulk auto grading request
 */
function imperial_sensei_bulk_autograde_quizzes() {
	// Ensure the form is added, and after the normal filters are shown
	add_action( 'sensei_before_list_table', 'imperial_sensei_bulk_autograde_quizzes_form', 20 );
	$lesson_id = intval( $_POST['lesson_id'] );
	if ( isset( $_POST['bulk-auto-grade'] ) && wp_verify_nonce( $_REQUEST['_wp_bag_nonce'], 'sensei_bulk_autograde-' . $lesson_id ) ) {
		global $woothemes_sensei, $current_user;
		$backup_user_id = get_current_user_id(); // Needed for later

		$quiz_id = $woothemes_sensei->post_types->lesson->lesson_quizzes( $lesson_id );
		// Get quiz pass setting
		$pass_required = get_post_meta( $quiz_id, '_pass_required', true );
		$_GET['quiz_id'] = $quiz_id; // Used later

		$cnt = 0;
		// Any user who has answered questions should have a Grade
		$quiz_submissions = WooThemes_Sensei_Utils::sensei_check_for_activity( array( 'post_id' => $quiz_id, 'type' => 'sensei_quiz_asked' ), true );
		foreach( $quiz_submissions as $submission ) {
			$user_id = $submission->user_id;
			// Has the user got a grade already?
			$quiz_grade = WooThemes_Sensei_Utils::sensei_get_activity_value( array( 'post_id' => $quiz_id, 'user_id' => $user_id, 'type' => 'sensei_quiz_grade', 'field' => 'comment_content' ) );
			if ( empty($quiz_grade) ) {
//				error_log( "Submission: $submission->comment_ID; User: $user_id no grade on quiz: $quiz_id");

				// For the next bits we need to "be" the user
				wp_set_current_user( $user_id );
				$current_user = wp_get_current_user();

				// sensei_grade_quiz_auto() expects to be run from a POST with that containing what the user sent, we
				// need to fake this by getting the questions they answered and building a POST
				// Get the questions of the user, (for Lesson_quiz_questions to restrict to a single users answers we need to pretend to be elsewhere)
				$_GET['user'] = $user_id;
				$questions_answered = $woothemes_sensei->post_types->lesson->lesson_quiz_questions( $quiz_id );

				$fake_posted_answers = array();
				foreach ( $questions_answered AS $question ) {
					$answer = WooThemes_Sensei_Utils::sensei_get_activity_value( array( 'post_id' => $question->ID, 'user_id' => $user_id, 'type' => 'sensei_user_answer', 'field' => 'comment_content' ) );
					$answer = maybe_unserialize( base64_decode( $answer ) );
//					error_log( "Question: $question->ID; Answer: " . print_r($answer, true));
					$fake_posted_answers[$question->ID] = $answer;
				}
				$quiz_grade = WooThemes_Sensei_Utils::sensei_grade_quiz_auto( $quiz_id, $fake_posted_answers ); // Don't need to provide the other 2 args

				// This bit duplicates some of Sensei => Frontend->sensei_complete_quiz()
					if ( $pass_required && !is_wp_error( $quiz_grade) ) {
						if ( $quiz_passmark <= $quiz_grade ) {
							// Student has reached the pass mark and lesson is complete
							$args = array(
										'post_id' => $lesson_id,
										'username' => $current_user->user_login,
										'user_email' => $current_user->user_email,
										'user_url' => $current_user->user_url,
										'data' => __( 'Lesson completed and passed by the user', 'woothemes-sensei' ),
										'type' => 'sensei_lesson_end', /* FIELD SIZE 20 */
										'parent' => 0,
										'user_id' => $current_user->ID
									);
							$activity_logged = WooThemes_Sensei_Utils::sensei_log_activity( $args );

							do_action( 'sensei_user_lesson_end', $current_user->ID, $lesson_id );

						} // End If Statement
					} else {
						// Mark lesson as complete
						$args = array(
									'post_id' => $lesson_id,
									'username' => $current_user->user_login,
									'user_email' => $current_user->user_email,
									'user_url' => $current_user->user_url,
									'data' => __( 'Lesson completed by the user', 'woothemes-sensei' ),
									'type' => 'sensei_lesson_end', /* FIELD SIZE 20 */
									'parent' => 0,
									'user_id' => $current_user->ID
								);
						$activity_logged = WooThemes_Sensei_Utils::sensei_log_activity( $args );

						do_action( 'sensei_user_lesson_end', $current_user->ID, $lesson_id );

					} // End If Statement
				$cnt++;
			} // No grade so auto grade
		} // Each submission
//		error_log( "Tracked: $cnt;");
		// Restore the correct user
		wp_set_current_user( $backup_user_id );
		// Unset the fake variables
		unset( $_GET['quiz_id'] );
		unset( $_GET['user'] );
	} // nonce verify
}
add_action( 'sensei_page_sensei_grading', 'imperial_sensei_bulk_autograde_quizzes', 9 ); // Just before the page loads

/**
 * Filters the User args used in the Sensei Grading admin to be restricted to those on the current Programme
 * 
 * @param type $args
 * @return type
 */
function imperial_sensei_filter_users_by_programme( $args ) {
	global $wpdb;
	// Restrict to the current site
//	$args['meta_key'] = $wpdb->get_blog_prefix() . 'capabilities';
//	$args['meta_value'] = 'a:2:{s:10:"subscriber";b:1;s:15:"bbp_participant";b:1;}'; // This is naughty as not fool proof
	$args['role'] = 'subscriber';
	if( isset( $_GET['programme_id'] ) ) {
		$selected_programme_id = intval( $_GET['programme_id'] );
		$prog_user_ids = imperial()->get_programme_user_ids( $selected_programme_id );
//		error_log('$prog_user_ids:'.print_r($prog_user_ids, true));
		if ( $prog_user_ids ) {
			if ( !empty($args['include']) ) {
				// If there already is a restriction (for whatever reason) don't completely overwrite it
				$args['include'] = array_intersect( $args['include'], $prog_user_ids );
			} 
			else {
				$args['include'] = $prog_user_ids;
			}
		}
		else {
			// No Students on Programme, shortcut
			$args['include'] = array( 1 );
		}
	}
	return $args;
}
add_filter( 'sensei_grading_filter_users', 'imperial_sensei_filter_users_by_programme' );
add_filter( 'sensei_analysis_filter_users', 'imperial_sensei_filter_users_by_programme' );

/**
 * 
 * @global type $wpdb
 * @param type $args
 * @return type
 */
function imperial_sensei_filter_activity_by_programme_users( $args ) {
	global $wpdb;
	if( isset( $_GET['programme_id'] ) ) {
		$selected_programme_id = intval( $_GET['programme_id'] );
		$prog_user_ids = imperial()->get_programme_user_ids( $selected_programme_id );
//		error_log('$prog_user_ids:'.print_r($prog_user_ids, true));
		if ( $prog_user_ids ) {
			$args['user_id'] = $prog_user_ids;
		}
	}
	return $args;
}
add_filter( 'sensei_learners_filter_activity_users', 'imperial_sensei_filter_activity_by_programme_users' );
add_filter( 'sensei_activity_total_courses_started', 'imperial_sensei_filter_activity_by_programme_users' );
add_filter( 'sensei_activity_total_courses_ended', 'imperial_sensei_filter_activity_by_programme_users' );
add_filter( 'sensei_activity_total_quiz_grades', 'imperial_sensei_filter_activity_by_programme_users' );
add_filter( 'sensei_analysis_activity_courses_started', 'imperial_sensei_filter_activity_by_programme_users' );
add_filter( 'sensei_analysis_activity_courses_ended', 'imperial_sensei_filter_activity_by_programme_users' );
add_filter( 'sensei_analysis_activity_lessons_started', 'imperial_sensei_filter_activity_by_programme_users' );
add_filter( 'sensei_analysis_activity_lessons_ended', 'imperial_sensei_filter_activity_by_programme_users' );
add_filter( 'sensei_analysis_activity_lesson_grades', 'imperial_sensei_filter_activity_by_programme_users' );


/**
 * Filters the Lesson args used in the Sensei Grading admin to be restricted to those that have quizzes with questions and to Courses in the current Programme
 * 
 * @param type $args
 * @return type
 */
function imperial_sensei_grading_filter_lessons_pieces( $pieces ) {
	if ( false === strstr( $pieces['where'], ' comment_post_ID = ' ) ) {
		$quiz_lesson_args = array(
			'post_type'         => 'lesson',
			'numberposts'       => -1,
			'meta_query'        => array(
				array(
					'key'       => '_quiz_has_questions',
					'value'     => 1,
					'compare'   => 'EXISTS',
				),
			),
			'fields'            => 'ids',
			'suppress_filters'  => 0
		);
		if( isset( $_GET['programme_id'] ) ) {
			$selected_programme_id = intval( $_GET['programme_id'] );
			$prog_course_ids = imperial()->get_courses_by_programme( $selected_programme_id, false, 'ids' );
	//			error_log('$prog_course_ids:'.print_r($prog_course_ids, true));
			$quiz_lesson_args['meta_query'][] =
				array(
					'key' => '_lesson_course',
					'value' => $prog_course_ids,
					'compare' => 'IN',
				);
		}
//		else {
//			$lesson_ids = array( 1 );
//		}
		$lesson_ids = get_posts( $quiz_lesson_args );
//		error_log('$lesson_ids:'.print_r($lesson_ids, true));
		if ( $lesson_ids ) {
			$pieces['where'] .= ' AND comment_post_ID IN (' . implode( ',', array_map( 'absint', $lesson_ids ) ) . ') ';
		}
	}
	return $pieces;
}
add_filter( 'sensei_grading_filter_lessons_pieces', 'imperial_sensei_grading_filter_lessons_pieces' );


/**
 * Add a Programme dropdown to the Sensei Analyis and Learners screens
 */
function imperial_sensei_before_dropdown_filters() {
	if ( doing_filter('sensei_analysis_after_headers') && isset($_GET['user']) && $_GET['user'] > 0 ) {
		return;
	}
	$programmes_array = imperial()->get_programmes();

	$selected_programme_id = 0;
	if ( isset( $_GET['programme_id'] ) ) {
		$selected_programme_id = intval( $_GET['programme_id'] );
	} // End If Statement

	echo '<div class="select-box">' . "\n";

		echo '<select id="sensei-programme-options" name="sensei_programme" class="chosen_select widefat">' . "\n";
			echo '<option value="">' . __( 'Select a programme', 'woothemes-sensei' ) . '</option>';
			if ( count( $programmes_array ) > 0 ) {
				foreach ($programmes_array as $post_item){
					echo '<option value="' . esc_attr( absint( $post_item->ID ) ) . '" ' . selected( $post_item->ID, $selected_programme_id, false ) . '>' . esc_html( $post_item->post_title ) . '</option>' . "\n";
				} // End For Loop
			} // End If Statement
		echo '</select>' . "\n";

	echo '</div>' . "\n";
}
add_action( 'sensei_learners_before_dropdown_filters', 'imperial_sensei_before_dropdown_filters' );
add_action( 'sensei_analysis_after_headers', 'imperial_sensei_before_dropdown_filters' );

/**
 * Add a Programme dropdown to the Sensei Grading
 */
function imperial_sensei_grading_before_dropdown_filters() {
	$programmes_array = imperial()->get_programmes();
	$selected_programme_id = 0;
	if ( isset( $_GET['programme_id'] ) ) {
		$selected_programme_id = intval( $_GET['programme_id'] );
	} // End If Statement

	echo '<div class="select-box">' . "\n";

		echo '<select id="grading-programme-options" name="grading_programme" class="chosen_select widefat">' . "\n";
			echo '<option value="">' . __( 'Select a programme', 'woothemes-sensei' ) . '</option>';
			if ( count( $programmes_array ) > 0 ) {
				foreach ($programmes_array as $post_item){
					echo '<option value="' . esc_attr( absint( $post_item->ID ) ) . '" ' . selected( $post_item->ID, $selected_programme_id, false ) . '>' . esc_html( $post_item->post_title ) . '</option>' . "\n";
				} // End For Loop
			} // End If Statement
		echo '</select>' . "\n";

	echo '</div>' . "\n";

}
add_action( 'sensei_grading_before_dropdown_filters', 'imperial_sensei_grading_before_dropdown_filters' );

/**
 * 
 * @param type $args
 * @return int
 */
function imperial_sensei_filter_courses_by_programme( $args ) {
	if ( isset( $_GET['programme_id'] ) ) {
		$selected_programme_id = intval( $_GET['programme_id'] );
		$courses_ids = imperial()->get_courses_by_programme( $selected_programme_id, false, 'ids' );
		if ( empty($courses_ids) ) {
			$courses_ids = array( 0 );
		}
		$args['post__in'] = $courses_ids;
//		error_log(__FUNCTION__.':'.print_r($args,true));
	} // End If Statement
	elseif ( // doing_filter('sensei_grading_filter_courses') || 
			( doing_filter('sensei_learners_filter_courses') && empty( $_GET['course_cat'] ) ) 
		) {
		$args['post__in'] = array( 0 );
	}
	return $args;
}
add_filter( 'sensei_course_count', 'imperial_sensei_filter_courses_by_programme' );
add_filter( 'sensei_grading_filter_courses', 'imperial_sensei_filter_courses_by_programme' );
add_filter( 'sensei_learners_filter_courses', 'imperial_sensei_filter_courses_by_programme' );
add_filter( 'sensei_analysis_filter_courses', 'imperial_sensei_filter_courses_by_programme' );

/**
 * 
 * @param type $args
 * @return type
 */
function imperial_sensei_filter_lessons_by_programme( $args ) {
	if ( isset( $_GET['programme_id'] ) ) {
		$selected_programme_id = intval( $_GET['programme_id'] );
		$courses_ids = imperial()->get_courses_by_programme( $selected_programme_id, false, 'ids' );
//		if ( empty($courses_ids) ) {
//			$courses_ids = array( 0 );
//		}
		if ( empty($args['meta_query']) ) {
			$args['meta_query'] = array();
		}
		$args['meta_query'][] = 
			array(
				'key' => '_lesson_course',
				'value' => $courses_ids,
				'compare' => 'IN',
			);
	}
	return $args;
}
add_filter( 'sensei_lesson_count', 'imperial_sensei_filter_lessons_by_programme' );
add_filter( 'sensei_analysis_filter_lessons', 'imperial_sensei_filter_lessons_by_programme' );
add_filter( 'sensei_learners_filter_lessons', 'imperial_sensei_filter_lessons_by_programme' );

/**
 * 
 * @param type $args
 * @return type
 */
function imperial_sensei_filter_lessons_by_quiz( $args ) {
	if ( empty($args['meta_query']) ) {
		$args['meta_query'] = array();
	}
	$args['meta_query'][] = 
		array(
			'key'       => '_quiz_has_questions',
			'value'     => 1,
			'compare'   => 'EXISTS',
		);
	return $args;
}
//add_filter( 'sensei_lesson_count', 'imperial_sensei_filter_lessons_by_quiz' );
add_filter( 'sensei_analysis_filter_lessons', 'imperial_sensei_filter_lessons_by_quiz' );
add_filter( 'sensei_grading_filter_lessons', 'imperial_sensei_filter_lessons_by_quiz' );
add_filter( 'sensei_learners_filter_lessons', 'imperial_sensei_filter_lessons_by_quiz' );

/**
 * 
 */
function imperial_sensei_analysis_total_users( $total, $user_counts ) {
	if ( isset( $_GET['programme_id'] ) ) {
		$selected_programme_id = intval( $_GET['programme_id'] );
		$prog_user_ids = imperial()->get_programme_user_ids( $selected_programme_id );
		return count( $prog_user_ids );
	}
	return $user_counts['avail_roles']['subscriber'];
}
add_filter( 'sensei_analysis_total_users', 'imperial_sensei_analysis_total_users', 10, 2 );

/**
 * 
 */
function imperial_sensei_get_courses_dropdown() {
	// Parse POST data
	$data = $_POST['data'];
	$course_data = array();
	parse_str($data, $course_data);

	$selected_programme_id = intval( $course_data['programme_id'] );
//	error_log(__FUNCTION__ .':'.print_r($programme_id, true));

	$html = '';
	if ( 0 < intval( $selected_programme_id ) ) {

		$courses_ids = imperial()->get_courses_by_programme( $selected_programme_id, false, 'ids' );
		if ( empty($courses_ids) ) {
			$courses_ids = array( 0 );
		}
//		error_log(__FUNCTION__ .':'.print_r($courses_ids, true));
		$post_args = array(	'post_type' 		=> 'course',
							'numberposts' 		=> -1,
							'orderby'         	=> 'title',
							'order'           	=> 'ASC',
							'post_status'      	=> 'any',
							'suppress_filters' 	=> 0,
							'post__in'          => $courses_ids,
							);
//		error_log(__FUNCTION__ .':'.print_r($post_args, true));
		$posts_array = get_posts( $post_args );

		$html .= '<option value="">' . __( 'Select a course', 'woothemes-sensei' ) . '</option>';
		if ( count( $posts_array ) > 0 ) {
			foreach ($posts_array as $post_item){
				$html .= '<option value="' . esc_attr( absint( $post_item->ID ) ) . '">' . esc_html( $post_item->post_title ) . '</option>' . "\n";
			} // End For Loop
		} // End If Statement

	} // End If Statement
	
	echo $html;
	die(); // WordPress may print out a spurious zero without this can be particularly bad if using JSON
}
add_action( 'wp_ajax_get_courses_dropdown', 'imperial_sensei_get_courses_dropdown' );

/**
 * 
 * @param type $args
 * @return type
 */
function imperial_sensei_learners_main_column_data( $args ) {
	if ( !empty( $_GET['programme_id'] ) ) {
		$selected_programme_id = intval( $_GET['programme_id'] );
		preg_match( '/href="([^"]+)"/', $args['action'], $url );
		$args['action'] = str_replace( $url[1], add_query_arg( array( 'programme_id' => $selected_programme_id ), $url[1] ), $args['action'] );
	}
	if ( false !== strstr( $args['action'], 'view=learners' ) && false === strstr( $args['action'], 'lesson_id=' ) ) {
		preg_match( '/href="([^"]+)"/', $args['action'], $url );
		$args['action'] = str_replace( $url[1], add_query_arg( array( 'view' => 'lessons' ), $url[1] ), $args['action'] );
	}
	return $args;
}
add_filter( 'sensei_learners_main_column_data', 'imperial_sensei_learners_main_column_data' );

/**
 * Filter the column data shown, remove general admin edit links, add Programme ID to the other links, change text
 * @param type $args
 * @return type
 */
function imperial_sensei_analysis_main_column_data( $args ) {
	if ( !empty( $_GET['programme_id'] ) ) {
		$arr_arg = '';
		$selected_programme_id = intval( $_GET['programme_id'] );
		if ( !empty( $args['lesson_title'] ) ) {
			$arr_arg = 'lesson_title';
		} elseif ( !empty( $args['course_title'] ) ) {
			$arr_arg = 'course_title';
		} elseif ( !empty( $args['user_login'] ) ) {
			$arr_arg = 'user_login';
		}
		if ( $arr_arg ) {
			preg_match( '/href="([^"]+)"/', $args[$arr_arg], $url );
			$args[$arr_arg] = str_replace( $url[1], add_query_arg( array( 'programme_id' => $selected_programme_id ), $url[1] ), $args[$arr_arg] );
		}
	}
	foreach ( $args as $key => $value ) {
		// Remove admin "edit" links
		if ( strpos( $value, 'action=edit' ) && preg_match( '/href="[^"]+' . 'action=edit' . '[^>]+>([^<]+)</', $value, $match ) ) {
			$args[ $key ] = $match[1];
		}
		if ( strpos( $value, 'Remove from activity' ) ) {
			$args[ $key ] = str_replace( 'Remove from activity', 'Reset activity', $value );
		}
	}
	
	return $args;
}
add_filter( 'sensei_analysis_overview_columns_column_data', 'imperial_sensei_analysis_main_column_data' );
add_filter( 'sensei_analysis_overview_lessons_column_data', 'imperial_sensei_analysis_main_column_data' );
add_filter( 'sensei_analysis_lesson_column_data', 'imperial_sensei_analysis_main_column_data' );
add_filter( 'sensei_analysis_course_lesson_column_data', 'imperial_sensei_analysis_main_column_data' );
add_filter( 'sensei_learners_main_column_data', 'imperial_sensei_analysis_main_column_data' );


/**
 * 
 * @param type $url
 */
function imperial_sensei_ajax_redirect_url( $url ) {
	$data = $_POST['data'];
	$lesson_data = array();
	parse_str($data, $lesson_data);

	$selected_programme_id = intval( $lesson_data['programme_id'] );
	return add_query_arg( array( 'programme_id' => $selected_programme_id ), $url );
}
add_filter( 'sensei_ajax_redirect_url', 'imperial_sensei_ajax_redirect_url' );

/**
 * This adds JS for the Admin to help with Imperial Sensei changes
 */
function imperial_sensei_admin_scripts() {
	$imp = imperial();
	$sensei_js = $imp->js_url( 'imperial_sensei.js' );
	if ( !empty( $sensei_js ) ) {
		wp_enqueue_script( 'imperial_sensei', $sensei_js, array( 'jquery' ), $imp->version, $in_footer = true );
	}
}
add_action( 'admin_print_scripts', 'imperial_sensei_admin_scripts', 20 );

/**
 * 
 * @param type $no_items_text
 */
function imperial_sensei_grading_no_items_text( $no_items_text ) {
	if ( !isset( $_GET['programme_id'] ) ) {
		$no_items_text = 'Please select a Programme, then a Course, then an Activity first.';
	}
	return $no_items_text;
}
add_filter( 'sensei_grading_no_items_text', 'imperial_sensei_grading_no_items_text' );

/**
 * 
 * @param type $no_items_text
 */
function imperial_sensei_learners_no_items_text( $no_items_text ) {
	if ( !isset( $_GET['programme_id'] ) ) {
		$no_items_text = 'Please select a Programme first.';
	}
	return $no_items_text;
}
add_filter( 'sensei_learners_no_items_text', 'imperial_sensei_learners_no_items_text' );

/**
 * 
 */
function imperial_sensei_learners_add_learner_form() {
	if ( !empty( $_GET['programme_id'] ) ) {
		$selected_programme_id = intval( $_GET['programme_id'] );
?>
		<input type="hidden" name="programme_id" value="<?php echo $selected_programme_id; ?>" />
<?php
	}
}
add_action( 'sensei_learners_add_learner_form', 'imperial_sensei_learners_add_learner_form' );

/**
 * 
 * @param type $url
 * @return type
 */
function imperial_sensei_learners_add_learner_redirect_url( $url ) {
	if ( !empty( $_POST['programme_id'] ) ) {
		$selected_programme_id = intval( $_POST['programme_id'] );
		$url = add_query_arg( array( 'programme_id' => $selected_programme_id ), $url );
	}
	return $url;
}
add_filter( 'sensei_learners_add_learner_redirect_url', 'imperial_sensei_learners_add_learner_redirect_url' );

/**
 * 
 */
function imperial_sensei_after_list_table_capture() {
	ob_start();
}
add_action( 'sensei_after_list_table', 'imperial_sensei_after_list_table_capture', 5 );

/**
 * 
 */
function imperial_sensei_after_list_table_output() {
	$html = ob_get_clean();
	if ( !empty( $_GET['programme_id'] ) ) {
		$selected_programme_id = intval( $_GET['programme_id'] );
		$html = preg_replace( '/href="([^"]+)"/', 'href="$1&programme_id='.$selected_programme_id.'"', $html );
	}
	echo $html;
}
add_action( 'sensei_after_list_table', 'imperial_sensei_after_list_table_output', 20 );


// Remove all column ordering
add_filter( 'sensei_grading_main_columns_sortable', '__return_empty_array' );
add_filter( 'sensei_analysis_overview_courses_columns_sortable', '__return_empty_array' );
add_filter( 'sensei_analysis_overview_lessons_columns_sortable', '__return_empty_array' );
add_filter( 'sensei_analysis_overview_users_columns_sortable', '__return_empty_array' );
add_filter( 'sensei_analysis_lesson_columns_sortable', '__return_empty_array' );
add_filter( 'sensei_analysis_user_profile_columns_sortable', '__return_empty_array' );
add_filter( 'sensei_analysis_course_user_columns_sortable', '__return_empty_array' );
add_filter( 'sensei_analysis_course_lesson_columns_sortable', '__return_empty_array' );
add_filter( 'sensei_learners_learners_columns_sortable', '__return_empty_array' );
add_filter( 'sensei_learners_default_columns_sortable', '__return_empty_array' );
