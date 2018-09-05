<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sensei Unsupported Theme Handler for the Course Page.
 *
 * Handles rendering the course page for themes that do not declare support for
 * Sensei.
 *
 * @author Automattic
 *
 * @since 1.12.0
 */
class Sensei_Unsupported_Theme_Handler_Course implements Sensei_Unsupported_Theme_Handler_Interface {

	/**
	 * We can handle this request if it is for a Course page.
	 *
	 * @return bool
	 */
	public function can_handle_request() {
		return is_single() && 'course' === get_post_type();
	}

	/**
	 * Set up handling for a single course page.
	 *
	 * @since 1.12.0
	 */
	public function handle_request() {
		add_filter( 'the_content', array( $this, 'course_page_content_filter' ) );
	}

	/**
	 * Filter the content and insert Sensei course content.
	 *
	 * @since 1.12.0
	 *
	 * @param string $content The raw post content.
	 *
	 * @return string The content to be displayed on the page.
	 */
	public function course_page_content_filter( $content ) {
		if ( ! is_main_query() ) {
			return $content;
		}

		// Remove the filter we're in to avoid nested calls.
		remove_filter( 'the_content', array( $this, 'course_page_content_filter' ) );

		$course_id = get_the_ID();

		/**
		 * Whether to show pagination on the course page when displaying on a
		 * theme that does not explicitly support Sensei.
		 *
		 * @param  bool $show_pagination The initial value.
		 * @param  int  $course_id       The course ID.
		 * @return bool
		 */
		$show_pagination = apply_filters( 'sensei_course_page_show_pagination', true, $course_id );

		$renderer = new Sensei_Renderer_Single_Course( $course_id, array(
			'show_pagination' => $show_pagination,
		) );
		$content = $renderer->render();

		return $content;
	}

}
