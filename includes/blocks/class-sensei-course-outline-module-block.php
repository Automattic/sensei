<?php
/**
 * File containing the Sensei_Course_Outline_Module_Block class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Module block of the Course Outline block.
 */
class Sensei_Course_Outline_Module_Block {

	/**
	 * Get module block HTML.
	 *
	 * @param array $block              Module block attributes.
	 * @param int   $course_id          The course id.
	 * @param array $outline_attributes Outline block attributes.
	 *
	 * @return string Module HTML
	 */
	public function render_module_block( $block, $course_id, $outline_attributes ) {
		if ( empty( $block['lessons'] ) ) {
			return '';
		}

		$progress_indicator = $this->get_module_progress_indicator( $block['id'], $course_id );

		$class_name = Sensei_Block_Helpers::block_class_with_default_style( $block['attributes'] );

		$is_default_style = false !== strpos( $class_name, 'is-style-default' );
		$is_minimal_style = false !== strpos( $class_name, 'is-style-minimal' );

		$header_css = Sensei_Block_Helpers::build_styles(
			$block['attributes'],
			[
				'mainColor' => $is_default_style ? 'background-color' : null,
			]
		);

		$style_header = '';

		if ( $is_minimal_style ) {

			$header_border_css = Sensei_Block_Helpers::build_styles(
				$block['attributes'],
				[
					'mainColor' => 'background-color',
				]
			);

			$style_header = '<div ' . Sensei_Block_Helpers::render_style_attributes( 'wp-block-sensei-lms-course-outline-module__name__minimal-border', $header_border_css ) . '></div>';

		}

		$title       = esc_html( $block['title'] );
		$description = '';

		if ( ! empty( $block['description'] ) ) {
			$description = '<div class="wp-block-sensei-lms-course-outline-module__description">' . wp_kses_post( $block['description'] ) . '</div>';
			$module_link = get_term_link( $block['id'], Sensei()->modules->taxonomy );

			if ( ! is_wp_error( $module_link ) ) {
				$module_link = add_query_arg( 'course_id', $course_id, $module_link );
				$title       = '<a href="' . $module_link . '">' . $title . '</a>';
			}
		}

		return '
			<section class="wp-block-sensei-lms-course-outline-module ' . esc_attr( $class_name ) . '">
				<header ' . Sensei_Block_Helpers::render_style_attributes( 'wp-block-sensei-lms-course-outline-module__header', $header_css ) . '>
					<h2 class="wp-block-sensei-lms-course-outline-module__title">' . $title . '</h2>
					' . $progress_indicator .
			( ! empty( $outline_attributes['collapsibleModules'] ) ?
				'<button type="button" class="wp-block-sensei-lms-course-outline__arrow">
						<svg><use xlink:href="#sensei-chevron-up"></use></svg>
						<span class="screen-reader-text">' . esc_html__( 'Toggle module content', 'sensei-lms' ) . '</span>
					</button>' : '' ) .
			'</header>
					' . $style_header . '
				<div class="wp-block-sensei-lms-collapsible">
					' . $description . '
					<h3 class="wp-block-sensei-lms-course-outline-module__lessons-title">
						' . esc_html__( 'Lessons', 'sensei-lms' ) . '
					</h3>' .
			implode(
				'',
				array_map(
					[ Sensei()->blocks->course_outline->lesson, 'render_lesson_block' ],
					$block['lessons']
				)
			)
			. '
				</div>
			</section>
		';
	}

	/**
	 * Get progress indicator HTML.
	 *
	 * @param array $module_id The module id.
	 * @param int   $course_id The course id.
	 *
	 * @return string Module HTML
	 */
	private function get_module_progress_indicator( $module_id, $course_id ) {

		$module_progress = Sensei()->modules->get_user_module_progress( $module_id, $course_id, get_current_user_id() );

		if ( empty( $module_progress ) ) {
			return '';
		}

		if ( $module_progress < 100 ) {
			$module_status   = __( 'In Progress', 'sensei-lms' );
			$indicator_class = '';
		} else {
			$module_status   = __( 'Completed', 'sensei-lms' );
			$indicator_class = 'completed';
		}

		return '
					<div
						class="wp-block-sensei-lms-course-outline-module__progress-indicator ' . $indicator_class . '"
					>
						<span class="wp-block-sensei-lms-course-outline-module__progress-indicator__text"> ' . esc_html( $module_status ) . ' </span>
					</div>
		';
	}

}
