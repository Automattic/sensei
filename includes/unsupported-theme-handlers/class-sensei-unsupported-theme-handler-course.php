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
class Sensei_Unsupported_Theme_Handler_Course extends Sensei_Unsupported_Theme_Handler_CPT {

	/**
	 * We can handle this request if it is for a Course page.
	 *
	 * @return bool
	 */
	public function can_handle_request() {
		return is_single() && 'course' === get_post_type();
	}

	/**
	 * Get the course renderer object.
	 */
	protected function get_renderer() {
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

		return new Sensei_Renderer_Single_Post( $course_id, 'single-course.php', array(
			'show_pagination' => $show_pagination,
		) );
	}
}
