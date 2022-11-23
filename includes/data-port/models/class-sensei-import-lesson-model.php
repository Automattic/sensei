<?php
/**
 * File containing the Sensei_Import_Lesson_Model class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles the import for lessons and quizzes.
 *
 * @property Sensei_Import_Lessons $task Task for importing lessons.
 */
class Sensei_Import_Lesson_Model extends Sensei_Import_Model {
	const MODEL_KEY = 'lesson';

	/**
	 * Get the model key to identify items in log entries.
	 *
	 * @return string
	 */
	public function get_model_key() {
		return self::MODEL_KEY;
	}

	/**
	 * Create a new lesson or update an existing lesson.
	 *
	 * @return true|WP_Error
	 */
	public function sync_post() {
		$result = $this->sync_lesson();

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$result = $this->sync_quiz();

		return is_wp_error( $result ) ? $result : true;
	}

	/**
	 * Helper method which stores the quiz post.
	 *
	 * @return bool|WP_Error  True on success, WP_Error on failure.
	 */
	private function sync_quiz() {
		$quiz_id = wp_insert_post( $this->get_quiz_args(), true );

		if ( is_wp_error( $quiz_id ) ) {
			return $quiz_id;
		}

		if ( $this->is_new() ) {
			wp_set_post_terms( $quiz_id, [ 'multiple-choice' ], 'quiz-type' );
		}

		update_post_meta( $this->get_post_id(), '_lesson_quiz', $quiz_id );

		$this->set_quiz_questions( $quiz_id );

		return true;
	}

	/**
	 * Helper method to get quiz post arguments.
	 *
	 * @return array The arguments.
	 */
	private function get_quiz_args() {
		$args = [
			'post_type'    => 'quiz',
			'post_parent'  => $this->get_post_id(),
			'post_content' => '',
		];

		if ( $this->is_new() ) {
			$current_user        = get_current_user_id();
			$args['post_author'] = $current_user ? $current_user : $this->get_default_author();
		} else {
			$args['ID'] = Sensei()->lesson->lesson_quizzes( $this->get_post_id() );
		}

		$value = $this->get_value( Sensei_Data_Port_Lesson_Schema::COLUMN_STATUS );
		if ( null !== $value ) {
			$args['post_status'] = $value;
		}

		$value = $this->get_value( Sensei_Data_Port_Lesson_Schema::COLUMN_TITLE );
		if ( null !== $value ) {
			$args['post_title'] = $value;
		}

		$args['meta_input'] = $this->get_quiz_meta();

		return $args;
	}

	/**
	 * Helper method to get quiz meta arguments.
	 *
	 * @return array The arguments.
	 */
	private function get_quiz_meta() {
		$meta = [ '_quiz_lesson' => $this->get_post_id() ];

		$pass_required = $this->get_value( Sensei_Data_Port_Lesson_Schema::COLUMN_PASS_REQUIRED );
		$pass_mark     = $this->get_value( Sensei_Data_Port_Lesson_Schema::COLUMN_PASSMARK );

		if ( empty( $pass_required ) && ! empty( $pass_mark ) ) {
			$pass_required = false;

			$this->add_line_warning(
				__( 'Both Passmark and Pass Required should be supplied.', 'sensei-lms' ),
				[
					'code' => 'sensei_data_port_lesson_passmark_pass_required_missing',
				]
			);
		}

		if ( null !== $pass_required ) {
			if ( true === $pass_required ) {
				$meta['_pass_required'] = 'on';
				if ( is_numeric( $pass_mark ) && ( 0 > $pass_mark || 100 < $pass_mark ) ) {
					$meta['_quiz_passmark'] = 0;

					$this->add_line_warning(
						__( 'Passmark must be between 0 and 100.', 'sensei-lms' ),
						[
							'code' => 'sensei_data_port_lesson_passmark_out_of_range',
						]
					);
				} else {
					$meta['_quiz_passmark'] = is_numeric( $pass_mark ) ? $pass_mark : 0;
				}
			} else {
				$meta['_pass_required'] = '';
				$meta['_quiz_passmark'] = 0;
			}
		}

		$value = $this->get_value( Sensei_Data_Port_Lesson_Schema::COLUMN_NUM_QUESTIONS );
		if ( null !== $value ) {
			if ( 1 > $value ) {
				$this->add_line_warning(
					__( 'Number of Questions must be greater than or equal to 1.', 'sensei-lms' ),
					[
						'code' => 'sensei_data_port_lesson_num_questions_negative',
					]
				);
				$value = '';
			}
			$meta['_show_questions'] = $value;
		}

		$value = $this->get_value( Sensei_Data_Port_Lesson_Schema::COLUMN_RANDOMIZE );
		if ( null !== $value ) {
			$meta['_random_question_order'] = true === $value ? 'yes' : 'no';
		}

		$value = $this->get_value( Sensei_Data_Port_Lesson_Schema::COLUMN_AUTO_GRADE );
		if ( null !== $value ) {
			$meta['_quiz_grade_type'] = true === $value ? 'auto' : 'manual';
		}

		$value = $this->get_value( Sensei_Data_Port_Lesson_Schema::COLUMN_QUIZ_RESET );
		if ( null !== $value ) {
			$meta['_enable_quiz_reset'] = true === $value ? 'on' : '';
		}

		return $meta;
	}

	/**
	 * Helper method to parse and store the quiz questions.
	 *
	 * @param int $quiz_id The quiz id.
	 */
	private function set_quiz_questions( $quiz_id ) {
		$questions = $this->get_value( Sensei_Data_Port_Lesson_Schema::COLUMN_QUESTIONS );
		if ( null === $questions ) {
			return;
		}

		$question_import_ids = array_unique( Sensei_Data_Port_Utilities::split_list_safely( $questions, true ) );
		$question_ids        = [];

		foreach ( $question_import_ids as $question_import_id ) {
			$question_id = $this->task->get_job()->translate_import_id( Sensei_Data_Port_Question_Schema::POST_TYPE, $question_import_id );

			if ( empty( $question_id ) ) {
				$this->add_line_warning(
					// translators: Placeholder is the question post ID which errored.
					sprintf( __( 'Question does not exist: %s.', 'sensei-lms' ), $question_import_id ),
					[
						'code' => 'sensei_data_port_quiz_missing_question',
					]
				);

				continue;
			}

			$question_ids[] = $question_id;
		}

		Sensei()->quiz->set_questions( $quiz_id, $question_ids );
	}

	/**
	 * Helper method which stores the lesson post.
	 *
	 * @return bool|WP_Error  True on success, WP_Error on failure.
	 */
	private function sync_lesson() {

		$post_args = $this->get_lesson_args();
		$post_id   = wp_insert_post( $post_args, true );

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		if ( 0 === $post_id ) {
			return new WP_Error(
				'sensei_data_port_creation_failure',
				__( 'Lesson insertion failed.', 'sensei-lms' )
			);
		}

		$this->set_post_id( $post_id );
		$this->store_import_id();

		$result = $this->add_thumbnail_to_post( Sensei_Data_Port_Lesson_Schema::COLUMN_IMAGE );
		if ( $result instanceof WP_Error ) {
			$this->add_line_warning(
				$result->get_error_message(),
				[
					'code' => $result->get_error_code(),
				]
			);
		}

		$prerequisite = $this->get_value( Sensei_Data_Port_Lesson_Schema::COLUMN_PREREQUISITE );
		if ( $prerequisite ) {
			$this->task->add_prerequisite_task( $post_id, $prerequisite, $this->line_number, $post_args['post_title'] );
		} elseif ( '' === $prerequisite ) {
			delete_post_meta( $post_id, '_lesson_prerequisite' );
		}

		$this->set_lesson_terms( Sensei_Data_Port_Lesson_Schema::COLUMN_TAGS, 'lesson-tag' );

		$module = $this->get_value( Sensei_Data_Port_Lesson_Schema::COLUMN_MODULE );
		if ( $module ) {
			/**
			 * Associations task object for this job.
			 *
			 * @var Sensei_Import_Associations $associations_task
			 */
			$associations_task = $this->task->get_job()->get_associations_task();
			$associations_task->add_lesson_module(
				$this->get_post_id(),
				$module,
				$this->line_number,
				$this->get_value( $this->schema->get_column_title() )
			);
		} elseif ( null !== $module ) {
			wp_delete_object_term_relationships( $this->get_post_id(), 'module' );
		}

		return true;
	}

	/**
	 * Helper method to get lesson post arguments.
	 *
	 * @return array  The arguments.
	 */
	private function get_lesson_args() {
		$args = [
			'ID'        => $this->get_post_id(),
			'post_type' => $this->schema->get_post_type(),
		];

		if ( $this->is_new() ) {
			$current_user        = get_current_user_id();
			$args['post_author'] = $current_user ? $current_user : $this->get_default_author();
		}

		$value = $this->get_value( Sensei_Data_Port_Lesson_Schema::COLUMN_DESCRIPTION );
		if ( null !== $value ) {
			$args['post_content'] = $value;
		}

		$value = $this->get_value( Sensei_Data_Port_Lesson_Schema::COLUMN_STATUS );
		if ( null !== $value ) {
			$args['post_status'] = $value;
		}

		$value = $this->get_value( Sensei_Data_Port_Lesson_Schema::COLUMN_TITLE );
		if ( null !== $value ) {
			$args['post_title'] = $value;
		}

		$value = $this->get_value( Sensei_Data_Port_Lesson_Schema::COLUMN_EXCERPT );
		if ( null !== $value ) {
			$args['post_excerpt'] = $value;
		}

		$value = $this->get_value( Sensei_Data_Port_Lesson_Schema::COLUMN_SLUG );
		if ( null !== $value ) {
			$args['post_name'] = $value;
		}

		$value = $this->get_value( Sensei_Data_Port_Lesson_Schema::COLUMN_ALLOW_COMMENTS );
		if ( null !== $value ) {
			$args['comment_status'] = true === $value ? 'open' : 'closed';
		}

		$meta = $this->get_lesson_meta();
		if ( ! empty( $meta ) ) {
			$args['meta_input'] = $meta;
		}

		return $args;
	}

	/**
	 * Helper method to get quiz meta arguments.
	 *
	 * @return array  The arguments.
	 */
	private function get_lesson_meta() {
		$meta = [];

		$value = $this->get_value( Sensei_Data_Port_Lesson_Schema::COLUMN_VIDEO );
		if ( null !== $value ) {
			$meta['_lesson_video_embed'] = $value;
		}

		$value = $this->get_value( Sensei_Data_Port_Lesson_Schema::COLUMN_PREVIEW );
		if ( null !== $value ) {
			$meta['_lesson_preview'] = true === $value ? 'preview' : '';
		}

		$value = $this->get_value( Sensei_Data_Port_Lesson_Schema::COLUMN_COMPLEXITY );
		if ( null !== $value ) {
			$meta['_lesson_complexity'] = $value;
		}

		// Lesson length.
		$value = $this->get_value( Sensei_Data_Port_Lesson_Schema::COLUMN_LENGTH );

		if ( null !== $value ) {
			if ( 1 > $value ) {
				$this->add_line_warning(
					__( 'Length must be greater than or equal to 1.', 'sensei-lms' ),
					[
						'code' => 'sensei_data_port_lesson_length_negative',
					]
				);
			} else {
				$meta['_lesson_length'] = intval( $value );
			}
		}

		return $meta;
	}

	/**
	 * Updates the terms of a lesson. The old terms are overwritten.
	 *
	 * @param string $column_name The CSV column name which contains the terms.
	 * @param string $taxonomy The taxonomy of the terms.
	 */
	private function set_lesson_terms( $column_name, $taxonomy ) {
		$new_terms = $this->get_value( $column_name );

		if ( null === $new_terms ) {
			return;
		}

		if ( '' === $new_terms ) {
			wp_delete_object_term_relationships( $this->get_post_id(), $taxonomy );

			return;
		}

		$terms        = [];
		$failed_terms = [];
		$new_terms    = Sensei_Data_Port_Utilities::split_list_safely( $new_terms, true );

		foreach ( $new_terms as $new_term ) {
			$term = Sensei_Data_Port_Utilities::get_term( $new_term, $taxonomy );

			if ( false === $term ) {
				$failed_terms[] = $new_term;
				continue;
			}

			$terms[] = $term;
		}

		if ( ! empty( $failed_terms ) ) {
			$this->add_line_warning(
				sprintf(
					// translators: Placeholder is comma separated list of terms that failed to save.
					__( 'The following terms failed to save: %s', 'sensei-lms' ),
					implode( ', ', $failed_terms )
				),
				[
					'code' => 'sensei_data_port_lesson_terms_failed_to_save',
				]
			);
		}

		$new_term_ids = wp_list_pluck( $terms, 'term_id' );
		$result       = wp_set_object_terms( $this->get_post_id(), $new_term_ids, $taxonomy );

		if ( is_wp_error( $result ) ) {
			$this->add_line_warning(
				$result->get_error_message(),
				[
					'code' => $result->get_error_code(),
				]
			);
		}
	}
}
