<?php
/**
 * File containing the Sensei_Course_Outline_Block class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Sensei_Course_Outline_Block
 */
class Sensei_Course_Outline_Block {
	/**
	 * Sensei_Course_Outline_Block constructor.
	 */
	public function __construct() {
		add_action( 'enqueue_block_assets', [ $this, 'enqueue_assets' ] );
		add_action( 'init', [ $this, 'register_course_template' ], 101 );
		add_action( 'save_post_course', [ $this, 'save_course' ], 10, 3 );
	}

	/**
	 * Enqueue frontend and editor assets.
	 *
	 * @access private
	 */
	public function enqueue_assets() {
		if ( 'course' !== get_post_type() ) {
			return;
		}

		Sensei()->assets->enqueue( 'sensei-course-outline-script', 'blocks/course-outline/index.js' );
		Sensei()->assets->enqueue( 'sensei-course-outline-style', 'blocks/course-outline/style.css' );
	}

	/**
	 * Register course template.
	 *
	 * @access private
	 */
	public function register_course_template() {
		$post_type_object = get_post_type_object( 'course' );

		$post_type_object->template = [
			[ 'sensei-lms/course-outline' ],
		];
	}

	/**
	 * Save course.
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    The post.
	 * @param bool     $update  Whether this is an existing post being updated.
	 */
	public function save_course( $post_id, $post, $update ) {
		$content = $post->post_content;
		$blocks  = parse_blocks( $content );

		// TODO: Get the attributes and save the data.
	}
}
