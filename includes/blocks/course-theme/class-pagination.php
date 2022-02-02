<?php
/**
 * File containing the Pagination class.
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
class Pagination {
	/**
	 * Exit_Course constructor.
	 */
	public function __construct() {
		Sensei_Blocks::register_sensei_block(
			'sensei-lms/pagination',
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
				return $this->render_post_pagination();
			case 'quiz':
				return do_blocks( '<!-- wp:sensei-lms/quiz-actions /-->' );
		}

		return '';
	}

	/**
	 * Render the WordPress post pagination.
	 *
	 * @return string
	 */
	private function render_post_pagination() {

		return wp_link_pages( [ 'echo' => false ] );
	}
}
