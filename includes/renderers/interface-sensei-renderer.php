<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * Renders a page within Sensei. For example, to render a course for the
 * shortcode.
 *
 * @author Automattic
 *
 * @since 1.12.0
 */
interface Sensei_Renderer_Interface {

	/**
	 * Public function for performing the render.
	 *
	 * @return string The rendered output.
	 */
	public function render();
}
