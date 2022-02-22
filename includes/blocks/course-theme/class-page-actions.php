<?php
/**
 * File containing the Page_Actions class.
 *
 * @package sensei
 * @since   4.0.0
 */

namespace Sensei\Blocks\Course_Theme;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use \Sensei_Blocks;

/**
 * Display lesson or quiz pagination.
 */
class Page_Actions {
	/**
	 * Exit_Course constructor.
	 */
	public function __construct() {
		Sensei_Blocks::register_sensei_block(
			'sensei-lms/page-actions',
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

		switch ( get_post_type() ) {
			case 'lesson':
				return $this->render_lesson_actions();
			case 'quiz':
				return do_blocks( '<!-- wp:sensei-lms/quiz-actions /-->' );
		}

		return '';
	}

	/**
	 * Render the Lesson actions.
	 *
	 * @return string
	 */
	private function render_lesson_actions() {
		// WordPress post pagination.
		$actions = wp_link_pages(
			[
				'echo'   => false,
				'before' => '<div class="wp-block-sensei-lms-page-actions sensei-course-theme__post-pagination">',
				'after'  => '</div>',
			]
		);

		// Prev and next navigation, and lesson actions.
		$actions = $actions .
			'<div class="screen-reader-text">' .
				do_blocks( '<!-- wp:sensei-lms/course-theme-prev-next-lesson /-->' ) .
				do_blocks( '<!-- wp:sensei-lms/course-theme-lesson-actions /-->' ) .
			'</div>';

		return $actions;
	}
}
