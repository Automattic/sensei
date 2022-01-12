<?php
/**
 * File containing the Focus_Mode class.
 *
 * @package sensei
 * @since
 */

namespace Sensei\Blocks\Course_Theme;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use \Sensei_Blocks;

/**
 * Support for focus mode.
 */
class Focus_Mode {

	/**
	 * Course_Title constructor.
	 */
	public function __construct() {
		Sensei_Blocks::register_sensei_block(
			'sensei-lms/focus-mode-toggle',
			[
				'render_callback' => [ $this, 'render_focus_mode_toggle' ],
			]
		);
	}

	/**
	 * Renders the block.
	 *
	 * @param array $attributes The block attributes.
	 *
	 * @access private
	 *
	 * @return string The block HTML.
	 */
	public function render_focus_mode_toggle( array $attributes = [] ): string {

		$wrapper_attributes = '';
		if ( function_exists( 'get_block_wrapper_attributes' ) ) {
			$wrapper_attributes = get_block_wrapper_attributes( $attributes );
		}

		$label_enable  = __( 'Collapse', 'sensei-lms' );
		$label_disable = __( 'Expand', 'sensei-lms' );

		return sprintf(
			'<button class="sensei-course-theme__focus-mode-toggle" %1s onclick="window.sensei.courseTheme.toggleFocusMode()">
				<span class="sensei-course-theme__focus-mode-toggle__enable">%2s</span>
				<span class="sensei-course-theme__focus-mode-toggle__disable">%3s</span>
			</button>',
			$wrapper_attributes,
			$label_enable,
			$label_disable
		);
	}
}
