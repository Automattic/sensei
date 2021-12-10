<?php
/**
 * File containing the Module_Title class.
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
class Module_Title {

	/**
	 * Module_Title constructor.
	 */
	public function __construct() {
		Sensei_Blocks::register_sensei_block(
			'sensei-lms/course-theme-module-title',
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

		return "<h1 class='sensei-course-theme-module-title'>{$title}</h1>";
	}
}
