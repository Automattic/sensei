<?php
/**
 * File containing the Sensei\Blocks\Course_Theme\Course_Content class.
 *
 * @package sensei
 * @since 4.0.0
 */

namespace Sensei\Blocks\Course_Theme;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use \Sensei_Blocks;

/**
 * Block to render the content for the current lesson or quiz page.
 */
class Course_Content {

	/**
	 * Content constructor.
	 */
	public function __construct() {
		Sensei_Blocks::register_sensei_block(
			'sensei-lms/course-content',
			[
				'render_callback' => [ $this, 'render_content' ],
			]
		);
	}

	/**
	 * Render content for the current page.
	 *
	 * @access private
	 *
	 * @return string HTML
	 */
	public function render_content() {
		$type = get_post_type();

		$content = '';

		switch ( $type ) {
			case 'quiz':
				$content = Quiz_Content::render_quiz();
				break;
			case 'lesson':
				$content = $this->render_lesson_content();
				break;
		}

		$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => 'entry-content' ) );

		return (
			'<div ' . $wrapper_attributes . '>' .
			$content .
			'</div>'
		);

	}

	/**
	 * Render the current lesson page's content.
	 *
	 * @return false|string
	 */
	private function render_lesson_content() {

		if ( ! in_the_loop() && have_posts() ) {
			the_post();
		}

		ob_start();

		if ( sensei_can_user_view_lesson() ) {
			the_content();
		} else {
			the_excerpt();
		}

		return ob_get_clean();
	}

}
