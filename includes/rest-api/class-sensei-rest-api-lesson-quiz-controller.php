<?php
/**
 * File containing the class Sensei_REST_API_Lesson_Quiz_Controller.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sensei Lesson Quiz REST API endpoints.
 *
 * @package Sensei
 * @author  Automattic
 * @since   3.9.0
 */
class Sensei_REST_API_Lesson_Quiz_Controller extends \WP_REST_Controller {
	use Sensei_REST_API_Question_Helpers_Trait;

	/**
	 * Routes namespace.
	 *
	 * @var string
	 */
	protected $namespace;

	/**
	 * Routes prefix.
	 *
	 * @var string
	 */
	protected $rest_base = 'lesson-quiz';

	/**
	 * Sensei_REST_API_Quiz_Controller constructor.
	 *
	 * @param string $namespace Routes namespace.
	 */
	public function __construct( $namespace ) {
		$this->namespace = $namespace;
	}

	/**
	 * Register the REST API endpoints for quiz.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			$this->rest_base . '/(?P<lesson_id>[0-9]+)',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_quiz' ],
					'permission_callback' => [ $this, 'can_user_get_quiz' ],
					'args'                => [
						'lesson_id' => [
							'type'              => 'integer',
							'sanitize_callback' => 'sanitize_key',
							'validate_callback' => 'rest_validate_request_arg',
						],
					],
				],
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'save_quiz' ],
					'permission_callback' => [ $this, 'can_user_save_quiz' ],
					'args'                => [
						'options'   => [
							'type'              => 'object',
							'required'          => true,
							'sanitize_callback' => 'rest_sanitize_request_arg',
							'validate_callback' => 'rest_validate_request_arg',
						],
						'questions' => [
							'type'              => 'array',
							'required'          => true,
							'sanitize_callback' => [ $this, 'sanitize_questions' ],
							'validate_callback' => [ $this, 'validate_questions' ],
						],
					],
				],
				'schema' => [ $this, 'get_item_schema' ],
			]
		);
	}

	/**
	 * Sanitization method for questions.
	 *
	 * This can be replaced by `rest_sanitize_request_arg` in the callback once we no longer support WordPress
	 * versions before 5.6 (when `oneOf` support was added).
	 *
	 * @param array $questions The questions.
	 *
	 * @return array|WP_Error The sanitized questions.
	 */
	public function sanitize_questions( array $questions ) {
		$sanitized_questions = [];
		foreach ( $questions as $question ) {
			$result = rest_sanitize_value_from_schema( $question, $this->get_question_schema( $question['type'] ) );

			if ( is_wp_error( $result ) ) {
				return $result;
			}

			$sanitized_questions[] = $result;
		}

		return $sanitized_questions;
	}

	/**
	 * Validation method for questions.
	 *
	 * This can be replaced by `rest_validate_request_arg` in the callback once we no longer support WordPress
	 * versions before 5.6 (when `oneOf` support was added).
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
	 * Check user permission for saving course structure.
	 *
	 * @param WP_REST_Request $request WordPress request object.
	 *
	 * @return bool|WP_Error Whether the user can save course structure data. Error if not found.
	 */
	public function can_user_save_quiz( WP_REST_Request $request ) {
		$lesson = get_post( (int) $request->get_param( 'lesson_id' ) );

		if ( ! $lesson || 'lesson' !== $lesson->post_type ) {
			return new WP_Error(
				'sensei_lesson_quiz_missing_lesson',
				__( 'Lesson not found.', 'sensei-lms' ),
				[ 'status' => 404 ]
			);
		}

		if ( ! is_user_logged_in() ) {
			return false;
		}

		return current_user_can( get_post_type_object( 'lesson' )->cap->edit_post, $lesson->ID );
	}

	/**
	 * Save the quiz.
	 *
	 * @param WP_REST_Request $request WordPress request object.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function save_quiz( WP_REST_Request $request ) {
		$lesson = get_post( (int) $request->get_param( 'lesson_id' ) );

		if ( 'auto-draft' === $lesson->post_status ) {
			return new WP_Error(
				'sensei_lesson_quiz_lesson_auto_draft',
				__( 'Cannot update the quiz of an Auto Draft lesson.', 'sensei-lms' ),
				[ 'status' => 400 ]
			);
		}

		$quiz_id = Sensei()->lesson->lesson_quizzes( $lesson->ID );
		$is_new  = null === $quiz_id;

		$json_params = $request->get_json_params();

		$quiz_id = wp_insert_post(
			[
				'ID'           => $quiz_id,
				'post_content' => '',
				'post_status'  => $lesson->post_status,
				'post_title'   => $lesson->post_title,
				'post_type'    => 'quiz',
				'post_parent'  => $lesson->ID,
				'meta_input'   => $this->get_quiz_meta( $json_params, $lesson ),
			]
		);

		if ( is_wp_error( $quiz_id ) ) {
			return $quiz_id;
		}

		if ( $is_new ) {
			update_post_meta( $lesson->ID, '_lesson_quiz', $quiz_id );
			wp_set_post_terms( $quiz_id, [ 'multiple-choice' ], 'quiz-type' );
		}

		$existing_question_ids = array_map( 'intval', wp_list_pluck( Sensei()->quiz->get_questions( $quiz_id ), 'ID' ) );

		$question_ids = [];
		foreach ( $json_params['questions'] as $question ) {
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
		do_action( 'sensei_rest_api_lesson_quiz_update', $lesson, $json_params );

		$response = new WP_REST_Response();
		$response->set_data( $this->get_quiz_data( get_post( $quiz_id ), $lesson ) );

		return $response;
	}

	/**
	 * Helper method to translate input to quiz meta.
	 *
	 * @param array   $json_params The input coming from JSON data.
	 * @param WP_Post $lesson       The parent lesson.
	 *
	 * @return array The meta.
	 */
	private function get_quiz_meta( array $json_params, WP_Post $lesson ) : array {
		$meta_input = [ '_quiz_lesson' => $lesson->ID ];

		$quiz_options = $json_params['options'];

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

		foreach ( [ 'failed_indicate_incorrect', 'failed_show_correct_answers', 'failed_show_answer_feedback' ] as $option ) {
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
	 * Check user permission for reading a quiz.
	 *
	 * @param WP_REST_Request $request WordPress request object.
	 *
	 * @return bool|WP_Error Whether the user can read a quiz. Error if not found.
	 */
	public function can_user_get_quiz( WP_REST_Request $request ) {
		$lesson = get_post( (int) $request->get_param( 'lesson_id' ) );
		if ( ! $lesson || 'lesson' !== $lesson->post_type ) {
			return new WP_Error(
				'sensei_lesson_quiz_missing_lesson',
				__( 'Lesson not found.', 'sensei-lms' ),
				[ 'status' => 404 ]
			);
		}

		if ( ! is_user_logged_in() ) {
			return false;
		}

		return current_user_can( get_post_type_object( 'lesson' )->cap->edit_post, $lesson->ID ) || current_user_can( 'manage_options' );
	}

	/**
	 * Get the quiz.
	 *
	 * @param WP_REST_Request $request WordPress request object.
	 *
	 * @return WP_REST_Response
	 */
	public function get_quiz( WP_REST_Request $request ) : WP_REST_Response {
		$lesson = get_post( (int) $request->get_param( 'lesson_id' ) );
		$quiz   = Sensei()->lesson->lesson_quizzes( $lesson->ID );

		if ( ! $quiz ) {
			return new WP_REST_Response( null, 204 );
		}

		$response = new WP_REST_Response();
		$response->set_data( $this->get_quiz_data( get_post( $quiz ), $lesson ) );

		return $response;
	}

	/**
	 * Helper method which retrieves quiz options.
	 *
	 * @param WP_Post $quiz   The quiz post.
	 * @param WP_Post $lesson The lesson post.
	 *
	 * @return array
	 */
	private function get_quiz_data( WP_Post $quiz, WP_Post $lesson ) : array {
		$post_meta = get_post_meta( $quiz->ID );

		$allow_retakes           = ! empty( $post_meta['_enable_quiz_reset'][0] ) && 'on' === $post_meta['_enable_quiz_reset'][0];
		$failed_feedback_default = ! $allow_retakes;

		$pagination_defaults = [
			'pagination_number'       => null,
			'show_progress_bar'       => false,
			'progress_bar_radius'     => 6,
			'progress_bar_height'     => 12,
			'progress_bar_color'      => null,
			'progress_bar_background' => null,
		];

		if ( empty( $post_meta['_pagination'][0] ) ) {
			$pagination = $pagination_defaults;
		} else {
			$pagination = json_decode( $post_meta['_pagination'][0], true );
			$pagination = $pagination ? $pagination : $pagination_defaults;
		}

		$quiz_data = [
			'options'       => [
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
			],
			'questions'     => $this->get_quiz_questions( $quiz ),
			'lesson_title'  => $lesson->post_title,
			'lesson_status' => $lesson->post_status,
		];

		/**
		 * Filters the response of lesson-quiz requests.
		 *
		 * @hook sensei_rest_api_lesson_quiz_response
		 *
		 * @param {array}   $quiz_data The response data.
		 * @param {WP_Post} $quiz      The quiz post.default interval.
		 *
		 * @return {array} $quiz_data The modified response data.
		 */
		return apply_filters( 'sensei_rest_api_lesson_quiz_response', $quiz_data, $quiz );
	}

	/**
	 * Returns all the questions of a quiz.
	 *
	 * @param WP_Post $quiz The quiz.
	 *
	 * @return array The array of the questions as defined by the schema.
	 */
	private function get_quiz_questions( WP_Post $quiz ) : array {
		$questions = Sensei()->quiz->get_questions( $quiz->ID );

		if ( empty( $questions ) ) {
			return [];
		}

		$quiz_questions = [];
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
	 * Schema for the endpoint.
	 *
	 * @return array Schema object.
	 */
	public function get_item_schema(): array {
		$schema = [
			'type'       => 'object',
			'properties' => [
				'options'       => [
					'type'       => 'object',
					'required'   => true,
					'properties' => [
						'pass_required'               => [
							'type'        => 'boolean',
							'description' => 'Pass required to complete lesson',
							'default'     => false,
						],
						'quiz_passmark'               => [
							'type'        => 'integer',
							'description' => 'Score grade between 0 and 100 required to pass the quiz',
							'default'     => 100,
						],
						'auto_grade'                  => [
							'type'        => 'boolean',
							'description' => 'Whether auto-grading should take place',
							'default'     => true,
						],
						'allow_retakes'               => [
							'type'        => 'boolean',
							'description' => 'Allow quizzes to be taken again',
							'default'     => true,
						],
						'show_questions'              => [
							'type'        => [ 'integer', 'null' ],
							'description' => 'Number of questions to show randomly',
							'default'     => null,
						],
						'random_question_order'       => [
							'type'        => 'boolean',
							'description' => 'Show questions in a random order',
							'default'     => false,
						],
						'failed_indicate_incorrect'   => [
							'type'        => [ 'boolean', 'null' ],
							'description' => 'Indicate which questions are incorrect',
							'default'     => null,
						],
						'failed_show_correct_answers' => [
							'type'        => [ 'boolean', 'null' ],
							'description' => 'Show correct answers',
							'default'     => null,
						],
						'failed_show_answer_feedback' => [
							'type'        => [ 'boolean', 'null' ],
							'description' => 'Show answer feedback text',
							'default'     => null,
						],
						'button_text_color'           => [
							'type'        => [ 'string', 'null' ],
							'description' => 'Button text color',
							'default'     => null,
						],
						'button_background_color'     => [
							'type'        => [ 'string', 'null' ],
							'description' => 'Button background color',
							'default'     => null,
						],
						'pagination'                  => [
							'type'       => 'object',
							'required'   => true,
							'properties' => [
								'pagination_number'       => [
									'type'        => [ 'integer', 'null' ],
									'description' => 'Number of questions per page',
									'default'     => null,
								],
								'show_progress_bar'       => [
									'type'        => 'boolean',
									'description' => 'Whether to show the progress bar in the frontend',
									'default'     => false,
								],
								'progress_bar_radius'     => [
									'type'        => 'integer',
									'description' => 'Progress bar radius',
									'default'     => 6,
								],
								'progress_bar_height'     => [
									'type'        => 'integer',
									'description' => 'Progress bar height',
									'default'     => 12,
								],
								'progress_bar_background' => [
									'type'        => [ 'string', 'null' ],
									'description' => 'Progress bar background color',
									'default'     => null,
								],
							],
						],
					],
				],
				'questions'     => [
					'type'     => 'array',
					'required' => true,
					'items'    => $this->get_single_question_schema(),
				],
				'lesson_title'  => [
					'type'        => 'string',
					'description' => 'The lesson title',
				],
				'lesson_status' => [
					'type'        => 'string',
					'description' => 'The lesson status',
				],
			],
		];

		return $schema;
	}
}
