<?php
/**
 * File containing the Sensei_Course_Outline_Lesson_Block class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Lesson block of the Course Outline block.
 */
class Sensei_Course_Outline_Lesson_Block {

	/**
	 * Get lesson block HTML.
	 *
	 * @param array $block Block information.
	 *
	 * @return string Lesson HTML
	 */
	public function render_lesson_block( $block ) {
		$lesson_id = $block['id'];
		$classes   = [ 'wp-block-sensei-lms-course-outline-lesson' ];

		$completed = Sensei_Utils::user_completed_lesson( $lesson_id, get_current_user_id() );

		if ( $completed ) {
			$classes[] = 'completed';
		}

		$css = Sensei_Block_Helpers::build_styles( $block['attributes'] ?? [] );

		$draft = ( $block['draft'] ? '<em>' . esc_html__( '(Draft)', 'sensei-lms' ) . '</em>' : '' );

		return '
			<a href="' . esc_url( get_permalink( $lesson_id ) ) . '" ' . Sensei_Block_Helpers::render_style_attributes( $classes, $css ) . '>
				<svg class="wp-block-sensei-lms-course-outline-lesson__status">
					' . ( $completed ? '<use xlink:href="#sensei-checked"></use>' : '' ) . '
				</svg>
				<span>
					' . esc_html( $block['title'] ) . '
					' . $draft . '
				</span>
				<svg class="wp-block-sensei-lms-course-outline-lesson__chevron"><use xlink:href="#sensei-chevron-right"></use></svg>
			</a>
		';
	}

}
