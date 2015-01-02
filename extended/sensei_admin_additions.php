<?php
// This file contains material which is the pre-existing property of Psycle Interactive Limited.
// Copyright (c) 2014 Psycle Interactive. All rights reserved.

// Contains Filters, Actions and functions specifically for the Admin of Sensei both changing and extending it

/***************************************************
 * BULK AUTO GRADING OF QUIZZES
 ***************************************************/

/**
 * Adds the form to allow Bulk grading of Quizzes when viewing a single Lesson on the 
 */
function imperial_sensei_bulk_autograde_quizzes_form() {
	if( !empty( $_GET['lesson_id'] ) ) {
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
		?>
	<label for="force-regrading">
		<input type="checkbox" name="force-regrading" id="force-regrading" value="1" />
		<?php _e('Force Re-Grading', 'imperial'); ?>
	</label>
	<?php
		// add the other GET variables!
		$get_args = array( 'page', 'programme_id', 'course_id', 'lesson_id', 'view' );
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
	global $woothemes_sensei, $current_user;
	// Ensure the form is added, and after the normal filters are shown
	add_action( 'sensei_grading_extra', 'imperial_sensei_bulk_autograde_quizzes_form', 20 );
	if( !empty( $_GET['lesson_id'] ) ) {
		$lesson_id = intval( $_GET['lesson_id'] );
		$force_regrading = isset( $_POST['force-regrading'] ) ? true : false;
		if ( isset( $_POST['bulk-auto-grade'] ) && wp_verify_nonce( $_REQUEST['_wp_bag_nonce'], 'sensei_bulk_autograde-' . $lesson_id ) ) {

			$backup_user_id = get_current_user_id(); // Needed for later

			$quiz_id = $woothemes_sensei->post_types->lesson->lesson_quizzes( $lesson_id );
			// Get quiz pass setting
			$pass_required = get_post_meta( $quiz_id, '_pass_required', true );
			$quiz_grade_type = get_post_meta( $quiz_id, '_quiz_grade_type', true );
			$_GET['quiz_id'] = $quiz_id; // Used later

			$cnt = 0;
			// Any user who has answered questions should have a Grade
			$quiz_submissions = WooThemes_Sensei_Utils::sensei_check_for_activity( array( 'post_id' => $lesson_id, 'type' => 'sensei_lesson_status' ), true );
			// We need the result always as an array
			if ( !is_array($quiz_submissions) ) {
				$quiz_submissions = array( $quiz_submissions );
			}
			foreach( $quiz_submissions as $submission ) {
				$user_id = $submission->user_id;
				$bulk_grade = false;
				if ( $force_regrading ) {
					$bulk_grade = true;
				}
				elseif ( 'ungraded' == $submission->comment_approved ) {
					$bulk_grade = true;
				}
				else {
					// Has the user got a grade already?
					$quiz_grade = get_comment_meta( $submission->comment_ID, 'grade', true );
					if ( empty($quiz_grade) ) {
						$bulk_grade = true;
					}
				}
//				error_log( "Submission: $submission->comment_ID; User: $user_id; quiz: $quiz_id; grade: $quiz_grade; grade2: ".print_r($lesson_status, true));
				if ( $bulk_grade ) {
//					error_log( "Submission: $submission->comment_ID; User: $user_id no grade on quiz: $quiz_id");

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
						$answer = wp_slash( maybe_unserialize( base64_decode( $answer ) ) );
	//					error_log( "Question: $question->ID; Answer: " . print_r($answer, true));
						$fake_posted_answers[$question->ID] = $answer;
					}
					$quiz_grade = WooThemes_Sensei_Utils::sensei_grade_quiz_auto( $quiz_id, $fake_posted_answers ); // Don't need to provide the other 2 args

					// This bit duplicates some of Sensei => Frontend->sensei_complete_quiz()
						$lesson_metadata = array();
						// Get Lesson Grading Setting
						if ( is_wp_error( $quiz_grade ) ) { // || 'auto' != $quiz_grade_type ) {
							$lesson_status = 'ungraded'; // Had an error is forced grading, will have to be manual
						}
						else {
							// Quiz has been automatically Graded
							if ( $pass_required ) {
								// Student has reached the pass mark and lesson is complete
								if ( $quiz_passmark <= $quiz_grade ) {
									$lesson_status = 'passed';
								}
								else {
									$lesson_status = 'failed';
								} // End If Statement
							}
							// Student only has to partake the quiz
							else {
								$lesson_status = 'graded';
							}
							$lesson_metadata['grade'] = $quiz_grade; // Technically already set as part of "WooThemes_Sensei_Utils::sensei_grade_quiz_auto()" above
						}

						WooThemes_Sensei_Utils::update_lesson_status( $current_user->ID, $lesson_id, $lesson_status, $lesson_metadata );

						switch( $lesson_status ) {
							case 'passed' :
							case 'graded' :
								do_action( 'sensei_user_lesson_end', $current_user->ID, $lesson_id );
								// As the frontend isn't getting added we need to manually call the check for Course completion
								$course_id = get_post_meta( $lesson_id, '_lesson_course', true );
								WooThemes_Sensei_Utils::user_complete_course( $course_id, $current_user->ID );

							break;
						}

						// Fake as if it was an auto quiz (bulk grading is to turn a manual quiz to auto)
						do_action( 'sensei_user_quiz_submitted', $current_user->ID, $quiz_id, $quiz_grade, $quiz_passmark, $quiz_grade_type );

					$cnt++;
				} // No grade so auto grade
			} // Each submission
//			error_log( "Tracked: $cnt;");
			// Restore the correct user
			wp_set_current_user( $backup_user_id );
			// Unset the fake variables
			unset( $_GET['quiz_id'] );
			unset( $_GET['user'] );
		} // nonce verify
	} // check for lesson id
}
add_action( 'sensei_page_sensei_grading', 'imperial_sensei_bulk_autograde_quizzes', 9 ); // Just before the page loads


/***************************************************
 * CSV EXPORT EXTENDING
 ***************************************************/

/**
 * Add columns to existing Sensei CSV output of Analysis data
 * 
 * @param type $columns
 * @param type $analysis_obj
 * @return type
 */
function imperial_sensei_analysis_csv_columns( $columns, $analysis_obj ) {
	if ( $analysis_obj->csv_output ) {
		$csv_columns = false;
		// Overview users
		if ( doing_filter( 'sensei_analysis_overview_columns' ) && 'users' == $analysis_obj->type ) {
			$csv_columns = true;
		}

		// Users on a specific Course
		if ( doing_filter( 'sensei_analysis_course_columns' ) && 'user' == $analysis_obj->view ) {
			$csv_columns = true;
		}

		// Users on a specific Lesson
		if ( doing_filter( 'sensei_analysis_lesson_columns' ) ) {
			$csv_columns = true;
		}

		if ( $csv_columns ) {
			$csv_columns = array(
				'progcode' => __( 'Programme Code', 'imperial' ),
				'cid' => __( 'Student CID', 'imperial' ),
				'username' => __( 'Username', 'imperial' ),
				'firstname' => __( 'First Name', 'imperial' ),
				'lastname' => __( 'Last Name', 'imperial' ),
			);
			unset( $columns['title'] );
			$columns = array_merge( $csv_columns, $columns );
		}
	}
	return $columns;
}
add_filter( 'sensei_analysis_overview_columns', 'imperial_sensei_analysis_csv_columns', 10, 2 );
add_filter( 'sensei_analysis_course_columns', 'imperial_sensei_analysis_csv_columns', 10, 2 );
add_filter( 'sensei_analysis_lesson_columns', 'imperial_sensei_analysis_csv_columns', 10, 2 );

/**
 * Display additional columns on the existing Sensei CSV export of Analysis data
 * 
 * @global type $wpdb
 * @global type $csv_user_prog_ids
 * @global type $csv_programme_codes
 * @param type $data
 * @param type $item
 * @param type $analysis_obj
 * @return type
 */
function imperial_sensei_analysis_csv_column_data( $data, $item, $analysis_obj ) {
	global $wpdb, $csv_user_prog_ids, $csv_programme_codes;

	if ( $analysis_obj->csv_output ) {
		$csv_data = $user = false;
		// Overview users
		if ( doing_filter( 'sensei_analysis_overview_column_data' ) && 'users' == $analysis_obj->type ) {
			$csv_data = true;
			$user = new WP_User;
			$user->init( $item );
		}

		// Users on a specific Course
		if ( doing_filter( 'sensei_analysis_course_column_data' ) && 'user' == $analysis_obj->view ) {
			$csv_data = true;
			$user = get_user_by( 'id', $item->user_id );
		}

		// Users on a specific Lesson
		if ( doing_filter( 'sensei_analysis_lesson_column_data' ) ) {
			$csv_data = true;
			$user = get_user_by( 'id', $item->user_id );
		}

		// The data to add massively increases overall speed of the export. This could/should be cached
		if ( $csv_data && $user ) {
			if ( !isset($csv_user_prog_ids) ) {
				$csv_user_prog_ids = array();
				$connections = p2p_get_connections( 'student_programme' );
				foreach( $connections AS $connection ) {
					$csv_user_prog_ids[ $connection->p2p_from ] = $connection->p2p_to; // Only store 1 entry (whatever's last)
				}
			}
			if ( !isset($csv_programme_codes) ) {
				$csv_programme_codes = array();
				$results = $wpdb->get_results( "SELECT ID, post_name FROM $wpdb->posts WHERE post_type = 'programme' ", ARRAY_A );
				foreach( $results AS $result ) {
					$csv_programme_codes[ $result['ID'] ] = strtoupper( $result['post_name'] );
				}
			}
			$csv_data = array(
				'progcode' => $csv_programme_codes[ $csv_user_prog_ids[ $user->id ] ],
				'cid' => $user->cid,
				'username' => $user->user_login,
				'firstname' => $user->first_name,
				'lastname' => $user->last_name,
			);
			unset( $data['title'] );
			$data = array_merge($csv_data, $data);
		}
	}
	return $data;
}
add_filter( 'sensei_analysis_overview_column_data', 'imperial_sensei_analysis_csv_column_data', 10, 3 );
add_filter( 'sensei_analysis_course_column_data', 'imperial_sensei_analysis_csv_column_data', 10, 3 );
add_filter( 'sensei_analysis_lesson_column_data', 'imperial_sensei_analysis_csv_column_data', 10, 3 );

/**
 * Adds a new CSV Export of all Learners answers for a specific Lesson/Quiz
 * 
 * @global type $page_hook
 */
function imperial_sensei_export_quiz_questions_button() {
	global $page_hook;

	$lesson_id = 0;
	if( isset( $_GET['lesson_id'] ) ) {
		$lesson_id = intval( $_GET['lesson_id'] );
	}
	if ( 'sensei_page_sensei_analysis' == $page_hook && !empty($lesson_id) ) {
		$slug = str_replace( 'sensei_page_', '', $page_hook );
		$lesson = get_post( $lesson_id );
		if ( 'lesson' == $lesson->post_type && false != get_post_meta( $lesson->ID, '_quiz_has_questions', true ) ) {
			$report = sanitize_title( $lesson->post_title ) . '-per-learner-answers';
			$url = add_query_arg( array( 'page' => $slug, 'lesson_id' => $lesson_id, 'imp_sensei_answers_report_download' => $report ), admin_url( 'admin.php' ) );
			echo ' <a class="button button-primary" href="' . wp_nonce_url( $url, 'imp_sensei_csv_download-' . $report . $lesson_id, '_sdl_nonce' ) . '">' . __( 'Export all Answers (CSV)', 'woothemes-sensei' ) . '</a>';
		} // is lesson and has questions checks
	} // correct page check
}
add_action( 'sensei_after_list_table', 'imperial_sensei_export_quiz_questions_button', 15 );

/**
 * Processes the download of the per Learner Answers on a Quiz CSV export request
 * 
 * @global type $wpdb
 * @global type $woothemes_sensei
 */
function imperial_sensei_export_quiz_questions() {
	global $wpdb, $woothemes_sensei;

	if ( !empty( $_GET['imp_sensei_answers_report_download'] ) ) {
		$report = sanitize_text_field( $_GET['imp_sensei_answers_report_download'] );

		$lesson_id = 0;
		if( isset( $_GET['lesson_id'] ) ) {
			$lesson_id = intval( $_GET['lesson_id'] );
		}

		// Simple verification to ensure intent, Note that a Nonce is per user, so the URL can't be shared
		if ( !wp_verify_nonce( $_REQUEST['_sdl_nonce'], 'imp_sensei_csv_download-' . $report . $lesson_id ) ) {
			wp_die( __('Invalid request', 'woothemes-sensei') );
		}

		// Setup the variables we might need
		$filename = apply_filters( 'sensei_csv_export_filename', $report );

		// Handle the headers
		header( 'Content-Type: text/csv' );
		header( 'Content-Disposition: attachment;filename=' . $filename . '.csv');

		$quiz_id = get_post_meta( $lesson_id, '_lesson_quiz', true );

		// Set the headers
		$report_headers = array(
			__( 'Question Title', 'imperial' ),
			__( 'Question Content', 'imperial' ),
			__( 'Programme Code', 'imperial' ),
			__( 'Student CID', 'imperial' ),
			__( 'Username', 'imperial' ),
			__( 'First Name', 'imperial' ),
			__( 'Last Name', 'imperial' ),
			__( 'Student Answer', 'imperial' ),
		);

		// Relationships between learners and what Programme they are on
		$csv_user_prog_ids = array();
		$connections = p2p_get_connections( 'student_programme' );
		foreach( $connections AS $connection ) {
			$csv_user_prog_ids[ $connection->p2p_from ] = $connection->p2p_to; // Only store 1 entry (whatever's last)
		}

		// Relationships of Programme ID to it's code
		$csv_programme_codes = array();
		$results = $wpdb->get_results( "SELECT ID, post_name FROM $wpdb->posts WHERE post_type = 'programme' ", ARRAY_A );
		foreach( $results AS $result ) {
			$csv_programme_codes[ $result['ID'] ] = strtoupper( $result['post_name'] );
		}

		$sql_count = 0;

		$question_args = array(
			'post_id' => $quiz_id,
			'type' => 'sensei_quiz_asked',
			'status' => 'any', 
		);
		$sql_count++;
		$users_question_asked = WooThemes_Sensei_Utils::sensei_check_for_activity( $question_args, true );
		if ( !is_array($users_question_asked) ) {
			$users_question_asked = array( $users_question_asked );
		}
		$user_cache = $question_cache = $report_data = $dup_check = array();

		// Loop through each user/questions asked ...
		foreach ( $users_question_asked AS $questions_asked ) {
			$question_ids = explode( ',', $questions_asked->comment_content );

			foreach( $question_ids AS $question_id ) {

				// Cache the question rows for reuse...
				if ( !isset($question_cache[ $question_id ]) ) {
					$question = get_post( $question_id );
					$question_cache[ $question_id ] = array(
						$question->post_title,
						str_replace( array( "\n", "\r" ), ' ', strip_tags( $question->post_content ) )
					);
				}

				// Cache the user rows for reuse...
				if ( !isset($user_cache[ $questions_asked->user_id ]) ) {
					$user = get_user_by( 'id', $questions_asked->user_id );
					$user_cache[ $questions_asked->user_id ] = array(
						$csv_programme_codes[ $csv_user_prog_ids[ $user->id ] ],
						$user->cid,
						$user->user_login,
						$user->first_name,
						$user->last_name,
					);
				}

				if ( empty($dup_check[ $question_id ][ $questions_asked->user_id ]) ) {
					// ...get the Learners' answer
					$answer_args = array(
						'post_id' => $question_id,
						'user_id' => $questions_asked->user_id,
						'type' => 'sensei_user_answer',
						'status' => 'any',
						'number' => 1, // remove duplicates
					);
					$sql_count++;
					$user_answer = WooThemes_Sensei_Utils::sensei_check_for_activity( $answer_args, true );
//					error_log(print_r($user_answer, true));
					$dup_check[ $question_id ][ $questions_asked->user_id ] = $user_answer->comment_ID;
					$dup2_check[ $questions_asked->user_id ][ $question_id ] = $user_answer->comment_ID;

					// ...build the csv row
					$row_data = array_merge( 
									$question_cache[ $question_id ],
									$user_cache[ $user_answer->user_id ],
									(array) maybe_unserialize( base64_decode( $user_answer->comment_content ) )
							);

					// Finally add it to the overall data
					$report_data[] = $row_data;
				} // dup check
			} // Each question
		} // each user/question asked


		// Output the data in csv format
		$fp = fopen('php://output', 'w');
		fputcsv($fp, $report_headers);
		foreach ($report_data as $row) {
			fputcsv($fp, $row);
		} // End For Loop
		fclose($fp);

//		error_log("There were " . count($dup_check) . " questions and " . count($dup2_check) . " users");
//		foreach( $dup_check AS $question_id => $user_ids ) {
//			error_log("For question $question_id, " . count($user_ids) . " users answered");
//		}
//		foreach( $dup2_check AS $user_id => $question_ids ) {
//			error_log("For user $user_id, " . count($question_ids) . " questions were answered");
//		}
//		error_log("$sql_count Bothans died to produce this information");

//		error_log( '=> Memory used: ' . size_format(memory_get_usage()) . "\n" .
//					'=> Time taken: ' . timer_stop() . ' secs' );

		// Cleanly exit
		exit;
	} // End "var to init" check
}
add_action( 'admin_init', 'imperial_sensei_export_quiz_questions' );

/**
 * Adds a new CSV Export of all Quizzes for a specific Course
 * 
 * @global type $page_hook
 */
function imperial_sensei_export_course_quizzes_button() {
	global $page_hook;

	$course_id = 0;
	if( isset( $_GET['course_id'] ) ) {
		$course_id = intval( $_GET['course_id'] );
	}
	if ( 'sensei_page_sensei_analysis' == $page_hook && !empty($course_id) ) {
		$slug = str_replace( 'sensei_page_', '', $page_hook );
		$course = get_post( $course_id );
		if ( 'course' == $course->post_type ) {
			$report = sanitize_title( $course->post_title ) . '-all-quizzes-learners-overview';
			$url = add_query_arg( array( 'page' => $slug, 'course_id' => $course_id, 'imp_sensei_quizzes_report_download' => $report ), admin_url( 'admin.php' ) );
			echo ' <a class="button button-primary" href="' . wp_nonce_url( $url, 'imp_sensei_csv_download-' . $report . $course_id, '_sdl_nonce' ) . '">' . __( 'Export all Quizzes Learners (CSV)', 'woothemes-sensei' ) . '</a>';
		} // is course check
	} // correct page check
}
add_action( 'sensei_after_list_table', 'imperial_sensei_export_course_quizzes_button', 15 );

/**
 * Add a Lesson/Quiz title as a header for CSV export
 * 
 * @param type $columns
 * @param type $analysis_obj
 * @return type
 */
function imperial_sensei_analysis_course_quizzes_csv_columns( $columns, $analysis_obj ) {
	if ( $analysis_obj->csv_output ) {
		$add_csv_column = array(
			'lesson_quiz_title' => __( 'Lesson / Quiz Title', 'imperial' ),
		);
		$columns = array_merge( $add_csv_column, $columns );
	} // check CSV output
	return $columns;
}

/**
 * Add the Lesson/Quiz titles to CSV exports
 * 
 * @global type $wpdb
 * @global type $csv_lesson_titles
 * @param type $data
 * @param type $item
 * @param type $analysis_obj
 */
function imperial_sensei_analysis_course_quizzes_csv_column_data( $data, $item, $analysis_obj ) {
	global $wpdb, $csv_lesson_titles;

	if ( $analysis_obj->csv_output ) {
		if ( !isset($csv_lesson_titles) ) {
			$csv_lesson_titles = array();
		}
		if ( empty($csv_lesson_titles[ $item->comment_post_ID ]) ) {
			$lesson = get_post( $item->comment_post_ID );
			$csv_lesson_titles[ $item->comment_post_ID ] = $lesson->post_title;
		}
		$csv_data = array(
			'lesson_quiz_title' => $csv_lesson_titles[ $item->comment_post_ID ],
		);
		$data = array_merge($csv_data, $data);
	} // check CSV output
	return $data;
}

/**
 * Processes the download of the per Learner Answers on a Quiz CSV export request
 * 
 * @global type $wpdb
 * @global type $woothemes_sensei
 */
function imperial_sensei_export_course_quizzes() {
	global $wpdb, $woothemes_sensei;

	if ( !empty( $_GET['imp_sensei_quizzes_report_download'] ) ) {
		$report = sanitize_text_field( $_GET['imp_sensei_quizzes_report_download'] );

		$course_id = 0;
		if( isset( $_GET['course_id'] ) ) {
			$course_id = intval( $_GET['course_id'] );
		}

		// Simple verification to ensure intent, Note that a Nonce is per user, so the URL can't be shared
		if ( !wp_verify_nonce( $_REQUEST['_sdl_nonce'], 'imp_sensei_csv_download-' . $report . $course_id ) ) {
			wp_die( __('Invalid request', 'woothemes-sensei') );
		}

		// Setup the variables we might need
		$filename = apply_filters( 'sensei_csv_export_filename', $report );

		// Handle the headers
		header( 'Content-Type: text/csv' );
		header( 'Content-Disposition: attachment;filename=' . $filename . '.csv');

		// Add the Lesson/Quiz title as the first column on the headers...
		add_filter( 'sensei_analysis_lesson_columns', 'imperial_sensei_analysis_course_quizzes_csv_columns', 15, 2 );
		// ...and on the data content too
		add_filter( 'sensei_analysis_lesson_column_data', 'imperial_sensei_analysis_course_quizzes_csv_column_data', 15, 3 );

		// Get all Lesson IDs with quizzes for the current Course ID
		$quiz_args = array(	'post_type'         => 'lesson',
							'numberposts'       => -1,
							'meta_key'          => '_order_' . $course_id,
							'orderby'           => 'meta_value_num date',
							'order'             => 'ASC',
							'meta_query'        => array(
								array(
									'key' => '_lesson_course',
									'value' => $course_id,
								),
								array(
									'key' => '_quiz_has_questions',
									'value' => 1,
								),
							),
							'post_status'       => 'any',
							'suppress_filters'  => 0,
							'fields'            => 'ids',
							);
		$lesson_ids = get_posts( $quiz_args );

		// Setup the Analysis object which we'll reuse over and over
		$classes_to_load = array(	'list-table',
									'analysis-lesson'
									);
		foreach ( $classes_to_load as $class_file ) {
			$woothemes_sensei->load_class( $class_file );
		}
		$sensei_analysis_report_object = new WooThemes_Sensei_Analysis_Lesson_List_Table( 0 );
		$sensei_analysis_report_object->course_id = $course_id;

		$first_report = true;
		$report_headers = $report_data = array(); 

		foreach ( $lesson_ids AS $lesson_id ) {
			$sensei_analysis_report_object->lesson_id = $lesson_id;
			$single_report = $sensei_analysis_report_object->generate_report( $report  = 'learners-overview' );
			if ( $first_report ) {
				// Put the headers elsewhere
				$report_headers = array_shift($single_report);
				$first_report = false;
			}
			else {
				unset($single_report[0]);
			}
			// Merge with the rest of the data
			$report_data = array_merge( $report_data, $single_report );
		}

		// Output the data in csv format
		$fp = fopen('php://output', 'w');
		fputcsv($fp, $report_headers);
		foreach ($report_data as $row) {
			fputcsv($fp, $row);
		} // End For Loop
		fclose($fp);

//		error_log( '=> Memory used: ' . size_format(memory_get_usage()) . "\n" .
//					'=> Time taken: ' . timer_stop() . ' secs' );

		// Cleanly exit
		exit;
	} // End "var to init" check
}
add_action( 'admin_init', 'imperial_sensei_export_course_quizzes' );

/***************************************************
 * PROGRAMME FILTERING OF SCREENS
 ***************************************************/

/**
 * Prefix the exported csv filename with the selected Programme code, if set
 * 
 * @param string $filename
 * @return string
 */
function imperial_sensei_csv_export_filename( $filename ) {
	global $wpdb;

	if ( !empty($_GET['programme_id']) ) {
		$prog_id = intval( $_GET['programme_id'] );
		if ( $prog_id ) {
			$code = $wpdb->get_var( $wpdb->prepare( "SELECT post_name FROM $wpdb->posts WHERE post_type = 'programme' AND ID = %d ", $prog_id ) );
			if ( !empty($code) ) {
				$filename = strtoupper( $code ) . '-' . $filename;
			}
		}
	}
	return $filename;
}
add_filter( 'sensei_csv_export_filename', 'imperial_sensei_csv_export_filename' );

/**
 * Inserts within the Sensei admin screen nav title the currently selected Programme title
 * 
 * @param string $title
 * @return string
 */
function imperial_sensei_prefix_programme_title_to_nav_title( $title ) {
	if ( isset( $_GET['programme_id'] ) ) { 
		$programme_id = intval( $_GET['programme_id'] );
		$programme = get_post( $programme_id );
		if ( 'programme' == $programme->post_type ) {
			$programme_title = sprintf( '&nbsp;&nbsp;<span class="programme-title">&gt;&nbsp;&nbsp;%s</span>', get_the_title( $programme ) );
			$title = preg_replace( '/^(<a [^<]+<\/a>)(.*)/Us', '$1' . $programme_title . '$2', $title );
		}
	}
	return $title;
}
add_filter( 'sensei_analysis_nav_title', 'imperial_sensei_prefix_programme_title_to_nav_title' );
add_filter( 'sensei_grading_nav_title', 'imperial_sensei_prefix_programme_title_to_nav_title' );
add_filter( 'sensei_learners_nav_title', 'imperial_sensei_prefix_programme_title_to_nav_title' );

/**
 * This adds JS for the Admin to help with Imperial Sensei Programme dropdown changes etc
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
 * Add a Programme dropdown to the Sensei Analyis, Grading and Learners screens
 */
function imperial_sensei_before_dropdown_filters() {
	// No need to have it if we've already limited to a single user
	if ( !empty($_GET['user_id']) && $_GET['user_id'] > 0 ) {
		return;
	}
	$programmes_array = imperial()->get_programmes();

	$selected_programme_id = 0;
	if ( isset( $_GET['programme_id'] ) ) {
		$selected_programme_id = intval( $_GET['programme_id'] );
	} // End If Statement

	echo '<div class="select-box sensei-programme-options">' . "\n";

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
add_action( 'sensei_analysis_after_headers', 'imperial_sensei_before_dropdown_filters' );
add_action( 'sensei_grading_before_dropdown_filters', 'imperial_sensei_before_dropdown_filters' );
add_action( 'sensei_learners_before_dropdown_filters', 'imperial_sensei_before_dropdown_filters' );

/**
 * Filter the total number of users within the Sensei Analysis Stats boxes to only those users with the 
 * subscriber role, or if a Programme is selected only to that Programme
 * 
 * @param type $total
 * @param type $user_counts
 * @return type
 */
function imperial_sensei_analysis_total_users( $total, $user_counts ) {
	global $sensei_restrict_users_to_programme_id;

	if ( isset( $_GET['programme_id'] ) ) {
		$selected_programme_id = intval( $_GET['programme_id'] );
		if ( !isset($sensei_restrict_users_to_programme_id) ) {
			$sensei_restrict_users_to_programme_id = imperial()->get_programme_user_ids( $selected_programme_id );
		}
		return count( $sensei_restrict_users_to_programme_id );
	}
	// Restrict to current users
	return $user_counts['avail_roles']['subscriber'];
}
add_filter( 'sensei_analysis_total_users', 'imperial_sensei_analysis_total_users', 10, 2 );

/**
 * Filters the User args used in the Sensei admin screens to be restricted to those not on Legacy and on the current Programme
 * 
 * @param type $args
 * @return type
 */
function imperial_sensei_filter_users_by_legacy_and_programme( $args ) {
	global $sensei_restrict_users_to_programme_id;

	if( !empty( $_GET['programme_id'] ) ) {
		$selected_programme_id = intval( $_GET['programme_id'] );
		if ( !isset($sensei_restrict_users_to_programme_id) ) {
			$sensei_restrict_users_to_programme_id = imperial()->get_programme_user_ids( $selected_programme_id );
		}
		if ( !empty($sensei_restrict_users_to_programme_id) ) {
			if ( !empty($args['include']) ) {
				// If there already is a restriction (for whatever reason) don't completely overwrite it
				$args['include'] = array_intersect( $args['include'], $sensei_restrict_users_to_programme_id );
			} 
			else {
				$args['include'] = $sensei_restrict_users_to_programme_id;
			}
		}
		else {
			// No Students on Programme, shortcut
			$args['include'] = array( 1 );
		}
	}

	// Restrict to current users
//	$args['meta_key'] = 'status';
//	$args['meta_value'] = 'Enrolled';
	$args['role'] = 'subscriber';

	return $args;
}
add_filter( 'sensei_analysis_overview_filter_users', 'imperial_sensei_filter_users_by_legacy_and_programme' );

/**
 * Filters the Courses shown in rows and counts etc to those within the selected Programme
 * 
 * @param array $args
 * @return array
 */
function imperial_sensei_filter_courses_by_programme( $args ) {
	global $sensei_restrict_courses_to_programme_id;

	if ( !empty( $_GET['programme_id'] ) ) {
		$selected_programme_id = intval( $_GET['programme_id'] );
		if ( !isset($sensei_restrict_courses_to_programme_id) ) {
			$sensei_restrict_courses_to_programme_id = imperial()->get_courses_by_programme( $selected_programme_id, false, 'ids' );
		}
		// Programme has no Courses (can happen), so ensure no results
		if ( empty($sensei_restrict_courses_to_programme_id) ) {
			$sensei_restrict_courses_to_programme_id = array( 0 );
		}
		$args['post__in'] = $sensei_restrict_courses_to_programme_id;
	} // End If Statement
	return $args;
}
// This is for the Analysis stat overview box
add_filter( 'sensei_course_count', 'imperial_sensei_filter_courses_by_programme' );
// Filter for Overview Courses
add_filter( 'sensei_analysis_overview_filter_courses', 'imperial_sensei_filter_courses_by_programme' );
//
add_filter( 'sensei_grading_filter_courses', 'imperial_sensei_filter_courses_by_programme' );
//
add_filter( 'sensei_learners_filter_courses', 'imperial_sensei_filter_courses_by_programme' );

/**
 * Filters the Lessons shown in rows and counts etc to those within the selected Programme
 * 
 * @param type $args
 * @return type
 */
function imperial_sensei_filter_lessons_by_programme( $args ) {
	global $sensei_restrict_courses_to_programme_id;

	if ( isset( $_GET['programme_id'] ) ) {
		$selected_programme_id = intval( $_GET['programme_id'] );
		if ( !isset($sensei_restrict_courses_to_programme_id) ) {
			$sensei_restrict_courses_to_programme_id = imperial()->get_courses_by_programme( $selected_programme_id, false, 'ids' );
		}
//		if ( empty($sensei_restrict_courses_to_programme_id) ) {
//			$sensei_restrict_courses_to_programme_id = array( 0 );
//		}
		$add_restriction = true;
		if ( empty($args['meta_query']) ) {
			$args['meta_query'] = array();
		}
		else {
			// Check and remove existing '_lesson_course' restriction
			foreach( $args['meta_query'] AS $qkey => $meta_query ) {
				if ( '_lesson_course' == $meta_query['key'] ) {
					$add_restriction = false; // Don't overwrite if existing course restriction
//					unset( $args['meta_query'][$qkey] );
				}
			}
		}
		if ( $add_restriction ) {
			$args['meta_query'][] = 
				array(
					'key' => '_lesson_course',
					'value' => $sensei_restrict_courses_to_programme_id,
					'compare' => 'IN',
				);
		}
	}
	return $args;
}
// This is for the Analysis stat overview box
add_filter( 'sensei_lesson_count', 'imperial_sensei_filter_lessons_by_programme' );
// Filter for Overview Lessons
add_filter( 'sensei_analysis_overview_filter_lessons', 'imperial_sensei_filter_lessons_by_programme' );

/**
 * Filters the Course/Lesson statuses to Users within the selected Programme (until WP 4.1 cannot directly restrict 
 * to specific groups of Course or Lesson ids). This is the *biggest* filter by far
 * 
 * @global type $wpdb
 * @param type $args
 * @return type
 */
function imperial_sensei_filter_statuses_by_programme_users( $args ) {
	global $sensei_restrict_users_to_programme_id;

	if( !empty( $_GET['programme_id'] ) ) {
		$selected_programme_id = intval( $_GET['programme_id'] );
		if ( !isset($sensei_restrict_users_to_programme_id) ) {
			$sensei_restrict_users_to_programme_id = imperial()->get_programme_user_ids( $selected_programme_id );
		}
//		error_log('$sensei_restrict_users_to_programme_id:'.print_r($sensei_restrict_users_to_programme_id, true));
		if ( !empty($sensei_restrict_users_to_programme_id) ) {
			if ( !empty($args['user_id']) ) {
				// If there already is a restriction (for whatever reason) don't completely overwrite it
				$args['user_id'] = array_intersect( $args['user_id'], $sensei_restrict_users_to_programme_id );
			} 
			else {
				$args['user_id'] = $sensei_restrict_users_to_programme_id;
			}
		}
		else {
			// No Students on Programme, shortcut
			$args['user_id'] = array( 1 );
		}
	}
	return $args;
}
// Adjust the Overview stat box
add_filter( 'sensei_analysis_total_courses_started', 'imperial_sensei_filter_statuses_by_programme_users' );
// Adjust the Overview stat box
add_filter( 'sensei_analysis_total_courses_ended', 'imperial_sensei_filter_statuses_by_programme_users' );
// Adjust the Overview stat box
add_filter( 'sensei_analysis_total_quiz_grades', 'imperial_sensei_filter_statuses_by_programme_users' );

// Filter Users on a specific Course to Programme Users
add_filter( 'sensei_analysis_course_filter_statuses', 'imperial_sensei_filter_statuses_by_programme_users' );
// Adjusts the count of learners on a Course
add_filter( 'sensei_analysis_course_learners', 'imperial_sensei_filter_statuses_by_programme_users' );
// Adjusts the count of completed for a Course
add_filter( 'sensei_analysis_course_completions', 'imperial_sensei_filter_statuses_by_programme_users' );
// Adjusts the average percentage for a Course
add_filter( 'sensei_analysis_course_percentage', 'imperial_sensei_filter_statuses_by_programme_users' );

// Filter Users on a specific Lesson to Programme Users
add_filter( 'sensei_analysis_lesson_filter_statuses', 'imperial_sensei_filter_statuses_by_programme_users' );
// Adjusts the count of learners on a Lesson
add_filter( 'sensei_analysis_lesson_learners', 'imperial_sensei_filter_statuses_by_programme_users' );
// Adjusts the count of Completed on a Lesson
add_filter( 'sensei_analysis_lesson_completions', 'imperial_sensei_filter_statuses_by_programme_users' );
// Adjusts the average grade for a Lesson
add_filter( 'sensei_analysis_lesson_grades', 'imperial_sensei_filter_statuses_by_programme_users' );

// Filter the rows shown
add_filter( 'sensei_grading_filter_statuses', 'imperial_sensei_filter_statuses_by_programme_users' );
// Filter the counts
add_filter( 'sensei_grading_count_statues', 'imperial_sensei_filter_statuses_by_programme_users' );

// Adjusts the count of learners on a Course
add_filter( 'sensei_learners_course_learners', 'imperial_sensei_filter_statuses_by_programme_users' );
// Adjusts the count of learners on a Lesson
add_filter( 'sensei_learners_lesson_learners', 'imperial_sensei_filter_statuses_by_programme_users' );
// Adjusts the users shown for a specific Course
add_filter( 'sensei_learners_filter_users', 'imperial_sensei_filter_statuses_by_programme_users' );

/**
 * Output buffer the entire Sensei admin screens to ensure a Programme ID is added to URLs (see imperial_sensei_after_list_table_output() )
 */
function imperial_sensei_before_list_table_capture() {
	ob_start();
}
add_action( 'sensei_before_list_table', 'imperial_sensei_before_list_table_capture', 5 );

/**
 * Taking the entire output of Sensei admin screens (see imperial_sensei_before_list_table_capture() ) 
 * add the Programme ID to all hrefs
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

/**
 * Ensure the Programme ID is part of the returned redirect URL
 * 
 * @param string $url
 */
function imperial_sensei_ajax_redirect_url( $url ) {
	if ( !empty( $_POST['programme_id'] ) ) {
		$selected_programme_id = intval( $_POST['programme_id'] );
		$url = add_query_arg( array( 'programme_id' => $selected_programme_id ), $url );
	}
	return $url;
}
add_filter( 'sensei_ajax_redirect_url', 'imperial_sensei_ajax_redirect_url' );
add_filter( 'sensei_learners_add_learner_redirect_url', 'imperial_sensei_ajax_redirect_url' );

/**
 * Add the currently selected Programme ID to the add learners form
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


/***************************************************
 * ADDITIONAL COLUMNS TO SCREENS, CHANGES TO COLUMNS
 ***************************************************/

/**
 * Adds Analysis columns for Imperial use
 *
 * @param  array $columns existing columns
 * @param  array $analysis_obj 
 * @return array $columns existing and new columns
 */
function imperial_sensei_analysis_course_columns( $columns, $analysis_obj ) {
	if ( 'user' == $analysis_obj->view ) {
		// Add the data later
		add_filter( 'sensei_analysis_course_column_data', 'imperial_sensei_analysis_course_column_data', 10, 3 );

		$add_columns = array(
			'last_active' => __('Last Login', 'imperial'),
		);

		$slice_point = 0;
		foreach ( $columns AS $key => $val ) {
			if ( 'completed' == $key ) {
				break;
			}
			$slice_point++;
		}
		if ( $slice_point ) {
			$options_s = array_slice( $columns, 0, $slice_point );
			$options_e = array_slice( $columns, $slice_point );

			$columns = array_merge( $options_s, $add_columns, $options_e );
		}
		else {
			$columns = array_merge( $columns, $add_columns );
		}
	}
	return $columns;
}
add_filter( 'sensei_analysis_course_columns', 'imperial_sensei_analysis_course_columns', 20, 2 );

/**
 * Populate the new columns for Imperial Sensei Analysis
 *
 * @param  array $content output
 * @param  int $course_id course post id
 * @param  int $user_id  course learner user id
 * @return array $content modified output
 */
function imperial_sensei_analysis_course_column_data( $content, $item, $analysis_obj ) {
	if ( 'user' == $analysis_obj->view ) {
		$new_data = array(
//			'last_active' => bp_get_last_activity( $item->user_id ),
			'last_active' => $item->comment_date,
		);

		$slice_point = 0;
		foreach ( $content AS $key => $val ) {
			if ( 'completed' == $key ) {
				break;
			}
			$slice_point++;
		}
		if ( $slice_point ) {
			$options_s = array_slice( $content, 0, $slice_point );
			$options_e = array_slice( $content, $slice_point );

			$content = array_merge( $options_s, $new_data, $options_e );
		}
		else {
			$content = array_merge( $content, $new_data );
		}
	}
	return $content;
}

/**
 * Add column headings to Sensei Learners admin screens
 * 
 * @param array $columns
 * @param object $analysis_obj
 * @return array
 */
function imperial_sensei_learners_default_columns( $columns, $analysis_obj ) {
	if ( 'learners' == $analysis_obj->view ) {
		// Add the data later
		add_filter( 'sensei_learners_main_column_data', 'imperial_sensei_learners_main_column_data', 10, 4 );

		$add_columns = array(
			'date_completed' => __('Date Completed', 'imperial'),
		);
		if ( $analysis_obj->lesson_id ) {
			$add_columns['grade'] = __('Grade', 'imperial');
		}
		else {
			$add_columns['percent_complete'] = __('Percentage Complete', 'imperial');
		}

		$slice_point = 0;
		foreach ( $columns AS $key => $val ) {
			$slice_point++;
			if ( 'date_started' == $key ) {
				break;
			}
		}
		if ( $slice_point ) {
			$options_s = array_slice( $columns, 0, $slice_point );
			$options_e = array_slice( $columns, $slice_point );

			$columns = array_merge( $options_s, $add_columns, $options_e );
		}
		else {
			$columns = array_merge( $columns, $add_columns );
		}
	}
	return $columns;
}
add_filter( 'sensei_learners_default_columns', 'imperial_sensei_learners_default_columns', 10, 2 );

/**
 * Add column data to Sensei Learners admin screens
 * 
 * @param array $args
 * @return array
 */
function imperial_sensei_learners_main_column_data( $content, $item, $secondary_id = 0, $post_type = '' ) {
	// Check for users, add data to row
	if ( 'lesson' == $post_type || 'course' == $post_type ) {
		$new_data = array();
		if ( 'lesson' == $post_type ) {
			if ( !empty($item->comment_ID) && 'in-progress' != $item->comment_approved ) {
				$new_data['date_completed'] = $item->comment_date;
				$new_data['grade'] = get_comment_meta( $item->comment_ID, 'grade', true);
			}
			else {
				$new_data['date_completed'] = '';
				$new_data['grade'] = '';
			}
		}
		if ( 'course' == $post_type ) {
			if ( !empty($item->comment_ID) && 'in-progress' != $item->comment_approved ) {
				$new_data['date_completed'] = $item->comment_date;
			}
			else {
				$new_data['date_completed'] = '';
			}
			$new_data['percent_complete'] = get_comment_meta( $item->comment_ID, 'percent', true) . '%';
		}

		$slice_point = 0;
		foreach ( $content AS $key => $val ) {
			if ( 'date_started' == $key ) {
				break;
			}
			$slice_point++;
		}
		if ( $slice_point ) {
			$options_s = array_slice( $content, 0, $slice_point );
			$options_e = array_slice( $content, $slice_point );

			$content = array_merge( $options_s, $new_data, $options_e );
		}
		else {
			$content = array_merge( $content, $new_data );
		}
	}

	return $content;
}

/**
 * Filter the column data shown, remove general admin edit links, change text
 * 
 * @param array $args
 * @return array
 */
function imperial_sensei_adjust_all_column_data( $args ) {
	foreach ( $args as $key => $value ) {
		// Remove admin "edit" links
//		if ( strpos( $value, 'action=edit' ) ) {
//			$args[ $key ] = preg_replace( '/<a[^>]+href="[^"]+action=edit[^>]+>(.+)<\/a>/', '$1', $value );
//		}
//		if ( strpos( $value, 'user-edit' ) ) {
//			$args[ $key ] = preg_replace( '/<a[^>]+href="[^"]+user-edit[^>]+>(.+)<\/a>/', '$1', $value );
//		}
		if ( strpos( $value, 'Remove from activity' ) ) {
			$args[ $key ] = str_replace( 'Remove from activity', 'Reset activity', $value );
		}
	}
	
	return $args;
}
add_filter( 'sensei_analysis_overview_column_data', 'imperial_sensei_adjust_all_column_data' );
add_filter( 'sensei_analysis_course_column_data', 'imperial_sensei_adjust_all_column_data' );
add_filter( 'sensei_analysis_lesson_column_data', 'imperial_sensei_adjust_all_column_data' );
add_filter( 'sensei_grading_main_column_data', 'imperial_sensei_adjust_all_column_data' );
add_filter( 'sensei_learners_main_column_data', 'imperial_sensei_adjust_all_column_data' );





// Remove all column ordering
//add_filter( 'sensei_grading_main_columns_sortable', '__return_empty_array' );
//add_filter( 'sensei_analysis_overview_courses_columns_sortable', '__return_empty_array' );
//add_filter( 'sensei_analysis_overview_lessons_columns_sortable', '__return_empty_array' );
//add_filter( 'sensei_analysis_overview_users_columns_sortable', '__return_empty_array' );
//add_filter( 'sensei_analysis_lesson_columns_sortable', '__return_empty_array' );
//add_filter( 'sensei_analysis_user_profile_columns_sortable', '__return_empty_array' );
//add_filter( 'sensei_analysis_course_user_columns_sortable', '__return_empty_array' );
//add_filter( 'sensei_analysis_course_lesson_columns_sortable', '__return_empty_array' );
//add_filter( 'sensei_learners_learners_columns_sortable', '__return_empty_array' );
//add_filter( 'sensei_learners_default_columns_sortable', '__return_empty_array' );

