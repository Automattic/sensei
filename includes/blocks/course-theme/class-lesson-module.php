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
	 * Block JSON file.
	 */
	const BLOCK_JSON_FILE = '/lesson-blocks/module-title/block.json';

	/**
	 * Lesson_Module constructor.
	 */
	public function __construct() {
		$block_json_path = Sensei()->assets->src_path( 'course-theme/blocks' ) . self::BLOCK_JSON_FILE;

		Sensei_Blocks::register_sensei_block(
			'sensei-lms/course-theme-lesson-module',
			[
				'render_callback' => [ $this, 'render' ],
				'style'           => 'sensei-theme-blocks',
			],
			$block_json_path
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
	public function render( array $attributes = array() ): string {
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

		$wrapper_attr = get_block_wrapper_attributes();

		return sprintf( '<%1$s %2$s>%3$s</%1$s>', 'h3', $wrapper_attr, $title );
	}
}
