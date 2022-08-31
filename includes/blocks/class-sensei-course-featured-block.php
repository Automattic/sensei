<?php
/**
 * File containing the Sensei_Course_Featured_Block class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Sensei_Course_Featured_Block
 */
class Sensei_Course_Featured_Block {



	/**
	 * Rendered HTML output for the block.
	 *
	 * @var string
	 */
	private $block_content;

	/**
	 * Sensei_Course_Categories_Block constructor.
	 */
	public function __construct() {
		$this->register_block();
	}

	/**
	 * Register course results block.
	 */
	private function register_block() {
		Sensei_Blocks::register_sensei_block(
			'sensei-lms/course-featured',
			[
				'render_callback' => [ $this, 'render_block' ],
			],
			Sensei()->assets->src_path( 'blocks/course-featured-block' )
		);
	}

	private function is_course_featured( $post_id ) {
		// Check if the course is featured.
		$course_featured = get_post_meta( $post_id, '_course_featured', true );
		return 'featured' === $course_featured;
	}

	/**
	 * Render the Course Categories block.
	 *
	 * @param Object   $attributes The block's attributes.
	 * @param string   $content    The block's content.
	 * @param WP_Block $block      The block instance.
	 * @return string
	 */
	public function render_block( $attributes, $content, WP_Block $block ): string {

		if ( ! isset( $block->context['postId'] ) ) {
			return '';
		}

		$post_id = $block->context['postId'];

		if ( 'course' !== get_post_type( $post_id ) ) {
			return '';
		}

		if ( $this->is_course_featured( $post_id ) ) {
			 return '<div class="class-course-featured"></div>';
		}
		return '';
	}
}
