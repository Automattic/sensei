<?php
/**
 * File containing the Course_Title class.
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
 * Display the title of the current course for the current lesson/quiz/module.
 */
class Course_Title {
	/**
	 * Block JSON file.
	 */
	const BLOCK_JSON_FILE = '/lesson-blocks/course-title.block.json';

	/**
	 * Course_Title constructor.
	 */
	public function __construct() {
		$block_json_path = Sensei()->assets->src_path( 'course-theme/blocks' ) . self::BLOCK_JSON_FILE;
		Sensei_Blocks::register_sensei_block(
			'sensei-lms/course-title',
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
	public function render( array $attributes = [] ): string {
		$course_id = \Sensei_Utils::get_current_course();

		if ( ! $course_id ) {
			return '';
		}

		$title = get_the_title( $course_id );

		if ( ! $title ) {
			return '';
		}

		$tag_name   = 'div';
		$class_name = 'wp-block-sensei-lms-course-title';

		// Translators: placeholder is the course title.
		$label = sprintf( __( '%s: back to course main page', 'sensei-lms' ), $title );

		$title_link = sprintf( '<a href="%1$s" class="%2$s__link" aria-label="%4$s">%3$s</a>', get_the_permalink( $course_id ), $class_name, $title, esc_attr( $label ) );

		$wrapper_attributes = get_block_wrapper_attributes( [ 'class' => $class_name ] );

		return sprintf(
			'<%1$s %2$s>%3$s</%1$s>',
			$tag_name,
			$wrapper_attributes,
			$title_link
		);
	}
}
