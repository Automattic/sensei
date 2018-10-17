<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sensei Unsupported Theme Handler for the Teacher Archive Page.
 *
 * Handles rendering the teacher archive page for themes that do not declare support for
 * Sensei.
 *
 * @author Automattic
 *
 * @since 1.12.0
 */
class Sensei_Unsupported_Theme_Handler_Teacher_Archive
	extends Sensei_Unsupported_Theme_Handler_Page_Imitator
	implements Sensei_Unsupported_Theme_Handler_Interface {

	/**
	 * @var WP_User $author
	 */
	private $author;

	/**
	 * We can handle this request if it is for a teacher archive page.
	 *
	 * @return bool
	 */
	public function can_handle_request() {
		return is_author()
				&& Sensei_Teacher::is_a_teacher( get_query_var( 'author' ) )
				&& ! user_can( get_query_var( 'author' ), 'manage_options' );
	}

	/**
	 * Set up handling for a teacher archive page.
	 *
	 * This is done by manually rendering the content for the page, creating a
	 * dummy post object, setting its content to the rendered content we generated,
	 * and then forcing WordPress to render that post.
	 * Adapted from WooCommerce and bbPress.
	 *
	 * @since 1.12.0
	 */
	public function handle_request() {
		$this->author = get_user_by( 'id', get_query_var( 'author' ) );

		// Render the teacher archive page and output it as a Page.
		$content = $this->render_page();
		$this->output_content_as_page( $content, $this->author );

		// Disable pagination.
		Sensei_Unsupported_Theme_Handler_Utils::disable_theme_pagination();
	}

	/**
	 * Return the content for the teacher archive page.
	 *
	 * @since 1.12.0
	 *
	 * @return string
	 */
	private function render_page() {
		ob_start();
		add_filter( 'sensei_show_main_header', '__return_false' );
		add_filter( 'sensei_show_main_footer', '__return_false' );
		Sensei_Templates::get_template( 'teacher-archive.php' );
		do_action( 'sensei_pagination' );
		$content = ob_get_clean();

		return $content;
	}

	/**
	 * Prepare the WP query object for the imitated request. The `queried_object` property should
	 * be the queried author in order for it to show up in the page's `<title>` tag for this author
	 * query.
	 *
	 * @param WP_Query $wp_query
	 * @param object   $object_to_copy
	 * @param array    $post_params
	 */
	protected function prepare_wp_query( $wp_query, $object_to_copy, $post_params ) {
		$wp_query->queried_object    = $this->author;
		$wp_query->queried_object_id = $this->author->ID;
	}

}
