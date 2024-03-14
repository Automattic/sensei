<?php
/**
 * Sensei REST API: Sensei_REST_API_Lessons_Controller class.
 *
 * @package sensei-lms
 * @since 3.9.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly.

/**
 * A REST controller for Sensei LMS lessons CPT.
 *
 * @since 3.9.0
 *
 * @see WP_REST_Posts_Controller
 */
class Sensei_REST_API_Lessons_Controller extends WP_REST_Posts_Controller {

	/**
	 * Sensei_REST_API_Lessons_Controller constructor.
	 *
	 * @param string $post_type The post type.
	 */
	public function __construct( $post_type ) {
		parent::__construct( $post_type );
		$this->init_post_meta();
		$this->custom_filter();
	}

	/**
	 * Register lesson post meta.
	 *
	 * @since  3.11.0
	 */
	private function init_post_meta() {
		register_post_meta(
			'lesson',
			'_quiz_has_questions',
			array(
				'show_in_rest'      => true,
				'single'            => true,
				'type'              => 'boolean',
				'sanitize_callback' => function ( $value ) {
					return $value ? 1 : 0;
				},
				'auth_callback'     => array( $this, 'auth_callback' ),
			)
		);

		register_post_meta(
			'lesson',
			'_lesson_complexity',
			array(
				'show_in_rest'      => true,
				'single'            => true,
				'type'              => 'string',
				'default'           => 'easy',
				'sanitize_callback' => function ( $value ) {
					if ( '' === $value ) {
						return $value;
					}

					return array_key_exists( $value, Sensei()->lesson->lesson_complexities() ) ? $value : 'easy';
				},
				'auth_callback'     => array( $this, 'auth_callback' ),
			)
		);

		register_post_meta(
			'lesson',
			'_lesson_length',
			array(
				'show_in_rest'      => true,
				'single'            => true,
				'type'              => 'integer',
				'default'           => 10,
				'sanitize_callback' => function ( $value ) {
					return absint( $value );
				},
				'auth_callback'     => array( $this, 'auth_callback' ),
			)
		);

		register_post_meta(
			'lesson',
			'_lesson_course',
			array(
				'show_in_rest'  => true,
				'single'        => true,
				'type'          => 'integer',
				'auth_callback' => array( $this, 'auth_callback' ),
			)
		);

		register_post_meta(
			'lesson',
			'_lesson_preview',
			array(
				'show_in_rest'  => true,
				'single'        => true,
				'type'          => 'string',
				'auth_callback' => array( $this, 'auth_callback' ),
			)
		);
	}

	/**
	 * Adjust the query to include meta query args and search by title.
	 *
	 * @since 4.21.0
	 */
	private function custom_filter() {
		add_filter( 'rest_lesson_query', array( $this, 'add_meta_query_args' ), 10, 2 );
		add_filter( 'rest_lesson_query', array( $this, 'search_by_title' ), 10, 2 );
	}

	/**
	 * Modifies the query to search by title only.
	 *
	 * @since 4.21.0
	 *
	 * @internal
	 *
	 * @param array           $args    The query args.
	 * @param WP_REST_Request $request The current REST request.
	 * @return array The modified query args.
	 */
	public function search_by_title( $args, $request ) {
		$request_source = $request->get_param( 'requestSource' );
		$search_term    = $request->get_param( 'search' );
		if ( ! empty( $search_term ) && 'add_existing_lesson_modal' === $request_source ) {
			$args['search_columns'] = array( 'post_title' );
		}
		return $args;
	}

	/**
	 * Add meta query to the lesson query.
	 *
	 * @since 4.21.0
	 *
	 * @internal
	 *
	 * @param array           $args    Query args.
	 * @param WP_REST_Request $request Full details about the request.
	 * @return array Modified query args.
	 */
	public function add_meta_query_args( $args, $request ) {

		$meta_key          = $request->get_param( 'metaKey' );
		$allowed_meta_keys = array( '_lesson_course', '_lesson_complexity', '_lesson_length', '_lesson_preview' );

		if ( isset( $meta_key ) && in_array( $meta_key, $allowed_meta_keys, true ) ) {
			$meta_query = $args['meta_query'] ?? array();
			$meta_value = $request->get_param( 'metaValue' );
			$meta_key   = esc_sql( $meta_key );

			if ( empty( $meta_value ) ) {
				$meta_query[] = array(
					'relation' => 'OR',
					array(
						'key'     => $meta_key,
						'compare' => 'NOT EXISTS',
					),
					array(
						'key'     => $meta_key,
						'value'   => '',
						'compare' => '=',
					),
					array(
						'key'     => $meta_key,
						'value'   => '0',
						'compare' => '=',
					),
				);
			} else {
				$meta_value   = esc_sql( $meta_value );
				$meta_query[] = array(
					'key'     => $meta_key,
					'value'   => $meta_value,
					'compare' => '=',
				);
			}

			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			$args['meta_query'] = $meta_query;
		}

		return $args;
	}

	/**
	 * Perform permissions check when editing post meta.
	 *
	 * @since  3.11.0
	 * @access private
	 *
	 * @param bool   $allowed True if allowed to view the meta field by default, false otherwise.
	 * @param string $meta_key Meta key.
	 * @param int    $post_id  Lesson ID.
	 * @return bool Whether the user can edit the post meta.
	 */
	public function auth_callback( $allowed, $meta_key, $post_id ) {
		return current_user_can( 'edit_post', $post_id );
	}

	/**
	 * Adds the quiz block if missing when a quiz is active on the lesson.
	 *
	 * @param array           $prepared Prepared response array.
	 * @param WP_REST_Request $request  Full details about the request.
	 * @return array Modified data object with additional fields.
	 */
	protected function add_additional_fields_to_object( $prepared, $request ) {

		$prepared = parent::add_additional_fields_to_object( $prepared, $request );

		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		if ( 'edit' === $context && isset( $prepared['content']['raw'] ) ) {
			$post = get_post();
			if ( Sensei()->lesson::lesson_quiz_has_questions( $post->ID ) && ! has_block( 'sensei-lms/quiz' ) ) {
				$prepared['content']['raw'] .= serialize_block(
					array(
						'blockName'    => 'sensei-lms/quiz',
						'innerContent' => array(),
						'attrs'        => array(),
					)
				);
			}
		}

		return $prepared;
	}
}
