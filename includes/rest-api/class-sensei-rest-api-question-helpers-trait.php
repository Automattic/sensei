<?php
/**
 * File containing the trait Sensei_REST_API_Question_Helpers_Trait.
 *
 * @package sensei
 * @author  Automattic
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sensei Lesson Quiz REST API endpoints.
 *
 * @since   3.9.0
 */
trait Sensei_REST_API_Question_Helpers_Trait {

	/**
	 * Returns the schema array for a question type.
	 *
	 * @param string $type The question type.
	 *
	 * @return array The question schema.
	 */
	private function get_question_schema( string $type ) : array {
		switch ( $type ) {
			case 'multiple-choice':
				return $this->get_multiple_choice_schema();
			case 'boolean':
				return $this->get_boolean_schema();
			case 'gap-fill':
				return $this->get_gap_fill_schema();
			case 'single-line':
				return $this->get_single_line_schema();
			case 'multi-line':
				return $this->get_multi_line_schema();
			case 'file-upload':
				return $this->get_file_upload_schema();
		}

		return [];
	}

	/**
	 * Helper method to save or update a question.
	 *
	 * @param array $question The question JSON array.
	 *
	 * @return int|WP_Error Question id on success.
	 */
	private function save_question( $question ) {
		$post_args = [
			'ID'          => isset( $question['id'] ) ? $question['id'] : null,
			'post_status' => 'publish',
			'post_type'   => 'question',
			'meta_input'  => $this->get_question_meta( $question ),
			'tax_input'   => [
				'question-type' => $question['type'],
			],
		];

		if ( isset( $question['description'] ) ) {
			$post_args['post_content'] = $question['description'];
		}

		if ( isset( $question['title'] ) ) {
			$post_args['post_title'] = $question['title'];
		}

		$result = wp_insert_post( $post_args );

		/**
		 * This action is triggered when a question is created or updated by the lesson quiz REST endpoint.
		 *
		 * @since 3.9.0
		 * @hook sensei_rest_api_question_saved
		 *
		 * @param {int|WP_Error} $result        Result of wp_insert_post. Post ID on success or WP_Error on failure.
		 * @param {string}       $question_type The question type.
		 * @param {array}        $question      The question JSON arguments.
		 */
		do_action( 'sensei_rest_api_question_saved', $result, $question['type'], $question );

		return wp_insert_post( $post_args );
	}

	/**
	 * Calculates the meta values for a question according to the input array.
	 *
	 * @param array $question The input array.
	 *
	 * @return array The calculated meta array.
	 */
	private function get_question_meta( array $question ) : array {
		$meta = [];

		if ( isset( $question['grade'] ) ) {
			$meta['_question_grade'] = $question['grade'];
		}

		switch ( $question['type'] ) {
			case 'multiple-choice':
				$meta = array_merge( $meta, $this->get_multiple_choice_meta( $question ) );
				break;
			case 'boolean':
				if ( isset( $question['answer'] ) ) {
					$meta['_question_right_answer'] = $question['answer'] ? 'true' : 'false';
				}

				if ( array_key_exists( 'answer_feedback', $question ) ) {
					$meta['_answer_feedback'] = $question['answer_feedback'];
				}
				break;
			case 'gap-fill':
				$meta = array_merge( $meta, $this->get_gap_fill_meta( $question ) );
				break;
			case 'single-line':
			case 'multi-line':
				if ( array_key_exists( 'teacher_notes', $question ) ) {
					$meta['_question_right_answer'] = $question['teacher_notes'];
				}
				break;
			case 'file-upload':
				if ( array_key_exists( 'teacher_notes', $question ) ) {
					$meta['_question_right_answer'] = $question['teacher_notes'];
				}

				if ( array_key_exists( 'student_help', $question ) ) {
					$meta['_question_wrong_answers'] = $question['student_help'];
				}
				break;
		}

		return $meta;
	}

	/**
	 * Calculates the meta values for a multiple choice question.
	 *
	 * @param array $question The multiple choice JSON array.
	 *
	 * @return array The calculated meta.
	 */
	private function get_multiple_choice_meta( array $question ) : array {
		$meta = [];

		if ( isset( $question['random_order'] ) ) {
			$meta['_random_order'] = $question['random_order'] ? 'yes' : 'no';
		}

		if ( array_key_exists( 'answer_feedback', $question ) ) {
			$meta['_answer_feedback'] = $question['answer_feedback'];
		}

		if ( isset( $question['options'] ) ) {
			foreach ( $question['options'] as $option ) {
				if ( empty( $option['label'] ) ) {
					continue;
				}

				if ( empty( $option['correct'] ) ) {
					$meta['_question_wrong_answers'][] = $option['label'];
				} else {
					$meta['_question_right_answer'][] = $option['label'];
				}

				$meta['_answer_order'][] = Sensei()->lesson->get_answer_id( $option['label'] );
			}

			$meta['_answer_order']       = implode( ',', $meta['_answer_order'] );
			$meta['_right_answer_count'] = count( $meta['_question_right_answer'] );
			$meta['_wrong_answer_count'] = count( $meta['_question_wrong_answers'] );
		}

		return $meta;
	}

	/**
	 * Calculates the meta values for a gap fill question.
	 *
	 * @param array $question The gap fill JSON array.
	 *
	 * @return array The calculated meta.
	 */
	private function get_gap_fill_meta( $question ) : array {
		if ( ! ( isset( $question['before'] ) || isset( $question['gap'] ) || isset( $question['after'] ) ) ) {
			return [];
		}

		$old_text_values = [];
		if ( isset( $question['id'] ) ) {
			$old_meta        = get_post_meta( $question['id'], '_question_right_answer', true );
			$old_text_values = explode( '||', $old_meta );
		}

		$text_values = [];

		if ( array_key_exists( 'before', $question ) ) {
			$text_values[0] = $question['before'];
		} else {
			$text_values[0] = isset( $old_text_values[0] ) ? $old_text_values[0] : '';
		}

		if ( ! array_key_exists( 'gap', $question ) ) {
			$text_values[1] = isset( $old_text_values[1] ) ? $old_text_values[1] : '';
		} else {
			$text_values[1] = implode( '|', $question['gap'] );
		}

		if ( array_key_exists( 'after', $question ) ) {
			$text_values[2] = $question['after'];
		} else {
			$text_values[2] = isset( $old_text_values[2] ) ? $old_text_values[2] : '';
		}

		return [ '_question_right_answer' => implode( '||', $text_values ) ];
	}

	/**
	 * This method retrieves questions as they are defined by a 'multiple_question' post type.
	 *
	 * @param WP_Post $multiple_question  The multiple question.
	 * @param array   $excluded_questions An array of question ids to exclude.
	 *
	 * @return array
	 */
	private function get_questions_from_category( WP_Post $multiple_question, array $excluded_questions ) : array {
		$category = (int) get_post_meta( $multiple_question->ID, 'category', true );
		$number   = (int) get_post_meta( $multiple_question->ID, 'number', true );

		$args = [
			'post_type'        => 'question',
			'posts_per_page'   => $number,
			'orderby'          => 'title',
			'tax_query'        => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Query limited by the number of questions.
				[
					'taxonomy' => 'question-category',
					'field'    => 'term_id',
					'terms'    => $category,
				],
			],
			'post_status'      => 'any',
			'suppress_filters' => 0,
			'post__not_in'     => $excluded_questions,
		];

		$questions = get_posts( $args );

		return array_map( [ $this, 'get_question' ], $questions );
	}

	/**
	 * Returns a question as defined by the schema.
	 *
	 * @param WP_Post $question The question post.
	 *
	 * @return array The question array.
	 */
	private function get_question( WP_Post $question ) : array {
		$common_properties        = $this->get_question_common_properties( $question );
		$type_specific_properties = $this->get_question_type_specific_properties( $question, $common_properties['type'] );

		return array_merge( $common_properties, $type_specific_properties );
	}

	/**
	 * Generates the common question properties.
	 *
	 * @param WP_Post $question The question post.
	 *
	 * @return array The question properties.
	 */
	private function get_question_common_properties( WP_Post $question ) : array {
		$question_meta = get_post_meta( $question->ID );
		return [
			'id'          => $question->ID,
			'title'       => $question->post_title,
			'description' => $question->post_content,
			'grade'       => Sensei()->question->get_question_grade( $question->ID ),
			'type'        => Sensei()->question->get_question_type( $question->ID ),
			'shared'      => ! empty( $question_meta['_quiz_id'] ) && count( $question_meta['_quiz_id'] ) > 1,
			'categories'  => wp_get_post_terms( $question->ID, 'question-category', [ 'fields' => 'ids' ] ),
		];
	}

	/**
	 * Generates the type specific question properties.
	 *
	 * @param WP_Post $question      The question post.
	 * @param string  $question_type The question type.
	 *
	 * @return array The question properties.
	 */
	private function get_question_type_specific_properties( WP_Post $question, string $question_type ) : array {
		$type_specific_properties = [];

		switch ( $question_type ) {
			case 'multiple-choice':
				$type_specific_properties = $this->get_multiple_choice_properties( $question );
				break;
			case 'boolean':
				$type_specific_properties['answer'] = 'true' === get_post_meta( $question->ID, '_question_right_answer', true );

				$answer_feedback                             = get_post_meta( $question->ID, '_answer_feedback', true );
				$type_specific_properties['answer_feedback'] = empty( $answer_feedback ) ? null : $answer_feedback;
				break;
			case 'gap-fill':
				$type_specific_properties = $this->get_gap_fill_properties( $question );
				break;
			case 'single-line':
			case 'multi-line':
				$teacher_notes                             = get_post_meta( $question->ID, '_question_right_answer', true );
				$type_specific_properties['teacher_notes'] = empty( $teacher_notes ) ? null : $teacher_notes;
				break;
			case 'file-upload':
				$teacher_notes                             = get_post_meta( $question->ID, '_question_right_answer', true );
				$type_specific_properties['teacher_notes'] = empty( $teacher_notes ) ? null : $teacher_notes;

				$student_help                             = get_post_meta( $question->ID, '_question_wrong_answers', true );
				$type_specific_properties['student_help'] = empty( $student_help[0] ) ? null : $student_help[0];
				break;
		}

		/**
		 * Allows modification of type specific question properties.
		 *
		 * @since 3.9.0
		 * @hook sensei_question_type_specific_properties
		 *
		 * @param {array}   $type_specific_properties The properties of the question.
		 * @param {string}  $question_type            The question type.
		 * @param {WP_Post} $question                 The question post.
		 *
		 * @return {array}
		 */
		return apply_filters( 'sensei_question_type_specific_properties', $type_specific_properties, $question_type, $question );
	}

	/**
	 * Helper method which generates the properties for gap fill questions.
	 *
	 * @param WP_Post $question The question post.
	 *
	 * @return array The gap fill question properties.
	 */
	private function get_gap_fill_properties( WP_Post $question ) : array {
		$right_answer_meta = get_post_meta( $question->ID, '_question_right_answer', true );

		if ( empty( $right_answer_meta ) ) {
			return [
				'before' => '',
				'gap'    => [],
				'after'  => '',
			];
		}

		$result      = [];
		$text_values = explode( '||', $right_answer_meta );

		$result['before'] = isset( $text_values[0] ) ? $text_values[0] : '';
		$result['gap']    = empty( $text_values[1] ) ? [] : explode( '|', $text_values[1] );
		$result['after']  = isset( $text_values[2] ) ? $text_values[2] : '';

		return $result;
	}

	/**
	 * Helper method which generates the properties for multiple choice questions.
	 *
	 * @param WP_Post $question The question post.
	 *
	 * @return array The multiple choice question properties.
	 */
	private function get_multiple_choice_properties( WP_Post $question ) : array {
		$type_specific_properties = [
			'random_order' => 'yes' === get_post_meta( $question->ID, '_random_order', true ),
		];

		$answer_feedback                             = get_post_meta( $question->ID, '_answer_feedback', true );
		$type_specific_properties['answer_feedback'] = empty( $answer_feedback ) ? null : $answer_feedback;

		$correct_answers = $this->get_answers_array( $question, '_question_right_answer', true );
		$wrong_answers   = $this->get_answers_array( $question, '_question_wrong_answers', false );

		$answer_order       = get_post_meta( $question->ID, '_answer_order', true );
		$all_answers_sorted = Sensei()->question->get_answers_sorted( array_merge( $correct_answers, $wrong_answers ), $answer_order );

		$type_specific_properties['options'] = array_values( $all_answers_sorted );

		return $type_specific_properties;
	}

	/**
	 * Helper method which transforms the answers question meta to an associative array. This is the format that is expected by
	 * Sensei_Question::get_answers_sorted.
	 *
	 * @param WP_Post $question   The question post.
	 * @param string  $meta_key   The answers meta key.
	 * @param bool    $is_correct Whether the questions are correct.
	 *
	 * @see Sensei_Question::get_answers_sorted
	 *
	 * @return array The answers array.
	 */
	private function get_answers_array( WP_Post $question, string $meta_key, bool $is_correct ) : array {
		$answers = get_post_meta( $question->ID, $meta_key, true );

		if ( empty( $answers ) ) {
			return [];
		}

		if ( ! is_array( $answers ) ) {
			$answers = [ $answers ];
		}

		$result = [];
		foreach ( $answers as $correct_answer ) {
			$result[ Sensei()->lesson->get_answer_id( $correct_answer ) ] = [
				'label'   => $correct_answer,
				'correct' => $is_correct,
			];
		}

		return $result;
	}

	/**
	 * Get the schema for a single question.
	 *
	 * @return array
	 */
	public function get_single_question_schema() {
		$single_question_schema = [
			'oneOf' => [
				$this->get_multiple_choice_schema(),
				$this->get_boolean_schema(),
				$this->get_gap_fill_schema(),
				$this->get_single_line_schema(),
				$this->get_multi_line_schema(),
				$this->get_file_upload_schema(),
			],
		];

		if ( ! is_wp_version_compatible( '5.6.0' ) ) {
			$single_question_schema = [
				'type' => 'object',
			];
		}

		/*
		 * Add additional question types to the REST API schema.
		 *
		 * @since 3.9.0
		 * @hook sensei_rest_api_schema_single_question
		 *
		 * @param {Array} $schema Schema for a single question.
		 *
		 * @return {array}
		 */
		return apply_filters( 'sensei_rest_api_schema_single_question', $single_question_schema );
	}

	/**
	 * Helper method which returns the schema for common question properties.
	 *
	 * @return array The properties
	 */
	public function get_common_question_properties_schema() : array {
		return [
			'id'          => [
				'type'        => 'integer',
				'description' => 'Question post ID',
			],
			'title'       => [
				'type'        => 'string',
				'description' => 'Question text',
			],
			'description' => [
				'type'        => 'string',
				'description' => 'Question description',
			],
			'grade'       => [
				'type'        => 'integer',
				'description' => 'Points this question is worth',
				'minimum'     => 0,
				'maximum'     => 100,
				'default'     => 1,
			],
			'shared'      => [
				'type'        => 'boolean',
				'description' => 'Whether the question has been added on other quizzes',
				'readonly'    => true,
			],
			'categories'  => [
				'type'        => 'array',
				'readonly'    => true,
				'description' => 'Category term IDs attached to the question',
				'items'       => [
					'type'        => 'integer',
					'description' => 'Term IDs',
				],
			],
		];
	}

	/**
	 * Helper method which returns the schema for multiple choice question properties.
	 *
	 * @return array The properties
	 */
	private function get_multiple_choice_schema() : array {
		$multiple_choice_properties = [
			'type'            => [
				'type'     => 'string',
				'pattern'  => 'multiple-choice',
				'required' => true,
			],
			'options'         => [
				'type'        => 'array',
				'description' => 'Options for the multiple choice',
				'items'       => [
					'type'       => 'object',
					'properties' => [
						'label'   => [
							'type'        => 'string',
							'description' => 'Label for answer option',
						],
						'correct' => [
							'type'        => 'boolean',
							'description' => 'Whether this answer is correct',
						],
					],
				],
			],
			'random_order'    => [
				'type'        => 'boolean',
				'description' => 'Should options be randomized when displayed to quiz takers',
				'default'     => false,
			],
			'answer_feedback' => [
				'type'        => [ 'string', 'null' ],
				'description' => 'Feedback to show quiz takers once quiz is submitted',
			],
		];

		return [
			'title'      => 'Question',
			'type'       => 'object',
			'properties' => array_merge( $this->get_common_question_properties_schema(), $multiple_choice_properties ),
		];
	}

	/**
	 * Helper method which returns the schema for true/false question properties.
	 *
	 * @return array The properties
	 */
	private function get_boolean_schema() : array {
		$boolean_properties = [
			'type'            => [
				'type'     => 'string',
				'pattern'  => 'boolean',
				'required' => true,
			],
			'answer'          => [
				'type'        => 'boolean',
				'description' => 'Correct answer for question',
			],
			'answer_feedback' => [
				'type'        => [ 'string', 'null' ],
				'description' => 'Feedback to show quiz takers once quiz is submitted',
			],
		];

		return [
			'title'      => 'Question',
			'type'       => 'object',
			'properties' => array_merge( $this->get_common_question_properties_schema(), $boolean_properties ),
		];
	}

	/**
	 * Helper method which returns the schema for gap fill question properties.
	 *
	 * @return array The properties
	 */
	private function get_gap_fill_schema() : array {
		$gap_fill_properties = [
			'type'   => [
				'type'     => 'string',
				'pattern'  => 'gap-fill',
				'required' => true,
			],
			'before' => [
				'type'        => 'string',
				'description' => 'Text before the gap',
			],
			'gap'    => [
				'type'        => 'array',
				'description' => 'Gap text answers',
				'items'       => [
					'type'        => 'string',
					'description' => 'Gap answers',
				],
			],
			'after'  => [
				'type'        => 'string',
				'description' => 'Text after the gap',
			],
		];

		return [
			'title'      => 'Question',
			'type'       => 'object',
			'properties' => array_merge( $this->get_common_question_properties_schema(), $gap_fill_properties ),
		];
	}

	/**
	 * Helper method which returns the schema for single line question properties.
	 *
	 * @return array The properties
	 */
	private function get_single_line_schema() : array {
		$single_line_properties = [
			'type'          => [
				'type'     => 'string',
				'pattern'  => 'single-line',
				'required' => true,
			],
			'teacher_notes' => [
				'type'        => [ 'string', 'null' ],
				'description' => 'Teacher notes for grading',
			],
		];

		return [
			'title'      => 'Question',
			'type'       => 'object',
			'properties' => array_merge( $this->get_common_question_properties_schema(), $single_line_properties ),
		];
	}

	/**
	 * Helper method which returns the schema for multi line question properties.
	 *
	 * @return array The properties
	 */
	private function get_multi_line_schema() : array {
		$multiline_properties = [
			'type'          => [
				'type'     => 'string',
				'pattern'  => 'multi-line',
				'required' => true,
			],
			'teacher_notes' => [
				'type'        => [ 'string', 'null' ],
				'description' => 'Teacher notes for grading',
			],
		];

		return [
			'title'      => 'Question',
			'type'       => 'object',
			'properties' => array_merge( $this->get_common_question_properties_schema(), $multiline_properties ),
		];
	}

	/**
	 * Helper method which returns the schema for file upload question properties.
	 *
	 * @return array The properties
	 */
	private function get_file_upload_schema() : array {
		$file_upload_properties = [
			'type'          => [
				'type'     => 'string',
				'pattern'  => 'file-upload',
				'required' => true,
			],
			'student_help'  => [
				'type'        => [ 'string', 'null' ],
				'description' => 'Description for student explaining what needs to be uploaded',
			],
			'teacher_notes' => [
				'type'        => [ 'string', 'null' ],
				'description' => 'Teacher notes for grading',
			],
		];

		return [
			'title'      => 'Question',
			'type'       => 'object',
			'properties' => array_merge( $this->get_common_question_properties_schema(), $file_upload_properties ),
		];
	}
}
