<?php
/**
 * File containing the Sensei_Import_Question_Model class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * This class is responsible for importing a single question.
 */
class Sensei_Import_Question_Model extends Sensei_Import_Model {
	const MODEL_KEY = 'question';

	/**
	 * Get the model key to identify items in log entries.
	 *
	 * @return string
	 */
	public function get_model_key() {
		return self::MODEL_KEY;
	}

	/**
	 * Cached question type.
	 *
	 * @var string
	 */
	private $question_type;

	/**
	 * Create a new question or update an existing question.
	 *
	 * @return true|WP_Error
	 */
	public function sync_post() {
		$question_type = $this->get_value( Sensei_Data_Port_Question_Schema::COLUMN_TYPE );

		// Check if multiple choice has a right answer.
		if ( 'multiple-choice' === $question_type ) {
			$answers = $this->parse_multiple_choice_answers();

			if ( empty( $answers['_question_right_answer'] ) ) {
				return new WP_Error(
					'sensei_data_port_question_without_right_answer',
					__( 'Question does not contain a right answer.', 'sensei-lms' )
				);
			}
		}

		$post_id = wp_insert_post( $this->get_post_array(), true );

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		if ( 0 === $post_id ) {
			return new WP_Error(
				'sensei_data_port_creation_failure',
				__( 'Question insertion failed.', 'sensei-lms' )
			);
		}

		$this->set_post_id( $post_id );
		$this->store_import_id();

		// Sync meta. This happens outside of the post save because question media needs the post ID.
		$this->sync_meta();

		return true;
	}

	/**
	 * Generates the post array.
	 *
	 * @return array
	 */
	private function get_post_array() {
		$postarr = [
			'post_type' => Sensei_Data_Port_Question_Schema::POST_TYPE,
		];

		if ( ! $this->is_new() ) {
			$postarr['ID'] = $this->get_post_id();
		} elseif ( get_current_user_id() ) {
			$postarr['post_author'] = get_current_user_id();
		} elseif ( $this->get_default_author() ) {
			$postarr['post_author'] = $this->get_default_author();
		}

		$data = $this->get_data();

		$postarr['post_title'] = $data[ Sensei_Data_Port_Question_Schema::COLUMN_TITLE ];

		$post_name = $this->get_value( Sensei_Data_Port_Question_Schema::COLUMN_SLUG );
		if ( null !== $post_name ) {
			$postarr['post_name'] = $post_name;
		}

		$post_content = $this->get_value( Sensei_Data_Port_Question_Schema::COLUMN_DESCRIPTION );
		if ( null !== $post_content ) {
			$postarr['post_content'] = $post_content;
		}

		$post_status = $this->get_value( Sensei_Data_Port_Question_Schema::COLUMN_STATUS );
		if ( null !== $post_status ) {
			$postarr['post_status'] = $post_status;
		}

		$postarr['tax_input'] = $this->get_taxonomy_terms();

		return $postarr;
	}

	/**
	 * Synchronize the post meta.
	 */
	private function sync_meta() {
		$current_meta = get_post_meta( $this->get_post_id() );
		$meta_fields  = $this->get_meta_fields();

		foreach ( $meta_fields as $field => $new_value ) {
			if ( is_callable( $new_value ) ) {
				$new_value = call_user_func( $new_value, $field );
			}

			if ( null === $new_value ) {
				continue;
			}

			if ( is_wp_error( $new_value ) ) {
				$this->add_line_warning(
					sprintf(
						// translators: First placeholder is name of field, second placeholder is error returned.
						__( 'Meta field "%1$s" is invalid: %2$s', 'sensei-lms' ),
						$field,
						$new_value->get_error_message()
					),
					[
						'code' => 'sensei_data_port_invalid_meta',
					]
				);

				continue;
			}

			$current_value = null;
			if ( ! empty( $current_meta[ $field ] ) ) {
				$current_value = $current_meta[ $field ][0];
			}

			if ( $new_value !== $current_value ) {
				update_post_meta( $this->get_post_id(), $field, $new_value );
			}
		}
	}

	/**
	 * Get the meta fields and their values.
	 *
	 * @return array|WP_Error
	 */
	private function get_meta_fields() {
		$fields = [];

		$fields['_question_grade']  = $this->get_value( Sensei_Data_Port_Question_Schema::COLUMN_GRADE );
		$fields['_random_order']    = $this->get_value( Sensei_Data_Port_Question_Schema::COLUMN_RANDOM_ORDER ) ? 'yes' : 'no';
		$fields['_answer_feedback'] = $this->get_value( Sensei_Data_Port_Question_Schema::COLUMN_FEEDBACK );
		$fields['_question_media']  = $this->get_question_media_value();

		$answer_field_values = $this->get_answer_field_values();
		$fields              = array_merge( $fields, $answer_field_values );

		return $fields;
	}

	/**
	 * Get the question media value.
	 *
	 * @return null|string
	 */
	private function get_question_media_value() {
		$column_name = Sensei_Data_Port_Question_Schema::COLUMN_MEDIA;

		$value = $this->get_value( $column_name );
		if ( null === $value ) {
			return null;
		}

		if ( empty( $value ) ) {
			return '';
		}

		return Sensei_Data_Port_Utilities::get_attachment_from_source( $value, $this->get_post_id(), $this->schema->get_schema()[ $column_name ]['mime_types'] );
	}

	/**
	 * Process answer field and return fields..
	 *
	 * @return array
	 */
	private function get_answer_field_values() {
		// Process answers.
		$values = [
			'_question_right_answer'  => '',
			'_question_wrong_answers' => '',
			'_wrong_answer_count'     => '',
			'_right_answer_count'     => '',
			'_answer_order'           => '',
		];

		$question_type = $this->get_value( Sensei_Data_Port_Question_Schema::COLUMN_TYPE );

		switch ( $question_type ) {
			case 'multiple-choice':
				$values = $this->parse_multiple_choice_answers();
				break;
			case 'boolean':
				$answers_raw = $this->get_value( Sensei_Data_Port_Question_Schema::COLUMN_ANSWER );
				$values      = [
					'_question_right_answer' => in_array( $answers_raw, [ '0', 'false' ], true ) ? 'false' : 'true',
				];

				break;
			case 'file-upload':
				$values = [
					'_question_right_answer'  => $this->get_value( Sensei_Data_Port_Question_Schema::COLUMN_TEACHER_NOTES ),
					'_question_wrong_answers' => [ $this->get_value( Sensei_Data_Port_Question_Schema::COLUMN_UPLOAD_NOTES ) ],
				];

				break;
			case 'gap-fill':
				$answer   = [];
				$answer[] = $this->get_value( Sensei_Data_Port_Question_Schema::COLUMN_TEXT_BEFORE_GAP );
				$answer[] = $this->get_value( Sensei_Data_Port_Question_Schema::COLUMN_GAP );
				$answer[] = $this->get_value( Sensei_Data_Port_Question_Schema::COLUMN_TEXT_AFTER_GAP );

				$values = [
					'_question_right_answer' => implode( '||', $answer ),
				];

				break;
			case 'single-line':
				$values = [
					'_question_right_answer' => $this->get_value( Sensei_Data_Port_Question_Schema::COLUMN_ANSWER ),
				];

				break;
			case 'multi-line':
				$values = [
					'_question_right_answer' => $this->get_value( Sensei_Data_Port_Question_Schema::COLUMN_TEACHER_NOTES ),
				];

				break;
		}

		return $values;
	}

	/**
	 * Get the question type.
	 *
	 * @return string|null
	 */
	public function get_question_type() {
		$data_column = $this->get_value( Sensei_Data_Port_Question_Schema::COLUMN_TYPE );
		if ( $data_column ) {
			return $data_column;
		}

		if ( ! $this->get_post_id() ) {
			return null;
		}

		if ( ! $this->question_type ) {
			$this->question_type = Sensei()->question->get_question_type( $this->get_post_id() );
		}

		return $this->question_type;
	}

	/**
	 * Parse multiple choice answer fields.
	 *
	 * @return array
	 */
	private function parse_multiple_choice_answers() {
		if ( isset( $this->processed_multiple_choice_answers ) ) {
			return $this->processed_multiple_choice_answers;
		}

		$values = [
			'_question_right_answer'  => [],
			'_question_wrong_answers' => [],
			'_right_answer_count'     => null,
			'_wrong_answer_count'     => null,
			'_answer_order'           => [],
		];

		$split_answers = Sensei_Data_Port_Utilities::split_list_safely( $this->get_value( Sensei_Data_Port_Question_Schema::COLUMN_ANSWER ), false );
		foreach ( $split_answers as $answer_raw ) {
			$type = strtolower( substr( $answer_raw, 0, 6 ) );

			if ( ! in_array( $type, [ 'right:', 'wrong:' ], true ) ) {
				continue;
			}

			$type   = substr( $type, 0, 5 );
			$answer = trim( substr( $answer_raw, 6 ) );
			$answer = trim( $answer, Sensei_Data_Port_Utilities::CHARS_WHITESPACE_AND_QUOTES );

			if ( 'right' === $type ) {
				$values['_question_right_answer'][] = $answer;
			} else {
				$values['_question_wrong_answers'][] = $answer;
			}

			$values['_answer_order'][] = md5( $answer );
		}

		$values['_answer_order']       = implode( ',', $values['_answer_order'] );
		$values['_right_answer_count'] = count( $values['_question_right_answer'] );
		$values['_wrong_answer_count'] = count( $values['_question_wrong_answers'] );

		$this->processed_multiple_choice_answers = $values;

		return $values;
	}

	/**
	 * Get the terms for the question, keyed by taxonomy type.
	 *
	 * @return array
	 */
	private function get_taxonomy_terms() {
		$taxonomy_terms = [];

		$question_type = $this->get_value( Sensei_Data_Port_Question_Schema::COLUMN_TYPE );
		if ( $question_type ) {
			$taxonomy_terms[ Sensei_Data_Port_Question_Schema::TAXONOMY_QUESTION_TYPE ] = [ $question_type ];
		}

		$taxonomy_terms[ Sensei_Data_Port_Question_Schema::TAXONOMY_QUESTION_CATEGORY ] = [];

		$category_list = Sensei_Data_Port_Utilities::split_list_safely( $this->get_value( Sensei_Data_Port_Question_Schema::COLUMN_CATEGORIES ), true );
		if ( ! empty( $category_list ) ) {
			foreach ( $category_list as $category ) {
				$category_term = Sensei_Data_Port_Utilities::get_term( $category, Sensei_Data_Port_Question_Schema::TAXONOMY_QUESTION_CATEGORY );
				if ( $category_term ) {
					$taxonomy_terms[ Sensei_Data_Port_Question_Schema::TAXONOMY_QUESTION_CATEGORY ][] = $category_term->term_id;
				}
			}
		}

		return $taxonomy_terms;
	}
}
