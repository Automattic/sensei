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
	 * REST API namespace.
	 *
	 * @var string
	 */
	private $namespace = 'sensei-internal/v1';

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

		register_rest_route(
			$this->namespace,
			'/lessons/prepare',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'prepare_lessons' ],
				'permission_callback' => [ $this, 'prepare_lessons_permissions_check' ],
				'args'                => [
					'lesson_ids' => [
						'description' => __( 'The IDs of lessons to duplicate.', 'sensei-lms' ),
						'type'        => 'array',
						'items'       => [
							'type' => 'integer',
						],
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
	 * Prepare lessons to attach to a course. This duplicates lessons if they are already related to a course.
	 *
	 * @since $$next-version$$
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function prepare_lessons( $request ) {
		$lesson_ids = $request->get_param( 'lesson_ids' );
		$course_id  = $request->get_param( 'course_id' );

		$response = new WP_REST_Response();
		if ( empty( $lesson_ids ) ) {
			$response->set_data( array() );

			return $response;
		}

		$lessons = get_posts(
			array(
				'post__in'       => (array) $lesson_ids,
				'post_type'      => 'lesson',
				'post_status'    => array( 'publish', 'draft' ),
				'posts_per_page' => -1,
			)
		);
		$course  = get_post( $course_id );

		if ( empty( $lessons ) || ! $course ) {
			return new WP_Error( 'sensei_rest_invalid_id', __( 'Invalid ID(s) provided.', 'sensei-lms' ), [ 'status' => 404 ] );
		}

		$post_duplicator        = new Post_Duplicator();
		$lesson_quiz_duplicator = new Lesson_Quiz_Duplicator();

		$duplicated_lessons = array();
		foreach ( $lessons as $lesson ) {
			$attached_course_id = get_post_meta( $lesson->ID, '_lesson_course', true );
			if ( $attached_course_id && $attached_course_id === $course_id ) {
				// lesson is already attached to the course.
				continue;
			}

			if ( $attached_course_id ) {
				// duplicate the lesson if it is already attached to another course.
				$add_lesson = $post_duplicator->duplicate( $lesson, null, true );

				$lesson_quiz_duplicator->duplicate( $lesson->ID, $add_lesson->ID );
			} else {
				$add_lesson = $lesson;
			}

			$duplicated_lessons[] = $add_lesson;
		}

		$response->set_data( $duplicated_lessons );

		return $response;
	}

	/**
	 * Check if the current user can prepare lessons to attach to a course.
	 *
	 * @since $$next-version$$
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool Whether the user can duplicate the lesson.
	 */
	public function prepare_lessons_permissions_check( $request ): bool {
		$lesson_ids = $request->get_param( 'lesson_ids' );
		$course_id  = $request->get_param( 'course_id' );

		if ( ! $lesson_ids || ! $course_id ) {
			return false;
		}

		$lessons = get_posts(
			array(
				'post__in'       => (array) $lesson_ids,
				'post_type'      => 'lesson',
				'post_status'    => array( 'publish', 'draft' ),
				'posts_per_page' => -1,
			)
		);
		$course  = get_post( $course_id );

		if ( ! $lessons || ! $course ) {
			return false;
		}

		foreach ( $lessons as $lesson ) {
			if ( ! current_user_can( 'edit_post', $lesson->ID ) ) {
				return false;
			}
		}

		if ( ! current_user_can( 'edit_post', $course_id ) ) {
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
