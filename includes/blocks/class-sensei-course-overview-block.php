<?php
/**
 * File containing the Sensei_Course_Overview_Block class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Sensei_Course_Overview_Block
 */
class Sensei_Course_Overview_Block {

	/**
	 * Sensei_Course_Overview_Block constructor.
	 */
	public function __construct() {
		Sensei_Blocks::register_sensei_block(
			'sensei-lms/course-overview',
			[
				'render_callback' => [ $this, 'render' ],
			],
			Sensei()->assets->src_path( 'blocks/course-overview-block' )
		);
	}

	/**
	 * Renders course overview block on the frontend.
	 *
	 * @param array  $attributes Block attributes.
	 * @param string $content    Inner block content.
	 *
	 * @return string HTML of the block.
	 */
	public function render( array $attributes, string $content ): string {
		$course_id = \Sensei_Utils::get_current_course();

		if ( ! $course_id ) {
			return '';
		}

		$wrapper_attributes = get_block_wrapper_attributes();

		return sprintf(
			'<div %1$s><a href="%2$s">%3$s</a></div>',
			$wrapper_attributes,
			esc_url( get_permalink( absint( $course_id ) ) ),
			__( 'Course Overview', 'sensei-lms' )
		);
	}
}
