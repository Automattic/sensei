<?php
/**
 * Sensei REST API: Sensei_REST_API_Lessons_Controller class.
 *
 * @package sensei-lms
 * @since 3.9.0
 */

use Sensei\Admin\Content_Duplicators\Lesson_Quiz_Duplicator;
use Sensei\Admin\Content_Duplicators\Post_Duplicator;

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

		register_post_meta(
			'lesson',
			'_lesson_preview',
			[
				'show_in_rest'  => true,
				'single'        => true,
				'type'          => 'string',
				'auth_callback' => [ $this, 'auth_callback' ],
			]
		);

		// add a route to duplicate a lesson and attach it to a course.
		register_rest_route(
			'sensei/v1',
			'/lessons/duplicate',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'duplicate_lesson' ],
				'permission_callback' => [ $this, 'duplicate_lesson_permissions_check' ],
				'args'                => [
					'lesson_ids' => [
						'description' => __( 'The IDs of lessons to duplicate.', 'sensei-lms' ),
						'type'        => 'integer',
					],
					'course_id'  => [
						'description' => __( 'The ID of the course to attach the duplicated lesson to.', 'sensei-lms' ),
						'type'        => 'integer',
					],
				],
			]
		);
	}

	/**
	 * Duplicate a lesson and attach it to a course.
	 *
	 * @since $$next-version$$
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function duplicate_lesson( $request ) {
		$lesson_ids = $request->get_param( 'lesson_ids' );
		$course_id  = $request->get_param( 'course_id' );

		$response = new WP_REST_Response();
		if ( empty( $lesson_ids ) ) {
			$response->set_data( [] );

			return $response;
		}

		$lessons = get_posts(
			[
				'post__in'       => $lesson_ids,
				'post_type'      => 'question',
				'posts_per_page' => -1,
			]
		);
		$course  = get_post( $course_id );

		if ( empty( $lesson ) || ! $course ) {
			return new WP_Error( 'sensei_rest_invalid_id', __( 'Invalid ID(s) provided.', 'sensei-lms' ), [ 'status' => 404 ] );
		}

		$post_duplicator        = new Post_Duplicator();
		$lesson_quiz_duplicator = new Lesson_Quiz_Duplicator();

		$duplicated_lessons = array();
		foreach ( $lessons as $lesson ) {
			$duplicated_lesson = $post_duplicator->duplicate( $lesson, null, true );

			$lesson_quiz_duplicator->duplicate( $lesson->ID, $duplicated_lesson->ID );
			update_post_meta( $duplicated_lesson->ID, '_lesson_course', $course_id );

			$duplicated_lessons[] = $duplicated_lesson;
		}

		$response->set_data( $duplicated_lessons );

		return $response;
	}

	/**
	 * Check if the current user can duplicate a lesson.
	 *
	 * @since $$next-version$$
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool Whether the user can duplicate the lesson.
	 */
	public function duplicate_lesson_permissions_check( $request ): bool {
		$lesson_id = $request->get_param( 'id' );
		$course_id = $request->get_param( 'course_id' );

		if ( ! $lesson_id || ! $course_id ) {
			return false;
		}

		$lesson = get_post( $lesson_id );
		$course = get_post( $course_id );

		if ( ! $lesson || ! $course ) {
			return false;
		}

		if ( ! current_user_can( 'edit_post', $lesson_id ) || ! current_user_can( 'edit_post', $course_id ) ) {
			return false;
		}

		return true;
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
}
