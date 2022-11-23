<?php
/**
 * File containing the class Sensei_REST_API_Question_Options_Controller.
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
 * @since 3.9.0
 */
class Sensei_REST_API_Question_Options_Controller extends \WP_REST_Controller {
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
	protected $rest_base = 'question-options';

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
			$this->rest_base,
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_multiple_questions' ],
					'permission_callback' => [ $this, 'can_user_get_multiple_questions' ],
					'args'                => [
						'question_ids' => [
							'type'              => 'string',
							'required'          => true,
							'sanitize_callback' => [ $this, 'sanitize_multiple_questions' ],
							'validate_callback' => 'rest_validate_request_arg',
						],
					],
				],
				'schema' => [ $this, 'get_multiple_question_schema' ],
			]
		);

		register_rest_route(
			$this->namespace,
			$this->rest_base . '/(?P<question_id>[0-9]+)',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_single_question' ],
					'permission_callback' => [ $this, 'can_user_get_question' ],
				],
				'schema' => [ $this, 'get_single_question_schema' ],
			]
		);
	}

	/**
	 * Get a single question.
	 *
	 * @param WP_REST_Request $request WordPress request object.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_single_question( WP_REST_Request $request ) {
		$question = get_post( (int) $request->get_param( 'question_id' ) );

		if ( ! $question ) {
			return new WP_Error(
				'sensei_question_options_missing_question',
				__( 'Question not found.', 'sensei-lms' ),
				[ 'status' => 404 ]
			);
		}

		$response = new WP_REST_Response();
		$response->set_data( $this->get_question( $question ) );

		return $response;
	}

	/**
	 * Get multiple questions.
	 *
	 * @param WP_REST_Request $request WordPress request object.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_multiple_questions( WP_REST_Request $request ) {
		$response     = new WP_REST_Response();
		$question_ids = $request->get_param( 'question_ids' );
		if ( empty( $question_ids ) ) {
			$response->set_data( [] );

			return $response;
		}

		$question_posts = get_posts(
			[
				'post__in'       => $question_ids,
				'post_type'      => 'question',
				'posts_per_page' => -1,
			]
		);

		$questions = [];
		foreach ( $question_posts as $question_post ) {
			if ( ! $this->has_question_read_access( $question_post ) ) {
				continue;
			}

			$questions[] = $this->get_question( $question_post );
		}

		$response->set_data( $questions );

		return $response;
	}

	/**
	 * Sanitize the question IDs input.
	 *
	 * @param string $question_ids_str Question IDs separated by a comma.
	 *
	 * @return int[]
	 */
	public function sanitize_multiple_questions( $question_ids_str ) {
		return array_filter(
			array_map(
				'intval',
				explode( ',', $question_ids_str )
			)
		);
	}

	/**
	 * Check user permission for fetching multiple questions
	 *
	 * @return bool|WP_Error Whether the user can get questions.
	 */
	public function can_user_get_multiple_questions() {
		// We'll do individual level question checks later.
		return is_user_logged_in();
	}

	/**
	 * Check user permission for fetching question.
	 *
	 * @param WP_REST_Request $request WordPress request object.
	 *
	 * @return bool|WP_Error Whether the user can read a question. Error if not found.
	 */
	public function can_user_get_question( WP_REST_Request $request ) {
		$question = get_post( (int) $request->get_param( 'question_id' ) );
		if ( ! $question || 'question' !== $question->post_type ) {
			return new WP_Error(
				'sensei_question_options_missing_question',
				__( 'Question not found.', 'sensei-lms' ),
				[ 'status' => 404 ]
			);
		}

		return $this->has_question_read_access( $question );
	}

	/**
	 * Check if current user has access to read question config.
	 *
	 * @param WP_Post $question
	 *
	 * @return bool
	 */
	private function has_question_read_access( WP_Post $question ) : bool {
		if ( ! is_user_logged_in() ) {
			return false;
		}

		return wp_get_current_user()->ID === (int) $question->post_author || current_user_can( 'manage_options' );
	}

	/**
	 * Schema for the endpoint when multiple questions are returned.
	 *
	 * @return array Schema object.
	 */
	public function get_multiple_question_schema() : array {
		return [
			'type'        => 'array',
			'description' => 'Questions in batch',
			'items'       => $this->get_single_question_schema(),
		];
	}
}
