<?php
/**
 * File containing the Lesson_Title class.
 *
 * @package sensei
 * @since
 */

namespace Sensei\Blocks\Course_Theme;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use \Sensei_Blocks;

/**
 * Display the title of the current lesson for the current lesson/quiz.
 */
class Lesson_Title {

	/**
	 * Lesson_Title constructor.
	 */
	public function __construct() {
		Sensei_Blocks::register_sensei_block(
			'sensei-lms/course-theme-lesson-title',
			[
				'render_callback' => [ $this, 'render' ],
			]
		);
	}

	/**
	 * Renders the block.
	 *
	 * @param array $attributes The block attributes.
	 *
	 * @access private
	 *
	 * @return string The block HTML.
	 */
	public function render( array $attributes = [] ): string {
		$lesson_id = \Sensei_Utils::get_current_lesson();

		if ( ! $lesson_id ) {
			return '';
		}

		$title = get_the_title( $lesson_id );

		if ( ! $title ) {
			return '';
		}

		return "<h1 class='sensei-course-theme-lesson-title'>{$title}</h1>";
	}
}
