<?php
/**
 * File containing the Sensei_Lesson_Completed_Block class.
 *
 * @package sensei
 * @since 4.19.2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Sensei_Lesson_Completed_Block is responsible for rendering the 'Lesson Completed' block.
 */
class Sensei_Lesson_Completed_Block {

	/**
	 * Sensei_Lesson_Completed_Block constructor.
	 */
	public function __construct() {
		Sensei_Blocks::register_sensei_block(
			'sensei-lms/button-lesson-completed',
			[
				'render_callback' => [ $this, 'render' ],
			]
		);
	}

	/**
	 * Renders the block.
	 *
	 * @param array  $attributes The block attributes.
	 * @param string $content    The block content.
	 *
	 * @access private
	 *
	 * @return string The block HTML.
	 */
	public function render( array $attributes, string $content ) : string {
		$lesson = get_post();

		if ( ! is_a( $lesson, WP_Post::class ) || ! Sensei_Utils::user_completed_lesson( $lesson->ID ) ) {
			return '';
		}

		return $content;
	}
}
