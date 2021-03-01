<?php
/**
 * Sensei REST API: Sensei_REST_API_Questions_Controller class.
 *
 * @package sensei-lms
 * @since 3.9.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly.

/**
 * A REST controller for Sensei LMS question CPT.
 *
 * @since 3.9.0
 *
 * @see WP_REST_Posts_Controller
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
				'get_callback' => function ( $object ) {
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

		if ( 'edit' !== $request['context'] ) {
			return $response;
		}

		$post        = $this->get_post( $request['id'] );
		$attrs       = $this->get_question( $post );
		$description = $attrs['description'];
		unset( $attrs['description'] );

		if ( ! has_blocks( $description ) ) {
			$description = serialize_block(
				[
					'blockName'    => 'core/paragraph',
					'innerContent' => [ $description ],
				]
			);
		}

		$block = [
			'blockName'    => 'sensei-lms/quiz-question',
			'innerContent' => [ $description ],
			'attrs'        => $attrs,
		];

		$response->data['content']['raw'] = serialize_block( $block );
		return $response;
	}

	/**
	 * Modifies the query for teachers so only their own questions are returned.
	 *
	 * @access private
	 *
	 * @param array           $args The query args.
	 * @param WP_REST_Request $request The current REST request.
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
