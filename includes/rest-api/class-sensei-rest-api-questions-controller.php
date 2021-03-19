<?php
/**
 * Sensei REST API: Sensei_REST_API_Questions_Controller class.
 *
 * @package sensei-lms
 * @since   3.9.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly.

/**
 * A REST controller for Sensei LMS question CPT.
 *
 * @since 3.9.0
 *
 * @see   WP_REST_Posts_Controller
 */
class Sensei_REST_API_Questions_Controller extends WP_REST_Posts_Controller {

	use Sensei_REST_API_Question_Helpers_Trait;

	/**
	 * Constructor.
	 *
	 * @param string $post_type Post type.
	 */
	public function __construct( $post_type ) {
		parent::__construct( $post_type );

		// This filter is needed in order for teachers to only see their own questions.
		add_filter( 'rest_question_query', [ $this, 'exclude_others_questions' ], 10, 2 );

		register_rest_field(
			'question',
			'question-type-slug',
			[
				'get_callback' => function( $object ) {
					return Sensei()->question->get_question_type( $object['id'] );
				},
				'context'      => [ 'view' ],
				'schema'       => [
					'description' => __( 'The question type term slug.', 'sensei-lms' ),
					'type'        => 'string',
					'readOnly'    => true,
				],
			]
		);
	}

	/**
	 * Return post content as a question block.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|WP_Post|WP_REST_Response
	 */
	public function get_item( $request ) {
		$response = parent::get_item( $request );

		if ( 'edit' !== $request['context'] || ! Sensei()->quiz->is_block_based_editor_enabled() ) {
			return $response;
		}

		$post = $this->get_post( $request['id'] );
		if ( is_wp_error( $post ) ) {
			return $post;
		}

		$block = $this->serialize_question_as_block( $post );

		$response->data['content']['raw'] = $block;
		return $response;
	}

	/**
	 * Render question as block.
	 *
	 * @param WP_Post $question
	 *
	 * @return string
	 */
	private function serialize_question_as_block( WP_Post $question ) {
		$attributes = $this->get_question( $question );

		$description = $attributes['description'];
		unset( $attributes['description'] );

		// For auto-draft questions, just pass back the `post_content` when it already has the quiz question block.
		if ( has_block( 'sensei-lms/quiz-question', $question ) ) {
			return $question->post_content;
		}

		// Wrap legacy question description in a paragraph block.
		if ( ! has_blocks( $description ) ) {
			$description = serialize_block(
				[
					'blockName'    => 'core/paragraph',
					'innerContent' => [ $description ],
					'attrs'        => [],
				]
			);
		}

		$question_block = [
			'blockName'    => 'sensei-lms/quiz-question',
			'innerContent' => [ $description ],
			'attrs'        => $attributes,
		];
		return serialize_block( $question_block );
	}

	/**
	 * Update question block from post content.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|WP_Post|WP_REST_Response
	 */
	public function update_item( $request ) {

		$block = $this->get_question_block_from_content( $request['content'] );

		if ( $block ) {
			$request['content'] = '';
		}
		$response = parent::update_item( $request );

		if ( ! $block ) {
			return $response;
		}

		$question_id = $this->update_question( $request['id'], $block, $request['status'] );

		if ( is_wp_error( $question_id ) ) {
			switch ( $question_id->get_error_code() ) {
				case 'sensei_lesson_quiz_question_missing_title':
					return new WP_Error(
						'sensei_lesson_quiz_question_missing_title',
						__( 'Please ensure the question has a title before saving.', 'sensei-lms' ),
						[ 'status' => 400 ]
					);
			}

			return $question_id;
		}

		// Return the updated question.
		$response = $this->prepare_item_for_response( get_post( $question_id ), $request );
		return rest_ensure_response( $response );

	}

	/**
	 * Parse and return question block if it's the first block in the content.
	 *
	 * @param string $post_content
	 *
	 * @return array|null Question block.
	 */
	private function get_question_block_from_content( $post_content ) {
		if ( ! has_block( 'sensei-lms/quiz-question', $post_content ) ) {
			return null;
		}
		Sensei()->blocks->quiz->initialize_blocks();
		$block = parse_blocks( trim( $post_content ) )[0] ?? null;

		if ( ! $block || 'sensei-lms/quiz-question' !== $block['blockName'] ) {
			return null;
		}

		return $block;
	}

	/**
	 * Update question with attributes from the block.
	 *
	 * @param int    $id     Question ID.
	 * @param array  $block  Question block.
	 * @param string $status Question status.
	 *
	 * @return int|WP_Error Question id on success.
	 */
	private function update_question( $id, $block, $status ) {
		$attrs       = $block['attrs'];
		$description = serialize_blocks( $block['innerBlocks'] );
		$question    = array_merge(
			$attrs,
			[
				'description' => $description,
				'id'          => $id,
			]
		);

		return $this->save_question( $question, $status );
	}

	/**
	 * Modifies the query for teachers so only their own questions are returned.
	 *
	 * @access private
	 *
	 * @param array           $args    The query args.
	 * @param WP_REST_Request $request The current REST request.
	 *
	 * @return array The modified query args.
	 */
	public function exclude_others_questions( $args, $request ) {
		if ( current_user_can( 'manage_sensei' ) ) {
			return $args;
		}

		$current_user   = wp_get_current_user();
		$args['author'] = $current_user ? $current_user->ID : -1;

		return $args;
	}

	/**
	 * Checks if a given request has access to read posts.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return true|WP_Error True if the request has read access, WP_Error object otherwise.
	 */
	public function get_items_permissions_check( $request ) {
		$parent_check = parent::get_items_permissions_check( $request );

		if ( is_wp_error( $parent_check ) ) {
			return $parent_check;
		}

		$post_type = get_post_type_object( $this->post_type );

		if ( ! current_user_can( $post_type->cap->edit_posts ) ) {
			return new WP_Error(
				'rest_forbidden_context',
				__( 'Sorry, you are not allowed to view posts in this post type.', 'sensei-lms' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return true;
	}

}
