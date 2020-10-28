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
	 * @param array $block     Block information.
	 * @param int   $course_id The course id.
	 *
	 * @return string Lesson HTML
	 */
	public function render_lesson_block( $block, $course_id ) {
		$lesson_id = $block['id'];
		$classes   = [ 'wp-block-sensei-lms-course-outline-lesson' ];

		$completed = Sensei_Utils::user_completed_lesson( $lesson_id, get_current_user_id() );

		if ( $completed ) {
			$classes[] = 'completed';
		}

		$css           = Sensei_Block_Helpers::build_styles( $block['attributes'] ?? [], [], [ 'fontSize' => 'font-size' ] );
		$preview_badge = '';

		if ( isset( $block['preview'] ) && true === $block['preview'] && ! Sensei_Course::is_user_enrolled( $course_id ) ) {
			$preview_badge = '
				<span class="wp-block-sensei-lms-course-outline-lesson__badge">
					' . esc_html__( 'Preview', 'sensei-lms' ) . '
				</span>
			';
		}

		$draft = ( ! empty( $block['draft'] ) ? '<em>' . esc_html__( '(Draft)', 'sensei-lms' ) . '</em>' : '' );

		return '
			<a href="' . esc_url( get_permalink( $lesson_id ) ) . '" ' . Sensei_Block_Helpers::render_style_attributes( $classes, $css ) . '>
				<svg class="wp-block-sensei-lms-course-outline-lesson__status">
					' . ( $completed ? '<use xlink:href="#sensei-checked"></use>' : '' ) . '
				</svg>
				<span>
					' . esc_html( $block['title'] ) . '
					' . $draft . '
				</span>
				' . $preview_badge . '
				<svg class="wp-block-sensei-lms-course-outline-lesson__chevron"><use xlink:href="#sensei-chevron-right"></use></svg>
			</a>
		';
	}

}
