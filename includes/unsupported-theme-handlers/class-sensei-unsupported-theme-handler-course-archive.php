<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sensei Unsupported Theme Handler for the Course Archive Page.
 *
 * Handles rendering the course archive page for themes that do not declare support for
 * Sensei.
 *
 * @author Automattic
 *
 * @since 1.12.0
 */
class Sensei_Unsupported_Theme_Handler_Course_Archive
	extends Sensei_Unsupported_Theme_Handler_Page_Imitator
	implements Sensei_Unsupported_Theme_Handler_Interface {

	/**
	 * We can handle this request if it is for a course archive page.
	 *
	 * @return bool
	 */
	public function can_handle_request() {
		return is_post_type_archive( 'course' )
			|| is_page( Sensei()->get_page_id( 'courses' ) )
			|| is_tax( 'course-category' );
	}

	/**
	 * Set up handling for a course archive page.
	 *
	 * This is done by manually rendering the content for the page, creating a
	 * dummy post object, setting its content to the rendered content we
	 * generated, and then forcing WordPress to render that post. Adapted from
	 * WooCommerce and bbPress.
	 *
	 * @since 1.12.0
	 */
	public function handle_request() {
		global $wp_query;

		// Render the course archive page and output it as a Page.
		$content = $this->render_page();

		if ( is_tax( 'course-category' ) ) {
			$term = $wp_query->get_queried_object();
			$this->output_content_as_page( $content, $term );
		} else {
			$this->output_content_as_page(
				$content,
				null,
				array(
					'post_title' => __( 'Courses', 'sensei-lms' ),
				)
			);
		}

		// Disable pagination.
		Sensei_Unsupported_Theme_Handler_Utils::disable_theme_pagination();
	}

	/**
	 * Return the content for the course archive page.
	 *
	 * @since 1.12.0
	 *
	 * @return string
	 */
	private function render_page() {
		ob_start();
		add_filter( 'sensei_show_main_header', '__return_false' );
		add_filter( 'sensei_show_main_footer', '__return_false' );
		Sensei_Templates::get_template( 'archive-course.php' );
		do_action( 'sensei_pagination' );
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
		if ( empty( $post_to_copy ) ) {
			return;
		}
		$wp_query->queried_object    = $post_to_copy;
		$wp_query->queried_object_id = $post_to_copy->ID;
	}

}
