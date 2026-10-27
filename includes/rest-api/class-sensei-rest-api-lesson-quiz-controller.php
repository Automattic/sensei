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

	/**
	 * Routes namespace.
	 *
	 * @var string
	 */
	protected $namespace;

	/**
	 * Quiz saver shared with the quiz-questions-set ability.
	 *
	 * @var Sensei_Lesson_Quiz_Saver
	 */
	private $saver;

	/**
	 * Routes prefix.
	 *
	 * @var string
	 */
	protected $rest_base = 'lesson-quiz';

	/**
	 * Sensei_REST_API_Quiz_Controller constructor.
	 *
	 * @param string $routes_namespace Routes namespace.
	 */
	public function __construct( $routes_namespace ) {
		$this->namespace = $routes_namespace;
		$this->saver     = new Sensei_Lesson_Quiz_Saver();
	}

	/**
	 * Register the REST API endpoints for quiz.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			$this->rest_base . '/(?P<lesson_id>[0-9]+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_quiz' ),
					'permission_callback' => array( $this, 'can_user_get_quiz' ),
					'args'                => array(
						'lesson_id' => array(
							'type'              => 'integer',
							'sanitize_callback' => 'sanitize_key',
							'validate_callback' => 'rest_validate_request_arg',
						),
					),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'save_quiz' ),
					'permission_callback' => array( $this, 'can_user_save_quiz' ),
					'args'                => array(
						'options'   => array(
							'type'              => 'object',
							'required'          => true,
							'sanitize_callback' => 'rest_sanitize_request_arg',
							'validate_callback' => 'rest_validate_request_arg',
						),
						'questions' => array(
							'type'              => 'array',
							'required'          => true,
							'sanitize_callback' => array( $this, 'sanitize_questions' ),
							'validate_callback' => array( $this, 'validate_questions' ),
						),
					),
				),
				'schema' => array( $this, 'get_item_schema' ),
			)
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
		return $this->saver->sanitize_questions( $questions );
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
		return $this->saver->validate_questions( $questions );
	}

	/**
	 * Check user permission for saving a quiz.
	 *
	 * @param WP_REST_Request $request WordPress request object.
	 *
	 * @return bool|WP_Error Whether the user can save the quiz. Error if the lesson is not found.
	 */
	public function can_user_save_quiz( WP_REST_Request $request ) {
		$lesson = get_post( (int) $request->get_param( 'lesson_id' ) );

		if ( ! $lesson || 'lesson' !== $lesson->post_type ) {
			return new WP_Error(
				'sensei_lesson_quiz_missing_lesson',
				__( 'Lesson not found.', 'sensei-lms' ),
				array( 'status' => 404 )
			);
		}

		return Sensei_Lesson_Quiz_Saver::can_current_user_save( $lesson->ID );
	}

	/**
	 * Save the quiz.
	 *
	 * @param WP_REST_Request $request WordPress request object.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function save_quiz( WP_REST_Request $request ) {
		$lesson  = get_post( (int) $request->get_param( 'lesson_id' ) );
		$quiz_id = $this->saver->save( $lesson, (array) $request->get_json_params() );

		if ( is_wp_error( $quiz_id ) ) {
			if ( 'sensei_lesson_quiz_lesson_auto_draft' === $quiz_id->get_error_code() ) {
				$quiz_id->add_data( array( 'status' => 400 ) );
			}

			return $quiz_id;
		}

		$response = new WP_REST_Response();
		$response->set_data( $this->saver->get_quiz_data( get_post( $quiz_id ), $lesson ) );

		return $response;
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
				array( 'status' => 404 )
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
	public function get_quiz( WP_REST_Request $request ): WP_REST_Response {
		$lesson = get_post( (int) $request->get_param( 'lesson_id' ) );
		$quiz   = Sensei()->lesson->lesson_quizzes( $lesson->ID );

		if ( ! $quiz ) {
			return new WP_REST_Response( null, 204 );
		}

		$response = new WP_REST_Response();
		$response->set_data( $this->saver->get_quiz_data( get_post( $quiz ), $lesson ) );

		return $response;
	}

	/**
	 * Schema for the endpoint.
	 *
	 * @return array Schema object.
	 */
	public function get_item_schema(): array {
		$options_schema             = $this->saver->get_options_schema();
		$options_schema['required'] = true;

		return array(
			'type'       => 'object',
			'properties' => array(
				'options'       => $options_schema,
				'questions'     => array(
					'type'     => 'array',
					'required' => true,
					'items'    => $this->saver->get_single_question_schema(),
				),
				'lesson_title'  => array(
					'type'        => 'string',
					'description' => 'The lesson title',
				),
				'lesson_status' => array(
					'type'        => 'string',
					'description' => 'The lesson status',
				),
			),
		);
	}
}
