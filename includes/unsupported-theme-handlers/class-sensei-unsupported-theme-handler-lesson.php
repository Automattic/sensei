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
class Sensei_Unsupported_Theme_Handler_Lesson implements Sensei_Unsupported_Theme_Handler_Interface {

	/**
	 * We can handle this request if it is for a Lesson page.
	 *
	 * @return bool
	 */
	public function can_handle_request() {
		return is_single() && 'lesson' === get_post_type();
	}

	/**
	 * Set up handling for a single lesson page.
	 *
	 * @since 1.12.0
	 */
	public function handle_request() {
		add_filter( 'the_content', array( $this, 'lesson_page_content_filter' ) );
	}

	/**
	 * Filter the content and insert Sensei lesson content.
	 *
	 * @since 1.12.0
	 *
	 * @param string $content The raw post content.
	 *
	 * @return string The content to be displayed on the page.
	 */
	public function lesson_page_content_filter( $content ) {
		if ( ! is_main_query() ) {
			return $content;
		}

		// Remove the filter we're in to avoid nested calls.
		remove_filter( 'the_content', array( $this, 'lesson_page_content_filter' ) );

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

		$renderer = $this->get_lesson_renderer( $lesson_id, $show_pagination );
		$content = $renderer->render();

		return $content;
	}

	/**
	 * Get a renderer that will render the lesson.
	 *
	 * @param int  $lesson_id       The ID of the lesson to render.
	 * @param bool $show_pagination Whether to show pagination in the rendereed output.
	 *
	 * @return Sensei_Renderer_Single_Post
	 */
	private function get_lesson_renderer( $lesson_id, $show_pagination ) {
		return new Sensei_Renderer_Single_Post( $lesson_id, 'single-lesson.php', array(
			'show_pagination' => $show_pagination,
		) );
	}

}
