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

		$progress_indicator = $this->get_module_progress_indicator( $block['id'], $course_id );

		// If no style is set, get the style of the outline or the default one.
		$class_name = Sensei_Block_Helpers::block_class_with_default_style( $block['attributes'], $outline_attributes );

		$is_default_style = false !== strpos( $class_name, 'is-style-default' );
		$is_minimal_style = false !== strpos( $class_name, 'is-style-minimal' );

		$header_css = [];

		// Only set header CSS whether it's the default style or the text color is set.
		if (
			$is_default_style
			|| ! empty( $block['attributes']['textColor'] )
			|| ! empty( $block['attributes']['customTextColor'] )
		) {
			$header_css = Sensei_Block_Helpers::build_styles(
				$block['attributes'],
				[
					'mainColor'       => $is_default_style ? 'background-color' : null,
					'backgroundColor' => null,
					'borderColor'     => null,
				]
			);
		}

		$style_header = '';

		if ( $is_minimal_style ) {

			$header_border_css = Sensei_Block_Helpers::build_styles(
				$block['attributes'],
				[
					'mainColor'   => 'background-color',
					'borderColor' => null,
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
				$module_link = esc_url( add_query_arg( 'course_id', $course_id, $module_link ) );
				$title       = '<a href="' . $module_link . '">' . $title . '</a>';
			}
		}

		return '
			<section ' . $this->get_block_html_attributes( $class_name, $block['attributes'], $outline_attributes ) . '>
				<header ' . Sensei_Block_Helpers::render_style_attributes( 'wp-block-sensei-lms-course-outline-module__header', $header_css ) . '>
					<h2 class="wp-block-sensei-lms-course-outline-module__title">' . $title . '</h2>
					' . $progress_indicator .
			( ! empty( $outline_attributes['collapsibleModules'] ) ?
				'<button type="button" class="wp-block-sensei-lms-course-outline__arrow sensei-collapsible__toggle">
						<svg><use xlink:href="#sensei-chevron-up"></use></svg>
						<span class="screen-reader-text">' . esc_html__( 'Toggle module content', 'sensei-lms' ) . '</span>
					</button>' : '' ) .
			'</header>
					' . $style_header . '
				<div class="wp-block-sensei-lms-collapsible sensei-collapsible__content">
					' . $description . '
					<h3 class="wp-block-sensei-lms-course-outline-module__lessons-title">
						' . esc_html__( 'Lessons', 'sensei-lms' ) . '
					</h3>' .
			implode(
				'',
				array_map(
					function( $block ) use ( $course_id ) {
						return Sensei()->blocks->course->outline->lesson->render_lesson_block( $block, $course_id );
					},
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

	/**
	 * Calculates the block html attributes.
	 *
	 * @param string $class_name         The block class name.
	 * @param array  $block_attributes   The block attributes.
	 * @param array  $outline_attributes The outline attributes.
	 *
	 * @return string The html attributes.
	 */
	private function get_block_html_attributes( $class_name, $block_attributes, $outline_attributes ) : string {
		$class_names   = [ 'wp-block-sensei-lms-course-outline-module', 'sensei-collapsible', $class_name ];
		$inline_styles = [];
		$css           = Sensei_Block_Helpers::build_styles(
			$block_attributes,
			[
				'textColor' => null,
			]
		);

		if ( array_key_exists( 'borderedSelected', $block_attributes ) ) {
			$should_have_border = ! empty( $block_attributes['borderedSelected'] );
		} else {
			$should_have_border = ! empty( $outline_attributes['moduleBorder'] );
		}

		if ( $should_have_border ) {
			$class_names[] = 'wp-block-sensei-lms-course-outline-module-bordered';

			$border_color_value = ! empty( $block_attributes['defaultBorderColorValue'] ) ? $block_attributes['defaultBorderColorValue'] : '';
			$border_color_value = ! empty( $block_attributes['borderColorValue'] ) ? $block_attributes['borderColorValue'] : $border_color_value;

			if ( ! empty( $border_color_value ) ) {
				$inline_styles[] = sprintf( 'border-color: %s;', $border_color_value );
			}
		}

		return Sensei_Block_Helpers::render_style_attributes(
			$class_names,
			[
				'css_classes'   => $css['css_classes'],
				'inline_styles' => array_merge( $css['inline_styles'], $inline_styles ),
			]
		);
	}

}
