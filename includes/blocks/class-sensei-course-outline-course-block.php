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

	private function get_modules_html( $modules_blocks, $post_id, $attributes ) {
		return implode(
			'',
			array_map(
				function( $block ) use ( $post_id, $attributes ) {
					return Sensei()->blocks->course_outline->module->render_module_block( $block, $post_id, $attributes );
				},
				$modules_blocks
			)
		);
	}

	private function get_lessons_html( $lessons_blocks ) {
		$title = '';
		if ( ! empty ( $lessons_blocks ) ) {
			$title = '<h2 class="wp-block-sensei-lms-course-outline__lessons-title">' . esc_html__( 'Other lessons', 'sensei-lms' ) . '</h2>';
		}

		return $title . implode(
			'',
			array_map(
				function( $block ) {
					return Sensei()->blocks->course_outline->lesson->render_lesson_block( $block );
				},
				$lessons_blocks
			)
		);
	}

	/**
	 * Render Course Outline block.
	 *
	 * @param array $outline Outline block attributes and inner blocks.
	 *
	 * @return string Block HTML.
	 */
	public function render_course_outline_block( $outline ) {

		$attributes = $outline['attributes'];
		$blocks     = $outline['blocks'];
		$post_id    = $outline['post_id'];

		$class_name = Sensei_Block_Helpers::block_class_with_default_style( $attributes );
		$css        = Sensei_Block_Helpers::build_styles(
			[
				'attributes' => $attributes,
			]
		);

		$icons = $this->render_svg_icon_library();

		$modules_blocks = array_filter(
			$blocks,
			function( $block ) {
				return 'module' === $block['type'];
			}
		);
		$lessons_blocks = array_filter(
			$blocks,
			function( $block ) {
				return 'lesson' === $block['type'];
			}
		);

		return '
			' . ( ! empty( $blocks ) ? $icons : '' ) . '
			<section ' . Sensei_Block_Helpers::render_style_attributes( [ 'wp-block-sensei-lms-course-outline', $class_name ], $css ) . '>
				' .
				$this->get_modules_html( $modules_blocks, $post_id, $attributes ) .
				$this->get_lessons_html( $lessons_blocks ) . '
			</section>
		';
	}
}
