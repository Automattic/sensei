<?php

use Sensei\Internal\Student_Progress\Course_Progress\Models\Course_Progress;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Sensei Utilities Class
 *
 * Common utility functions for Sensei.
 *
 * @package Core
 * @author Automattic
 *
 * @since 1.0.0
 */
class Sensei_Utils {
	const WC_INFORMATION_TRANSIENT = 'sensei_woocommerce_plugin_information';

	/**
	 * Get the placeholder thumbnail image.
	 *
	 * @access  public
	 * @since   1.0.0
	 * @return  string The URL to the placeholder thumbnail image.
	 */
	public static function get_placeholder_image() {

		return esc_url( apply_filters( 'sensei_placeholder_thumbnail', Sensei()->plugin_url . 'assets/images/placeholder.png' ) );
	}

	/**
	 * Log an activity item.
	 *
	 * @access public
	 * @since  1.0.0
	 * @param  array $args (default: array())
	 * @return bool | int
	 */
	public static function sensei_log_activity( $args = array() ) {
		global $wpdb;

		// Args, minimum data required for WP
		$data = array(
			'comment_post_ID'      => intval( $args['post_id'] ),
			'comment_author'       => '', // Not needed
			'comment_author_email' => '', // Not needed
			'comment_author_url'   => '', // Not needed
			'comment_content'      => ! empty( $args['data'] ) ? esc_html( $args['data'] ) : '',
			'comment_type'         => esc_attr( $args['type'] ),
			'user_id'              => intval( $args['user_id'] ),
			'comment_approved'     => ! empty( $args['status'] ) ? esc_html( $args['status'] ) : 'log',
		);
		// Allow extra data
		if ( ! empty( $args['username'] ) ) {
			$data['comment_author'] = sanitize_user( $args['username'] );
		}
		if ( ! empty( $args['user_email'] ) ) {
			$data['comment_author_email'] = sanitize_email( $args['user_email'] );
		}
		if ( ! empty( $args['user_url'] ) ) {
			$data['comment_author_url'] = esc_url( $args['user_url'] );
		}
		if ( ! empty( $args['parent'] ) ) {
			$data['comment_parent'] = $args['parent'];
		}
		// Sanity check
		if ( empty( $args['user_id'] ) ) {
			_deprecated_argument( __FUNCTION__, '1.0', esc_html__( 'At no point should user_id be equal to 0.', 'sensei-lms' ) );
			return false;
		}

		do_action( 'sensei_log_activity_before', $args, $data );

		// Custom Logic
		// Check if comment exists first
		$comment_id = $wpdb->get_var( $wpdb->prepare( "SELECT comment_ID FROM $wpdb->comments WHERE comment_post_ID = %d AND user_id = %d AND comment_type = %s ", $args['post_id'], $args['user_id'], $args['type'] ) );
		if ( ! $comment_id ) {
			// Add the comment
			$comment_id = wp_insert_comment( $data );
		} elseif ( isset( $args['action'] ) && 'update' == $args['action'] ) {
			// Update the comment if an update was requested
			$data['comment_ID'] = $comment_id;
			// By default update the timestamp of the comment
			if ( empty( $args['keep_time'] ) ) {
				$data['comment_date'] = current_time( 'mysql' );
			}
			wp_update_comment( $data );
		}

		do_action( 'sensei_log_activity_after', $args, $data, $comment_id );

		Sensei()->flush_comment_counts_cache( $args['post_id'] );

		if ( 0 < $comment_id ) {
			// Return the ID so that it can be used for meta data storage
			return $comment_id;
		} else {
			return false;
		}
	}


	/**
	 * Check for Sensei activity.
	 *
	 * @access public
	 * @since  1.0.0
	 * @param  array $args (default: array())
	 * @param  bool  $return_comments (default: false)
	 * @return mixed | int
	 */
	public static function sensei_check_for_activity( $args = array(), $return_comments = false ) {
		if ( ! $return_comments ) {
			$args['count'] = true;
		}

		// A user ID of 0 is invalid, so shortcut this.
		if ( isset( $args['user_id'] ) && 0 === intval( $args['user_id'] ) ) {
			_deprecated_argument( __FUNCTION__, '1.0', esc_html__( 'At no point should user_id be equal to 0.', 'sensei-lms' ) );
			return false;
		}

		if ( ! isset( $args['status'] ) ) {
			$args['status'] = 'any';
		}

		/**
		 * This filter runs inside Sensei_Utils::sensei_check_for_activity
		 *
		 * It runs while getting the comments for the given request.
		 *
		 * @param int|array $comments
		 * @param array $args Search arguments.
		 */
		$comments = apply_filters( 'sensei_check_for_activity', get_comments( $args ), $args );

		// Return comments.
		if ( $return_comments ) {
			// Could check for array of 1 and just return the 1 item?
			if ( is_array( $comments ) && 1 == count( $comments ) ) {
				$comments = array_shift( $comments );
			}

			return $comments;
		}
		// Count comments.
		return intval( $comments ); // This is the count, check the return from WP_Comment_Query.
	}


	/**
	 * Get IDs of Sensei activity items.
	 *
	 * @access public
	 * @since  1.0.0
	 * @param  array $args (default: array())
	 * @return array
	 */
	public static function sensei_activity_ids( $args = array() ) {

		$comments = self::sensei_check_for_activity( $args, true );
		// Need to always use an array, even with only 1 item
		if ( ! is_array( $comments ) ) {
			$comments = array( $comments );
		}

		$post_ids = array();
		// Count comments
		if ( is_array( $comments ) && ( 0 < intval( count( $comments ) ) ) ) {
			foreach ( $comments as $key => $value ) {
				// Add matches to id array
				if ( isset( $args['field'] ) && 'comment' == $args['field'] ) {
					array_push( $post_ids, $value->comment_ID );
				} elseif ( isset( $args['field'] ) && 'user_id' == $args['field'] ) {
					array_push( $post_ids, $value->user_id );
				} else {
					array_push( $post_ids, $value->comment_post_ID );
				}
			}
			// Reset array indexes
			$post_ids = array_unique( $post_ids );
			$post_ids = array_values( $post_ids );
		}

		return $post_ids;
	}


	/**
	 * Delete Sensei activities.
	 *
	 * @access public
	 * @since  1.0.0
	 * @param  array $args (default: array())
	 * @return boolean
	 */
	public static function sensei_delete_activities( $args = array() ) {

		$dataset_changes = false;

		// If activity exists remove activity from log
		$comments = self::sensei_check_for_activity(
			array(
				'post_id' => intval( $args['post_id'] ),
				'user_id' => intval( $args['user_id'] ),
				'type'    => esc_attr( $args['type'] ),
			),
			true
		);
		if ( $comments ) {
			// Need to always return an array, even with only 1 item
			if ( ! is_array( $comments ) ) {
				$comments = array( $comments );
			}
			foreach ( $comments as $key => $value ) {
				if ( isset( $value->comment_ID ) && 0 < $value->comment_ID ) {
					$dataset_changes = wp_delete_comment( intval( $value->comment_ID ), true );
				}
			}
		}

		Sensei()->flush_comment_counts_cache( $args['post_id'] );

		return $dataset_changes;
	}

	/**
	 * Delete all activity for specified user.
	 *
	 * @access public
	 * @since  1.5.0
	 *
	 * @deprecated 3.0.0 Use `\Sensei_Learner::delete_all_user_activity` instead.
	 *
	 * @param  integer $user_id User ID.
	 * @return boolean
	 */
	public static function delete_all_user_activity( $user_id = 0 ) {
		_deprecated_function( __METHOD__, '3.0.0', 'Sensei_Learner::delete_all_user_activity' );

		return \Sensei_Learner::instance()->delete_all_user_activity( $user_id );
	}

	/**
	 * Get value for a specified activity.
	 *
	 * @access public
	 * @since  1.0.0
	 * @param  array $args (default: array())
	 * @return string
	 */
	public static function sensei_get_activity_value( $args = array() ) {

		$activity_value = false;
		if ( ! empty( $args['field'] ) ) {
			$comment = self::sensei_check_for_activity( $args, true );

			if ( isset( $comment->{$args['field']} ) && '' != $comment->{$args['field']} ) {
				$activity_value = $comment->{$args['field']};
			}
		}
		return $activity_value;
	}

	/**
	 * Load the WordPress rich text editor
	 *
	 * @param  string $content    Initial content for editor
	 * @param  string $editor_id  ID of editor (only lower case characters - no spaces, underscores, hyphens, etc.)
	 * @param  string $input_name Name for text area form element
	 * @return void
	 */
	public static function sensei_text_editor( $content = '', $editor_id = 'senseitexteditor', $input_name = '' ) {

		if ( ! $input_name ) {
			$input_name = $editor_id;
		}

		$buttons = 'bold,italic,underline,strikethrough,blockquote,bullist,numlist,justifyleft,justifycenter,justifyright,undo,redo,pastetext';

		$settings = array(
			'media_buttons' => false,
			'wpautop'       => true,
			'textarea_name' => $input_name,
			'editor_class'  => 'sensei_text_editor',
			'teeny'         => false,
			'dfw'           => false,
			'editor_css'    => '<style> .mce-top-part button { background-color: rgba(0,0,0,0); } </style>',
			'tinymce'       => array(
				'theme_advanced_buttons1' => $buttons,
				'theme_advanced_buttons2' => '',
			),
			'quicktags'     => false,
		);

		wp_editor( $content, $editor_id, $settings );

	}

	public static function upload_file( $file = array() ) {

		require_once ABSPATH . 'wp-admin/includes/admin.php';

		/**
		 * Filter the data array for the Sensei wp_handle_upload function call
		 *
		 * This filter was mainly added for Unit Testing purposes.
		 *
		 * @since 1.7.4
		 *
		 * @param array  $file_upload_args {
		 *      array of current values
		 *
		 *     @type string test_form set to false by default
		 * }
		 */
		$file_upload_args = apply_filters( 'sensei_file_upload_args', array( 'test_form' => false ) );

		/**
		 * Customize the prefix prepended onto files uploaded in Sensei.
		 *
		 * @since 3.9.0
		 * @hook sensei_file_upload_file_prefix
		 *
		 * @param {string} $prefix Prefix to prepend to uploaded files.
		 * @param {array}  $file   Arguments with uploaded file information.
		 *
		 * @return {string}
		 */
		$file_prefix = apply_filters( 'sensei_file_upload_file_prefix', substr( md5( uniqid() ), 0, 7 ) . '_', $file );

		$file['name'] = $file_prefix . $file['name'];
		$file_return  = wp_handle_upload( $file, $file_upload_args );

		if ( isset( $file_return['error'] ) || isset( $file_return['upload_error_handler'] ) ) {
			return false;
		} else {

			$filename = $file_return['file'];

			$attachment = array(
				'post_mime_type' => $file_return['type'],
				'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $filename ) ),
				'post_content'   => '',
				'post_status'    => 'inherit',
				'guid'           => $file_return['url'],
			);

			$attachment_id = wp_insert_attachment( $attachment, $filename );

			require_once ABSPATH . 'wp-admin/includes/image.php';
			$attachment_data = wp_generate_attachment_metadata( $attachment_id, $filename );
			wp_update_attachment_metadata( $attachment_id, $attachment_data );

			if ( 0 < intval( $attachment_id ) ) {
				return $attachment_id;
			}
		}

		return false;
	}

	/**
	 * Grade quiz
	 *
	 * @param  integer $quiz_id ID of quiz.
	 * @param  float   $grade   Grade received.
	 * @param  integer $user_id ID of user being graded.
	 * @param  string  $quiz_grade_type default 'auto'.
	 *
	 * @return boolean
	 */
	public static function sensei_grade_quiz( $quiz_id = 0, $grade = 0, $user_id = 0, $quiz_grade_type = 'auto' ): bool {
		$user_id = $user_id ? $user_id : get_current_user_id();

		if ( ! $quiz_id || ! $user_id ) {
			return false;
		}

		$quiz_submission = Sensei()->quiz_submission_repository->get( $quiz_id, $user_id );
		if ( ! $quiz_submission ) {
			return false;
		}

		$quiz_submission->set_final_grade( $grade );
		Sensei()->quiz_submission_repository->save( $quiz_submission );

		$quiz_passmark = absint( get_post_meta( $quiz_id, '_quiz_passmark', true ) );

		do_action( 'sensei_user_quiz_grade', $user_id, $quiz_id, $grade, $quiz_passmark, $quiz_grade_type );

		return true;
	}

	/**
	 * Grade question
	 *
	 * @param  integer $question_id ID of question
	 * @param  integer $grade       Grade received
	 * @param int     $user_id
	 * @return boolean
	 */
	public static function sensei_grade_question( $question_id = 0, $grade = 0, $user_id = 0 ) {
		if ( intval( $user_id ) == 0 ) {
			$user_id = get_current_user_id();
		}

		$activity_logged = false;
		if ( intval( $question_id ) > 0 && intval( $user_id ) > 0 ) {

			$user_answer_id  = self::sensei_get_activity_value(
				array(
					'post_id' => $question_id,
					'user_id' => $user_id,
					'type'    => 'sensei_user_answer',
					'field'   => 'comment_ID',
				)
			);
			$activity_logged = update_comment_meta( $user_answer_id, 'user_grade', $grade );

			$answer_notes = get_post_meta( $question_id, '_answer_feedback', true );
			if ( ! empty( $answer_notes ) ) {
				update_comment_meta( $user_answer_id, 'answer_note', base64_encode( $answer_notes ) );
			}
		}

		return $activity_logged;
	}

	public static function sensei_delete_question_grade( $question_id = 0, $user_id = 0 ) {
		if ( intval( $user_id ) == 0 ) {
			$user_id = get_current_user_id();
		}

		$activity_logged = false;
		if ( intval( $question_id ) > 0 ) {
			$user_answer_id  = self::sensei_get_activity_value(
				array(
					'post_id' => $question_id,
					'user_id' => $user_id,
					'type'    => 'sensei_user_answer',
					'field'   => 'comment_ID',
				)
			);
			$activity_logged = delete_comment_meta( $user_answer_id, 'user_grade' );
		}

		return $activity_logged;
	}


	/**
	 * Alias to Woothemes_Sensei_Utils::sensei_start_lesson
	 *
	 * @since 1.7.4
	 *
	 * @param integer $user_id
	 * @param integer $lesson_id
	 * @param bool    $complete
	 *
	 * @return mixed boolean or comment_ID
	 */
	public static function user_start_lesson( $user_id = 0, $lesson_id = 0, $complete = false ) {

		return self::sensei_start_lesson( $lesson_id, $user_id, $complete );

	}

	/**
	 * Mark a lesson as started for user
	 *
	 * Will also start the lesson course for the user if the user hasn't started taking it already.
	 *
	 * @since 1.6.0
	 *
	 * @param  integer     $lesson_id ID of lesson
	 * @param int| string $user_id default 0
	 * @param bool        $complete default false
	 *
	 * @return mixed boolean or comment_ID
	 */
	public static function sensei_start_lesson( $lesson_id = 0, $user_id = 0, $complete = false ) {

		if ( 0 === (int) $user_id ) {
			$user_id = get_current_user_id();
		}

		if ( 0 >= (int) $lesson_id ) {
			return false;
		}

		$course_id = get_post_meta( $lesson_id, '_lesson_course', true );
		if ( $course_id ) {
			$is_user_taking_course = self::has_started_course( $course_id, $user_id );
			if ( ! $is_user_taking_course ) {
				self::user_start_course( $user_id, $course_id );
			}
		}

		// Note: When this action runs the lesson status may not yet exist.
		do_action( 'sensei_user_lesson_start', $user_id, $lesson_id );

		$lesson_progress = Sensei()->lesson_progress_repository->get( $lesson_id, $user_id );
		if ( ! $lesson_progress ) {
			$lesson_progress = Sensei()->lesson_progress_repository->create( $lesson_id, $user_id );
			$has_questions   = Sensei_Lesson::lesson_quiz_has_questions( $lesson_id );
			if ( $complete && $has_questions ) {
				update_comment_meta( $lesson_progress->get_id(), 'grade', 0 );
			}
		}

		if ( $complete && ! $lesson_progress->is_complete() ) {
			$lesson_progress->complete();
			Sensei()->lesson_progress_repository->save( $lesson_progress );
		}

		if ( $complete ) {
			// Run this *after* the lesson status has been created/updated.
			do_action( 'sensei_user_lesson_end', $user_id, $lesson_id );
		}

		return $lesson_progress->get_id();
	}

	/**
	 * Remove user from lesson, deleting all data from the corresponding quiz
	 *
	 * @param int $lesson_id
	 * @param int $user_id
	 * @return boolean
	 */
	public static function sensei_remove_user_from_lesson( $lesson_id = 0, $user_id = 0, $from_course = false ) {

		if ( ! $lesson_id ) {
			return false;
		}

		if ( intval( $user_id ) == 0 ) {
			$user_id = get_current_user_id();
		}

		// Process quiz
		$lesson_quiz_id = Sensei()->lesson->lesson_quizzes( $lesson_id );

		// Delete quiz answers, this auto deletes the corresponding meta data, such as the question/answer grade
		self::sensei_delete_quiz_answers( $lesson_quiz_id, $user_id );

		// Delete quiz saved answers
		Sensei()->quiz->reset_user_lesson_data( $lesson_id, $user_id );

		// Delete lesson progress.
		$lesson_progress = Sensei()->lesson_progress_repository->get( $lesson_id, $user_id );
		if ( $lesson_progress ) {
			Sensei()->lesson_progress_repository->delete( $lesson_progress );
		}

		if ( ! $from_course ) {
			do_action( 'sensei_user_lesson_reset', $user_id, $lesson_id );
		}

		return true;
	}

	/**
	 * Remove a user from a course, deleting all activities across all lessons
	 *
	 * @param int $course_id
	 * @param int $user_id
	 * @return boolean
	 */
	public static function sensei_remove_user_from_course( $course_id = 0, $user_id = 0 ) {

		if ( ! $course_id ) {
			return false;
		}

		if ( intval( $user_id ) == 0 ) {
			$user_id = get_current_user_id();
		}

		$lesson_ids = Sensei()->course->course_lessons( $course_id, 'any', 'ids' );

		foreach ( $lesson_ids as $lesson_id ) {
			self::sensei_remove_user_from_lesson( $lesson_id, $user_id, true );
		}

		// Delete course progress.
		$course_progress = Sensei()->course_progress_repository->get( $course_id, $user_id );
		if ( $course_progress ) {
			Sensei()->course_progress_repository->delete( $course_progress );
		}

		do_action( 'sensei_user_course_reset', $user_id, $course_id );

		return true;
	}

	public static function sensei_get_quiz_questions( $quiz_id = 0 ) {

		$questions = array();

		if ( intval( $quiz_id ) > 0 ) {
			$questions = Sensei()->lesson->lesson_quiz_questions( $quiz_id );
			$questions = self::array_sort_reorder( $questions );
		}

		return $questions;
	}

	public static function sensei_get_quiz_total( $quiz_id = 0 ) {

		$quiz_total = 0;

		if ( $quiz_id > 0 ) {
			$questions      = self::sensei_get_quiz_questions( $quiz_id );
			$question_grade = 0;
			foreach ( $questions as $question ) {
				$question_grade = Sensei()->question->get_question_grade( $question->ID );
				$quiz_total    += $question_grade;
			}
		}

		return $quiz_total;
	}

	/**
	 * Returns the user_grade for a specific question and user, or sensei_user_answer entry
	 *
	 * @param mixed $question
	 * @param int   $user_id
	 * @return string
	 */
	public static function sensei_get_user_question_grade( $question = 0, $user_id = 0 ) {
		$question_grade = false;
		if ( $question ) {
			if ( is_object( $question ) ) {
				$user_answer_id = $question->comment_ID;
			} else {
				if ( intval( $user_id ) == 0 ) {
					$user_id = get_current_user_id();
				}
				$user_answer_id = self::sensei_get_activity_value(
					array(
						'post_id' => intval( $question ),
						'user_id' => $user_id,
						'type'    => 'sensei_user_answer',
						'field'   => 'comment_ID',
					)
				);
			}
			if ( $user_answer_id ) {
				$question_grade = get_comment_meta( $user_answer_id, 'user_grade', true );
			}
		}

		return $question_grade;
	}

	public static function sensei_delete_quiz_answers( $quiz_id = 0, $user_id = 0 ) {
		if ( intval( $user_id ) == 0 ) {
			$user_id = get_current_user_id();
		}

		$delete_answers = false;
		if ( intval( $quiz_id ) > 0 ) {
			$questions = self::sensei_get_quiz_questions( $quiz_id );
			foreach ( $questions as $question ) {
				$delete_answers = self::sensei_delete_activities(
					array(
						'post_id' => $question->ID,
						'user_id' => $user_id,
						'type'    => 'sensei_user_answer',
					)
				);
			}
		}

		return $delete_answers;
	}

	/**
	 * Delete the quiz submission grade.
	 *
	 * @param int $quiz_id The quiz ID.
	 * @param int $user_id The user ID. Defaults to the current user ID.
	 *
	 * @return bool
	 */
	public static function sensei_delete_quiz_grade( $quiz_id = 0, $user_id = 0 ): bool {
		if ( intval( $user_id ) === 0 ) {
			$user_id = get_current_user_id();
		}

		if ( ! $quiz_id || ! $user_id ) {
			return false;
		}

		$quiz_submission = Sensei()->quiz_submission_repository->get( $quiz_id, $user_id );
		if ( ! $quiz_submission ) {
			return false;
		}

		$quiz_submission->set_final_grade( null );
		Sensei()->quiz_submission_repository->save( $quiz_submission );

		return true;
	}

	/**
	 * Add answer notes to question
	 *
	 * @param  integer $question_id ID of question
	 * @param  integer $user_id     ID of user
	 * @param string  $notes
	 * @return boolean
	 */
	public static function sensei_add_answer_notes( $question_id = 0, $user_id = 0, $notes = '' ) {
		if ( intval( $user_id ) == 0 ) {
			$user_id = get_current_user_id();
		}

		$activity_logged = false;

		if ( intval( $question_id ) > 0 ) {
			$notes = base64_encode( $notes );

			// Don't store empty values, no point
			if ( ! empty( $notes ) ) {
				$user_lesson_id  = self::sensei_get_activity_value(
					array(
						'post_id' => $question_id,
						'user_id' => $user_id,
						'type'    => 'sensei_user_answer',
						'field'   => 'comment_ID',
					)
				);
				$activity_logged = update_comment_meta( $user_lesson_id, 'answer_note', $notes );
			} else {
				$activity_logged = true;
			}
		}

		return $activity_logged;
	}

	/**
	 * array_sort_reorder handle sorting of table data
	 *
	 * @since  1.3.0
	 * @param  array $return_array data to be ordered
	 * @return array $return_array ordered data
	 */
	public static function array_sort_reorder( $return_array ) {
		if ( isset( $_GET['orderby'] ) && '' != esc_html( $_GET['orderby'] ) ) {
			$sort_key = '';
			if ( '' != $sort_key ) {
					self::sort_array_by_key( $return_array, $sort_key );
				if ( isset( $_GET['order'] ) && 'desc' == esc_html( $_GET['order'] ) ) {
					$return_array = array_reverse( $return_array, true );
				}
			}
			return $return_array;
		} else {
			return $return_array;
		}
	}

	/**
	 * sort_array_by_key sorts array by key
	 *
	 * @since  1.3.0
	 * @param  array                           $array by ref
	 * @param  $key string column name in array
	 * @return void
	 */
	public static function sort_array_by_key( $array, $key ) {
		$sorter = array();
		$ret    = array();
		reset( $array );
		foreach ( $array as $ii => $va ) {
			$sorter[ $ii ] = $va[ $key ];
		}
		asort( $sorter );
		foreach ( $sorter as $ii => $va ) {
			$ret[ $ii ] = $array[ $ii ];
		}
		$array = $ret;
	}

	/**
	 * This function returns an array of lesson quiz questions
	 *
	 * @since 1.3.2
	 * @since 3.5.0 Added $query_args.
	 *
	 * @param  integer $quiz_id
	 * @param  array   $query_args Additional args for the query.
	 * @return array of quiz questions
	 */
	public static function lesson_quiz_questions( $quiz_id = 0, $query_args = [] ) {
		$questions_array = array();
		if ( 0 < $quiz_id ) {
			$defaults      = array(
				'post_type'        => 'question',
				'posts_per_page'   => -1,
				'orderby'          => 'ID',
				'order'            => 'ASC',
				'meta_query'       => array(
					array(
						'key'   => '_quiz_id',
						'value' => $quiz_id,
					),
				),
				'post_status'      => 'any',
				'suppress_filters' => 0,
			);
			$question_args = wp_parse_args( $query_args, $defaults );

			$questions_array = get_posts( $question_args );
		}
		return $questions_array;
	}

	/**
	 * Complete this course forcefully for this user by passing all the lessons.
	 *
	 * @param int $user_id User ID.
	 * @param int $course_id Course ID
	 */
	public static function force_complete_user_course( $user_id, $course_id ) {
		$user = get_user_by( 'id', $user_id );
		if ( false === $user ) {
			return;
		}
		$lesson_ids = Sensei()->course->course_lessons( $course_id, 'any', 'ids' );

		foreach ( $lesson_ids as $id ) {
			self::sensei_start_lesson( $id, $user_id, true );
		}
	}

	/**
	 * Get pass mark for course
	 *
	 * @param  integer $course_id ID of course
	 * @return integer            Pass mark for course
	 */
	public static function sensei_course_pass_grade( $course_id = 0 ) {

		$course_passmark = 0;

		if ( $course_id > 0 ) {
			$lessons        = Sensei()->course->course_lessons( $course_id );
			$lesson_count   = 0;
			$total_passmark = 0;
			foreach ( $lessons as $lesson ) {

				// Get Quiz ID
				$quiz_id = Sensei()->lesson->lesson_quizzes( $lesson->ID );

				// Check for a pass being required
				$pass_required = get_post_meta( $quiz_id, '_pass_required', true );
				if ( $pass_required ) {
					// Get quiz passmark
					$quiz_passmark = absint( get_post_meta( $quiz_id, '_quiz_passmark', true ) );

					// Add up total passmark
					$total_passmark += $quiz_passmark;

					++$lesson_count;
				}
			}
			// Might be a case of no required lessons
			if ( $lesson_count ) {
				$course_passmark = ( $total_passmark / $lesson_count );
			}
		}

		/**
		 * Filter the course pass mark
		 *
		 * @since 1.9.7
		 *
		 * @param integer $course_passmark  Pass mark for course
		 * @param integer $course_id        ID of course
		 */
		return apply_filters( 'sensei_course_pass_grade', self::round( $course_passmark ), $course_id );
	}

	/**
	 * Get user total grade for course
	 *
	 * @param  integer $course_id ID of course
	 * @param  integer $user_id   ID of user
	 * @return integer            User's total grade
	 */
	public static function sensei_course_user_grade( $course_id = 0, $user_id = 0 ) {

		if ( intval( $user_id ) == 0 ) {
			$user_id = get_current_user_id();
		}

		$total_grade = 0;

		if ( $course_id > 0 && $user_id > 0 ) {
			$lessons      = Sensei()->course->course_lessons( $course_id );
			$lesson_count = 0;
			$total_grade  = 0;
			foreach ( $lessons as $lesson ) {

				// Check for lesson having questions, thus a quiz, thus having a grade
				$has_questions = Sensei()->lesson->lesson_has_quiz_with_graded_questions( $lesson->ID );

				if ( $has_questions ) {
					$user_lesson_status = self::user_lesson_status( $lesson->ID, $user_id );

					if ( empty( $user_lesson_status ) ) {
						continue;
					}
					// Get user quiz grade
					$quiz_grade = get_comment_meta( $user_lesson_status->comment_ID, 'grade', true );

					// Add up total grade
					$total_grade += intval( $quiz_grade );

					++$lesson_count;
				}
			}

			// Might be a case of no lessons with quizzes
			if ( $lesson_count ) {
				$total_grade = ( $total_grade / $lesson_count );
			}
		}

		/**
		 * Filter the user total grade for course
		 *
		 * @since 1.9.7
		 *
		 * @param integer $total_grade  User's total grade
		 * @param integer $course_id    ID of course
		 * @param integer $user_id      ID of user
		 */
		return apply_filters( 'sensei_course_user_grade', self::round( $total_grade ), $course_id, $user_id );
	}

	/**
	 * Check if user has passed a course
	 *
	 * @param  integer $course_id ID of course
	 * @param  integer $user_id   ID of user
	 * @return boolean
	 */
	public static function sensei_user_passed_course( $course_id = 0, $user_id = 0 ) {
		if ( intval( $user_id ) == 0 ) {
			$user_id = get_current_user_id();
		}

		$pass = false;

		if ( $course_id > 0 && $user_id > 0 ) {
			$passmark   = self::sensei_course_pass_grade( $course_id );
			$user_grade = self::sensei_course_user_grade( $course_id, $user_id );

			if ( $user_grade >= $passmark ) {
				$pass = true;
			}
		}

		return $pass; // Should add the $passmark and $user_grade as part of the return!

	}

	/**
	 * Set the status message displayed to the user for a course
	 *
	 * @param  integer $course_id ID of course
	 * @param  integer $user_id   ID of user
	 */
	public static function sensei_user_course_status_message( $course_id = 0, $user_id = 0 ) {
		if ( intval( $user_id ) == 0 ) {
			$user_id = get_current_user_id();
		}

		$status    = 'not_started';
		$box_class = 'info';
		$message   = __( 'You have not started this course yet.', 'sensei-lms' );

		if ( $course_id > 0 && $user_id > 0 ) {

			$started_course = self::has_started_course( $course_id, $user_id );

			if ( $started_course ) {
				$passmark   = self::sensei_course_pass_grade( $course_id ); // This happens inside sensei_user_passed_course()!
				$user_grade = self::sensei_course_user_grade( $course_id, $user_id ); // This happens inside sensei_user_passed_course()!

				// if the user has started the course but there is no passmark
				// then do not show passed/failed messages.
				if ( ! $passmark ) {
					return;
				}

				if ( $user_grade >= $passmark ) {
					$status    = 'passed';
					$box_class = 'tick';
					// translators: Placeholder is the user's grade.
					$message = sprintf( __( 'You have passed this course with a grade of %1$d%%.', 'sensei-lms' ), $user_grade );
				} else {
					$status    = 'failed';
					$box_class = 'alert';
					// translators: Placeholders are the required grade and the actual grade, respectively.
					$message = sprintf( __( 'You require %1$d%% to pass this course. Your grade is %2$s%%.', 'sensei-lms' ), $passmark, $user_grade );
				}
			}
		}

		$message = apply_filters( 'sensei_user_course_status_' . $status, $message );
		Sensei()->notices->add_notice( $message, $box_class );
	}

	/**
	 * Set the status message displayed to the user for a quiz
	 *
	 * @param  integer $lesson_id ID of quiz lesson
	 * @param  integer $user_id   ID of user
	 * @param  bool    $is_lesson
	 * @return array              Status code and message
	 */
	public static function sensei_user_quiz_status_message( $lesson_id = 0, $user_id = 0, $is_lesson = false ) {
		global  $current_user;
		if ( intval( $user_id ) == 0 ) {
			$user_id = $current_user->ID;
		}

		$status    = 'not_started';
		$box_class = 'info';
		$message   = __( "You have not taken this lesson's quiz yet", 'sensei-lms' );
		$extra     = '';

		if ( $lesson_id > 0 && $user_id > 0 ) {
			// Course ID
			$course_id = absint( get_post_meta( $lesson_id, '_lesson_course', true ) );

			// Has user started course
			$started_course = Sensei_Course::is_user_enrolled( $course_id, $user_id );

			// Has user completed lesson
			$user_lesson_status = self::user_lesson_status( $lesson_id, $user_id );
			$lesson_complete    = self::user_completed_lesson( $user_lesson_status );

			// Quiz ID
			$quiz_id = Sensei()->lesson->lesson_quizzes( $lesson_id );

			// Quiz grade
			$quiz_grade = 0;
			if ( $user_lesson_status ) {
				// user lesson status can return as an array.
				if ( is_array( $user_lesson_status ) ) {
					$comment_ID = $user_lesson_status[0]->comment_ID;

				} else {
					$comment_ID = $user_lesson_status->comment_ID;
				}

				$quiz_grade = get_comment_meta( $comment_ID, 'grade', true );
			}

			// Quiz passmark
			$quiz_passmark = absint( get_post_meta( $quiz_id, '_quiz_passmark', true ) );

			// Pass required
			$pass_required = get_post_meta( $quiz_id, '_pass_required', true );

			// Quiz questions
			$has_quiz_questions = Sensei_Lesson::lesson_quiz_has_questions( $lesson_id );

			if ( ! $started_course ) {
				$status    = 'not_started_course';
				$box_class = 'info';
				// translators: Placeholders are an opening and closing <a> tag linking to the course permalink.
				$message = sprintf( __( 'Please sign up for %1$sthe course%2$s before taking this quiz', 'sensei-lms' ), '<a href="' . esc_url( get_permalink( $course_id ) ) . '" title="' . esc_attr( __( 'Sign Up', 'sensei-lms' ) ) . '">', '</a>' );

			} elseif ( ! is_user_logged_in() ) {

				$status    = 'login_required';
				$box_class = 'info';
				$message   = __( 'You must be logged in to take this quiz', 'sensei-lms' );

			}
			// Lesson/Quiz is marked as complete thus passing any quiz restrictions
			elseif ( $lesson_complete ) {

				$status    = 'passed';
				$box_class = 'tick';
				// Lesson status will be "complete" (has no Quiz)
				if ( ! $has_quiz_questions ) {
					$message = sprintf( __( 'Congratulations! You have passed this lesson.', 'sensei-lms' ) );
				}
				// Lesson status will be "graded" (no passmark required so might have failed all the questions)
				elseif ( empty( $quiz_grade ) ) {
					$message = sprintf( __( 'Congratulations! You have completed this lesson.', 'sensei-lms' ) );
				}
				// Lesson status will be "passed" (passmark reached)
				elseif ( ! empty( $quiz_grade ) && abs( $quiz_grade ) >= 0 ) {
					if ( $is_lesson ) {
						// translators: Placeholder is the quiz grade.
						$message = sprintf( __( 'Congratulations! You have passed this lesson\'s quiz achieving %s%%', 'sensei-lms' ), self::round( $quiz_grade, 2 ) );
					} else {
						// translators: Placeholder is the quiz grade.
						$message = sprintf( __( 'Congratulations! You have passed this quiz achieving %s%%', 'sensei-lms' ), self::round( $quiz_grade, 2 ) );
					}
				}

				// add next lesson button
				$nav_links = sensei_get_prev_next_lessons( $lesson_id );

				// Output HTML
				if ( isset( $nav_links['next'] ) ) {
					if ( ! $is_lesson || ! has_block( 'sensei-lms/lesson-actions', $lesson_id ) ) {
						$message .= ' <a class="next-lesson" href="' . esc_url( $nav_links['next']['url'] )
									. '" rel="next"><span class="meta-nav"></span>' . __( 'Next Lesson', 'sensei-lms' )
									. '</a>';
					}
				}
			} else {  // Lesson/Quiz not complete.

				$lesson_prerequisite = \Sensei_Lesson::find_first_prerequisite_lesson( $lesson_id, $user_id );

				if ( ! $is_lesson && $lesson_prerequisite > 0 ) {
					$lesson_status = self::user_lesson_status( $lesson_prerequisite, $user_id );

					$prerequisite_lesson_link = '<a href="'
						. esc_url( get_permalink( $lesson_prerequisite ) )
						. '" title="'
						// translators: Placeholder is the item title.
						. sprintf( esc_attr__( 'You must first complete: %1$s', 'sensei-lms' ), get_the_title( $lesson_prerequisite ) )
						. '">'
						. esc_html__( 'prerequisites', 'sensei-lms' )
						. '</a>';

					$message = ! empty( $lesson_status ) && 'ungraded' === $lesson_status->comment_approved
						// translators: Placeholder is the link to the prerequisite lesson.
						? sprintf( esc_html__( 'You will be able to access this quiz once the %1$s are completed and graded.', 'sensei-lms' ), $prerequisite_lesson_link )
						// translators: Placeholder is the link to the prerequisite lesson.
						: sprintf( esc_html__( 'Please complete the %1$s to access this quiz.', 'sensei-lms' ), $prerequisite_lesson_link );

					// Lesson/Quiz isn't "complete" instead it's ungraded (previously this "state" meant that it *was* complete).
				} elseif ( isset( $user_lesson_status->comment_approved ) && 'ungraded' == $user_lesson_status->comment_approved ) {
					$status    = 'complete';
					$box_class = 'info';
					if ( $is_lesson ) {
						// translators: Placeholders are an opening and closing <a> tag linking to the quiz permalink.
						$message = sprintf( __( 'You have completed this lesson\'s quiz and it will be graded soon. %1$sView the lesson quiz%2$s', 'sensei-lms' ), '<a href="' . esc_url( get_permalink( $quiz_id ) ) . '" title="' . esc_attr( get_the_title( $quiz_id ) ) . '">', '</a>' );
					} else {
						// translators: Placeholder is the quiz passmark.
						$message = sprintf( __( 'You have completed this quiz and it will be graded soon. You require %1$s%% to pass.', 'sensei-lms' ), self::round( $quiz_passmark, 2 ) );
					}

					// Lesson status must be "failed".
				} elseif ( isset( $user_lesson_status->comment_approved ) && 'failed' == $user_lesson_status->comment_approved ) {
					$status    = 'failed';
					$box_class = 'alert';
					if ( $is_lesson ) {
						// translators: Placeholders are the quiz passmark and the learner's grade, respectively.
						$message = sprintf( __( 'You require %1$d%% to pass this lesson\'s quiz. Your grade is %2$s%%', 'sensei-lms' ), self::round( $quiz_passmark, 2 ), self::round( $quiz_grade, 2 ) );
					} else {
						// translators: Placeholders are the quiz passmark and the learner's grade, respectively.
						$message = sprintf( __( 'You require %1$d%% to pass this quiz. Your grade is %2$s%%', 'sensei-lms' ), self::round( $quiz_passmark, 2 ), self::round( $quiz_grade, 2 ) );
					}

					// Lesson/Quiz requires a pass.
				} elseif ( $pass_required ) {
					$status    = 'not_started';
					$box_class = 'info';

					if ( ! Sensei_Lesson::is_prerequisite_complete( $lesson_id, get_current_user_id() ) ) {
						$message = '';
					} elseif ( $is_lesson ) {
						// translators: Placeholder is the quiz passmark.
						$message = sprintf( __( 'You require %1$d%% to pass this lesson\'s quiz.', 'sensei-lms' ), self::round( $quiz_passmark, 2 ) );
					} else {
						// translators: Placeholder is the quiz passmark.
						$message = sprintf( __( 'You require %1$d%% to pass this quiz.', 'sensei-lms' ), self::round( $quiz_passmark, 2 ) );
					}
				}
			}
		} else {

			$course_id    = Sensei()->lesson->get_course_id( $lesson_id );
			$course_link  = '<a href="' . esc_url( get_permalink( $course_id ) ) . '" title="' . esc_attr__( 'Sign Up', 'sensei-lms' ) . '">';
			$course_link .= esc_html__( 'course', 'sensei-lms' );
			$course_link .= '</a>';

			// translators: Placeholder is a link to the course permalink.
			$message_default = sprintf( __( 'Please sign up for the %1$s before taking this quiz.', 'sensei-lms' ), $course_link );

			/**
			 * Filter the course sign up notice message on the quiz page.
			 *
			 * @since 2.0.0
			 *
			 * @param string $message     Message to show user.
			 * @param int    $course_id   Post ID for the course.
			 * @param string $course_link Generated HTML link to the course.
			 */
			$message = apply_filters( 'sensei_quiz_course_signup_notice_message', $message_default, $course_id, $course_link );
		}

		// Legacy filter
		$message = apply_filters( 'sensei_user_quiz_status_' . $status, $message );

		if ( $is_lesson && ! in_array( $status, array( 'login_required', 'not_started_course' ) ) ) {
			$quiz_id = Sensei()->lesson->lesson_quizzes( $lesson_id );
			$extra   = '<p><a class="button" href="' . esc_url( get_permalink( $quiz_id ) ) . '" title="' . __( 'View the lesson quiz', 'sensei-lms' ) . '">' . __( 'View the lesson quiz', 'sensei-lms' ) . '</a></p>';
		}

		// Filter of all messages
		return apply_filters(
			'sensei_user_quiz_status',
			array(
				'status'    => $status,
				'box_class' => $box_class,
				'message'   => $message,
				'extra'     => $extra,
			),
			$lesson_id,
			$user_id,
			$is_lesson
		);
	}

	/**
	 * Start course for user
	 *
	 * @since  1.4.8
	 * @param  integer $user_id   User ID
	 * @param  integer $course_id Course ID
	 * @return bool|int False if they haven't started; Comment ID of course progress if they have.
	 */
	public static function user_start_course( $user_id = 0, $course_id = 0 ) {

		$activity_comment_id = false;

		if ( $user_id && $course_id ) {
			// Check if user is already on the Course.
			$activity_comment_id = self::get_course_progress_comment_id( $course_id, $user_id );
			if ( false === $activity_comment_id ) {
				$activity_comment_id = self::start_user_on_course( $user_id, $course_id );
			}
		}

		return $activity_comment_id;
	}

	/**
	 * Check if a user has started a course or not.
	 *
	 * @since  1.7.0
	 * @deprecated 3.0.0 No longer returns comment ID when they have access. To check if a user is enrolled use `Sensei_Course::is_user_enrolled()`. For course progress check, use `Sensei_Utils::has_started_course()`.
	 *
	 * @param int $course_id Course ID.
	 * @param int $user_id   User ID.
	 * @return bool
	 */
	public static function user_started_course( $course_id = 0, $user_id = 0 ) {
		_deprecated_function( __METHOD__, '3.0.0', '`Sensei_Course::is_user_enrolled()`. For course progress check, use `Sensei_Utils::has_started_course()`' );

		if ( empty( $course_id ) ) {
			return false;
		}

		// This was mainly used to check if a user was enrolled in a course. For now, use this replacement method.
		return Sensei_Course::is_user_enrolled( $course_id, $user_id );
	}

	/**
	 * Get the course progress comment ID, if it exists.
	 *
	 * @since 3.0.0
	 *
	 * @param int $course_id Course ID.
	 * @param int $user_id   User ID.
	 * @return int|false false or comment_ID
	 */
	public static function get_course_progress_comment_id( $course_id, $user_id = null ) {
		if ( empty( $course_id ) ) {
			return false;
		}

		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		if ( ! $user_id ) {
			return false;
		}

		$course_progress = Sensei()->course_progress_repository->get( $course_id, $user_id );
		if ( ! $course_progress ) {
			return false;
		}

		return $course_progress->get_id();
	}

	/**
	 * Check if a user has started a course or not.
	 *
	 * @since 3.0.0
	 *
	 * @param int $course_id Course ID.
	 * @param int $user_id   User ID.
	 * @return int|bool false or comment_ID
	 */
	public static function has_started_course( $course_id = 0, $user_id = 0 ) {
		$user_started_course = self::get_course_progress_comment_id( $course_id, $user_id );

		/**
		 * Filter the user started course value
		 *
		 * @since 1.9.3
		 *
		 * @param bool|int $user_started_course
		 * @param integer $course_id
		 */
		return apply_filters( 'sensei_user_started_course', $user_started_course, $course_id, $user_id );

	}

	/**
	 * Checks if a user has completed a course by checking every lesson status,
	 * and then updates the course metadata with that information.
	 *
	 * @since  1.7.0
	 * @param  integer $course_id Course ID
	 * @param  integer $user_id   User ID
	 * @return mixed boolean or comment_ID
	 */
	public static function user_complete_course( $course_id = 0, $user_id = 0, $trigger_completion_action = true ) {
		if ( ! $course_id ) {
			return false;
		}

		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		$course_progress = Sensei()->course_progress_repository->get( $course_id, $user_id );
		if ( ! $course_progress ) {
			$course_progress = Sensei()->course_progress_repository->create( $course_id, $user_id );
		}
		if ( ! $course_progress->get_started_at() ) {
			$course_progress->start();
		}

		$course_completion  = Sensei()->settings->settings['course_completion'];
		$lessons_completed  = 0;
		$lesson_status_args = array(
			'user_id' => $user_id,
			'status'  => 'any',
			'type'    => 'sensei_lesson_status', /* FIELD SIZE 20 */
		);

		// Grab all of this Courses' lessons, looping through each...
		$lesson_ids    = Sensei()->course->course_lessons( $course_id, 'publish', 'ids' );
		$total_lessons = count( $lesson_ids );
			// ...if course completion not set to 'passed', and all lessons are complete or graded,
			// ......then all lessons are 'passed'
			// ...else if course completion is set to 'passed', check if each lesson has questions...
			// ......if no questions yet the status is 'complete'
			// .........then the lesson is 'passed'
			// ......else if questions check the lesson status has a grade and that the grade is greater than the lesson passmark
			// .........then the lesson is 'passed'
			// ...if all lessons 'passed' then update the course status to complete
		// The below checks if a lesson is fully completed, though maybe should be Utils::user_completed_lesson()
		$lesson_status_args['post__in'] = $lesson_ids;
		$all_lesson_statuses            = self::sensei_check_for_activity( $lesson_status_args, true );
		// Need to always return an array, even with only 1 item.
		if ( ! is_array( $all_lesson_statuses ) ) {
			$all_lesson_statuses = array( $all_lesson_statuses );
		}

		foreach ( $all_lesson_statuses as $lesson_status ) {
			// If lessons are complete without needing quizzes to be passed
			if ( 'passed' !== $course_completion ) {
				// A user cannot 'complete' a course if a lesson...
				// ...is still in progress
				// ...hasn't yet been graded.
				$lesson_not_complete_stati = array( 'in-progress', 'ungraded' );
				if ( ! in_array( $lesson_status->comment_approved, $lesson_not_complete_stati, true ) ) {
					$lessons_completed++;
				}
			} else {
				$lesson_complete_stati = array( 'complete', 'graded', 'passed' );
				if ( in_array( $lesson_status->comment_approved, $lesson_complete_stati, true ) ) {
					$lessons_completed++;
				}
			}
		} // Each lesson

		if ( $lessons_completed === $total_lessons ) {
			$course_progress->complete();
		}

		Sensei()->course_progress_repository->save( $course_progress );

		$course_progress_metadata = [
			// How many lessons have been completed.
			'complete' => $lessons_completed,
			// Overall percentage of the course lessons complete (or graded) compared to 'in-progress' regardless of the above.
			'percent'  => self::quotient_as_absolute_rounded_percentage( $lessons_completed, $total_lessons ),
		];
		foreach ( $course_progress_metadata as $key => $value ) {
			update_comment_meta( $course_progress->get_id(), $key, $value );
		}

		// Allow further actions.
		if ( 'complete' === $course_progress->get_status() && true === $trigger_completion_action ) {
			do_action( 'sensei_user_course_end', $user_id, $course_id );
		}

		return $course_progress->get_id();
	}

	/**
	 * Get completion percentage
	 *
	 * @param $numerator
	 * @param $denominator
	 * @param int         $decimal_places_to_round
	 * @return int|number
	 */
	public static function quotient_as_absolute_rounded_percentage( $numerator, $denominator, $decimal_places_to_round = 0 ) {
		return self::quotient_as_absolute_rounded_number( $numerator * 100.0, $denominator, $decimal_places_to_round );
	}

	public static function quotient_as_absolute_rounded_number( $numerator, $denominator, $decimal_places_to_round = 0 ) {
		if ( 0 === $denominator ) {
			return 0;
		}

		return self::as_absolute_rounded_number( doubleval( $numerator ) / ( $denominator ), $decimal_places_to_round );
	}

	public static function as_absolute_rounded_number( $number, $decimal_places_to_round = 0 ) {
		return abs( round( ( doubleval( $number ) ), $decimal_places_to_round ) );
	}

	/**
	 * Check if a user has completed a course or not
	 *
	 * @param int | WP_Post | WP_Comment $course course_id or sensei_course_status entry
	 * @param int                        $user_id User ID.
	 * @return boolean
	 */
	public static function user_completed_course( $course, $user_id = 0 ) {

		if ( ! $course ) {
			return false;
		}

		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		if ( empty( $user_id ) ) {
			return false;
		}

		$user_course_status = null;
		if ( is_object( $course ) && is_a( $course, 'WP_Comment' ) ) {
			$user_course_status = $course->comment_approved;
		} elseif ( ! is_numeric( $course ) && ! is_a( $course, 'WP_Post' ) ) {
			$user_course_status = $course;
		} else {

			if ( is_a( $course, 'WP_Post' ) ) {
				$course = $course->ID;
			} else {
				$course = (int) $course;
			}

			$course_progress = Sensei()->course_progress_repository->get( $course, $user_id );
			if ( $course_progress ) {
				$user_course_status = $course_progress->get_status();
			}
		}

		if ( $user_course_status && Course_Progress::STATUS_COMPLETE === $user_course_status ) {
			return true;
		}

		return false;
	}

	/**
	 * Check if a user has started a lesson or not
	 *
	 * @since  1.7.0
	 * @param int $lesson_id
	 * @param int $user_id
	 * @return mixed false or comment_ID
	 */
	public static function user_started_lesson( $lesson_id = 0, $user_id = 0 ) {
		if ( ! $lesson_id ) {
			return false;
		}

		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		$lesson_progress = Sensei()->lesson_progress_repository->get( $lesson_id, $user_id );
		if ( ! $lesson_progress ) {
			return false;
		}

		return $lesson_progress->get_id();
	}

	/**
	 * Get the number of lessons of a course that a user started.
	 *
	 * @since  3.13.3
	 *
	 * @param int $course_id The course id.
	 * @param int $user_id   The user id.
	 *
	 * @return int Lesson count.
	 */
	public static function user_started_lesson_count( int $course_id, int $user_id ) : int {
		return Sensei()->lesson_progress_repository->count( $course_id, $user_id );
	}

	/**
	 * Check if a user has completed a lesson or not
	 *
	 * @uses  Sensei()
	 * @param mixed $lesson lesson_id or sensei_lesson_status entry
	 * @param int   $user_id
	 * @return boolean
	 */
	public static function user_completed_lesson( $lesson = 0, $user_id = 0 ): bool {
		if ( $lesson ) {
			$lesson_id = 0;
			if ( is_object( $lesson ) ) {
				$user_lesson_status = $lesson->comment_approved;
				$lesson_id          = $lesson->comment_post_ID;
			} elseif ( ! is_numeric( $lesson ) ) {
				$user_lesson_status = $lesson;
			} else {
				if ( ! $user_id ) {
					$user_id = get_current_user_id();
				}

				// the user is not logged in
				if ( 0 >= (int) $user_id ) {
					return false;
				}
				$_user_lesson_status = self::user_lesson_status( $lesson, $user_id );

				if ( isset( $_user_lesson_status->comment_approved ) ) {

					$user_lesson_status = $_user_lesson_status->comment_approved;

				} else {

					return false; // No status means not complete

				}

				$lesson_id = $lesson;
			}

			/**
			 * Filter the user lesson status
			 *
			 * @since 1.9.7
			 *
			 * @param string    $user_lesson_status User lesson status
			 * @param int       $lesson_id          ID of lesson
			 * @param int       $user_id            ID of user
			 */
			$user_lesson_status = apply_filters( 'sensei_user_completed_lesson', $user_lesson_status, $lesson_id, $user_id );

			if ( 'in-progress' != $user_lesson_status ) {
				// Check for Passed or Completed Setting
				// Should we be checking for the Course completion setting? Surely that should only affect the Course completion, not bypass each Lesson setting
				switch ( $user_lesson_status ) {
					case 'complete':
					case 'graded':
					case 'passed':
						return true;

					case 'failed':
						// This may be 'completed' depending on...
						if ( $lesson_id ) {
							// Get Quiz ID, this won't be needed once all Quiz meta fields are stored on the Lesson
							$lesson_quiz_id = Sensei()->lesson->lesson_quizzes( $lesson_id );
							if ( $lesson_quiz_id ) {
								// ...the quiz pass setting
								$pass_required = get_post_meta( $lesson_quiz_id, '_pass_required', true );
								if ( empty( $pass_required ) ) {
									// We just require the user to have done the quiz, not to have passed
									return true;
								}
							}
						}
						return false;
				}
			}
		}

		return false;
	}

	/**
	 * Returns the requested course status
	 *
	 * @since 1.7.0
	 * @param int $course_id
	 * @param int $user_id
	 * @return object
	 */
	public static function user_course_status( $course_id = 0, $user_id = 0 ) {

		if ( $course_id ) {
			if ( ! $user_id ) {
				$user_id = get_current_user_id();
			}

			$user_course_status = self::sensei_check_for_activity(
				array(
					'post_id' => $course_id,
					'user_id' => $user_id,
					'type'    => 'sensei_course_status',
				),
				true
			);
			return $user_course_status;
		}

		return false;
	}

	/**
	 * Returns the requested lesson status
	 *
	 * @since 1.7.0
	 * @param int $lesson_id
	 * @param int $user_id
	 * @return WP_Comment|false
	 */
	public static function user_lesson_status( $lesson_id = 0, $user_id = 0 ) {

		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		if ( $lesson_id > 0 && $user_id > 0 ) {

			$user_lesson_status = self::sensei_check_for_activity(
				array(
					'post_id' => $lesson_id,
					'user_id' => $user_id,
					'type'    => 'sensei_lesson_status',
				),
				true
			);
			return $user_lesson_status;
		}

		return false;
	}

	public static function is_preview_lesson( $lesson_id ) {
		$is_preview = false;

		if ( 'lesson' == get_post_type( $lesson_id ) ) {
			$lesson_preview = get_post_meta( $lesson_id, '_lesson_preview', true );
			if ( isset( $lesson_preview ) && '' != $lesson_preview ) {
				$is_preview = true;
			}
		}

		return $is_preview;
	}

	public static function user_passed_quiz( $quiz_id = 0, $user_id = 0 ) {

		if ( ! $quiz_id ) {
			return false;
		}

		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}
		$lesson_id = get_post_meta( $quiz_id, '_quiz_lesson', true );

		// Quiz Grade
		$lesson_status = self::user_lesson_status( $lesson_id, $user_id );
		$quiz_grade    = get_comment_meta( $lesson_status->comment_ID, 'grade', true );

		// Check if Grade is greater than or equal to pass percentage
		$quiz_passmark = self::as_absolute_rounded_number( get_post_meta( $quiz_id, '_quiz_passmark', true ), 2 );
		if ( $quiz_passmark <= intval( $quiz_grade ) ) {
			return true;
		}

		return false;

	}

	/**
	 * Sets the status for the lesson
	 *
	 * @since  1.7.0
	 *
	 * @param int|string $user_id
	 * @param int|string $lesson_id
	 * @param string     $status
	 * @param array      $metadata
	 *
	 * @return mixed false or comment_ID
	 */
	public static function update_lesson_status( $user_id, $lesson_id, $status = 'in-progress', $metadata = array() ) {
		$comment_id = false;
		if ( ! empty( $status ) ) {
			$args = array(
				'user_id'   => $user_id,
				'username'  => get_userdata( $user_id )->user_login ?? null,
				'post_id'   => $lesson_id,
				'status'    => $status,
				'type'      => 'sensei_lesson_status', /* FIELD SIZE 20 */
				'action'    => 'update', // Update the existing status...
				'keep_time' => true, // ...but don't change the existing timestamp
			);
			if ( in_array( $status, array( 'in-progress', 'ungraded', 'graded', 'passed', 'failed' ), true ) ) {
				unset( $args['keep_time'] ); // Keep updating what's happened
			}

			$comment_id = self::sensei_log_activity( $args );
			if ( $comment_id && ! empty( $metadata ) ) {
				foreach ( $metadata as $key => $value ) {
					update_comment_meta( $comment_id, $key, $value );
				}
			}

			do_action( 'sensei_lesson_status_updated', $status, $user_id, $lesson_id, $comment_id );
		}
		return $comment_id;
	}

	/**
	 * Sets the statuses for the Course
	 *
	 * @access public
	 * @since  1.7.0
	 * @param int    $user_id
	 * @param int    $course_id
	 * @param string $status
	 * @param array  $metadata
	 * @return mixed false or comment_ID
	 */
	public static function update_course_status( $user_id, $course_id, $status = 'in-progress', $metadata = array() ) {
		$comment_id = false;
		if ( ! empty( $status ) ) {
			$args = array(
				'user_id'  => $user_id,
				'username' => get_userdata( $user_id )->user_login ?? null,
				'post_id'  => $course_id,
				'status'   => $status,
				'type'     => 'sensei_course_status', /* FIELD SIZE 20 */
				'action'   => 'update', // Update the existing status...
			);

			$comment_id = self::sensei_log_activity( $args );
			if ( $comment_id && ! empty( $metadata ) ) {
				foreach ( $metadata as $key => $value ) {
					update_comment_meta( $comment_id, $key, $value );
				}
			}
			do_action( 'sensei_course_status_updated', $status, $user_id, $course_id, $comment_id );
		}
		return $comment_id;
	}

	/**
	 * Remove the orderby for comments
	 *
	 * @access public
	 * @since  1.7.0
	 * @param  array $pieces (default: array())
	 * @return array
	 */
	public static function single_comment_filter( $pieces ) {
		unset( $pieces['orderby'] );
		unset( $pieces['order'] );

		return $pieces;
	}

	/**
	 * Allow retrieving comments with any comment_approved status, little bypass to WP_Comment. Required only for WP < 4.1
	 *
	 * @access public
	 *
	 * @deprecated 3.13.4
	 *
	 * @since  1.7.0
	 * @param  array $pieces (default: array())
	 * @return array
	 */
	public static function comment_any_status_filter( $pieces ) {
		_deprecated_function( __FUNCTION__, '3.13.4' );

		$pieces['where'] = str_replace( array( "( comment_approved = '0' OR comment_approved = '1' ) AND", "comment_approved = 'any' AND" ), '', $pieces['where'] );

		return $pieces;
	}

	/**
	 * Allow retrieving comments within multiple statuses, little bypass to WP_Comment. Required only for WP < 4.1
	 *
	 * @access public
	 *
	 * @deprecated 3.13.4
	 *
	 * @since  1.7.0
	 * @param  array $pieces (default: array())
	 * @return array
	 */
	public static function comment_multiple_status_filter( $pieces ) {
		_deprecated_function( __FUNCTION__, '3.13.4' );

		preg_match( "/^comment_approved = '([a-z\-\,]+)'/", $pieces['where'], $placeholder );
		if ( ! empty( $placeholder[1] ) ) {
			$statuses        = explode( ',', $placeholder[1] );
			$pieces['where'] = str_replace( "comment_approved = '" . $placeholder[1] . "'", "comment_approved IN ('" . implode( "', '", $statuses ) . "')", $pieces['where'] );
		}

		return $pieces;
	}

	/**
	 * Adjust the comment query to be faster on the database, used by Analysis admin
	 *
	 * @since  1.7.0
	 * @param array $pieces
	 * @return array $pieces
	 */
	public static function comment_total_sum_meta_value_filter( $pieces ) {
		global $wpdb;

		$pieces['fields'] = " COUNT(*) AS total, SUM($wpdb->commentmeta.meta_value) AS meta_sum ";
		unset( $pieces['groupby'] );

		return $pieces;
	}

	/**
	 * Shifts counting of posts to the database where it should be. Likely not to be used due to knock on issues.
	 *
	 * @access public
	 * @since  1.7.0
	 * @param  array $pieces (default: array())
	 * @return array
	 */
	public static function get_posts_count_only_filter( $pieces ) {
		$pieces['fields'] = ' COUNT(*) AS total ';
		unset( $pieces['groupby'] );

		return $pieces;
	}

	/**
	 *
	 * Alias to Woothemes_Sensei_Utils::update_user_data
	 *
	 * @since 1.7.4
	 *
	 * @param string $data_key maximum 39 characters allowed
	 * @param int    $post_id
	 * @param mixed  $value
	 * @param int    $user_id
	 *
	 * @return bool $success
	 */
	public static function add_user_data( $data_key, $post_id, $value = '', $user_id = 0 ) {

		return self::update_user_data( $data_key, $post_id, $value, $user_id );

	}

	/**
	 * add user specific data to the passed in sensei post type id
	 *
	 * This function saves comment meta on the users current status. If no status is available
	 * status will be created. It only operates on the available sensei Post types: course, lesson, quiz.
	 *
	 * @since 1.7.4
	 *
	 * @param string $data_key maximum 39 characters allowed
	 * @param int    $post_id
	 * @param mixed  $value
	 * @param int    $user_id
	 *
	 * @return bool $success
	 */
	public static function update_user_data( $data_key, $post_id, $value = '', $user_id = 0 ) {

		if ( ! ( $user_id > 0 ) ) {
			$user_id = get_current_user_id();
		}

		$supported_post_types = array( 'course', 'lesson' );
		$post_type            = get_post_type( $post_id );
		if ( empty( $post_id ) || empty( $data_key )
			|| ! is_int( $post_id ) || ! ( intval( $post_id ) > 0 ) || ! ( intval( $user_id ) > 0 )
			|| ! get_userdata( $user_id )
			|| ! in_array( $post_type, $supported_post_types, true ) ) {

			return false;
		}

		// check if there and existing Sensei status on this post type if not create it
		// and get the  activity ID
		$status_function    = 'user_' . $post_type . '_status';
		$sensei_user_status = self::$status_function( $post_id, $user_id );
		if ( ! isset( $sensei_user_status->comment_ID ) ) {

			$start_function          = 'user_start_' . $post_type;
			$sensei_user_activity_id = self::$start_function( $user_id, $post_id );

		} else {

			$sensei_user_activity_id = $sensei_user_status->comment_ID;

		}

		// store the data
		$success = update_comment_meta( $sensei_user_activity_id, $data_key, $value );

		return $success;

	}

	/**
	 * Get the user data stored on the passed in post type
	 *
	 * This function gets the comment meta on the lesson or course status
	 *
	 * @since 1.7.4
	 *
	 * @param $data_key
	 * @param $post_id
	 * @param int      $user_id
	 *
	 * @return mixed $user_data_value
	 */
	public static function get_user_data( $data_key, $post_id, $user_id = 0 ) {

		$user_data_value = true;

		if ( ! ( $user_id > 0 ) ) {
			$user_id = get_current_user_id();
		}

		$supported_post_types = array( 'course', 'lesson' );
		$post_type            = get_post_type( $post_id );
		if ( empty( $post_id ) || empty( $data_key )
			|| ! ( intval( $post_id ) > 0 ) || ! ( intval( $user_id ) > 0 )
			|| ! get_userdata( $user_id )
			|| ! in_array( $post_type, $supported_post_types, true ) ) {

			return false;
		}

		// check if there and existing Sensei status on this post type if not create it
		// and get the  activity ID
		$status_function    = 'user_' . $post_type . '_status';
		$sensei_user_status = self::$status_function( $post_id, $user_id );
		if ( ! isset( $sensei_user_status->comment_ID ) ) {
			return false;
		}

		$sensei_user_activity_id = $sensei_user_status->comment_ID;
		$user_data_value         = get_comment_meta( $sensei_user_activity_id, $data_key, true );

		return $user_data_value;

	}

	/**
	 * Delete the Sensei user data for the given key, Sensei post type and user combination.
	 *
	 * @param int $data_key
	 * @param int $post_id
	 * @param int $user_id
	 *
	 * @return bool $deleted
	 */
	public static function delete_user_data( $data_key, $post_id, $user_id ) {
		$deleted = true;

		if ( ! ( $user_id > 0 ) ) {
			$user_id = get_current_user_id();
		}

		$supported_post_types = array( 'course', 'lesson' );
		$post_type            = get_post_type( $post_id );
		if ( empty( $post_id ) || empty( $data_key )
			|| ! is_int( $post_id ) || ! ( intval( $post_id ) > 0 ) || ! ( intval( $user_id ) > 0 )
			|| ! get_userdata( $user_id )
			|| ! in_array( $post_type, $supported_post_types, true ) ) {

			return false;
		}

		// check if there and existing Sensei status on this post type if not create it
		// and get the  activity ID
		$status_function    = 'user_' . $post_type . '_status';
		$sensei_user_status = self::$status_function( $post_id, $user_id );
		if ( ! isset( $sensei_user_status->comment_ID ) ) {
			return false;
		}

		$sensei_user_activity_id = $sensei_user_status->comment_ID;
		$deleted                 = delete_comment_meta( $sensei_user_activity_id, $data_key );

		return $deleted;

	}


	/**
	 * The function creates a drop down. Never write up a Sensei select statement again.
	 *
	 * @since 1.8.0
	 *
	 * @param string   $selected_value
	 * @param $options{
	 *    @type string $value the value saved in the database
	 *    @type string $option what the user will see in the list of items
	 * }
	 * @param array    $attributes{
	 *   @type string $attribute  type such name or id etc.
	 *  @type string $value
	 * }
	 * @param bool     $enable_none_option
	 *
	 * @return string $drop_down_element
	 */
	public static function generate_drop_down( $selected_value, $options = array(), $attributes = array(), $enable_none_option = true ) {

		$drop_down_element = '';

		// setup the basic attributes
		if ( ! isset( $attributes['name'] ) || empty( $attributes['name'] ) ) {

			$attributes['name'] = 'sensei-options';

		}

		if ( ! isset( $attributes['id'] ) || empty( $attributes['id'] ) ) {

			$attributes['id'] = 'sensei-options';

		}

		if ( ! isset( $attributes['class'] ) || empty( $attributes['class'] ) ) {

			$attributes['class'] = 'chosen_select widefat';

		}

		// create element attributes
		$combined_attributes = '';
		foreach ( $attributes as $attribute => $value ) {

			$combined_attributes .= $attribute . '="' . esc_attr( $value ) . '"' . ' ';

		}

		// create the select element
		$drop_down_element .= '<select ' . $combined_attributes . ' >' . "\n";

		// show the none option if the client requested
		if ( $enable_none_option ) {
			$drop_down_element .= '<option value="">' . esc_html__( 'None', 'sensei-lms' ) . '</option>';
		}

		if ( count( $options ) > 0 ) {

			foreach ( $options as $value => $option ) {

				$element  = '';
				$element .= '<option value="' . esc_attr( $value ) . '"';
				$element .= selected( $value, $selected_value, false ) . '>';
				$element .= esc_html( $option ) . '</option>' . "\n";

				// add the element to the select html
				$drop_down_element .= $element;
			}
		}

		$drop_down_element .= '</select>' . "\n";

		return wp_kses(
			$drop_down_element,
			array(
				'option' => array(
					'selected' => array(),
					'value'    => array(),
				),
				'select' => array(
					'class' => array(),
					'id'    => array(),
					'name'  => array(),
					'style' => array(),
				),
			)
		);
	}

	/**
	 * Wrapper for the default php round() function.
	 * This allows us to give more control to a user on how they can round Sensei
	 * decimals passed through this function.
	 *
	 * @since 1.8.5
	 *
	 * @param double $val
	 * @param int    $precision
	 * @param $mode
	 * @param string $context
	 *
	 * @return double $val
	 */
	public static function round( $val, $precision = 0, $mode = PHP_ROUND_HALF_UP, $context = '' ) {

		/**
		 * Change the precision for the Sensei_Utils::round function.
		 * the precision given will be passed into the php round function
		 *
		 * @since 1.8.5
		 */
		$precision = apply_filters( 'sensei_round_precision', $precision, $val, $context, $mode );

		/**
		 * Change the mode for the Sensei_Utils::round function.
		 * the mode given will be passed into the php round function
		 *
		 * This applies only to PHP version 5.3.0 and greater
		 *
		 * @since 1.8.5
		 */
		$mode = apply_filters( 'sensei_round_mode', $mode, $val, $context, $precision );

		return round( $val, $precision, $mode );
	}

	/**
	 * Returns the current url with all the query vars.
	 *
	 * @since 1.9.0
	 * @deprecated 4.0.2
	 * @return string $url
	 */
	public static function get_current_url() {
		_deprecated_function( __METHOD__, '4.0.2' );

		global $wp;
		$current_url = trailingslashit( home_url( $wp->request ) );
		if ( isset( $_GET ) ) {

			foreach ( $_GET as $param => $val ) {

				$current_url = add_query_arg( $param, $val, $current_url );

			}
		}

		return $current_url;
	}

	/**
	 * Get the course id of the current post.
	 *
	 * @return int|null The course id or null if it was not found.
	 */
	public static function get_current_course() {
		global $post;

		$post_type = get_post_type( $post );
		$course_id = null;

		switch ( $post_type ) {
			case 'course':
				$course_id = $post->ID;
				break;

			case 'lesson':
				$course_id = Sensei()->lesson->get_course_id( $post->ID );
				break;

			case 'quiz':
				$lesson_id = (int) get_post_meta( $post->ID, '_quiz_lesson', true );
				$course_id = $lesson_id ? Sensei()->lesson->get_course_id( $lesson_id ) : null;
				break;
		}

		return $course_id ? absint( $course_id ) : null;
	}


	/**
	 * Get the lesson id of the current post, if it's a lesson or quiz.
	 *
	 * @return int|null The lesson id or null if it was not found.
	 */
	public static function get_current_lesson() {
		global $post;

		if ( empty( $post ) ) {
			return null;
		}

		switch ( get_post_type( $post ) ) {
			case 'lesson':
				return $post->ID;
			case 'quiz':
				return Sensei()->quiz->get_lesson_id( $post->ID );
		}

		return null;
	}

	/**
	 * Restore the global WP_Query
	 *
	 * @since 1.9.0
	 */
	public static function restore_wp_query() {

		wp_reset_query();

	}

	/**
	 * Merge two arrays in a zip like fashion.
	 * If one array is longer than the other the elements will be apended
	 * to the end of the resulting array.
	 *
	 * @since 1.9.0
	 *
	 * @param array $array_a
	 * @param array $array_b
	 * @return array $merged_array
	 */
	public static function array_zip_merge( $array_a, $array_b ) {

		if ( ! is_array( $array_a ) || ! is_array( $array_b ) ) {
			trigger_error( 'array_zip_merge requires both arrays to be indexed arrays ' );
		}

		$merged_array   = array();
		$total_elements = count( $array_a ) + count( $array_b );

		// Zip arrays
		for ( $i = 0; $i < $total_elements; $i++ ) {

			// if has an element at current index push a on top
			if ( isset( $array_a[ $i ] ) ) {
				$merged_array[] = $array_a[ $i ];
			}

			// next if $array_b has an element at current index push a on top of the element
			// from a if there was one, if not the element before that.
			if ( isset( $array_b[ $i ] ) ) {
				$merged_array[] = $array_b[ $i ];
			}
		}

		return $merged_array;
	}

	/**
	 * What type of request is this?
	 *
	 * @param  string $type admin, ajax, cron or frontend.
	 * @return bool
	 */
	public static function is_request( $type ) {
		switch ( $type ) {
			case 'admin':
				return is_admin();
			case 'ajax':
				return defined( 'DOING_AJAX' );
			case 'cron':
				return defined( 'DOING_CRON' );
			case 'frontend':
				return ( ! is_admin() || defined( 'DOING_AJAX' ) ) && ! defined( 'DOING_CRON' );
		}
	}

	/**
	 * Check if this is a REST API request.
	 *
	 * @since 4.10.0
	 *
	 * @return bool
	 */
	public static function is_rest_request(): bool {
		return defined( 'REST_REQUEST' ) && REST_REQUEST;
	}

	/**
	 * Add user to course.
	 *
	 * @param int $user_id The user ID.
	 * @param int $course_id The course ID.
	 * @return int Returns the ID of the user course progress or false on failure. The progress ID might have different meanings depending on the underlying implementation.
	 */
	public static function start_user_on_course( $user_id, $course_id ) {
		$course_progress = Sensei()->course_progress_repository->create( $course_id, $user_id );

		// Allow further actions.
		$course_metadata = [
			'percent'  => 0,
			'complete' => 0,
		];
		foreach ( $course_metadata as $key => $value ) {
			update_comment_meta( $course_progress->get_id(), $key, $value );
		}

		do_action( 'sensei_user_course_start', $user_id, $course_id );

		return $course_progress->get_id();
	}

	public static function is_plugin_present_and_activated( $plugin_class_to_look_for, $plugin_registered_path ) {
		$active_plugins = (array) get_option( 'active_plugins', array() );

		if ( is_multisite() ) {

			$active_sitewide_plugins = get_site_option( 'active_sitewide_plugins' );
			if ( $active_sitewide_plugins ) {
				$active_plugins = array_merge( $active_plugins, $active_sitewide_plugins );
			}
		}

		$plugin_present_and_activated = in_array( $plugin_registered_path, $active_plugins ) || array_key_exists( $plugin_registered_path, $active_plugins );
		return class_exists( $plugin_class_to_look_for ) || $plugin_present_and_activated;
	}

	/**
	 * Check if WooCommerce is installed.
	 *
	 * @since 3.11.0
	 *
	 * @return bool
	 */
	public static function is_woocommerce_installed() {
		return file_exists( WP_PLUGIN_DIR . '/woocommerce/woocommerce.php' );
	}

	/**
	 * Checks if the given version pf WooCommerce plugin is installed and activated.
	 *
	 * @param string $minimum_version
	 *
	 * @return bool
	 * @since Sensei 3.2.0
	 */
	public static function is_woocommerce_active( $minimum_version = null ) {
		$is_active = self::is_plugin_present_and_activated( 'Woocommerce', 'woocommerce/woocommerce.php' );

		if ( ! $is_active ) {
			return false;
		}

		if ( null !== $minimum_version ) {
			return version_compare( WC()->version, $minimum_version, '>=' );
		}

		return true;
	}

	/**
	 * Get WooCommerce plugin information.
	 *
	 * @return array WooCommerce information.
	 */
	public static function get_woocommerce_plugin_information() {
		$wc_information = get_transient( self::WC_INFORMATION_TRANSIENT );

		if ( false === $wc_information ) {
			if ( ! function_exists( 'plugins_api' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
			}

			$wc_slug            = 'woocommerce';
			$plugin_information = plugins_api(
				'plugin_information',
				[
					'slug'   => $wc_slug,
					'fields' => [
						'short_description' => true,
						'description'       => false,
						'sections'          => false,
						'tested'            => false,
						'requires'          => false,
						'requires_php'      => false,
						'rating'            => false,
						'ratings'           => false,
						'downloaded'        => false,
						'downloadlink'      => false,
						'last_updated'      => false,
						'added'             => false,
						'tags'              => false,
						'compatibility'     => false,
						'homepage'          => false,
						'versions'          => false,
						'donate_link'       => false,
						'reviews'           => false,
						'banners'           => false,
						'icons'             => false,
						'active_installs'   => false,
						'group'             => false,
						'contributors'      => false,
					],
				]
			);

			$wc_information = (object) [
				'product_slug' => $wc_slug,
				'title'        => $plugin_information->name,
				'excerpt'      => $plugin_information->short_description,
				'plugin_file'  => 'woocommerce/woocommerce.php',
				'link'         => 'https://wordpress.org/plugins/' . $wc_slug,
				'unselectable' => true,
				'version'      => $plugin_information->version,
			];

			set_transient( self::WC_INFORMATION_TRANSIENT, $wc_information, DAY_IN_SECONDS );
		}

		// Add installed properties to the object.
		$wc_information = Sensei_Extensions::instance()->add_installed_extensions_properties( [ $wc_information ] );
		$wc_information = $wc_information[0];

		return $wc_information;
	}

	/**
	 * Get data used for WooCommerce.com purchase redirect.
	 *
	 * @deprecated 4.8.0
	 *
	 * @return array The data.
	 */
	public static function get_woocommerce_connect_data() {
		_deprecated_function( __METHOD__, '4.8.0' );

		$wc_params                = [];
		$is_woocommerce_installed = self::is_woocommerce_active( '3.7.0' ) && class_exists( 'WC_Admin_Addons' );

		if ( $is_woocommerce_installed ) {
			$wc_params = WC_Admin_Addons::get_in_app_purchase_url_params();

		} else {
			$wc_info = self::get_woocommerce_plugin_information();

			$wc_params = [
				'wccom-site'          => site_url(),
				'wccom-woo-version'   => $wc_info->version,
				'wccom-connect-nonce' => wp_create_nonce( 'connect' ),
			];
		}

		$wc_params['wccom-back'] = rawurlencode( 'admin.php' );

		return $wc_params;
	}

	/**
	 * Hard - Resets a Learner's Course Progress
	 *
	 * @param $course_id int
	 * @param $user_id int
	 * @return bool
	 */
	public static function reset_course_for_user( $course_id, $user_id ) {
		self::sensei_remove_user_from_course( $course_id, $user_id );

		if ( ! Sensei_Course::is_user_enrolled( $course_id, $user_id ) ) {
			return true;
		}

		return false !== self::user_start_course( $user_id, $course_id );
	}

	/**
	 * @param $setting_name string
	 * @param null|string         $filter_to_apply
	 * @return bool
	 */
	public static function get_setting_as_flag( $setting_name, $filter_to_apply = null ) {
		$setting_on = false;

		if ( isset( Sensei()->settings->settings[ $setting_name ] ) ) {
			$setting_on = (bool) Sensei()->settings->settings[ $setting_name ];
		}

		return ( null !== $filter_to_apply ) ? (bool) apply_filters( $filter_to_apply, $setting_on ) : $setting_on;
	}


	/**
	 * Determine whether to show the lessons on the single course page.
	 *
	 * @since 2.2.0
	 *
	 * @param int|false $course_id Course ID.
	 * @return bool Whether to show the lessons. Default true.
	 */
	public static function show_course_lessons( $course_id ) {
		/**
		 * Set the visibility of lessons on the single course page.
		 *
		 * @since 2.2.0
		 *
		 * @param bool $show_lessons   Whether the lessons should be shown. Default true.
		 * @param int|false $course_id Course ID.
		 */
		return apply_filters( 'sensei_course_show_lessons', true, $course_id );
	}

	/**
	 * Determine if the current page is a Sensei learner profile page.
	 *
	 * @since 3.2.0
	 *
	 * @return bool True if the current page is a Sensei learner profile page.
	 */
	public static function is_learner_profile_page() {
		global $wp_query;
		return isset( $wp_query->query_vars['learner_profile'] );
	}

	/**
	 * Determine if the current page is a Sensei course results page.
	 *
	 * @since 3.2.0
	 *
	 * @return bool True if the current page is a Sensei course results page.
	 */
	public static function is_course_results_page() {
		global $wp_query;
		return isset( $wp_query->query_vars['course_results'] );
	}

	/**
	 * Determine if the current page is a Sensei teacher archive page.
	 *
	 * @access  public
	 * @since 3.2.0
	 * @return bool True if the current page is a Sensei teacher archive page.
	 */
	public static function is_teacher_archive_page() {
		if ( is_author()
			&& Sensei()->teacher->is_a_teacher( get_query_var( 'author' ) )
			&& ! user_can( get_query_var( 'author' ), 'manage_options' ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Output the current query params as hidden inputs.
	 *
	 * @since 4.2.0
	 *
	 * @param array $excluded The query params that should be excluded.
	 */
	public static function output_query_params_as_inputs( array $excluded = [] ) {
		// phpcs:ignore WordPress.Security.NonceVerification -- The nonce should be checked before calling this method.
		foreach ( $_GET as $name => $value ) {
			if ( in_array( $name, $excluded, true ) ) {
				continue;
			}

			?>
			<input type="hidden" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( wp_unslash( $value ) ); ?>">
			<?php
		}
	}
	/**
	 * Format the last activity date to a more readable form.
	 *
	 * @since 4.4.0
	 *
	 * @param string $date The last activity date.
	 *
	 * @return string The formatted last activity date.
	 */
	public static function format_last_activity_date( string $date ) {
		$timezone     = new DateTimeZone( 'GMT' );
		$now          = new DateTime( 'now', $timezone );
		$date         = new DateTime( $date, $timezone );
		$diff_in_days = $now->diff( $date )->days;

		// Show a human readable date if activity is within 6 days.
		if ( $diff_in_days < 7 ) {
			return sprintf(
			/* translators: Time difference between two dates. %s: Number of seconds/minutes/etc. */
				__( '%s ago', 'sensei-lms' ),
				human_time_diff( $date->getTimestamp() )
			);
		}

		return wp_date( get_option( 'date_format' ), $date->getTimestamp(), $timezone );
	}

	/**
	 * Render a video embed.
	 *
	 * @param string $url The URL for the video embed.
	 *
	 * @return string an embeddable HTML string.
	 */
	public static function render_video_embed( $url ) {
		$allowed_html = array(
			'embed'  => array(),
			'iframe' => array(
				'title'           => array(),
				'width'           => array(),
				'height'          => array(),
				'src'             => array(),
				'frameborder'     => array(),
				'allowfullscreen' => array(),
			),
			'video'  => Sensei_Wp_Kses::get_video_html_tag_allowed_attributes(),
		);

		if ( 'http' === substr( $url, 0, 4 ) ) {
			// V2 - make width and height a setting for video embed.
			$url = wp_oembed_get( esc_url( $url ) );
			$url = do_shortcode( html_entity_decode( $url ) );
		}
		return Sensei_Wp_Kses::maybe_sanitize( $url, $allowed_html );
	}

	/**
	 * Gets the HTML content from the Featured Video for a lesson.
	 *
	 * @since 4.7.0
	 *
	 * @param string $post_id the post ID.
	 *
	 * @return string|null The featured video HTML output if it exists.
	 */
	public static function get_featured_video_html( $post_id = null ) {
		$post = get_post( $post_id );

		if ( empty( $post ) ) {
			return null;
		}

		if ( has_block( 'sensei-lms/featured-video', $post ) ) {
			$blocks = parse_blocks( $post->post_content );
			foreach ( $blocks as $block ) {
				if ( 'sensei-lms/featured-video' === $block['blockName'] ) {
					return render_block( $block );
				}
			}
		}
		$video_embed = get_post_meta( $post->ID, '_lesson_video_embed', true );
		return $video_embed ? self::render_video_embed( $video_embed ) : null;

	}

	/**
	 * Get the featured video thumbnail URL from a Post's metadata.
	 *
	 * @param int $post_id The Post ID.
	 * @return string The video thumbnail URL.
	 */
	public static function get_featured_video_thumbnail_url( $post_id ) {
		return get_post_meta( $post_id, '_featured_video_thumbnail', true );
	}

	/**
	 * Tells if the website is hosted on the wp.com atomic site.
	 */
	public static function is_atomic_platform(): bool {
		return defined( 'ATOMIC_SITE_ID' ) && ATOMIC_SITE_ID && defined( 'ATOMIC_CLIENT_ID' ) && ATOMIC_CLIENT_ID;
	}

	/**
	 * Get count of users for a provided role.
	 *
	 * @param  string $role Slug of the Role.
	 * @return int    Count of users having the provided role.
	 */
	public static function get_user_count_for_role( $role ) {
		return count(
			Sensei_Temporary_User::get_all_users(
				[
					'fields' => 'ID',
					'role'   => $role,
				]
			)
		);
	}

	/**
	 * Tells if the current site is hosted in wordpress.com and the
	 * plan includes an active subscription for a paid Sensei product.
	 *
	 * @return bool {bool} If there is an active WPCOM subscription or not.
	 * @since 4.11.0
	 */
	public static function has_wpcom_subscription(): bool {
		$subscriptions = get_option( 'wpcom_active_subscriptions', [] );

		/**
		 * Filter to allow adding products slugs to check if it has an active WPCOM subscription.
		 *
		 * @hook sensei_wpcom_product_slugs
		 * @since 4.11.0
		 *
		 * @param {Array} $products Array of products slugs to check if it has an active WPCOM subscription.
		 *
		 * @return {array}
		 */
		$product_slugs = apply_filters( 'sensei_wpcom_product_slugs', [] );
		foreach ( $product_slugs as $product_slug ) {
			if ( array_key_exists( $product_slug, $subscriptions ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Gets the id for the last lesson the user was working on, or the next lesson, or
	 * the course id as fallback for fresh users or courses with no lessons.
	 *
	 * @param int $course_id Id of the course.
	 * @param int $user_id   Id of the user.
	 *
	 * @since 4.12.0
	 *
	 * @return int
	 */
	public static function get_target_page_post_id_for_continue_url( $course_id, $user_id ) {
		$course_lessons = Sensei()->course->course_lessons( $course_id, 'publish', 'ids' );

		if ( empty( $course_lessons ) ) {
			return $course_id;
		}
		// First try to get the lesson the user started or updated last.
		$activity_args = [
			'post__in' => $course_lessons,
			'user_id'  => $user_id,
			'type'     => 'sensei_lesson_status',
			'number'   => 1,
			'orderby'  => 'comment_date',
			'order'    => 'DESC',
			'status'   => [ 'in-progress', 'ungraded' ],
		];

		$last_lesson_activity = self::sensei_check_for_activity( $activity_args, true );

		if ( ! empty( $last_lesson_activity ) ) {
			return $last_lesson_activity->comment_post_ID;
		} else {
			// If there is no such lesson, get the first lesson that the user has not yet started.
			$completed_lessons     = Sensei()->course->get_completed_lesson_ids( $course_id, $user_id );
			$not_completed_lessons = array_diff( $course_lessons, $completed_lessons );

			if ( count( $course_lessons ) !== count( $not_completed_lessons ) && ! empty( $not_completed_lessons ) ) {
				return current( $not_completed_lessons );
			}
		}
		return $course_id;
	}
}

/**
 * Class WooThemes_Sensei_Utils
 *
 * @ignore only for backward compatibility
 * @since 1.9.0
 */
class WooThemes_Sensei_Utils extends Sensei_Utils{}
