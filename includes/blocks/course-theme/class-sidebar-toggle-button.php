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
	 * Sidebar_Toggle_Button constructor.
	 */
	public function __construct() {
		Sensei_Blocks::register_sensei_block(
			'sensei-lms/sidebar-toggle-button',
			[
				'render_callback' => [ $this, 'render' ],
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
	public function render( array $attributes = [] ) : string {
		$icon  = \Sensei()->assets->get_icon( 'menu' );
		$label = __( 'Toggle course navigation', 'sensei-lms' );
		return "<button class='sensei-course-theme__sidebar-toggle' onclick='sensei.courseTheme.toggleSidebar()' title='{$label}'>{$icon}</button>";
	}
}
