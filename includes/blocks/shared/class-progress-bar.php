<?php
/**
 * File containing the Progress_Bar class.
 *
 * @package sensei
 * @since   4.0.0
 */

namespace Sensei\Blocks\Shared;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Common progress bar block component, with a label.
 */
class Progress_Bar {

	/**
	 * Renders the progress bar component.
	 *
	 * @access private
	 *
	 * @param array $attributes {
	 *     Component attributes.
	 *
	 *     @type string $label      Text label.
	 *     @type float  $percentage Progress bar percentage.
	 * }
	 *
	 * @return string HTML.
	 */
	public static function render( $attributes ): string {

		$base_css           = \Sensei_Block_Helpers::build_styles( $attributes );
		$bar_background_css = \Sensei_Block_Helpers::build_styles(
			$attributes,
			[
				'textColor'          => null,
				'barBackgroundColor' => 'background-color',
			],
			[
				'height'       => 'height',
				'borderRadius' => 'border-radius',
			]
		);

		$bar_css = \Sensei_Block_Helpers::build_styles(
			$attributes,
			[
				'textColor' => null,
				'barColor'  => 'background-color',
			]
		);

		$bar_css['inline_styles'][] = 'width: ' . $attributes['percentage'] . '%';

		$class_names = [ 'sensei-block-wrapper' ];

		if ( ! empty( $attributes['className'] ) ) {
			$class_names[] = $attributes['className'];
		}

		$label_html = ! empty( $attributes['label'] ) ? '<div class="sensei-progress-bar__label">' . $attributes['label'] . '</div>' : '';

		return '
			<div ' . \Sensei_Block_Helpers::render_style_attributes( $class_names, $base_css ) . '>
				' . $label_html . '
				<div role="progressbar" aria-valuenow="' . esc_attr( $attributes['percentage'] ) . '" aria-valuemin="0" aria-valuemax="100" ' . \Sensei_Block_Helpers::render_style_attributes( [ 'sensei-progress-bar__bar' ], $bar_background_css ) . '>
					<div ' . \Sensei_Block_Helpers::render_style_attributes( [ 'sensei-progress-bar__progress' ], $bar_css ) . '></div>
				</div>
			</div>
		';
	}
}
