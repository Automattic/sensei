<?php
// This file contains material which is the pre-existing property of Psycle Interactive Limited.
// Copyright (c) 2014 Psycle Interactive. All rights reserved.

// Bail if WP-CLI is not present
if ( !defined( 'WP_CLI' ) ) return;


// This class is shared between the others to centralise the Notification ability
class Imperial_Sensei_CLI_Command extends WP_CLI_Command {

	/**
	 * Fix those lessons that are marked as having a quiz with questions when the quiz doesn't
	 *
	 * @subcommand fix-lesson-quizzes
	 */
	function fix_lessons() {
		global $wpdb;

		// Get all Lessons with Quizzes...
		$args = array(
			'post_type' => 'lesson',
			'numberposts' => -1,
//			'meta_query' => array(
//				array(
//					'key' => '_quiz_has_questions',
//					'value' => 1,
//				),
//			),
			'fields' => 'ids'
		);
		$lesson_ids = get_posts( $args );

		// ...get all Quiz IDs for the above Lessons
		$id_list = join( ',', $lesson_ids );
		$meta_list = $wpdb->get_results( "SELECT post_id, meta_value FROM $wpdb->postmeta WHERE meta_key = '_lesson_quiz' AND post_id IN ($id_list)", ARRAY_A );
		$lesson_quiz_ids = array();
		if ( !empty($meta_list) ) {
			foreach ( $meta_list as $metarow ) {
				$lesson_id = $metarow['post_id'];
				$quiz_id = $metarow['meta_value'];
				$lesson_quiz_ids[ $lesson_id ] = $quiz_id;
			}
		}

		// ...check all Quiz IDs for questions
		$id_list = join( ',', array_values($lesson_quiz_ids) );
		$meta_list = $wpdb->get_results( "SELECT meta_value FROM $wpdb->postmeta WHERE meta_key = '_quiz_id' AND meta_value IN ($id_list)", ARRAY_A );
		$lesson_quiz_ids_with_questions = array();
		if ( !empty($meta_list) ) {
			foreach ( $meta_list as $metarow ) {
				$quiz_id = $metarow['meta_value'];
				$lesson_quiz_ids_with_questions[] = $quiz_id;
			}
		}

		// For each quiz check there are questions, if not remove the corresponding meta keys from Quizzes and Lessons
		// if there are questions on the quiz add the corresponding meta keys to Quizzes and Lessons
		$d_count = $a_count =0;
		foreach ( $lesson_quiz_ids AS $lesson_id => $quiz_id ) {
			if ( !in_array( $quiz_id, $lesson_quiz_ids_with_questions ) ) {
//				error_log( "Error with quiz $quiz_id and lesson $lesson_id ");
				// Quiz has no questions, drop the corresponding data
				delete_post_meta( $quiz_id, '_pass_required' );
				delete_post_meta( $quiz_id, '_quiz_passmark' );
				delete_post_meta( $lesson_id, '_quiz_has_questions' );
				$d_count++;
			}
			else if ( in_array( $quiz_id, $lesson_quiz_ids_with_questions ) ) {
//				error_log( "Error with quiz $quiz_id and lesson $lesson_id ");
				// Quiz has no questions, drop the corresponding data
				update_post_meta( $lesson_id, '_quiz_has_questions', true );
				$a_count++;
			}
		}
		WP_CLI::success( sprintf( __("Adjusted %s Lessons!", 'imperial'), $d_count + $a_count ) );
	}

	/**
	 * Convert the existing Sensei lesson and course activity logs to new status entries
	 *
	 * @subcommand convert-lesson-activities
	 */
	function convert_lessons() {
		global $wpdb;

		wp_defer_comment_counting( true );

			// Directly querying the database is normally frowned upon, but all
			// of the API functions will return full objects or are limited to 
			// single post_IDs which will overall suck up lots of memory and take
			// far longer to process, in addition to calling filters and actions
			// that we wouldn't want running. This is best, just not as future proof.
			// But then this isn't expected to run more than once.

		// Get all Lessons with Quizzes...
		$args = array(
			'post_type' => 'lesson',
			'post_status' => 'any',
			'numberposts' => -1,
			'meta_query' => array(
				array(
					'key' => '_quiz_has_questions',
					'value' => 1,
				),
			),
			'fields' => 'ids'
		);
		$lesson_ids_with_quizzes = get_posts( $args );

		// ...get all Quiz IDs for the above Lessons
		$id_list = join( ',', $lesson_ids_with_quizzes );
		$meta_list = $wpdb->get_results( "SELECT post_id, meta_value FROM $wpdb->postmeta WHERE meta_key = '_lesson_quiz' AND post_id IN ($id_list)", ARRAY_A );
		$lesson_quiz_ids = array();
		if ( !empty($meta_list) ) {
			foreach ( $meta_list as $metarow ) {
				$lesson_id = $metarow['post_id'];
				$quiz_id = $metarow['meta_value'];
				$lesson_quiz_ids[ $lesson_id ] = $quiz_id;
			}
		}

		// ...get all Pass Required & Passmarks for the above Lesson/Quizzes
		$id_list = join( ',', array_values($lesson_quiz_ids) );
		$meta_list = $wpdb->get_results( "SELECT post_id, meta_key, meta_value FROM $wpdb->postmeta WHERE ( meta_key = '_pass_required' OR meta_key = '_quiz_passmark' ) AND post_id IN ($id_list)", ARRAY_A );
		$quizzes_pass_required = $quizzes_passmarks = array();
		if ( !empty($meta_list) ) {
			foreach ( $meta_list as $metarow ) {
				if ( !empty($metarow['meta_value']) ) {
					$quiz_id = $metarow['post_id'];
					$key = $metarow['meta_key'];
					$value = $metarow['meta_value'];
					if ( '_pass_required' == $key ) {
						$quizzes_pass_required[ $quiz_id ] = $value;
					}
					if ( '_quiz_passmark' == $key ) {
						$quizzes_passmarks[ $quiz_id ] = $value;
					}
				}
			}
		}

		$statuses_to_check = array( 'in-progress' => 1, 'complete' => 1, 'ungraded' => 1, 'graded' => 1, 'passed' => 1, 'failed' => 1 );

		$per_page = 40;
		$user_id_offset = 0;
		$count = $statuses_added = $dup_logs = $dup_statuses = 0;

		$users_sql = "SELECT ID FROM $wpdb->users WHERE ID > %d LIMIT $per_page";
		$start_sql = "SELECT comment_post_ID, comment_date FROM $wpdb->comments WHERE comment_type = 'sensei_lesson_start' AND user_id = %d GROUP BY comment_post_ID ";
		$end_sql = "SELECT comment_post_ID, comment_date FROM $wpdb->comments WHERE comment_type = 'sensei_lesson_end' AND user_id = %d GROUP BY comment_post_ID ";
		$grade_sql = "SELECT comment_post_ID, comment_content FROM $wpdb->comments WHERE comment_type = 'sensei_quiz_grade' AND user_id = %d GROUP BY comment_post_ID ORDER BY comment_content DESC ";
		$answers_sql = "SELECT comment_post_ID, comment_content FROM $wpdb->comments WHERE comment_type = 'sensei_quiz_asked' AND user_id = %d GROUP BY comment_post_ID ORDER BY comment_date_gmt DESC ";
		$check_existing_sql = "SELECT comment_ID FROM $wpdb->comments WHERE comment_post_ID = %d AND user_id = %d AND comment_type = 'sensei_lesson_status' ";

		// $per_page users at a time, could be batch run via an admin ajax command, 1 user at a time?
		while ( $user_ids = $wpdb->get_col( $wpdb->prepare($users_sql, $user_id_offset) ) ) {

			foreach ( $user_ids AS $user_id ) {

				$lesson_ends = $lesson_grades = $lesson_answers = array();

				// Pre-process the lesson ends
				$_lesson_ends = $wpdb->get_results( $wpdb->prepare($end_sql, $user_id), ARRAY_A );
				foreach ( $_lesson_ends as $lesson_end ) {
					// This will overwrite existing entries with the newer ones
					$lesson_ends[ $lesson_end['comment_post_ID'] ] = $lesson_end['comment_date'];
				}
				unset( $_lesson_ends );

				// Pre-process the lesson grades
				$_lesson_grades = $wpdb->get_results( $wpdb->prepare($grade_sql, $user_id), ARRAY_A );
				foreach ( $_lesson_grades as $lesson_grade ) {
					// This will overwrite existing entries with the newer ones (assuming the grade is higher)
					if ( empty($lesson_grades[ $lesson_grade['comment_post_ID'] ]) || $lesson_grades[ $lesson_grade['comment_post_ID'] ] < $lesson_grade['comment_content'] ) {
						$lesson_grades[ $lesson_grade['comment_post_ID'] ] = $lesson_grade['comment_content'];
					}
				}
				unset( $_lesson_grades );

				// Pre-process the lesson answers
				$_lesson_answers = $wpdb->get_results( $wpdb->prepare($answers_sql, $user_id), ARRAY_A );
				foreach ( $_lesson_answers as $lesson_answer ) {
					// This will overwrite existing entries with the newer ones
					$lesson_answers[ $lesson_answer['comment_post_ID'] ] = $lesson_answer['comment_content'];
				}
				unset( $_lesson_answers );

				// Grab all the lesson starts for the user
				$lesson_starts = $wpdb->get_results( $wpdb->prepare($start_sql, $user_id), ARRAY_A );
				foreach ( $lesson_starts as $lesson_log ) {

					$lesson_id = $lesson_log['comment_post_ID'];

					// Default status
					$status = 'in-progress';

					$status_date = $lesson_log['comment_date'];
					// Additional data for the lesson
					$meta_data = array(
						'start' => $status_date,
					);
					// Check if there is a lesson end
					if ( !empty($lesson_ends[$lesson_id]) ) {
						$status_date = $lesson_ends[$lesson_id];
						// Check lesson has quiz
						if ( !empty( $lesson_quiz_ids[$lesson_id] ) ) {
							// Check for the quiz answers
							if ( !empty($lesson_answers[$quiz_id]) ) {
								$meta_data['questions_asked'] = $lesson_answers[$quiz_id];
							}
							// Check if there is a quiz grade
							$quiz_id = $lesson_quiz_ids[$lesson_id];
							if ( !empty($lesson_grades[$quiz_id]) ) {
								$meta_data['grade'] = $quiz_grade = $lesson_grades[$quiz_id];
								// Check if the user has to get the passmark and has or not
								if ( !empty( $quizzes_pass_required[$quiz_id] ) && $quizzes_passmarks[$quiz_id] <= $quiz_grade ) {
									$status = 'passed';
								}
								elseif ( !empty( $quizzes_pass_required[$quiz_id] ) && $quizzes_passmarks[$quiz_id] > $quiz_grade ) {
									$status = 'failed';
								}
								else {
									$status = 'graded';
								}
							}
							else {
								// If the lesson has a quiz, but the user doesn't have a grade, it's not yet been graded
								$status = 'ungraded';
							}
						}
						else {
							// Lesson has no quiz, so it can only ever be this status
							$status = 'complete';
						}
					}
					$data = array(
						// This is the minimum data needed, the db defaults handle the rest
							'comment_post_ID' => $lesson_id,
							'comment_approved' => $status,
							'comment_type' => 'sensei_lesson_status',
							'comment_date' => $status_date,
							'user_id' => $user_id,
							'comment_date_gmt' => get_gmt_from_date($status_date),
							'comment_author' => '',
						);
					// Check it doesn't already exist
					$sql = $wpdb->prepare( $check_existing_sql, $lesson_id, $user_id );
					$comment_ID = $wpdb->get_var( $sql );
					if ( !$comment_ID ) {
						if ( array_key_exists( $status, $statuses_to_check ) ) {
							unset( $statuses_to_check[$status] );
							error_log( 'Adding: ' . print_r($data, true) . ' with meta: '.print_r($meta_data, true));
						}
						// Bypassing WP wp_insert_comment( $data ), so no actions/filters are run
						$wpdb->insert($wpdb->comments, $data);
						$comment_ID = (int) $wpdb->insert_id;

						if ( $comment_ID && !empty($meta_data) ) {
							foreach ( $meta_data as $key => $value ) {
								// Bypassing WP add_comment_meta(() so no actions/filters are run
								if ( $wpdb->get_var( $wpdb->prepare(
										"SELECT COUNT(*) FROM $wpdb->commentmeta WHERE comment_id = %d AND meta_key = %s ",
										$comment_ID, $key ) ) ) {
										continue; // Found the meta data already
								}
								$result = $wpdb->insert( $wpdb->commentmeta, array(
									'comment_id' => $comment_ID,
									'meta_key' => $key,
									'meta_value' => $value
								) );
							}
						}
						$statuses_added++;
					}
					else {
						$dup_statuses++;
						if ( 0 == ( $dup_statuses % 100 ) ) {
							WP_CLI::line( '...' );
						}
					}
				}
				$count++;
				if ( 0 == ( $count % 100 ) ) {
					WP_CLI::line( '.' );
//					break 2; // Handbreak to stop rather than process it all
				}
			}
			$wpdb->flush();
			$user_id_offset = $user_id; // Next set of users, basically an offset
		}

		WP_CLI::line( 'Timer end: ' . timer_stop() . ' secs' );
		WP_CLI::line( 'Memory used: ' . size_format(memory_get_usage()) );
		WP_CLI::line( sprintf( __('%s duplicates found.', 'imperial'), $dup_statuses ) );
		WP_CLI::success( sprintf( __('%s lesson statuses added.', 'imperial'), $statuses_added ) );
	}

	/**
	 * Example command
	 *
	 * @subcommand convert-course-activities
	 */
	function convert_courses() {
		global $wpdb;

		wp_defer_comment_counting( true );

			// Directly querying the database is normally frowned upon, but all
			// of the API functions will return full objects or are limited to 
			// single post_IDs which will overall suck up lots of memory and take
			// far longer to process, in addition to calling filters and actions
			// that we wouldn't want running. This is best, just not as future proof.
			// But then this isn't expected to run more than once.

		// Get all Lesson => Course relationships
		$meta_list = $wpdb->get_results( "SELECT $wpdb->postmeta.post_id, $wpdb->postmeta.meta_value FROM $wpdb->postmeta INNER JOIN $wpdb->posts ON ($wpdb->posts.ID = $wpdb->postmeta.post_id) WHERE $wpdb->posts.post_type = 'lesson' AND $wpdb->postmeta.meta_key = '_lesson_course' ", ARRAY_A );
		$course_lesson_ids = array();
		if ( !empty($meta_list) ) {
			foreach ( $meta_list as $metarow ) {
				$lesson_id = $metarow['post_id'];
				$course_id = $metarow['meta_value'];
				$course_lesson_ids[ $course_id ][] = $lesson_id;
			}
		}

		$statuses_to_check = array( 'in-progress' => 1, 'complete' => 1 );

		$per_page = 40;
		$user_id_offset = 0;
		$count = $statuses_added = $dup_logs = $dup_statuses = 0;

		$users_sql = "SELECT ID FROM $wpdb->users WHERE ID > %d LIMIT $per_page";
		$start_sql = "SELECT comment_post_ID, comment_date FROM $wpdb->comments WHERE comment_type = 'sensei_course_start' AND user_id = %d GROUP BY comment_post_ID ";
		$lessons_sql = "SELECT comment_approved AS status, comment_date FROM $wpdb->comments WHERE comment_type = 'sensei_lesson_status' AND user_id = %d AND comment_post_ID IN ( %s ) GROUP BY comment_post_ID ORDER BY comment_date_gmt DESC ";
		$check_existing_sql = "SELECT comment_ID FROM $wpdb->comments WHERE comment_post_ID = %d AND user_id = %d AND comment_type = 'sensei_course_status' ";

		// $per_page users at a time, could be batch run via an admin ajax command, 1 user at a time?
		while ( $user_ids = $wpdb->get_col( $wpdb->prepare($users_sql, $user_id_offset) ) ) {

			foreach ( $user_ids AS $user_id ) {

				// Grab all the course starts for the user
				$course_starts = $wpdb->get_results( $wpdb->prepare($start_sql, $user_id), ARRAY_A );
				foreach ( $course_starts as $course_log ) {

					$course_id = $course_log['comment_post_ID'];

					// Default status
					$status = 'complete';

					$status_date = $course_log['comment_date'];
					// Additional data for the course
					$meta_data = array(
						'start' => $status_date,
						'percent' => 0,
					);
					// Check if the course has lessons
					if ( !empty( $course_lesson_ids[$course_id] ) ) {

						$lessons_completed = 0;
						$total_lessons = count( $course_lesson_ids[ $course_id ] );

						// Don't use prepare as we need to provide the id join
						$sql = sprintf($lessons_sql, $user_id, join(', ', $course_lesson_ids[ $course_id ]) );
						// Get all lesson statuses for this Courses' lessons
						$lesson_statuses = $wpdb->get_results( $sql, ARRAY_A );
						// Not enough lesson statuses, thus cannot be complete
						if ( $total_lessons > count($lesson_statuses) ) {
							$status = 'in-progress';
						}
						// Count each lesson to work out the overall percentage
						foreach ( $lesson_statuses as $lesson_status ) {
							$status_date = $lesson_status['comment_date'];
							switch ( $lesson_status['status'] ) {
								case 'complete': // Lesson has no quiz/questions
								case 'graded': // Lesson has quiz, but it's not important what the grade was
								case 'passed': 
									$lessons_completed++;
									break;

								case 'in-progress':
								case 'ungraded': // Lesson has quiz, but it hasn't been graded
								case 'failed': // User failed the passmark on the lesson/quiz
									$status = 'in-progress';
									break;
							}
						}
						$meta_data['complete'] = $lessons_completed;
						$meta_data['percent'] = abs( round( ( doubleval( $lessons_completed ) * 100 ) / ( $total_lessons ), 0 ) );
					}
					else {
						// Course has no lessons, therefore cannot be 'complete'
						$status = 'in-progress';
					}
					$data = array(
						// This is the minimum data needed, the db defaults handle the rest
							'comment_post_ID' => $course_id,
							'comment_approved' => $status,
							'comment_type' => 'sensei_course_status',
							'comment_date' => $status_date,
							'user_id' => $user_id,
							'comment_date_gmt' => get_gmt_from_date($status_date),
							'comment_author' => '',
						);
					// Check it doesn't already exist
					$sql = $wpdb->prepare( $check_existing_sql, $course_id, $user_id );
					$comment_ID = $wpdb->get_var( $sql );
					if ( !$comment_ID ) {
						if ( array_key_exists( $status, $statuses_to_check ) ) {
							unset( $statuses_to_check[$status] );
							error_log( 'Adding: ' . print_r($data, true) . ' with meta: '.print_r($meta_data, true));
						}
						// Bypassing WP wp_insert_comment( $data ), so no actions/filters are run
						$wpdb->insert($wpdb->comments, $data);
						$comment_ID = (int) $wpdb->insert_id;

						if ( $comment_ID && !empty($meta_data) ) {
							foreach ( $meta_data as $key => $value ) {
								// Bypassing WP wp_insert_comment( $data ), so no actions/filters are run
								if ( $wpdb->get_var( $wpdb->prepare(
										"SELECT COUNT(*) FROM $wpdb->commentmeta WHERE comment_id = %d AND meta_key = %s ",
										$comment_ID, $key ) ) ) {
										continue; // Found the meta data already
								}
								$result = $wpdb->insert( $wpdb->commentmeta, array(
									'comment_id' => $comment_ID,
									'meta_key' => $key,
									'meta_value' => $value
								) );
							}
						}
						$statuses_added++;
					}
					else {
						$dup_statuses++;
						if ( 0 == ( $dup_statuses % 100 ) ) {
							WP_CLI::line( '...' );
						}
					}
				}
				$count++;
				if ( 0 == ( $count % 100 ) ) {
					WP_CLI::line( '.' );
//					break 2; // Handbreak to stop rather than process it all
				}
			}
			$wpdb->flush();
			$user_id_offset = $user_id; // Next set of users, basically an offset
		}

		WP_CLI::line( 'Timer end: ' . timer_stop() . ' secs' );
		WP_CLI::line( 'Memory used: ' . size_format(memory_get_usage()) );
		WP_CLI::line( sprintf( __('%s duplicates found.', 'imperial'), $dup_statuses ) );
		WP_CLI::success( sprintf( __('%s course statuses added.', 'imperial'), $statuses_added ) );
	}

	/**
	 * Repairs Course statuses that have greater than 100% completion
	 *
	 * @subcommand repair-course-statuses
	 */
	function repair_course_statuses() {
		global $woothemes_sensei,$wpdb;

		$course_lesson_ids = $lesson_user_statuses = array();

		// Get all Lesson => Course relationships
		$meta_list = $wpdb->get_results( "SELECT $wpdb->postmeta.post_id, $wpdb->postmeta.meta_value FROM $wpdb->postmeta INNER JOIN $wpdb->posts ON ($wpdb->posts.ID = $wpdb->postmeta.post_id) WHERE $wpdb->posts.post_type = 'lesson' AND $wpdb->postmeta.meta_key = '_lesson_course' ", ARRAY_A );
		if ( !empty($meta_list) ) {
			foreach ( $meta_list as $metarow ) {
				$lesson_id = $metarow['post_id'];
				$course_id = $metarow['meta_value'];
				$course_lesson_ids[ $course_id ][] = $lesson_id;
			}
		}

		// Get all Lesson => Course relationships
		$status_list = $wpdb->get_results( "SELECT user_id, comment_post_ID, comment_approved FROM $wpdb->comments WHERE comment_type = 'sensei_lesson_status' GROUP BY user_id, comment_post_ID ", ARRAY_A );
		if ( !empty($status_list) ) {
			foreach ( $status_list as $status ) {
				$lesson_user_statuses[ $status['comment_post_ID'] ][ $status['user_id'] ] = $status['comment_approved'];
			}
		}
		WP_CLI::line( 'Timer end: ' . timer_stop() . ' secs' );
		WP_CLI::line( 'Memory used: ' . size_format(memory_get_usage()) );

		$course_completion = $woothemes_sensei->settings->settings[ 'course_completion' ];

		$per_page = 40;
		$comment_id_offset = $count = 0;

		$course_sql = "SELECT * FROM $wpdb->comments WHERE comment_type = 'sensei_course_status' AND comment_ID > %d LIMIT $per_page";
		// $per_page users at a time
		while ( $course_statuses = $wpdb->get_results( $wpdb->prepare($course_sql, $comment_id_offset) ) ) {

			foreach ( $course_statuses AS $course_status ) {
				$user_id = $course_status->user_id;
				$course_id = $course_status->comment_post_ID;
				$total_lessons = count( $course_lesson_ids[ $course_id ] );
				if ( $total_lessons <= 0 ) {
					$total_lessons = 1; // Fix division of zero error, some courses have no lessons
				}
				$lessons_completed = 0;
				$status = 'in-progress';

				// Some Courses have no lessons... (can they ever be complete?)
				if ( !empty($course_lesson_ids[ $course_id ]) ) {
					foreach( $course_lesson_ids[ $course_id ] AS $lesson_id ) {
						$lesson_status = $lesson_user_statuses[ $lesson_id ][ $user_id ];
//						error_log( "Lesson: $lesson_id; User $user_id; ".print_r($lesson_status, true));
						// If lessons are complete without needing quizzes to be passed
						if ( 'passed' != $course_completion ) {
							switch ( $lesson_status ) {
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
							switch ( $lesson_status ) {
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
				} // Check for lessons
				if ( $lessons_completed == $total_lessons ) {
					$status = 'complete';
				}
//				error_log(" => total lessons: $total_lessons, lessons completed: $lessons_completed");
				// update the overall percentage of the course lessons complete (or graded) compared to 'in-progress' regardless of the above
				$metadata = array(
					'complete' => $lessons_completed,
					'percent' => abs( round( ( doubleval( $lessons_completed ) * 100 ) / ( $total_lessons ), 0 ) ),
				);
//				error_log(print_r($metadata, true));
				imperial_sensei_update_course_status( $user_id, $course_id, $status, $metadata );
				$count++;
//				break 2; // Handbreak to stop rather than process it all
				if ( 0 == ( $count % 100 ) ) {
					WP_CLI::line( '.' );
//					break 2; // Handbreak to stop rather than process it all
				}
			} // per course status
			$comment_id_offset = $course_status->comment_ID;
		} // all course statuses

		WP_CLI::line( 'Timer end: ' . timer_stop() . ' secs' );
		WP_CLI::line( 'Memory used: ' . size_format(memory_get_usage()) );
		WP_CLI::success( sprintf( __('%s course statuses fixed.', 'imperial'), $count ) );
	}

	/**
	 * Example command ( NOT YET COMPLETE )
	 *
	 * @subcommand convert-question-activities
	 */
	function convert_questions() {
		global $wpdb;

		wp_defer_comment_counting( true );

			// Directly querying the database is normally frowned upon, but all
			// of the API functions will return full objects or are limited to 
			// single post_IDs which will overall suck up lots of memory and take
			// far longer to process, in addition to calling filters and actions
			// that we wouldn't want running. This is best, just not as future proof.
			// But then this isn't expected to run more than once.

		$per_page = 40;
		$user_id_offset = $count = $questions_updated = 0;

		$users_sql = "SELECT ID FROM $wpdb->users WHERE ID > %d LIMIT $per_page";
		$answers_sql = "SELECT * FROM $wpdb->comments WHERE comment_type = 'sensei_user_answer' AND user_id = %d GROUP BY comment_post_ID ";
		$grades_sql = "SELECT comment_post_ID, comment_content FROM $wpdb->comments WHERE comment_type = 'sensei_user_grade' AND user_id = %d GROUP BY comment_post_ID ";
		$notes_sql = "SELECT comment_post_ID, comment_content FROM $wpdb->comments WHERE comment_type = 'sensei_answer_notes' AND user_id = %d GROUP BY comment_post_ID ";

		// $per_page users at a time
		while ( $user_ids = $wpdb->get_col( $wpdb->prepare($users_sql, $user_id_offset) ) ) {

			foreach ( $user_ids AS $user_id ) {

				$answer_grades = $answer_notes = array();

				// Pre-process the answer grades
				$_answer_grades = $wpdb->get_results( $wpdb->prepare($grades_sql, $user_id), ARRAY_A );
				foreach ( $_answer_grades as $answer_grade ) {
					// This will overwrite existing entries with the newer ones
					$answer_grades[ $answer_grade['comment_post_ID'] ] = $answer_grade['comment_content'];
				}
				unset( $_answer_grades );
//	error_log(count($answer_grades) . ' : ' . print_r($answer_grades, true));

				// Pre-process the answer notes
				$_answer_notes = $wpdb->get_results( $wpdb->prepare($notes_sql, $user_id), ARRAY_A );
				foreach ( $_answer_notes as $answer_note ) {
					// This will overwrite existing entries with the newer ones
					$answer_notes[ $answer_note['comment_post_ID'] ] = $answer_note['comment_content'];
				}
				unset( $_answer_notes );
//	error_log(count($answer_notes) . ' : ' . print_r($answer_notes, true));

				// Grab all the questions for the user
				$sql = $wpdb->prepare($answers_sql, $user_id);
//	error_log($sql);
				$answers = $wpdb->get_results( $sql, ARRAY_A );
//	error_log(count($answers) . ' : ' . print_r($answers, true));
				foreach ( $answers as $answer ) {

					// Excape data
					$answer = wp_slash($answer);

					$comment_ID = $answer['comment_ID'];

					$meta_data = array();

					// Check if the question has been graded, add as meta
					if ( !empty($answer_grades[ $answer['comment_post_ID'] ]) ) {
						$meta_data['user_grade'] = $answer_grades[ $answer['comment_post_ID'] ];
					}
					// Check if there is an answer note, add as meta
					if ( !empty($answer_notes[ $answer['comment_post_ID'] ]) ) {
						$meta_data['answer_note'] = $answer_notes[ $answer['comment_post_ID'] ];
					}

					// Wipe the unnessary data from the main comment
					$data = array(
							'comment_author' => '',
							'comment_author_email' => '',
							'comment_author_url' => '',
							'comment_author_IP' => '',
							'comment_agent' => '',
//							'comment_approved' => 'log', // New status for 'sensei_user_answer'
						);
					$data = array_merge($answer, $data);
//					error_log( print_r($data, true));

					$rval = $wpdb->update( $wpdb->comments, $data, compact( 'comment_ID' ) );
					if ( $rval ) {
						if ( !empty($meta_data) ) {
							foreach ( $meta_data as $key => $value ) {
								// Bypassing WP wp_insert_comment( $data ), so no actions/filters are run
								if ( $wpdb->get_var( $wpdb->prepare(
										"SELECT COUNT(*) FROM $wpdb->commentmeta WHERE comment_id = %d AND meta_key = %s ",
										$comment_ID, $key ) ) ) {
										continue; // Found the meta data already
								}
								$result = $wpdb->insert( $wpdb->commentmeta, array(
									'comment_id' => $comment_ID,
									'meta_key' => $key,
									'meta_value' => $value
								) );
							}
						}
						$questions_updated++;
					}
				}
				$count++;
				if ( 0 == ( $count % 100 ) ) {
					WP_CLI::line( '.' );
//					break 2; // Handbreak to stop rather than process it all
				}
			}
			$wpdb->flush();
			$user_id_offset = $user_id;
		}
//		wp_defer_comment_counting( false );

		WP_CLI::line( 'Timer end: ' . timer_stop() . ' secs' );
		WP_CLI::line( 'Memory used: ' . size_format(memory_get_usage()) );
		WP_CLI::success( sprintf( __('%s questions updated.', 'imperial'), $questions_updated ) );
	}

} // Imperial_Sensei_CLI_Command

WP_CLI::add_command( 'sensei', 'Imperial_Sensei_CLI_Command' );
