<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sensei Unsupported Theme Handler for the Lesson Tag Archive Page.
 *
 * Handles rendering the lesson tag archive page for themes that do not declare support for
 * Sensei.
 *
 * @author Automattic
 *
 * @since 1.12.0
 */
class Sensei_Unsupported_Theme_Handler_Lesson_Tag_Archive
	extends Sensei_Unsupported_Theme_Handler_Page_Imitator
	implements Sensei_Unsupported_Theme_Handler_Interface {

	/**
	 * We can handle this request if it is for a lesson tag archive page.
	 *
	 * @return bool
	 */
	public function can_handle_request() {
		return is_tax( 'lesson-tag' );
	}

	/**
	 * Set up handling for a lesson tag archive page.
	 *
	 * This is done by manually rendering the content for the page, creating a
	 * dummy post object, setting its content to the rendered content we generated,
	 * and then forcing WordPress to render that post.
	 * Adapted from WooCommerce and bbPress.
	 *
	 * @since 1.12.0
	 */
	public function handle_request() {
		global $wp_query;

		/**
		 * @var WP_Term $term Term object. `is_tax` should ensure that is what is queried here.
		 */
		$term = $wp_query->get_queried_object();

		// Render the lesson tag archive page and output it as a Page.
		$content = $this->render_page();
		$this->output_content_as_page( $content, $term );

		// Disable pagination.
		Sensei_Unsupported_Theme_Handler_Utils::disable_theme_pagination();
	}

	/**
	 * Return the content for the lesson tag archive page.
	 *
	 * @since 1.12.0
	 *
	 * @return string
	 */
	private function render_page() {
		ob_start();
		add_filter( 'sensei_show_main_header', '__return_false' );
		add_filter( 'sensei_show_main_footer', '__return_false' );

		$legacy_template       = Sensei()->template_url . 'taxonomy-lesson-tag.php';
		$found_legacy_template = locate_template( array( $legacy_template ) );
		if ( $found_legacy_template ) {
			Sensei_Templates::get_template( 'taxonomy-lesson-tag.php' );
		} else {
			Sensei_Templates::get_template( 'archive-lesson.php' );
		}
		remove_action( 'sensei_pagination', array( 'Sensei_Lesson', 'output_comments' ), 90 );
		do_action( 'sensei_pagination' );
		$content = ob_get_clean();

		return $content;
	}

}
