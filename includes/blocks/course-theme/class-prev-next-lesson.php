<?php
/**
 * File containing the Prev_Next_Lesson class.
 *
 * @package sensei
 * @since   3.13.4
 */

namespace Sensei\Blocks\Course_Theme;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use \Sensei_Blocks;

/**
 * Class Prev_Next_Lesson is responsible for rendering the '< Prev Lesson | Next Lesson >' blocks.
 */
class Prev_Next_Lesson {
	/**
	 * Prev_Next_Lesson constructor.
	 */
	public function __construct() {
		Sensei_Blocks::register_sensei_block(
			'sensei-lms/course-theme-prev-next-lesson',
			[
				'render_callback' => [ $this, 'render' ],
			]
		);
	}

	/**
	 * Renders the block.
	 *
	 * @param array  $attributes The attributes that were saved for this block.
	 * @param string $content    The content that is rendered by the inner blocks.
	 *
	 * @return string The block HTML.
	 */
	public function render( array $attributes, string $content ): string {
		return do_blocks( '<!-- wp:sensei-lms/course-theme-prev-lesson /--><!-- wp:sensei-lms/course-theme-next-lesson /-->' );
	}
}
