<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Abstract Sensei Unsupported Theme Handler for a CPT.
 *
 * Handles rendering the CPT page for themes that do not declare support for
 * Sensei.
 *
 * @author Automattic
 *
 * @since 1.12.0
 */
abstract class Sensei_Unsupported_Theme_Handler_CPT implements Sensei_Unsupported_Theme_Handler_Interface {

	/**
	 * Get the renderer for this handler.
	 *
	 * @return Sensei_Renderer_Interface The renderer object to use.
	 */
	protected abstract function get_renderer();

	/**
	 * Set up handling for a single CPT page.
	 *
	 * @since 1.12.0
	 */
	public function handle_request() {
		add_filter( 'the_content', array( $this, 'cpt_page_content_filter' ) );
	}

	/**
	 * Filter the content and insert Sensei CPT content.
	 *
	 * @since 1.12.0
	 *
	 * @param string $content The raw post content.
	 *
	 * @return string The content to be displayed on the page.
	 */
	public function cpt_page_content_filter( $content ) {
		if ( ! is_main_query() ) {
			return $content;
		}

		// Remove the filter we're in to avoid nested calls.
		remove_filter( 'the_content', array( $this, 'cpt_page_content_filter' ) );

		$renderer = $this->get_renderer();
		$content  = $renderer->render();

		return $content;
	}
}
