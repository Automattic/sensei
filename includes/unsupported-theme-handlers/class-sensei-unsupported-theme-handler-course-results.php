<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sensei Unsupported Theme Handler for the Course Results Page.
 *
 * Handles rendering the course results page for themes that do not declare support for
 * Sensei.
 *
 * @author Automattic
 *
 * @since 1.12.0
 */
class Sensei_Unsupported_Theme_Handler_Course_Results
	extends Sensei_Unsupported_Theme_Handler_Page_Imitator
	implements Sensei_Unsupported_Theme_Handler_Interface {

	/**
	 * We can handle this request if it is for a course results page.
	 *
	 * @return bool
	 */
	public function can_handle_request() {
		global $wp_query;

		return $wp_query && isset( $wp_query->query_vars['course_results'] );
	}

	/**
	 * Set up handling for a course results page.
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
		 * @var WP_Post $course
		 */
		$course = get_page_by_path( $wp_query->query_vars['course_results'], OBJECT, 'course' );

		// Render the course results page and output it as a Page.
		$content = $this->render_page();
		$this->output_content_as_page(
			$content,
			$course,
			array(
				'post_title' => sanitize_text_field( $course->post_title ),
			)
		);

		// Disable pagination.
		Sensei_Unsupported_Theme_Handler_Utils::disable_theme_pagination();
	}

	/**
	 * Return the content for the course results page.
	 *
	 * @since 1.12.0
	 *
	 * @return string
	 */
	private function render_page() {
		ob_start();
		add_filter( 'sensei_show_main_header', '__return_false' );
		add_filter( 'sensei_show_main_footer', '__return_false' );
		Sensei_Templates::get_template( 'course-results.php' );
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
		$wp_query->queried_object    = $post_to_copy;
		$wp_query->queried_object_id = $post_to_copy->ID;
	}

}
