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
	public static function render_svg_icon_library() {
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
	 * @access private
	 *
	 * @param array $outline Outline block attributes and inner blocks.
	 *
	 * @return string Block HTML.
	 */
	public static function render_course_outline_block( $outline ) {

		$attributes = $outline['attributes'];
		$blocks     = $outline['blocks'];
		$post_id    = $outline['post_id'];

		$class_name = Sensei_Block_Helpers::block_class_with_default_style( $attributes );
		$css        = Sensei_Block_Helpers::build_styles(
			[
				'attributes' => $attributes,
			]
		);

		$notice = '';

		if ( ! empty( $attributes['preview_drafts'] ) ) {
			$notice = '<div class="sensei-message info">' . esc_html__( 'One or more lessons in this course are not published. Unpublished lessons and empty modules are only displayed in preview mode and will not be displayed to learners.', 'sensei-lms' ) . '</div>';
		}

		$icons = self::render_svg_icon_library();

		return '
			' . ( ! empty( $blocks ) ? $icons : '' ) . '
			' . $notice . '
			<section ' . Sensei_Block_Helpers::render_style_attributes( [ 'wp-block-sensei-lms-course-outline', $class_name ], $css ) . '>
				' .
			implode(
				'',
				array_map(
					function( $block ) use ( $post_id, $attributes ) {
						if ( 'module' === $block['type'] ) {
							return Sensei_Course_Outline_Module_Block::render_module_block( $block, $post_id, $attributes );
						}

						if ( 'lesson' === $block['type'] ) {
							return Sensei_Course_Outline_Lesson_Block::render_lesson_block( $block );
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
