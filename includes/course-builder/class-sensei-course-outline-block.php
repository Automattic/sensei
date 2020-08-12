<?php
/**
 * File containing the class Sensei_Course_Outline_Block.
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
	 * Register course outline block.
	 */
	public function register_block_type() {
		register_block_type(
			'sensei-lms/course-outline',
			[
				'render_callback' => [ $this, 'render' ],
				'editor_script'   => 'sensei-course-builder',
				'attributes'      => [
					'course_id' => [
						'type' => 'int',
					],
				],
				'supports'        => [],
			]
		);
	}

	/**
	 * Render course outline block.
	 *
	 * @param array  $attributes
	 * @param string $content
	 *
	 * @return string
	 */
	public function render( $attributes, $content ) {
		// If we need something about the $attributes, or the $content saved, we can use here.
		global $post;
		return '<div id="course-outline-block" data-id="' . $post->ID . '">Loading page!</div>';
	}
}
