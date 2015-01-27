<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Sensei WP_CLI commands Class
 *
 * All functionality pertaining to the WP_CLI commands for Sensei.
 *
 * @package WordPress
 * @subpackage Sensei
 * @category Core
 * @author WooThemes
 * @since 1.7.0
 *
 */
WP_CLI::add_command( 'sensei', 'WooThemes_Sensei_CLI_Command' );

class WooThemes_Sensei_CLI_Command extends WP_CLI_Command {

	/**
	 * Adds data to quizzes to ensure that they maintain their 1 to 1 relationship to lessons.
	 *
	 * @subcommand status-changes-update-quiz-lesson-relationships
	 */
	function update_quiz_lesson_relationship( $args, $assoc_args ) {

		$args = array(
			'post_type' => 'quiz',
			'post_status' => 'any',
			'numberposts' => -1,
		);

		$quizzes = get_posts( $args );
		if ( empty($quizzes) ) {
			WP_CLI::error( __("No Quizzes available to fix!", 'woothemes-sensei') );
		}

		$count = 0;
		foreach( $quizzes as $quiz ) {

			if( ! isset( $quiz->ID ) || 0 != $quiz->post_parent ) continue;

			$lesson_id = get_post_meta( $quiz->ID, '_quiz_lesson', true );

			if( empty( $lesson_id ) ) continue;

			$data = array(
				'ID' => $quiz->ID,
				'post_parent' => $lesson_id,
			);
			wp_update_post( $data );

			update_post_meta( $lesson_id, '_lesson_quiz', $quiz->ID );
			$count++;
			// Show progress indicator
			if ( 0 == ( $count % 100 ) ) {
				WP_CLI::line( '.' );
			}
		}

		WP_CLI::line( sprintf( __("Time taken: %s secs, memory used: %s", 'woothemes-sensei'), timer_stop(), size_format( memory_get_usage() ) ) );
		WP_CLI::success( sprintf( __("Adjusted %s Quizzes!", 'woothemes-sensei'), $count ) );
	}

	/**
	 * Fix those lessons that are marked as having a quiz with questions when the quiz doesn't.
	 *
	 * @subcommand status-changes-fix-lessons
	 */
	function status_changes_fix_lessons( $args, $assoc_args ) {
		global $wpdb;

		// Get all Lessons with (and without) Quizzes...
		$args = array(
			'post_type' => 'lesson',
			'post_status' => 'any',
			'numberposts' => -1,
			'fields' => 'ids',
		);
		$lesson_ids = get_posts( $args );
		if ( empty($lesson_ids) ) {
			WP_CLI::error( __("No Lessons available to fix!", 'woothemes-sensei') );
		}

		// ...get all Quiz IDs for the above Lessons
		$id_list = join( ',', $lesson_ids );
		$meta_list = $wpdb->get_results( "SELECT post_id, meta_value FROM $wpdb->postmeta WHERE meta_key = '_quiz_lesson' AND meta_value IN ($id_list)", ARRAY_A );
		$lesson_quiz_ids = array();
		if ( !empty($meta_list) ) {
			foreach ( $meta_list as $metarow ) {
				$lesson_id = $metarow['meta_value'];
				$quiz_id = $metarow['post_id'];
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
		$d_count = $a_count = 0;
		foreach ( $lesson_quiz_ids AS $lesson_id => $quiz_id ) {
			if ( !in_array( $quiz_id, $lesson_quiz_ids_with_questions ) ) {

				// Quiz has no questions, drop the corresponding data
				delete_post_meta( $quiz_id, '_pass_required' );
				delete_post_meta( $quiz_id, '_quiz_passmark' );
				delete_post_meta( $lesson_id, '_quiz_has_questions' );
				$d_count++;
			}
			else if ( in_array( $quiz_id, $lesson_quiz_ids_with_questions ) ) {

				// Quiz has no questions, drop the corresponding data
				update_post_meta( $lesson_id, '_quiz_has_questions', true );
				$a_count++;
			}
			// Show progress indicator
			if ( 0 == ( ( $d_count + $a_count ) % 100 ) ) {
				WP_CLI::line( '.' );
			}
		}

		WP_CLI::line( sprintf( __("Time taken: %s secs, memory used: %s", 'woothemes-sensei'), timer_stop(), size_format( memory_get_usage() ) ) );
		WP_CLI::success( sprintf( __("Adjusted %s Lessons!", 'woothemes-sensei'), $d_count + $a_count ) );
	}

	/**
	 * Convert the existing Sensei lesson comment entries to new status entries
	 *
	 * @subcommand status-changes-convert-lessons
	 */
	function status_changes_convert_lessons( $args, $assoc_args ) {
		global $wpdb;

		wp_defer_comment_counting( true );

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
		if ( empty($lesson_ids_with_quizzes) ) {
			WP_CLI::error( __("No Lessons available to convert!", 'woothemes-sensei') );
		}

		// ...get all Quiz IDs for the above Lessons
		$id_list = join( ',', $lesson_ids_with_quizzes );
		$meta_list = $wpdb->get_results( "SELECT post_id, meta_value FROM $wpdb->postmeta WHERE meta_key = '_quiz_lesson' AND meta_value IN ($id_list)", ARRAY_A );
		$lesson_quiz_ids = array();
		if ( !empty($meta_list) ) {
			foreach ( $meta_list as $metarow ) {
				$lesson_id = $metarow['meta_value'];
				$quiz_id = $metarow['post_id'];
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

		$user_id_offset = $status_count = 0;
		$users_sql = "SELECT ID FROM $wpdb->users WHERE ID > %d ORDER BY ID ASC LIMIT 50";
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
						$status_count++;
					}
				} // each lesson
			} // each user
			$wpdb->flush();
			$user_id_offset = $user_id; // Next set of users, basically an offset
			// Show progress indicator
			WP_CLI::line( '.' );
		} // Batching users

		WP_CLI::line( sprintf( __("Time taken: %s secs, memory used: %s", 'woothemes-sensei'), timer_stop(), size_format( memory_get_usage() ) ) );
		WP_CLI::success( sprintf( __("Updated %s Lessons!", 'woothemes-sensei'), $status_count ) );
	}

	/**
	 * Convert the existing Sensei course comment entries to new status entries
	 *
	 * @subcommand status-changes-convert-courses
	 */
	function status_changes_convert_courses( $args, $assoc_args ) {
		global $wpdb;

		wp_defer_comment_counting( true );

		// Get all Lesson => Course relationships
		$meta_list = $wpdb->get_results( "SELECT $wpdb->postmeta.post_id, $wpdb->postmeta.meta_value FROM $wpdb->postmeta INNER JOIN $wpdb->posts ON ($wpdb->posts.ID = $wpdb->postmeta.post_id) WHERE $wpdb->posts.post_type = 'lesson' AND $wpdb->postmeta.meta_key = '_lesson_course'", ARRAY_A );
		$course_lesson_ids = array();
		if ( !empty($meta_list) ) {
			foreach ( $meta_list as $metarow ) {
				$lesson_id = $metarow['post_id'];
				$course_id = $metarow['meta_value'];
				$course_lesson_ids[ $course_id ][] = $lesson_id;
			}
		}

		$user_id_offset = $status_count = 0;
		$users_sql = "SELECT ID FROM $wpdb->users WHERE ID > %d ORDER BY ID ASC LIMIT 50";
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
						'complete' => 0,
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
						$status_count++;
					}
				} // each course
			} // each user
			$wpdb->flush();
			$user_id_offset = $user_id; // Next set of users, basically an offset
			// Show progress indicator
			WP_CLI::line( '.' );
		} // Batching users

		WP_CLI::line( sprintf( __("Time taken: %s secs, memory used: %s", 'woothemes-sensei'), timer_stop(), size_format( memory_get_usage() ) ) );
		WP_CLI::success( sprintf( __("Updated %s Courses!", 'woothemes-sensei'), $status_count ) );
	}

	/**
	 * Update the existing Sensei question answer comment entries to merge in the related grade and note comment entries
	 *
	 * @subcommand status-changes-convert-questions
	 */
	function status_changes_convert_questions( $args, $assoc_args ) {
		global $wpdb;

		wp_defer_comment_counting( true );

		$user_id_offset = $update_count = 0;
		$users_sql = "SELECT ID FROM $wpdb->users WHERE ID > %d ORDER BY ID ASC LIMIT 50";
		$answers_sql = "SELECT * FROM $wpdb->comments WHERE comment_type = 'sensei_user_answer' AND user_id = %d GROUP BY comment_post_ID ";
		$grades_sql = "SELECT comment_post_ID, comment_content FROM $wpdb->comments WHERE comment_type = 'sensei_user_grade' AND user_id = %d GROUP BY comment_post_ID ";
		$notes_sql = "SELECT comment_post_ID, comment_content FROM $wpdb->comments WHERE comment_type = 'sensei_answer_notes' AND user_id = %d GROUP BY comment_post_ID ";

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

				// Pre-process the answer notes
				$_answer_notes = $wpdb->get_results( $wpdb->prepare($notes_sql, $user_id), ARRAY_A );
				foreach ( $_answer_notes as $answer_note ) {
					// This will overwrite existing entries with the newer ones
					$answer_notes[ $answer_note['comment_post_ID'] ] = $answer_note['comment_content'];
				}
				unset( $_answer_notes );

				// Grab all the questions for the user
				$sql = $wpdb->prepare($answers_sql, $user_id);
				$answers = $wpdb->get_results( $sql, ARRAY_A );
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
						);
					$data = array_merge($answer, $data);

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
						$update_count++;
					}
				} // Each sensei_user_answer
			} // each user
			$wpdb->flush();
			$user_id_offset = $user_id; // Next set of users, basically an offset
			// Show progress indicator
			WP_CLI::line( '.' );
		} // Batching users

		WP_CLI::line( sprintf( __("Time taken: %s secs, memory used: %s", 'woothemes-sensei'), timer_stop(), size_format( memory_get_usage() ) ) );
		WP_CLI::success( sprintf( __("Updated %s Question Answers!", 'woothemes-sensei'), $update_count ) );
	}

	/**
	 * Update the comment counts for all Courses and Lessons now that sensei comments will no longer be counted.
	 *
	 * @subcommand status-changes-update-comment-counts
	 */
	function update_comment_course_lesson_comment_counts( $args, $assoc_args ) {
		global $wpdb;

		$item_count_result = $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->posts WHERE post_type IN ('course', 'lesson') " );

		if ( 0 == $item_count_result ) {
			WP_CLI::error( __("No Courses or Lessons available to update counts!", 'woothemes-sensei') );
		}

		$post_count = 0;
		// Recalculate all counts
		$items = $wpdb->get_results( "SELECT ID FROM $wpdb->posts WHERE post_type IN ('course', 'lesson') " );
		foreach ( (array) $items as $post ) {
			// Code copied from wp_update_comment_count_now()
			$new = (int) $wpdb->get_var( $wpdb->prepare("SELECT COUNT(*) FROM $wpdb->comments WHERE comment_post_ID = %d AND comment_approved = '1'", $post->ID) );
			$wpdb->update( $wpdb->posts, array('comment_count' => $new), array('ID' => $post->ID) );

			clean_post_cache( $post->ID );
			$post_count++;
			// Show progress indicator
			if ( 0 == ( $post_count % 100 ) ) {
				WP_CLI::line( '.' );
			}
		}

		WP_CLI::line( sprintf( __("Time taken: %s secs, memory used: %s", 'woothemes-sensei'), timer_stop(), size_format( memory_get_usage() ) ) );
		WP_CLI::success( sprintf( __("Updated %s Course/Lesson comment counts!", 'woothemes-sensei'), $post_count ) );
	}

}

