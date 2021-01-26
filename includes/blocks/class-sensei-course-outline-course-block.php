<?php
/**
 * File containing the Sensei_Course_Outline_Course_Block class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Top-level block of the Course Outline block.
 */
class Sensei_Course_Outline_Course_Block {


	/**
	 * Build HTML to reference SVG icons from.
	 *
	 * @return string
	 */
	public function render_svg_icon_library() {
		return '<svg xmlns="http://www.w3.org/2000/svg" style="display: none">
			<symbol id="sensei-chevron-right" viewBox="0 0 24 24">
				<path d="M10.6 6L9.4 7l4.6 5-4.6 5 1.2 1 5.4-6z" fill="" />
			</symbol>
			<symbol id="sensei-chevron-up" viewBox="0 0 24 24">
				<path d="M6.5 12.4L12 8l5.5 4.4-.9 1.2L12 10l-4.5 3.6-1-1.2z" fill="" />
			</symbol>
			<symbol id="sensei-checked" viewBox="0 0 24 24">
				<path d="M9 18.6L3.5 13l1-1L9 16.4l9.5-9.9 1 1z" fill="" />
			</symbol>
		</svg>';
	}

	/**
	 * Render Course Outline block.
	 *
	 * @param array $outline Outline block attributes and inner blocks.
	 *
	 * @return string Block HTML.
	 */
	public function render_course_outline_block( $outline ) {
		if ( empty( $outline ) ) {
			return '';
		}

		$attributes = $outline['attributes'];
		$blocks     = $outline['blocks'];
		$post_id    = $outline['post_id'];

		if ( empty( $blocks ) ) {
			Sensei()->notices->add_notice( __( 'There is no published content in this course yet.', 'sensei-lms' ), 'info', 'sensei-course-outline-no-content' );
			return '';
		}

		$class_name = Sensei_Block_Helpers::block_class_with_default_style( $attributes );
		$css        = Sensei_Block_Helpers::build_styles( $attributes );

		if ( ! empty( $attributes['preview_drafts'] ) ) {
			Sensei()->notices->add_notice( __( 'One or more lessons in this course are not published. Unpublished lessons and empty modules are only displayed in preview mode and will not be displayed to learners.', 'sensei-lms' ), 'info', 'sensei-course-outline-drafts' );
		}

		$icons = $this->render_svg_icon_library();

		return '
			' . ( ! empty( $blocks ) ? $icons : '' ) . '
			<section ' . Sensei_Block_Helpers::render_style_attributes( [ 'wp-block-sensei-lms-course-outline', 'sensei-block-wrapper', $class_name ], $css ) . '>
				' .
			implode(
				'',
				array_map(
					function( $block ) use ( $post_id, $attributes ) {
						if ( 'module' === $block['type'] ) {
							return Sensei()->blocks->course->outline->module->render_module_block( $block, $post_id, $attributes );
						}

						if ( 'lesson' === $block['type'] ) {
							return Sensei()->blocks->course->outline->lesson->render_lesson_block( $block, $post_id );
						}
					},
					$blocks
				)
			)
			. '
			</section>
		';
	}

}
