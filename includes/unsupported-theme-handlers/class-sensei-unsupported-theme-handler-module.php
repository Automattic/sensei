<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sensei Unsupported Theme Handler for the Module Page.
 *
 * Handles rendering the module page for themes that do not declare support for
 * Sensei.
 *
 * @author Automattic
 *
 * @since 1.12.0
 */
class Sensei_Unsupported_Theme_Handler_Module
	extends Sensei_Unsupported_Theme_Handler_Page_Imitator
	implements Sensei_Unsupported_Theme_Handler_Interface {

	/**
	 * We can handle this request if it is for a Course page.
	 *
	 * @return bool
	 */
	public function can_handle_request() {
		return is_tax( Sensei()->modules->taxonomy );
	}

	/**
	 * Set up handling for a module page. This is done by manually rendering
	 * the content for the page, creating a dummy post object, setting its
	 * content to the rendered content we generated, and then forcing WordPress
	 * to render that post. Adapted from WooCommerce and bbPress.
	 *
	 * @since 1.12.0
	 */
	public function handle_request() {
		global $post;

		// The post is always a lesson. This is because a Module query is
		// always limited to lessons (see function `module_archive_filter` in
		// Sensei_Modules).
		$lesson_id = $post->ID;
		$course_id = Sensei()->lesson->get_course_id( $lesson_id );
		$course    = get_post( $course_id );
		$module    = get_queried_object();

		// Render the module page and output it as a Page.
		$content = $this->render_module_page();
		$this->output_content_as_page(
			$content,
			$course,
			array(
				'post_title' => sanitize_text_field( $module->name ),
				'post_name'  => $module->slug,
			)
		);

		// Disable pagination.
		Sensei_Unsupported_Theme_Handler_Utils::disable_theme_pagination();
	}

	/**
	 * Return the content for the course module page.
	 *
	 * @since 1.12.0
	 *
	 * @return string
	 */
	private function render_module_page() {
		ob_start();
		add_filter( 'sensei_show_main_header', '__return_false' );
		add_filter( 'sensei_show_main_footer', '__return_false' );
		add_action( 'sensei_taxonomy_module_content_after', array( $this, 'do_sensei_pagination' ) );
		Sensei_Templates::get_template( 'taxonomy-module.php' );
		$content = ob_get_clean();

		return $content;
	}

	/**
	 * Prepare the WP query object for the imitated request.
	 *
	 * @param WP_Query $wp_query
	 * @param WP_Post  $post_to_copy
	 * @param array    $post_params
	 */
	protected function prepare_wp_query( $wp_query, $post_to_copy, $post_params ) {
		$wp_query->is_tax = true;
	}

}
