<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sensei Unsupported Theme Handler for the Lesson Page.
 *
 * Handles rendering the lesson page for themes that do not declare support for
 * Sensei.
 *
 * @author Automattic
 *
 * @since 1.12.0
 */
class Sensei_Unsupported_Theme_Handler_Lesson extends Sensei_Unsupported_Theme_Handler_CPT {

	/**
	 * We can handle this request if it is for a Lesson page.
	 *
	 * @return bool
	 */
	public function can_handle_request() {
		return is_single() && 'lesson' === get_post_type();
	}

	/**
	 * Get the lesson renderer object.
	 */
	protected function get_renderer() {
		$lesson_id = get_the_ID();

		/**
		 * Whether to show pagination on the lesson page when displaying on a
		 * theme that does not explicitly support Sensei.
		 *
		 * @param  bool $show_pagination The initial value.
		 * @param  int  $lesson_id       The lesson ID.
		 * @return bool
		 */
		$show_pagination = apply_filters( 'sensei_lesson_page_show_pagination', true, $lesson_id );

		return new Sensei_Renderer_Single_Post( $lesson_id, 'single-lesson.php', array(
			'show_pagination' => $show_pagination,
		) );
	}
}
