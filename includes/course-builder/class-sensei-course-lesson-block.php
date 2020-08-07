<?php
/**
 * File containing the class Sensei_Course_Lesson_Block.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Sensei_Course_Lesson_Block
 */
class Sensei_Course_Lesson_Block {
	/**
	 * Register lesson block.
	 */
	public function register_block_type() {
		register_block_type(
			'sensei-lms/course-lesson',
			array(
				'render_callback' => array( $this, 'render' ),
				'editor_script'   => 'sensei-course-builder',
				'attributes'      => [
					'lesson_id' => [
						'type' => 'int'
					]
				],
				'supports'        => [],
			)
		);
	}

	public function render( $attributes, $content ) {

	}
}