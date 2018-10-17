<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sensei Unsupported Theme Handler for the Message Archive Page.
 *
 * Handles rendering the message archive page for themes that do not declare support for
 * Sensei.
 *
 * @author Automattic
 *
 * @since 1.12.0
 */
class Sensei_Unsupported_Theme_Handler_Message_Archive
	extends Sensei_Unsupported_Theme_Handler_Page_Imitator
	implements Sensei_Unsupported_Theme_Handler_Interface {

	/**
	 * We can handle this request if it is for a message archive page.
	 *
	 * @return bool
	 */
	public function can_handle_request() {
		return is_post_type_archive( 'sensei_message' );
	}

	/**
	 * Set up handling for a message archive page.
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
		 * @var WP_Post_Type $post_type Post type object.
		 */
		$post_type = $wp_query->get_queried_object();

		// Render the message archive page and output it as a Page.
		$content = $this->render_page();
		$this->output_content_as_page( $content, $post_type );

		// Disable pagination.
		Sensei_Unsupported_Theme_Handler_Utils::disable_theme_pagination();
	}

	/**
	 * Return the content for the message archive page.
	 *
	 * @since 1.12.0
	 *
	 * @return string
	 */
	private function render_page() {
		ob_start();
		add_filter( 'sensei_show_main_header', '__return_false' );
		add_filter( 'sensei_show_main_footer', '__return_false' );

		Sensei_Templates::get_template( 'archive-message.php' );
		do_action( 'sensei_pagination' );

		$content = ob_get_clean();

		return $content;
	}

	/**
	 * Prepare the WP query object for the imitated request.
	 *
	 * @param WP_Query $wp_query
	 * @param object   $object_to_copy
	 * @param array    $post_params
	 */
	protected function prepare_wp_query( $wp_query, $object_to_copy, $post_params ) {
		global $post;
		$wp_query->queried_object = $post;
	}
}
