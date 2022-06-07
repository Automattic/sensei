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

		add_filter( 'rest_lesson_query', array( $this, 'exclude_others_lessons' ), 10, 2 );

		$this->init_post_meta();
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
			[
				'show_in_rest'      => true,
				'single'            => true,
				'type'              => 'boolean',
				'sanitize_callback' => function( $value ) {
					return $value ? 1 : 0;
				},
				'auth_callback'     => [ $this, 'auth_callback' ],
			]
		);

		register_post_meta(
			'lesson',
			'_lesson_complexity',
			[
				'show_in_rest'      => true,
				'single'            => true,
				'type'              => 'string',
				'default'           => 'easy',
				'sanitize_callback' => function( $value ) {
					if ( '' === $value ) {
						return $value;
					}

					return array_key_exists( $value, Sensei()->lesson->lesson_complexities() ) ? $value : 'easy';
				},
				'auth_callback'     => [ $this, 'auth_callback' ],
			]
		);

		register_post_meta(
			'lesson',
			'_lesson_length',
			[
				'show_in_rest'      => true,
				'single'            => true,
				'type'              => 'integer',
				'default'           => 10,
				'sanitize_callback' => function( $value ) {
					return absint( $value );
				},
				'auth_callback'     => [ $this, 'auth_callback' ],
			]
		);

		register_post_meta(
			'lesson',
			'_lesson_course',
			[
				'show_in_rest'  => true,
				'single'        => true,
				'type'          => 'integer',
				'auth_callback' => [ $this, 'auth_callback' ],
			]
		);
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
		global $pagenow;

		if ( ! Sensei()->quiz->is_block_based_editor_enabled() || 'post-new.php' === $pagenow ) {
			return $prepared;
		}

		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		if ( 'edit' === $context && isset( $prepared['content']['raw'] ) ) {
			$post = get_post();
			if ( ! has_block( 'sensei-lms/quiz' ) ) {
				$prepared['content']['raw'] .= serialize_block(
					[
						'blockName'    => 'sensei-lms/quiz',
						'innerContent' => [],
						'attrs'        => [],
					]
				);
			}
		}

		return $prepared;
	}

	/**
	 * Checks if the logged-in user should have access to a specific lesson.
	 *
	 * @since $$next-version$$
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return true|WP_Error True if the request has read access, WP_Error object otherwise.
	 */
	public function get_item_permissions_check( $request ) {
		$check_result = parent::get_item_permissions_check( $request );

		if ( true !== $check_result ) {
			return $check_result;
		}

		if ( current_user_can( 'manage_sensei' ) || current_user_can( 'manage_options' ) ) {
			return true;
		}

		$lesson = get_post( $request['id'] );

		if ( get_current_user_id() === (int) $lesson->post_author ) {
			return true;
		}

		$course_id = Sensei()->lesson->get_course_id( $request['id'] );

		if ( empty( $course_id ) ) {
			return new WP_Error(
				'rest_forbidden_context',
				__( 'Sorry, you are not allowed to edit this post.', 'sensei-lms' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return Sensei()->course::is_user_enrolled( $course_id );
	}

	/**
	 * Modifies the query for teachers so only their own lessons are returned.
	 *
	 * @access private
	 *
	 * @param array           $args    The query args.
	 * @param WP_REST_Request $request The current REST request.
	 *
	 * @return array The modified query args.
	 */
	public function exclude_others_lessons( array $args, WP_REST_Request $request ) : array {
		if ( current_user_can( 'manage_sensei' ) || current_user_can( 'manage_options' ) ) {
			return $args;
		}

		$current_user   = wp_get_current_user();
		$args['author'] = $current_user->ID ?? - 1;

		return $args;
	}
}
