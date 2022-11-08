<?php
/**
 * File containing the Sidebar_Toggle_Button class.
 *
 * @package sensei
 * @since 3.13.4
 */

namespace Sensei\Blocks\Course_Theme;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use \Sensei_Blocks;

/**
 * A button to toggle the sidebar in mobile view.
 */
class Sidebar_Toggle_Button {
	/**
	 * Block JSON file.
	 */
	const BLOCK_JSON_FILE = '/lesson-blocks/sidebar-toggle-button.block.json';

	/**
	 * Sidebar_Toggle_Button constructor.
	 */
	public function __construct() {

		$block_json_path = Sensei()->assets->src_path( 'course-theme/blocks' ) . self::BLOCK_JSON_FILE;

		Sensei()->assets->register( 'sensei-sidebar-mobile-menu', 'css/sensei-course-theme/sidebar-mobile-menu.css' );

		Sensei_Blocks::register_sensei_block(
			'sensei-lms/sidebar-toggle-button',
			[
				'render_callback' => [ $this, 'render' ],
				'style'           => 'sensei-sidebar-mobile-menu',
			],
			$block_json_path
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
	public function render( array $attributes = [] ) : string {
		$icon  = \Sensei()->assets->get_icon( 'menu' );
		$label = __( 'Toggle course navigation', 'sensei-lms' );

		$wrapper_attr = get_block_wrapper_attributes(
			[
				'class' => 'sensei-course-theme__sidebar-toggle',
			]
		);
		return sprintf(
			'<button %s onclick="sensei.courseTheme.toggleSidebar()" title="%s">%s</button>',
			$wrapper_attr,
			$label,
			$icon
		);
	}
}
