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

use \Sensei_Blocks;

/**
 * Display the title of the current lesson for the current post.
 */
class Post_Title {

	/**
	 * Allowed HTML wrapper tag names for this block.
	 *
	 * @var array
	 */
	const ALLOWED_HTML_TAG_NAMES = [ 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'div', 'span' ];

	/**
	 * The default HTML tag name.
	 *
	 * @var string
	 */
	const DEFAULT_HTML_TAG_NAME = 'h1';

	/**
	 * Post_Title constructor.
	 */
	public function __construct() {
		Sensei_Blocks::register_sensei_block(
			'sensei-lms/course-theme-post-title',
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
		$post_id = get_the_ID();

		if ( ! $post_id ) {
			return '';
		}

		$title = get_the_title( $post_id );

		if ( ! $title ) {
			return '';
		}

		// Determine the output tag.
		$tag_name = self::DEFAULT_HTML_TAG_NAME;
		if ( isset( $attributes['tagName'] ) && in_array( $attributes['tagName'], self::ALLOWED_HTML_TAG_NAMES, true ) ) {
			$tag_name = $attributes['tagName'];
		}

		// Determine the output class.
		$class     = 'sensei-course-theme-post-title';
		$post_type = get_post_type( $post_id );
		if ( 'quiz' === $post_type ) {
			$class = 'sensei-course-theme-quiz-title';
		}
		if ( isset( $attributes['className'] ) ) {
			$class = sanitize_html_class( $attributes['className'], $class );
		}

		return "<{$tag_name} class='{$class}'>{$title}</{$tag_name}>";
	}
}
