<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sensei Unsupported Theme Handler for the Learner Profile Page.
 *
 * Handles rendering the learner profile page for themes that do not declare support for
 * Sensei.
 *
 * @author Automattic
 *
 * @since 1.12.0
 */
class Sensei_Unsupported_Theme_Handler_Learner_Profile
	extends Sensei_Unsupported_Theme_Handler_Page_Imitator
	implements Sensei_Unsupported_Theme_Handler_Interface {

	/**
	 * We can handle this request if it is for a learner profile page.
	 *
	 * @return bool
	 */
	public function can_handle_request() {
		global $wp_query;

		return $wp_query && isset( $wp_query->query_vars['learner_profile'] );
	}

	/**
	 * Set up handling for a learner profile page.
	 *
	 * This is done by manually rendering the content for the page, creating a
	 * dummy post object, setting its content to the rendered content we generated,
	 * and then forcing WordPress to render that post.
	 * Adapted from WooCommerce and bbPress.
	 *
	 * @since 1.12.0
	 */
	public function handle_request() {
		/**
		 * @var WP_User $learner_user
		 */
		$learner_user = Sensei_Learner::find_by_query_var( get_query_var( 'learner_profile' ) );

		if ( ! $learner_user ) {
			$learner_user = new WP_User();
		}

		// Render the learner profile page and output it as a Page.
		$content = $this->render_page();
		$this->output_content_as_page( $content, $learner_user );

		// Disable pagination.
		Sensei_Unsupported_Theme_Handler_Utils::disable_theme_pagination();
	}

	/**
	 * Return the content for the learner profile page.
	 *
	 * @since 1.12.0
	 *
	 * @return string
	 */
	private function render_page() {
		ob_start();
		add_filter( 'sensei_show_main_header', '__return_false' );
		add_filter( 'sensei_show_main_footer', '__return_false' );
		Sensei_Templates::get_template( 'learner-profile.php' );
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
