<?php
/**
 * File containing the Lesson_Module class.
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
 * Display the title of the current module for the current lesson.
 */
class Lesson_Module {

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
	const DEFAULT_HTML_TAG_NAME = 'em';

	/**
	 * Lesson_Module constructor.
	 */
	public function __construct() {
		Sensei_Blocks::register_sensei_block(
			'sensei-lms/course-theme-lesson-module',
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

		$module_term = \Sensei()->modules->get_lesson_module( $lesson_id );
		if ( ! $module_term ) {
			return '';
		}

		$title = $module_term->name;
		if ( ! $title ) {
			return '';
		}

		// Determine the output tag.
		$tag_name = self::DEFAULT_HTML_TAG_NAME;
		if ( isset( $attributes['tagName'] ) && in_array( $attributes['tagName'], self::ALLOWED_HTML_TAG_NAMES, true ) ) {
			$tag_name = $attributes['tagName'];
		}

		// Determine the output class.
		$class = 'sensei-course-theme-lesson-module';
		if ( isset( $attributes['className'] ) ) {
			$class = sanitize_html_class( $attributes['className'], $class );
		}

		return "<{$tag_name} class='{$class}'>{$title}</{$tag_name}>";
	}
}
