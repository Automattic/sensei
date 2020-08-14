<?php
/**
 * File containing the class Sensei_Course_Outline.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Sensei_Course_Outline
 */
class Sensei_Course_Outline {

	/**
	 * Sensei_Course_Outline constructor.
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'register_blocks' ] );
		add_action( 'rest_api_init', [ $this, 'register_rest' ] );
	}

	/**
	 * Register blocks.
	 */
	public function register_blocks() {
		Sensei()->assets->register( 'sensei-course-builder', 'course-builder/index.js', [], true );

		$blocks = [
			new Sensei_Course_Outline_Block(),
			new Sensei_Course_Lesson_Block(),
		];
		foreach ( $blocks as $block ) {
			$block->register_block_type();
		}

	}

	/**
	 * Register REST endpoints.
	 */
	public function register_rest() {

		register_rest_route(
			'sensei-internal/v1/course-builder',
			'course-lessons/(?P<course_id>\d+)',
			array(
				array(
					'methods'  => WP_REST_Server::READABLE,
					'callback' => array( $this, 'request_get_course_lessons' ),
				),
				array(
					'methods'  => WP_REST_Server::EDITABLE,
					'callback' => array( $this, 'request_update_course_lessons' ),
				),
			)
		);
	}

	/**
	 * Get lessons for course in correct order.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return array[]
	 */
	public function request_get_course_lessons( WP_REST_Request $request ) {

		$course_id = intval( $request->get_param( 'course_id' ) );

		$query = $this->course_lessons( $course_id );
		return array_map(
			function( $post ) {
				return [
					'id'        => $post->ID,
					'title'     => $post->post_title,
					'status'    => $post->post_status,
					'permalink' => get_permalink( $post->ID ),
				];
			},
			$query->posts
		);
	}


	/**
	 * Update course lessons and order based on blocks.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return mixed
	 */
	public function request_update_course_lessons( WP_REST_Request $request ) {
		$data      = $request->get_json_params();
		$lessons   = $data['lessons'];
		$course_id = $request->get_param( 'course_id' );

		foreach ( $lessons as $index => $lesson ) {
			if ( ! $lesson['id'] && $lesson['title'] ) {
				$lessons[ $index ]['id'] = wp_insert_post(
					[
						'post_type'  => 'lesson',
						'post_title' => $lesson['title'],
						'meta_input' => [
							'_lesson_course' => $course_id,
						],
					]
				);
			} else {
				wp_update_post(
					[
						'ID'         => $lesson['id'],
						'post_title' => $lesson['title'],
					]
				);
			}
		}

		$lesson_order = implode( ',', array_column( $lessons, 'id' ) );

		update_post_meta( $course_id, '_lesson_order', $lesson_order );

		return $lessons;
	}

	/**
	 * @param int $course_id
	 *
	 * @return WP_Query
	 */
	public function course_lessons( int $course_id ): WP_Query {
		$course_lesson_query_args = array(
			'post_type'        => 'lesson',
			'post_status'      => 'any',
			'posts_per_page'   => 500,
			'orderby'          => 'date',
			'order'            => 'ASC',
			'meta_query'       => array(
				array(
					'key'   => '_lesson_course',
					'value' => $course_id,
				),
			),
			'suppress_filters' => 0,
		);

		// setting lesson order.
		$course_lesson_order = get_post_meta( $course_id, '_lesson_order', true );
		$all_ids             = get_posts(
			array(
				'post_type'      => 'lesson',
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_key'       => '_lesson_course',
				'meta_value'     => intval( $course_id ),
			)
		);
		if ( ! empty( $course_lesson_order ) ) {
			$course_lesson_query_args['post__in'] = array_merge( explode( ',', $course_lesson_order ), $all_ids );
			$course_lesson_query_args['orderby']  = 'post__in';
			unset( $course_lesson_query_args['order'] );

		}
		return new WP_Query( $course_lesson_query_args );
}
}
