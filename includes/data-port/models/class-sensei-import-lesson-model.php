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
 */
class Sensei_Import_Lesson_Model extends Sensei_Import_Model {

	/**
	 * Lesson's course.
	 *
	 * @var int
	 */
	private $course_id;

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
		$quiz_args = $this->get_quiz_args();

		if ( is_wp_error( $quiz_args ) ) {
			return $quiz_args;
		}

		$quiz_id = wp_insert_post( $quiz_args, true );

		if ( is_wp_error( $quiz_id ) ) {
			return $quiz_id;
		}

		if ( $this->is_new() ) {
			wp_set_post_terms( $quiz_id, array( 'multiple-choice' ), 'quiz-type' );
		}

		update_post_meta( $this->get_post_id(), '_lesson_quiz', $quiz_id );

		$result = $this->set_quiz_questions( $quiz_id );

		return is_wp_error( $result ) ? $result : true;
	}

	/**
	 * Helper method to get quiz post arguments.
	 *
	 * @return array  The arguments.
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

		$meta = $this->get_quiz_meta();

		if ( is_wp_error( $meta ) ) {
			return $meta;
		}

		$args['meta_input'] = $meta;

		return $args;
	}

	/**
	 * Helper method to get quiz meta arguments.
	 *
	 * @return array|WP_Error  The arguments on success, WP_Error on failure.
	 */
	private function get_quiz_meta() {
		$meta = [ '_quiz_lesson' => $this->get_post_id() ];

		$pass_required = $this->get_value( Sensei_Data_Port_Lesson_Schema::COLUMN_PASS_REQUIRED );
		$pass_mark     = $this->get_value( Sensei_Data_Port_Lesson_Schema::COLUMN_PASSMARK );

		if (
			( empty( $pass_required ) && ! empty( $pass_mark ) ) ||
			( ! empty( $pass_required ) && empty( $pass_mark ) )
		) {
			return new WP_Error(
				'sensei_data_port_passmark_incorrect',
				__( 'Both Passmark and Pass Required should be supplied.', 'sensei-lms' )
			);
		}

		if ( null !== $pass_required ) {
			if ( true === $pass_required ) {
				$meta['_pass_required'] = 'on';
				$meta['_quiz_passmark'] = $pass_mark;
			} else {
				$meta['_pass_required'] = '';
				$meta['_quiz_passmark'] = 0;
			}
		}

		$value = $this->get_value( Sensei_Data_Port_Lesson_Schema::COLUMN_NUM_QUESTIONS );
		if ( null !== $value ) {
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
	 * @param int $quiz_id  The quiz id.
	 *
	 * @return bool|WP_Error  True on success, WP_Error on failure.
	 */
	private function set_quiz_questions( $quiz_id ) {
		$questions = $this->get_value( Sensei_Data_Port_Lesson_Schema::COLUMN_QUESTIONS );
		if ( null === $questions ) {
			return true;
		}

		if ( empty( $questions ) ) {
			$this->delete_quiz_question_meta( $quiz_id );
			delete_post_meta( $this->get_post_id(), '_quiz_has_questions' );
			delete_post_meta( $quiz_id, '_question_order' );

			return true;
		}

		$question_import_ids = array_unique( Sensei_Data_Port_Utilities::split_list_safely( $questions, true ) );
		$question_ids        = [];

		foreach ( $question_import_ids as $question_import_id ) {
			$question_id = $this->translate_import_id( Sensei_Data_Port_Question_Schema::POST_TYPE, $question_import_id );

			if ( empty( $question_id ) ) {
				return new WP_Error(
					'sensei_data_port_question_not_found',
					// translators: Placeholder is the term which errored.
					sprintf( __( 'Question does not exist: %s.', 'sensei-lms' ), $question_id )
				);
			}

			$question_ids[] = $question_id;
		}

		if ( array_map( 'strval', $question_ids ) === get_post_meta( $quiz_id, '_question_order', true ) ) {
			return true;
		}

		$this->delete_quiz_question_meta( $quiz_id );

		$question_count = 1;
		foreach ( $question_ids as $question_id ) {
			add_post_meta( $question_id, '_quiz_id', $quiz_id, false );
			add_post_meta( $question_id, '_quiz_question_order' . $quiz_id, $quiz_id . '000' . $question_count );
			$question_count++;
		}

		update_post_meta( $this->get_post_id(), '_quiz_has_questions', '1' );
		update_post_meta( $quiz_id, '_question_order', array_map( 'strval', $question_ids ) );

		return true;
	}

	/**
	 * Helper method to delete all related meta of quiz's questions.
	 *
	 * @param int $quiz_id  The quiz id.
	 */
	private function delete_quiz_question_meta( $quiz_id ) {
		$question_order = get_post_meta( $quiz_id, '_question_order', true );

		if ( empty( $question_order ) ) {
			return;
		}

		foreach ( $question_order as $question_id ) {
			delete_post_meta( $question_id, '_quiz_id', $quiz_id );
			delete_post_meta( $question_id, '_quiz_question_order' . $quiz_id );
		}
	}

	/**
	 * Helper method which stores the lesson post.
	 *
	 * @return bool|WP_Error  True on success, WP_Error on failure.
	 */
	private function sync_lesson() {

		$value = $this->get_value( Sensei_Data_Port_Lesson_Schema::COLUMN_COURSE );
		if ( ! empty( $value ) ) {
			$course = $this->translate_import_id( Sensei_Data_Port_Course_Schema::POST_TYPE, $value );

			if ( empty( $course ) ) {
				return new WP_Error(
					'sensei_data_port_course_not_found',
					// translators: Placeholder is the term which errored.
					sprintf( __( 'Course does not exist: %s.', 'sensei-lms' ), $value )
				);
			}

			$this->course_id = $course;
		} else {
			$this->course_id = null;
		}

		$lesson_args = $this->get_lesson_args();

		if ( is_wp_error( $lesson_args ) ) {
			return $lesson_args;
		}

		$post_id = wp_insert_post( $lesson_args, true );

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

		if ( null !== $this->course_id && $this->is_new() ) {
			$old_lesson_order = get_post_meta( $this->course_id, '_lesson_order', true );
			$new_lesson_order = empty( $old_lesson_order ) ? $this->get_post_id() : $old_lesson_order . ',' . $this->get_post_id();
			update_post_meta( $this->course_id, '_lesson_order', $new_lesson_order );

			$current_lesson_index = count( explode( ',', $new_lesson_order ) );
			update_post_meta( $this->get_post_id(), '_order_' . $this->course_id, $current_lesson_index );
		}

		$result = $this->set_lesson_terms( Sensei_Data_Port_Lesson_Schema::COLUMN_MODULE, 'module' );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$result = $this->set_lesson_terms( Sensei_Data_Port_Lesson_Schema::COLUMN_TAGS, 'lesson-tag' );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$result = $this->add_thumbnail_to_post( Sensei_Data_Port_Lesson_Schema::COLUMN_IMAGE );

		return is_wp_error( $result ) ? $result : true;
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

		if ( is_wp_error( $meta ) ) {
			return $meta;
		}

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

		if ( null !== $this->course_id ) {
			$meta['_lesson_course'] = $this->course_id;
		}

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

		$value = $this->get_value( Sensei_Data_Port_Lesson_Schema::COLUMN_LENGTH );
		if ( null !== $value ) {
			$meta['_lesson_length'] = $value;
		}

		return $meta;
	}

	/**
	 * Updates the terms of a lesson. The old terms are overwritten.
	 *
	 * @param string $column_name  The CSV column name which contains the terms.
	 * @param string $taxonomy     The taxonomy of the terms.
	 *
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	private function set_lesson_terms( $column_name, $taxonomy ) {
		$new_terms = $this->get_value( $column_name );

		if ( null === $new_terms ) {
			return true;
		}

		if ( '' === $new_terms ) {
			wp_delete_object_term_relationships( $this->get_post_id(), $taxonomy );
			return true;
		}

		$terms = [];

		if ( 'module' === $taxonomy ) {
			$term = $this->get_lesson_module( $new_terms );

			if ( is_wp_error( $term ) ) {
				return $term;
			}

			$terms[] = $term;
		} else {
			$new_terms = Sensei_Data_Port_Utilities::split_list_safely( $new_terms, true );

			foreach ( $new_terms as $new_term ) {
				$term = Sensei_Data_Port_Utilities::get_term( $new_term, $taxonomy );

				if ( false === $term ) {
					return new WP_Error(
						'sensei_data_port_creation_failure',
						// translators: Placeholder is the term which errored.
						sprintf( __( 'Error getting term: %s.', 'sensei-lms' ), $new_term )
					);
				}

				$terms[] = $term;
			}
		}

		$new_term_ids = wp_list_pluck( $terms, 'term_id' );

		$result = wp_set_object_terms( $this->get_post_id(), $new_term_ids, $taxonomy );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return true;
	}

	/**
	 * Helper method which gets a module by name and checks if the module can be applied to the lesson.
	 *
	 * @param string $module_name  The module name.
	 *
	 * @return WP_Error|WP_Term  WP_Error when the module can't be applied to the lesson, WP_Term otherwise.
	 */
	private function get_lesson_module( $module_name ) {
		if ( null === $this->course_id ) {
			return new WP_Error(
				'sensei_data_port_course_empty',
				// translators: Placeholder is the term which errored.
				__( 'Module is defined while no course is specified.', 'sensei-lms' )
			);
		}

		$module = get_term_by( 'name', $module_name, 'module' );

		if ( ! $module ) {
			return new WP_Error(
				'sensei_data_port_module_not_found',
				// translators: Placeholder is the term which errored.
				sprintf( __( 'Module does not exist: %s.', 'sensei-lms' ), $module_name )
			);
		}

		$course_modules = wp_list_pluck( wp_get_post_terms( $this->course_id, 'module' ), 'term_id' );

		if ( ! in_array( $module->term_id, $course_modules, true ) ) {
			return new WP_Error(
				'sensei_data_port_module_not_part_of_course',
				// translators: First placeholder is the term which errored, second is the course id.
				sprintf( __( 'Module %1$s is not part of course %2$s.', 'sensei-lms' ), $module_name, $this->course_id )
			);
		}

		return $module;
	}
}
