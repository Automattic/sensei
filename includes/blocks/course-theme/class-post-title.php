<?php
/**
 * File containing the Post_Title class.
 *
 * @package sensei
 * @since
 */

namespace Sensei\Blocks\Course_Theme;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Display the title of the current lesson for the current post.
 */
class Post_Title {

	/**
	 * Post_Title constructor.
	 */
	public function __construct() {
		if ( ! \WP_Block_Type_Registry::get_instance()->is_registered( 'core/post-title' ) ) {
			register_block_type(
				'core/post-title',
				[
					'render_callback' => [ $this, 'render_title_block' ],
					'style'           => 'sensei-theme-blocks',
				]
			);
		}
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
	public function render_title_block( array $attributes = [] ): string {

		$title = get_the_title();

		if ( ! $title ) {
			return '';
		}

		return sprintf(
			'<h1 class="wp-block-post-title">%1$s</h1>',
			$title
		);
	}
}
