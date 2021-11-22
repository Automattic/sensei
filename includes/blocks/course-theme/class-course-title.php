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
	 * Course_Title constructor.
	 */
	public function __construct() {
		Sensei_Blocks::register_sensei_block(
			'sensei-lms/course-title',
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
		$course_id = \Sensei_Utils::get_current_course();

		if ( ! $course_id ) {
			return '';
		}

		$title = get_the_title( $course_id );

		if ( ! $title ) {
			return '';
		}

		$tag_name   = 'h2';
		$class_name = 'wp-block-sensei-lms-course-title';

		if ( isset( $attributes['level'] ) ) {
			$tag_name = 0 === $attributes['level'] ? 'p' : 'h' . $attributes['level'];
		}

		$title = sprintf( '<a href="%1$s" class="%2$s__link">%3$s</a>', get_the_permalink( $course_id ), $class_name, $title );

		$wrapper_attributes = sprintf( ' class="%s"', $class_name );
		if ( function_exists( 'get_block_wrapper_attributes' ) ) {
			$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => $class_name ) );
		}

		return sprintf(
			'<%1$s %2$s>%3$s</%1$s>',
			$tag_name,
			$wrapper_attributes,
			$title
		);
	}
}
