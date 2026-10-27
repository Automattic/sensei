<?php
/**
 * File containing the class Sensei_Lesson_Quiz_Saver.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Saves a lesson's quiz settings and questions.
 *
 * This is the save path of the lesson quiz block in the editor, shared by the
 * lesson-quiz REST controller and the `sensei/quiz-questions-set` ability.
 *
 * @since $$next-version$$
 */
class Sensei_Lesson_Quiz_Saver {
	use Sensei_REST_API_Question_Helpers_Trait;

	/**
	 * Quiz options stored as a yes/no/unset tri-state.
	 *
	 * @var string[]
	 */
	const TRISTATE_OPTIONS = array( 'failed_indicate_incorrect', 'failed_show_correct_answers', 'failed_show_answer_feedback' );

	/**
	 * Check whether the current user may save the quiz of a lesson.
	 *
	 * @param int $lesson_id Lesson post ID.
	 *
	 * @return bool
	 */
	public static function can_current_user_save( int $lesson_id ): bool {
		$lesson = get_post( $lesson_id );

		if ( ! $lesson instanceof WP_Post || 'lesson' !== $lesson->post_type ) {
			return false;
		}

		$post_type_object = get_post_type_object( 'lesson' );

		return $post_type_object && is_user_logged_in() && current_user_can( $post_type_object->cap->edit_post, $lesson->ID );
	}

	/**
	 * Sanitize questions against the schema of their type.
	 *
	 * @param array $questions The questions.
	 *
	 * @return array|WP_Error The sanitized questions.
	 */
	public function sanitize_questions( array $questions ) {
		$sanitized_questions = array();
		foreach ( $questions as $question ) {
			$result = rest_sanitize_value_from_schema( $question, $this->get_question_schema( $question['type'] ) );

			if ( $result instanceof WP_Error ) {
				return $result;
			}

			$sanitized_questions[] = $result;
		}

		return $sanitized_questions;
	}

	/**
	 * Validate questions against the schema of their type.
	 *
	 * @param array $questions The questions.
	 *
	 * @return true|WP_Error True on success, error otherwise.
	 */
	public function validate_questions( array $questions ) {
		foreach ( $questions as $question ) {
			$result = rest_validate_value_from_schema( $question, $this->get_question_schema( $question['type'] ) );

			if ( is_wp_error( $result ) ) {
				return $result;
			}
		}

		return true;
	}

	/**
	 * Save the quiz options and questions of a lesson, creating the quiz when the lesson has none.
	 *
	 * Questions without an id are created; questions left out are removed from the quiz.
	 * Options not present in `$params['options']` are reset, as the editor always sends all of them;
	 * see get_options_for_update() to merge over the stored options instead.
	 *
	 * @param WP_Post $lesson The lesson.
	 * @param array   $params Quiz data: `options` and `questions`, as validated and sanitized against the schemas.
	 *
	 * @return int|WP_Error The quiz ID, or an error. Errors carry no HTTP status.
	 */
	public function save( WP_Post $lesson, array $params ) {
		if ( 'auto-draft' === $lesson->post_status ) {
			return new WP_Error(
				'sensei_lesson_quiz_lesson_auto_draft',
				__( 'Cannot update the quiz of an Auto Draft lesson.', 'sensei-lms' )
			);
		}

		$options   = is_array( $params['options'] ?? null ) ? $params['options'] : array();
		$questions = is_array( $params['questions'] ?? null ) ? $params['questions'] : array();

		// Fail before writing anything, so an invalid question does not leave a quiz or earlier questions behind.
		$question_error = $this->find_question_error( $questions );
		if ( $question_error ) {
			return $question_error;
		}

		$quiz_id = Sensei()->lesson->lesson_quizzes( $lesson->ID );
		$is_new  = null === $quiz_id;

		$quiz_id = wp_insert_post(
			wp_slash(
				array(
					'ID'           => $quiz_id,
					'post_content' => '',
					'post_status'  => $lesson->post_status,
					'post_title'   => $lesson->post_title,
					'post_type'    => 'quiz',
					'post_parent'  => $lesson->ID,
					'meta_input'   => $this->get_quiz_meta( $options, $lesson ),
				)
			),
			true
		);

		if ( is_wp_error( $quiz_id ) ) {
			return $quiz_id;
		}

		if ( $is_new ) {
			update_post_meta( (int) $lesson->ID, '_lesson_quiz', (int) $quiz_id );
			wp_set_post_terms( $quiz_id, array( 'multiple-choice' ), 'quiz-type' );

			/**
			 * Fires after a quiz is created via the REST API.
			 *
			 * @since 4.22.0
			 *
			 * @hook sensei_quiz_create
			 *
			 * @param {int} $quiz_id   Quiz post ID.
			 * @param {int} $lesson_id Course post ID.
			 */
			do_action( 'sensei_quiz_create', (int) $quiz_id, (int) $lesson->ID );
		}

		$existing_question_ids = array_map( 'intval', wp_list_pluck( Sensei()->quiz->get_questions( $quiz_id ), 'ID' ) );

		$question_ids = array();
		foreach ( $questions as $question ) {
			if ( ! is_array( $question ) ) {
				continue;
			}

			if ( isset( $question['type'] ) && 'category-question' === $question['type'] ) {
				$question_id = $this->save_category_question( $question );
			} else {
				$question_id = $this->save_question( $question );
			}

			if ( is_wp_error( $question_id ) ) {
				if ( 'sensei_lesson_quiz_question_not_available' === $question_id->get_error_code() ) {
					// Gracefully ignore this error and include it (unchanged) in the quiz if it already exists.
					$question_id = (int) $question_id->get_error_data();

					if ( ! in_array( $question_id, $existing_question_ids, true ) ) {
						$question_id = null;
					}
				} else {
					return $question_id;
				}
			}

			$question_ids[] = $question_id;
		}

		Sensei()->quiz->set_questions( $quiz_id, array_filter( $question_ids ) );

		/**
		 * Fire action when a the lesson-quiz controller updates the data of the quiz.
		 *
		 * @hook sensei_rest_api_lesson_quiz_update
		 *
		 * @param {WP_Post} $lesson         The lesson.
		 * @param {array}   $request_params The request parameters.
		 */
		do_action( 'sensei_rest_api_lesson_quiz_update', $lesson, $params );

		return (int) $quiz_id;
	}

	/**
	 * Find the first question that save() would reject, before anything is written.
	 *
	 * Mirrors the checks in save_question() and save_category_question(); questions the
	 * user may not edit are skipped here because save() keeps or drops them unchanged.
	 *
	 * @param array $questions The questions.
	 *
	 * @return WP_Error|null
	 */
	private function find_question_error( array $questions ): ?WP_Error {
		foreach ( $questions as $question ) {
			if ( ! is_array( $question ) ) {
				continue;
			}

			$question_id      = $question['id'] ?? null;
			$is_category      = isset( $question['type'] ) && 'category-question' === $question['type'];
			$post_type_object = get_post_type_object( $is_category ? 'multiple_question' : 'question' );

			if ( ! $post_type_object ) {
				continue;
			}

			if (
				$question_id
				&& (
					get_post_type( $question_id ) !== $post_type_object->name
					|| ! current_user_can( $post_type_object->cap->edit_post, $question_id )
				)
			) {
				continue;
			}

			if ( $is_category ) {
				$category_id = (int) ( $question['options']['category'] ?? 0 );
				if ( ! $category_id || ! get_term( $category_id, 'question-category' ) instanceof WP_Term ) {
					return new WP_Error( 'sensei_lesson_quiz_question_invalid_category', esc_html__( 'Invalid question category selected.', 'sensei-lms' ), $question_id );
				}
			} elseif ( empty( $question['title'] ) ) {
				return new WP_Error(
					'sensei_lesson_quiz_question_missing_title',
					__( 'Please ensure all questions have a title before saving.', 'sensei-lms' )
				);
			}
		}

		return null;
	}

	/**
	 * Helper method to translate input to quiz meta.
	 *
	 * @param array   $quiz_options The quiz options.
	 * @param WP_Post $lesson       The parent lesson.
	 *
	 * @return array The meta.
	 */
	private function get_quiz_meta( array $quiz_options, WP_Post $lesson ): array {
		$meta_input = array( '_quiz_lesson' => $lesson->ID );

		if ( isset( $quiz_options['pass_required'] ) ) {
			$meta_input['_pass_required'] = true === $quiz_options['pass_required'] ? 'on' : '';
		}

		if ( isset( $quiz_options['quiz_passmark'] ) ) {
			$meta_input['_quiz_passmark'] = empty( $quiz_options['quiz_passmark'] ) ? 0 : $quiz_options['quiz_passmark'];
		}

		if ( isset( $quiz_options['auto_grade'] ) ) {
			$meta_input['_quiz_grade_type'] = true === $quiz_options['auto_grade'] ? 'auto' : 'manual';
		}

		if ( isset( $quiz_options['allow_retakes'] ) ) {
			$meta_input['_enable_quiz_reset'] = true === $quiz_options['allow_retakes'] ? 'on' : '';
		}

		if ( array_key_exists( 'show_questions', $quiz_options ) ) {
			$meta_input['_show_questions'] = empty( $quiz_options['show_questions'] ) ? '' : $quiz_options['show_questions'];
		}

		if ( isset( $quiz_options['random_question_order'] ) ) {
			$meta_input['_random_question_order'] = true === $quiz_options['random_question_order'] ? 'yes' : 'no';
		}

		foreach ( self::TRISTATE_OPTIONS as $option ) {
			if ( isset( $quiz_options[ $option ] ) ) {
				$meta_input[ '_' . $option ] = $quiz_options[ $option ] ? 'yes' : 'no';
			} else {
				$meta_input[ '_' . $option ] = null;
			}
		}

		$meta_input['_button_text_color']       = $quiz_options['button_text_color'] ?? null;
		$meta_input['_button_background_color'] = $quiz_options['button_background_color'] ?? null;

		if ( isset( $quiz_options['pagination'] ) ) {
			$meta_input['_pagination'] = wp_json_encode( $quiz_options['pagination'] );
		}

		return $meta_input;
	}

	/**
	 * Build the options for a partial update: the given options over the stored ones, or over the
	 * schema defaults when the lesson has no quiz yet.
	 *
	 * @param WP_Post|null $quiz    The quiz, or null when the lesson has none.
	 * @param array        $options Options to change.
	 *
	 * @return array Complete options to pass to save().
	 */
	public function get_options_for_update( ?WP_Post $quiz, array $options ): array {
		$current = $quiz ? $this->get_stored_options( $quiz ) : $this->get_default_options();

		$pagination = array_merge(
			is_array( $current['pagination'] ?? null ) ? $current['pagination'] : array(),
			is_array( $options['pagination'] ?? null ) ? $options['pagination'] : array()
		);

		$merged               = array_merge( $current, $options );
		$merged['pagination'] = $pagination;

		return $merged;
	}

	/**
	 * Get the options schema defaults.
	 *
	 * @return array
	 */
	public function get_default_options(): array {
		$defaults = array();

		foreach ( $this->get_options_schema()['properties'] as $key => $property ) {
			if ( 'pagination' === $key ) {
				$defaults[ $key ] = array();
				foreach ( $property['properties'] as $pagination_key => $pagination_property ) {
					$defaults[ $key ][ $pagination_key ] = $pagination_property['default'] ?? null;
				}
				continue;
			}

			$defaults[ $key ] = $property['default'] ?? null;
		}

		return $defaults;
	}

	/**
	 * Get the options stored on a quiz, with unset tri-state options as null rather than their
	 * displayed default, so that saving them back leaves them unset.
	 *
	 * @param WP_Post $quiz The quiz.
	 *
	 * @return array
	 */
	public function get_stored_options( WP_Post $quiz ): array {
		$options = $this->get_quiz_options( $quiz );

		foreach ( self::TRISTATE_OPTIONS as $option ) {
			$meta               = get_post_meta( $quiz->ID, '_' . $option, true );
			$options[ $option ] = '' === $meta || null === $meta ? null : 'yes' === $meta;
		}

		return $options;
	}

	/**
	 * Helper method which retrieves quiz options and questions.
	 *
	 * @param WP_Post $quiz   The quiz post.
	 * @param WP_Post $lesson The lesson post.
	 *
	 * @return array
	 */
	public function get_quiz_data( WP_Post $quiz, WP_Post $lesson ): array {
		$quiz_data = array(
			'options'       => $this->get_quiz_options( $quiz ),
			'questions'     => $this->get_quiz_questions( $quiz ),
			'lesson_title'  => $lesson->post_title,
			'lesson_status' => $lesson->post_status,
		);

		/**
		 * Filters the response of lesson-quiz requests.
		 *
		 * @hook sensei_rest_api_lesson_quiz_response
		 *
		 * @param {array}   $quiz_data The response data.
		 * @param {WP_Post} $quiz      The quiz post.default interval.
		 * @return {array} $quiz_data The modified response data.
		 */
		return apply_filters( 'sensei_rest_api_lesson_quiz_response', $quiz_data, $quiz );
	}

	/**
	 * Read the quiz options from the quiz meta.
	 *
	 * @param WP_Post $quiz The quiz post.
	 *
	 * @return array
	 */
	private function get_quiz_options( WP_Post $quiz ): array {
		$post_meta = get_post_meta( $quiz->ID );

		$allow_retakes           = ! empty( $post_meta['_enable_quiz_reset'][0] ) && 'on' === $post_meta['_enable_quiz_reset'][0];
		$failed_feedback_default = ! $allow_retakes;

		$pagination_defaults = array(
			'pagination_number'       => null,
			'show_progress_bar'       => false,
			'progress_bar_radius'     => 6,
			'progress_bar_height'     => 12,
			'progress_bar_color'      => null,
			'progress_bar_background' => null,
		);

		if ( empty( $post_meta['_pagination'][0] ) ) {
			$pagination = $pagination_defaults;
		} else {
			$pagination = json_decode( $post_meta['_pagination'][0], true );
			$pagination = $pagination ? $pagination : $pagination_defaults;
		}

		return array(
			'pass_required'               => ! empty( $post_meta['_pass_required'][0] ) && 'on' === $post_meta['_pass_required'][0],
			'quiz_passmark'               => empty( $post_meta['_quiz_passmark'][0] ) ? 0 : (int) $post_meta['_quiz_passmark'][0],
			'auto_grade'                  => ! empty( $post_meta['_quiz_grade_type'][0] ) && 'auto' === $post_meta['_quiz_grade_type'][0],
			'allow_retakes'               => $allow_retakes,
			'show_questions'              => empty( $post_meta['_show_questions'][0] ) ? null : (int) $post_meta['_show_questions'][0],
			'random_question_order'       => ! empty( $post_meta['_random_question_order'][0] ) && 'yes' === $post_meta['_random_question_order'][0],
			'failed_indicate_incorrect'   => empty( $post_meta['_failed_indicate_incorrect'][0] ) ? $failed_feedback_default : 'yes' === $post_meta['_failed_indicate_incorrect'][0],
			'failed_show_correct_answers' => empty( $post_meta['_failed_show_correct_answers'][0] ) ? $failed_feedback_default : 'yes' === $post_meta['_failed_show_correct_answers'][0],
			'failed_show_answer_feedback' => empty( $post_meta['_failed_show_answer_feedback'][0] ) ? $failed_feedback_default : 'yes' === $post_meta['_failed_show_answer_feedback'][0],
			'button_text_color'           => ! empty( $post_meta['_button_text_color'][0] ) ? $post_meta['_button_text_color'][0] : null,
			'button_background_color'     => ! empty( $post_meta['_button_background_color'][0] ) ? $post_meta['_button_background_color'][0] : null,
			'pagination'                  => $pagination,
		);
	}

	/**
	 * Returns all the questions of a quiz.
	 *
	 * @param WP_Post $quiz The quiz.
	 *
	 * @return array The array of the questions as defined by the schema.
	 */
	private function get_quiz_questions( WP_Post $quiz ): array {
		$questions = Sensei()->quiz->get_questions( $quiz->ID );

		if ( empty( $questions ) ) {
			return array();
		}

		$quiz_questions = array();
		foreach ( $questions as $question ) {
			if ( 'multiple_question' === $question->post_type ) {
				$quiz_questions[] = $this->get_category_question( $question );
			} else {
				$quiz_questions[] = $this->get_question( $question );
			}
		}

		return $quiz_questions;
	}

	/**
	 * Schema of the quiz options.
	 *
	 * @return array Schema object for the `options` property.
	 */
	public function get_options_schema(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'pass_required'               => array(
					'type'        => 'boolean',
					'description' => 'Pass required to complete lesson',
					'default'     => false,
				),
				'quiz_passmark'               => array(
					'type'        => 'integer',
					'description' => 'Score grade between 0 and 100 required to pass the quiz',
					'default'     => 100,
				),
				'auto_grade'                  => array(
					'type'        => 'boolean',
					'description' => 'Whether auto-grading should take place',
					'default'     => true,
				),
				'allow_retakes'               => array(
					'type'        => 'boolean',
					'description' => 'Allow quizzes to be taken again',
					'default'     => true,
				),
				'show_questions'              => array(
					'type'        => array( 'integer', 'null' ),
					'description' => 'Number of questions to show randomly',
					'default'     => null,
				),
				'random_question_order'       => array(
					'type'        => 'boolean',
					'description' => 'Show questions in a random order',
					'default'     => false,
				),
				'failed_indicate_incorrect'   => array(
					'type'        => array( 'boolean', 'null' ),
					'description' => 'Indicate which questions are incorrect',
					'default'     => null,
				),
				'failed_show_correct_answers' => array(
					'type'        => array( 'boolean', 'null' ),
					'description' => 'Show correct answers',
					'default'     => null,
				),
				'failed_show_answer_feedback' => array(
					'type'        => array( 'boolean', 'null' ),
					'description' => 'Show answer feedback text',
					'default'     => null,
				),
				'button_text_color'           => array(
					'type'        => array( 'string', 'null' ),
					'description' => 'Button text color',
					'default'     => null,
				),
				'button_background_color'     => array(
					'type'        => array( 'string', 'null' ),
					'description' => 'Button background color',
					'default'     => null,
				),
				'pagination'                  => array(
					'type'       => 'object',
					'required'   => true,
					'properties' => array(
						'pagination_number'       => array(
							'type'        => array( 'integer', 'null' ),
							'description' => 'Number of questions per page',
							'default'     => null,
						),
						'show_progress_bar'       => array(
							'type'        => 'boolean',
							'description' => 'Deprecated. Retained for compatibility; no longer controls automatic quiz progress bar rendering.',
							'default'     => false,
						),
						'progress_bar_radius'     => array(
							'type'        => 'integer',
							'description' => 'Deprecated. Retained for compatibility; no longer controls the quiz progress bar radius.',
							'default'     => 6,
						),
						'progress_bar_height'     => array(
							'type'        => 'integer',
							'description' => 'Deprecated. Retained for compatibility; no longer controls the quiz progress bar height.',
							'default'     => 12,
						),
						'progress_bar_background' => array(
							'type'        => array( 'string', 'null' ),
							'description' => 'Deprecated. Retained for compatibility; no longer controls the quiz progress bar background color.',
							'default'     => null,
						),
					),
				),
			),
		);
	}
}
