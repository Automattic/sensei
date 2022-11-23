<?php
/**
 * File containing the Sensei_Learner_Courses_Block class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Sensei_Learner_Courses_Block
 */
class Sensei_Learner_Courses_Block {

	const NAME = 'sensei-lms/learner-courses';

	/**
	 * Sensei_Learner_Courses_Block constructor.
	 */
	public function __construct() {
		Sensei_Blocks::register_sensei_block(
			self::NAME,
			[
				'render_callback' => [ $this, 'render' ],
			],
			Sensei()->assets->src_path( 'blocks/learner-courses-block' )
		);
	}

	/**
	 * Renders learner courses block in the frontend.
	 *
	 * @param array  $attributes The block attributes.
	 * @param string $content    The inner block content.
	 *
	 * @return string The HTML of the block.
	 */
	public function render( $attributes, $content ): string {

		$shortcode = new Sensei_Shortcode_User_Courses( [ 'options' => $attributes['options'] ?? [] ], null, null );

		list( $style, $class ) = Sensei_Block_Helpers::css_variables(
			[
				'accent-color'               => $attributes['options']['accentColor'] ?? null,
				'primary-color'              => $attributes['options']['primaryColor'] ?? null,
				'progress-bar-height'        => Sensei_Block_Helpers::css_unit( $attributes['options']['progressBarHeight'] ?? null, 'px' ),
				'progress-bar-border-radius' => Sensei_Block_Helpers::css_unit( $attributes['options']['progressBarBorderRadius'] ?? null, 'px' ),
			]
		);

		if ( isset( $attributes['options']['layoutView'] ) ) {
			$class .= ' wp-block-sensei-lms-learner-courses--is-' . $attributes['options']['layoutView'] . '-view';
		}

		return '<div class="wp-block-sensei-lms-learner-courses ' . $class . ' " style="' . esc_attr( $style ) . '">' . $shortcode->render() . '</div>';
	}
}
