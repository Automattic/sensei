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
	private function get_question_schema( string $type ): array {
		$schema = $this->get_common_question_properties_schema();
		switch ( $type ) {
			case 'category-question':
				$schema = $this->get_category_question_schema();
				break;
			case 'multiple-choice':
				$schema = $this->get_multiple_choice_schema();
				break;
			case 'boolean':
				$schema = $this->get_boolean_schema();
				break;
			case 'gap-fill':
				$schema = $this->get_gap_fill_schema();
				break;
			case 'single-line':
				$schema = $this->get_single_line_schema();
				break;
			case 'multi-line':
				$schema = $this->get_multi_line_schema();
				break;
			case 'file-upload':
				$schema = $this->get_file_upload_schema();
				break;
		}

		/**
		 * Modify or add REST API schema for a question type.
		 *
		 * @since  3.9.0
		 * @hook   sensei_rest_api_schema_question_type
		 *
		 * @param  {Array}  $schema Schema for a single question.
		 * @param  {string} $type   Question type.
		 *
		 * @return {array}
		 */
		return apply_filters( 'sensei_rest_api_schema_question_type', $schema, $type );
	}

	/**
	 * Helper method to save or update a category question.
	 *
	 * @param array $question The question JSON array.
	 *
	 * @return int|WP_Error Multiple question id on success.
	 */
	private function save_category_question( $question ) {
		$question_id = $question['id'] ?? null;

		if (
			$question_id
			&& (
				'multiple_question' !== get_post_type( $question_id )
				|| ! current_user_can( get_post_type_object( 'multiple_question' )->cap->edit_post, $question_id )
			)
		) {
			return new WP_Error( 'sensei_lesson_quiz_question_not_available', '', $question_id );
		}

		if ( ! isset( $question['options'] ) ) {
			$question['options'] = [];
		}

		$question_id          = isset( $question['id'] ) ? (int) $question['id'] : null;
		$question_number      = (int) $question['options']['number'];
		$question_category_id = (int) $question['options']['category'];
		$question_category    = false;

		if ( $question_category_id ) {
			$question_category = get_term( $question_category_id, 'question-category' );
		}

		if ( ! $question_category || is_wp_error( $question_category ) ) {
			return new WP_Error( 'sensei_lesson_quiz_question_invalid_category', esc_html__( 'Invalid question category selected.', 'sensei-lms' ), $question_id );
		}

		if ( ! $question_number ) {
			$question_number = 1;
		}

		// translators: Placeholders are the question number and the question category name.
		$post_title = sprintf( esc_html__( '%1$s Question(s) from %2$s', 'sensei-lms' ), $question_number, $question_category->name );

		$post_args = [
			'ID'          => $question_id,
			'post_title'  => $post_title,
			'post_status' => 'publish',
			'post_type'   => 'multiple_question',
			'meta_input'  => [
				'category' => $question_category->term_id,
				'number'   => $question_number,
			],
		];

		$result = wp_insert_post( $post_args );

		/**
		 * This action is triggered when a category question is created or updated by the lesson quiz REST endpoint.
		 *
		 * @since 3.9.0
		 * @hook  sensei_rest_api_category_question_saved
		 *
		 * @param {int|WP_Error} $result   Result of wp_insert_post. Post ID on success or WP_Error on failure.
		 * @param {array}        $question The question JSON arguments.
		 */
		do_action( 'sensei_rest_api_category_question_saved', $result, $question );

		return $result;
	}

	/**
	 * Helper method to save or update a question.
	 *
	 * @param array  $question The question JSON array.
	 * @param string $status Question status.
	 *
	 * @return int|WP_Error Question id on success.
	 */
	private function save_question( $question, $status = 'publish' ) {
		$question_id = $question['id'] ?? null;

		if (
			$question_id
			&& (
				'question' !== get_post_type( $question_id )
				|| ! current_user_can( get_post_type_object( 'question' )->cap->edit_post, $question_id )
			)
		) {
			return new WP_Error( 'sensei_lesson_quiz_question_not_available', '', $question_id );
		}

		if ( empty( $question['title'] ) ) {
			return new WP_Error(
				'sensei_lesson_quiz_question_missing_title',
				__( 'Please ensure all questions have a title before saving.', 'sensei-lms' )
			);
		}

		if ( ! isset( $question['options'] ) ) {
			$question['options'] = [];
		}

		if ( ! isset( $question['type'] ) ) {
			$question['type'] = 'multiple-choice';
		}

		$is_new = null === $question_id;

		$post_args = [
			'ID'         => $question_id,
			'post_title' => $question['title'],
			'post_type'  => 'question',
			'meta_input' => $this->get_question_meta( $question ),
			'tax_input'  => [
				'question-type' => $question['type'],
			],
		];

		if ( $status ) {
			$post_args['post_status'] = $status;
		}

		// Force publish the question if it's part of a quiz.
		if ( $this->is_question_used_in_quiz( $question_id ) ) {
			$post_args['post_status'] = 'publish';
		}

		if ( isset( $question['description'] ) ) {
			$post_args['post_content'] = $question['description'];
		}

		$result = wp_insert_post( $post_args );

		if ( ! $is_new && ! is_wp_error( $result ) ) {
			$this->migrate_non_editor_question( $result, $question['type'] );
		}

		/**
		 * This action is triggered when a question is created or updated by the lesson quiz REST endpoint.
		 *
		 * @since 3.9.0
		 * @hook  sensei_rest_api_question_saved
		 *
		 * @param {int|WP_Error} $result        Result of wp_insert_post. Post ID on success or WP_Error on failure.
		 * @param {string}       $question_type The question type.
		 * @param {array}        $question      The question JSON arguments.
		 */
		do_action( 'sensei_rest_api_question_saved', $result, $question['type'], $question );

		return $result;
	}

	/**
	 * Check if question is being used in a quiz.
	 *
	 * @param int $question_id Question ID.
	 *
	 * @return boolean
	 */
	private function is_question_used_in_quiz( $question_id ) {
		if ( ! empty( get_post_meta( $question_id, '_quiz_id', false ) ) ) {
			return true;
		} else {
			$question_categories = wp_get_post_terms( $question_id, 'question-category' );

			foreach ( $question_categories as $question_category ) {
				$multiple_questions = get_posts(
					[
						'post_type'      => 'multiple_question',
						'posts_per_page' => 1,
						// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Needed to identify if question is being used.
						'meta_query'     => [
							[
								'key'   => 'category',
								'value' => $question_category->term_id,
							],
							[
								'key'     => '_quiz_id',
								'compare' => 'EXISTS',
							],
						],
					]
				);

				if ( ! empty( $multiple_questions ) ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Helper method to delete question meta that were deprecated by the block editor.
	 *
	 * @param int    $question_id   Question post id.
	 * @param string $question_type Question type.
	 */
	private function migrate_non_editor_question( int $question_id, string $question_type ) {
		delete_post_meta( $question_id, '_question_media' );

		if ( 'file-upload' === $question_type ) {
			delete_post_meta( $question_id, '_question_wrong_answers' );
		}
	}

	/**
	 * Calculates the meta values for a question according to the input array.
	 *
	 * @param array $question The input array.
	 *
	 * @return array The calculated meta array.
	 */
	private function get_question_meta( array $question ): array {
		$meta    = [];
		$options = $question['options'] ?? [];

		if ( isset( $options['grade'] ) ) {
			$meta['_question_grade'] = $options['grade'];
		}

		// Common meta.
		switch ( $question['type'] ) {
			case 'multiple-choice':
			case 'boolean':
			case 'gap-fill':
				if ( isset( $options['answerFeedback'] ) ) {
					$meta['_answer_feedback'] = $options['answerFeedback'];
				}
				break;
			case 'single-line':
			case 'multi-line':
			case 'file-upload':
				if ( array_key_exists( 'teacherNotes', $options ) ) {
					$meta['_question_right_answer'] = $options['teacherNotes'];
				}
				break;
		}

		// Type-specific meta.
		switch ( $question['type'] ) {
			case 'multiple-choice':
				$meta = array_merge( $meta, $this->get_multiple_choice_meta( $question ) );
				break;
			case 'boolean':
				if ( isset( $question['answer']['correct'] ) ) {
					$meta['_question_right_answer'] = $question['answer']['correct'] ? 'true' : 'false';
				}
				break;
			case 'gap-fill':
				$meta = array_merge( $meta, $this->get_gap_fill_meta( $question ) );
				break;
			case 'file-upload':
				if ( array_key_exists( 'studentHelp', $options ) ) {
					$meta['_question_wrong_answers'] = $options['studentHelp'];
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
	private function get_multiple_choice_meta( array $question ): array {
		$meta = [];

		if ( isset( $question['options']['randomOrder'] ) ) {
			$meta['_random_order'] = $question['options']['randomOrder'] ? 'yes' : 'no';
		}

		if ( isset( $question['answer'] ) ) {
			$meta['_question_right_answer']  = [];
			$meta['_question_wrong_answers'] = [];
			$meta['_answer_order']           = [];

			foreach ( $question['answer']['answers'] ?? [] as $option ) {
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
	private function get_gap_fill_meta( $question ): array {
		$answer = $question['answer'];
		if ( ! ( isset( $answer['before'] ) || isset( $answer['gap'] ) || isset( $answer['after'] ) ) ) {
			return [];
		}

		$old_text_values = [];
		if ( isset( $question['id'] ) ) {
			$old_meta        = get_post_meta( $question['id'], '_question_right_answer', true );
			$old_text_values = explode( '||', $old_meta );
		}

		$text_values = [];

		if ( array_key_exists( 'before', $answer ) ) {
			$text_values[0] = $answer['before'];
		} else {
			$text_values[0] = isset( $old_text_values[0] ) ? $old_text_values[0] : '';
		}

		if ( ! array_key_exists( 'gap', $answer ) ) {
			$text_values[1] = isset( $old_text_values[1] ) ? $old_text_values[1] : '';
		} else {
			$text_values[1] = implode( '|', $answer['gap'] );
		}

		if ( array_key_exists( 'after', $answer ) ) {
			$text_values[2] = $answer['after'];
		} else {
			$text_values[2] = isset( $old_text_values[2] ) ? $old_text_values[2] : '';
		}

		return [ '_question_right_answer' => implode( '||', $text_values ) ];
	}

	/**
	 * Returns a question as defined by the schema.
	 *
	 * @param WP_Post $question The question post.
	 *
	 * @return array The question array.
	 */
	private function get_question( WP_Post $question ): array {
		$common_properties        = $this->get_question_common_properties( $question );
		$type_specific_properties = $this->get_question_type_specific_properties( $question, $common_properties['type'] );

		return array_merge_recursive( $common_properties, $type_specific_properties );
	}

	/**
	 * Get the multiple/category question as defined by the schema.
	 *
	 * @param WP_Post $question The question post.
	 *
	 * @return array
	 */
	private function get_category_question( WP_Post $question ) : array {
		$category = (int) get_post_meta( $question->ID, 'category', true );
		$number   = (int) get_post_meta( $question->ID, 'number', true );

		return [
			'type'    => 'category-question',
			'id'      => $question->ID,
			'options' => [
				'category' => $category,
				'number'   => $number,
			],
		];
	}

	/**
	 * Generates the common question properties.
	 *
	 * @param WP_Post $question The question post.
	 *
	 * @return array The question properties.
	 */
	private function get_question_common_properties( WP_Post $question ): array {
		$question_meta = get_post_meta( $question->ID );

		$common_properties = [
			'id'          => $question->ID,
			'title'       => 'auto-draft' !== $question->post_status ? $question->post_title : '',
			'description' => $question->post_content,
			'options'     => [
				'grade' => Sensei()->question->get_question_grade( $question->ID ),
			],
			'type'        => Sensei()->question->get_question_type( $question->ID ),
			'shared'      => ! empty( $question_meta['_quiz_id'] ) && count( $question_meta['_quiz_id'] ) > 1,
			'editable'    => current_user_can( get_post_type_object( 'question' )->cap->edit_post, $question->ID ),
			'categories'  => wp_get_post_terms( $question->ID, 'question-category', [ 'fields' => 'ids' ] ),
		];

		if ( ! empty( $question_meta['_question_media'][0] ) ) {
			$question_media = $this->get_question_media( (int) $question_meta['_question_media'][0], $question->ID );

			if ( ! empty( $question_media ) ) {
				$common_properties['media'] = $question_media;
			}
		}

		return $common_properties;
	}

	/**
	 * Helper method to get question media.
	 *
	 * @param int $question_media_id The attachment id.
	 * @param int $question_id       The question id.
	 *
	 * @return array Media info. It includes the type, id, url and title.
	 */
	private function get_question_media( int $question_media_id, int $question_id ) : array {
		$question_media = [];
		$mimetype       = get_post_mime_type( $question_media_id );
		$attachment     = get_post( $question_media_id );

		if ( $mimetype || null !== $attachment ) {
			$mimetype_array = explode( '/', $mimetype );

			if ( ! empty( $mimetype_array[0] ) ) {
				if ( 'image' === $mimetype_array[0] ) {
					// This filter is documented in class-sensei-question.php.
					$image_size            = apply_filters( 'sensei_question_image_size', 'medium', $question_id );
					$attachment_src        = wp_get_attachment_image_src( $question_media_id, $image_size );
					$question_media['url'] = esc_url( $attachment_src[0] );
				} else {
					$question_media['url'] = esc_url( wp_get_attachment_url( $question_media_id ) );
				}

				$question_media['type']  = $mimetype_array[0];
				$question_media['id']    = $attachment->ID;
				$question_media['title'] = esc_html( $attachment->post_title );
			}
		}

		return $question_media;
	}

	/**
	 * Generates the type specific question properties.
	 *
	 * @param WP_Post $question      The question post.
	 * @param string  $question_type The question type.
	 *
	 * @return array The question properties.
	 */
	private function get_question_type_specific_properties( WP_Post $question, string $question_type ): array {
		$type_specific_properties = [
			'options' => [],
			'answer'  => [],
		];

		switch ( $question_type ) {
			case 'multiple-choice':
			case 'gap-fill':
			case 'boolean':
				$answer_feedback                                       = get_post_meta( $question->ID, '_answer_feedback', true );
				$type_specific_properties['options']['answerFeedback'] = empty( $answer_feedback ) ? null : $answer_feedback;
				break;
			case 'single-line':
			case 'multi-line':
			case 'file-upload':
				$teacher_notes                                       = get_post_meta( $question->ID, '_question_right_answer', true );
				$type_specific_properties['options']['teacherNotes'] = empty( $teacher_notes ) ? null : $teacher_notes;
				break;
		}

		switch ( $question_type ) {
			case 'multiple-choice':
				$type_specific_properties = array_merge_recursive( $type_specific_properties, $this->get_multiple_choice_properties( $question ) );
				break;
			case 'boolean':
				$type_specific_properties['answer']['correct'] = 'true' === get_post_meta( $question->ID, '_question_right_answer', true );
				break;
			case 'gap-fill':
				$type_specific_properties['answer'] = $this->get_gap_fill_properties( $question );
				break;
			case 'file-upload':
				$student_help                                       = get_post_meta( $question->ID, '_question_wrong_answers', true );
				$type_specific_properties['options']['studentHelp'] = empty( $student_help[0] ) ? null : esc_html( $student_help[0] );
				break;
		}

		/**
		 * Allows modification of type specific question properties.
		 *
		 * @since  3.9.0
		 * @hook   sensei_question_type_specific_properties
		 *
		 * @param  {array}   $type_specific_properties The properties of the question.
		 * @param  {string}  $question_type            The question type.
		 * @param  {WP_Post} $question                 The question post.
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
	private function get_gap_fill_properties( WP_Post $question ): array {
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
	private function get_multiple_choice_properties( WP_Post $question ): array {
		$type_specific_properties = [
			'options' => [ 'randomOrder' => 'no' !== get_post_meta( $question->ID, '_random_order', true ) ],
		];

		$correct_answers = $this->get_answers_array( $question, '_question_right_answer', true );
		$wrong_answers   = $this->get_answers_array( $question, '_question_wrong_answers', false );

		$answer_order       = get_post_meta( $question->ID, '_answer_order', true );
		$all_answers_sorted = Sensei()->question->get_answers_sorted( array_merge( $correct_answers, $wrong_answers ), $answer_order );

		$type_specific_properties['answer'] = [ 'answers' => array_values( $all_answers_sorted ) ];

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
	private function get_answers_array( WP_Post $question, string $meta_key, bool $is_correct ): array {
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
				$this->get_category_question_schema(),
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

		/**
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
	 * Helper method which returns the schema for category question properties.
	 *
	 * @return array The properties.
	 */
	private function get_category_question_schema(): array {
		$question_category_properties = [
			'type'    => [
				'type'     => 'string',
				'pattern'  => 'category-question',
				'required' => true,
			],
			'id'      => [
				'type'        => 'integer',
				'description' => 'Multiple question post ID',
			],
			'options' => [
				'type'       => 'object',
				'properties' => [
					'category' => [
						'type'        => [ 'integer', 'null' ],
						'description' => 'Term ID of the category where questions are picked',
						'required'    => true,
					],
					'number'   => [
						'type'        => 'integer',
						'description' => 'Number of questions to select from the category',
						'required'    => true,
					],
				],
			],
		];

		return [
			'title'      => 'Category Question',
			'type'       => 'object',
			'properties' => $question_category_properties,
		];
	}

	/**
	 * Helper method which returns the schema for common question properties.
	 *
	 * @return array The properties
	 */
	public function get_common_question_properties_schema(): array {
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
			'answer'      => [
				'type' => 'object',
			],
			'options'     => [
				'type'       => 'object',
				'properties' => [
					'grade' => [
						'type'        => 'integer',
						'description' => 'Points this question is worth',
						'minimum'     => 0,
						'maximum'     => 100,
						'default'     => 1,
					],
				],
			],
			'shared'      => [
				'type'        => 'boolean',
				'description' => 'Whether the question has been added on other quizzes',
				'readonly'    => true,
			],
			'editable'    => [
				'type'        => 'boolean',
				'description' => 'Whether the question can be edited by the current user',
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
			'media'       => [
				'type'       => 'object',
				'readonly'   => true,
				'properties' => [
					'id'    => [
						'type'        => 'integer',
						'description' => 'Linked media id',
					],
					'type'  => [
						'type'        => 'string',
						'description' => 'Media type',
					],
					'url'   => [
						'type'        => 'string',
						'description' => 'Media url',
					],
					'title' => [
						'type'        => 'string',
						'description' => 'Media title',
					],
				],
			],
		];
	}

	/**
	 * Helper method which returns the schema for multiple choice question properties.
	 *
	 * @return array The properties
	 */
	private function get_multiple_choice_schema(): array {
		$multiple_choice_properties = [
			'type'    => [
				'type'     => 'string',
				'pattern'  => 'multiple-choice',
				'required' => true,
			],
			'answer'  => [
				'properties' => [
					'answers' => [
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
				],
			],
			'options' => [
				'properties' => [
					'randomOrder'    => [
						'type'        => 'boolean',
						'description' => 'Should options be randomized when displayed to quiz takers',
						'default'     => false,
					],
					'answerFeedback' => [
						'type'        => [ 'string', 'null' ],
						'description' => 'Feedback to show quiz takers once quiz is submitted',
					],
				],
			],

		];

		return [
			'title'      => 'Question',
			'type'       => 'object',
			'properties' => array_merge_recursive( $this->get_common_question_properties_schema(), $multiple_choice_properties ),
		];
	}

	/**
	 * Helper method which returns the schema for true/false question properties.
	 *
	 * @return array The properties
	 */
	private function get_boolean_schema(): array {
		$boolean_properties = [
			'type'    => [
				'type'     => 'string',
				'pattern'  => 'boolean',
				'required' => true,
			],
			'answer'  => [
				'properties' => [
					'correct' => [
						'type'        => 'boolean',
						'description' => 'Correct answer for question',
					],
				],
			],
			'options' => [
				'properties' => [
					'randomOrder'    => [
						'type'        => 'boolean',
						'description' => 'Should options be randomized when displayed to quiz takers',
						'default'     => true,
					],
					'answerFeedback' => [
						'type'        => [ 'string', 'null' ],
						'description' => 'Feedback to show quiz takers once quiz is submitted',
					],
				],
			],
		];

		return [
			'title'      => 'Question',
			'type'       => 'object',
			'properties' => array_merge_recursive( $this->get_common_question_properties_schema(), $boolean_properties ),
		];
	}

	/**
	 * Helper method which returns the schema for gap fill question properties.
	 *
	 * @return array The properties
	 */
	private function get_gap_fill_schema(): array {
		$gap_fill_properties = [
			'type'   => [
				'type'     => 'string',
				'pattern'  => 'gap-fill',
				'required' => true,
			],
			'answer' => [
				'description' => 'Answer before and after text, and correct answers.',
				'properties'  => [
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
				],
			],
		];

		return [
			'title'      => 'Question',
			'type'       => 'object',
			'properties' => array_merge_recursive( $this->get_common_question_properties_schema(), $gap_fill_properties ),
		];
	}

	/**
	 * Helper method which returns the schema for single line question properties.
	 *
	 * @return array The properties
	 */
	private function get_single_line_schema(): array {
		$single_line_properties = [
			'type'    => [
				'type'     => 'string',
				'pattern'  => 'single-line',
				'required' => true,
			],
			'options' => [
				'properties' => [
					'teacherNotes' => [
						'type'        => [ 'string', 'null' ],
						'description' => 'Teacher notes for grading',
					],
				],
			],
		];

		return [
			'title'      => 'Question',
			'type'       => 'object',
			'properties' => array_merge_recursive( $this->get_common_question_properties_schema(), $single_line_properties ),
		];
	}

	/**
	 * Helper method which returns the schema for multi line question properties.
	 *
	 * @return array The properties
	 */
	private function get_multi_line_schema(): array {
		$multiline_properties = [
			'type'    => [
				'type'     => 'string',
				'pattern'  => 'multi-line',
				'required' => true,
			],
			'options' => [
				'properties' => [
					'teacherNotes' => [
						'type'        => [ 'string', 'null' ],
						'description' => 'Teacher notes for grading',
					],
				],
			],
		];

		return [
			'title'      => 'Question',
			'type'       => 'object',
			'properties' => array_merge_recursive( $this->get_common_question_properties_schema(), $multiline_properties ),
		];
	}

	/**
	 * Helper method which returns the schema for file upload question properties.
	 *
	 * @return array The properties
	 */
	private function get_file_upload_schema(): array {
		$file_upload_properties = [
			'type'    => [
				'type'     => 'string',
				'pattern'  => 'file-upload',
				'required' => true,
			],
			'options' => [
				'properties' => [
					'teacherNotes' => [
						'type'        => [ 'string', 'null' ],
						'description' => 'Teacher notes for grading',
					],
					'studentHelp'  => [
						'type'        => [ 'string', 'null' ],
						'description' => 'Description for student explaining what needs to be uploaded',
						'readonly'    => true,
					],
				],
			],
		];

		return [
			'title'      => 'Question',
			'type'       => 'object',
			'properties' => array_merge_recursive( $this->get_common_question_properties_schema(), $file_upload_properties ),
		];
	}
}
