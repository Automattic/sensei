<?php
/**
 * Sensei REST API: Sensei_REST_API_Messages_Controller class
 *
 * @package sensei-lms
 * @since 2.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * A REST controller for the Sensei messages.
 *
 * @since 2.3.0
 *
 * @see WP_REST_Posts_Controller
 */
class Sensei_REST_API_Messages_Controller extends WP_REST_Posts_Controller {

	/**
	 * Constructor.
	 *
	 * @param string $post_type Post type.
	 */
	public function __construct( $post_type ) {
		parent::__construct( $post_type );

		// This filter is needed in order for students to be able to see their own messages only.
		add_filter( 'rest_sensei_message_query', array( $this, 'exclude_others_comments' ), 10, 2 );

		register_rest_field(
			'sensei_message',
			'sender',
			array(
				'get_callback' => function( $object ) {
					return get_post_meta( $object['id'], '_sender', true );
				},
				'context'      => array( 'view' ),
				'schema'       => array(
					'description' => __( 'The username of the message\'s sender.', 'sensei-lms' ),
					'type'        => 'string',
				),
			)
		);

		register_rest_field(
			'sensei_message',
			'displayed_title',
			array(
				'get_callback' => function( $object ) {
					return Sensei_Messages::get_the_message_title( $object['id'] );
				},
				'context'      => array( 'view' ),
				'schema'       => array(
					'description' => __( 'The message\'s title.', 'sensei-lms' ),
					'type'        => 'string',
				),
			)
		);

		register_rest_field(
			'sensei_message',
			'displayed_date',
			array(
				'get_callback' => function( $object ) {
					return get_the_date( '', $object['id'] );
				},
				'context'      => array( 'view' ),
				'schema'       => array(
					'description' => __( 'The formatted date that the message was created.', 'sensei-lms' ),
					'type'        => 'string',
				),
			)
		);
	}

	/**
	 * Overrides get_item_schema to add the 'excerpt'. The 'exceprt' is also returned in WP_REST_Posts_Controller but
	 * only when the post type supports the 'excerpt' feature which we cannot add since it is going to have side effects.
	 *
	 * @since 2.3.0
	 *
	 * @return array The schema.
	 */
	public function get_item_schema() {
		$schema = parent::get_item_schema();

		$schema['properties']['excerpt'] = array(
			'description' => __( 'The excerpt for the object.', 'sensei-lms' ),
			'type'        => 'object',
			'context'     => array( 'view' ),
			'properties'  => array(
				'raw'       => array(
					'description' => __( 'Excerpt for the object, as it exists in the database.', 'sensei-lms' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
				),
				'rendered'  => array(
					'description' => __( 'HTML excerpt for the object, transformed for display.', 'sensei-lms' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'protected' => array(
					'description' => __( 'Whether the excerpt is protected with a password.', 'sensei-lms' ),
					'type'        => 'boolean',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
			),
		);

		return $schema;
	}

	/**
	 * Overrides get_collection_params to add the 'sender' argument.
	 *
	 * @since 2.3.0
	 */
	public function get_collection_params() {

		$query_params = parent::get_collection_params();

		$query_params['sender'] = array(
			'description'       => __( 'Returns the messages from either all or the current user.', 'sensei-lms' ),
			'type'              => 'string',
			'default'           => 'current',
			'validate_callback' => function ( $value ) {

				if ( in_array( $value, array( 'all', 'current' ), true ) ) {
					return true;
				}

				return new WP_Error( 'rest_invalid_param', __( 'Parameter sender can be either current or all', 'sensei-lms' ) );
			},
		);

		return $query_params;
	}

	/**
	 * This method adds filters to the sensei message query to restrict students to see their own messages only.
	 *
	 * @since 2.3.0
	 * @access private
	 *
	 * @param array           $args The query args.
	 * @param WP_REST_Request $request The current REST request.
	 * @return array The modified query args.
	 */
	public function exclude_others_comments( $args, $request ) {

		if (
			'all' === $request->get_param( 'sender' ) &&
			( current_user_can( 'moderate_comments' ) || current_user_can( 'read_private_sensei_messages' ) )
		) {
			return $args;
		}

		$current_user = wp_get_current_user();

		$args['meta_key']   = '_sender'; // phpcs:ignore  Slow query ok.
		$args['meta_value'] = $current_user->user_login; // phpcs:ignore Slow query ok.

		return $args;
	}
}
