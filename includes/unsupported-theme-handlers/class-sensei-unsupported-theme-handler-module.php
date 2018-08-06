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
class Sensei_Unsupported_Theme_Handler_Module implements Sensei_Unsupported_Theme_Handler_Interface {

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
		$this->output_content_as_page( $content, $course, array(
			'post_title' => sanitize_text_field( $module->name ),
			'post_name'  => $module->slug,
		) );
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
	 * Set up this request to output the given content as if it were the HTML
	 * inside a Page object.
	 *
	 * To do this, we set up a "dummy" post (with type "page") and tell
	 * WordPress to render it. In order to render it realistically, we need to
	 * copy some properties from another Post object (e.g. the `post_date` in
	 * case that is displayed by the template). Such an object must be provided
	 * in the $post_to_copy parameter.
	 *
	 * @since 1.12.0
	 *
	 * @param string  $content      The content to output as a Page.
	 * @param WP_Post $post_to_copy The WP_Post to use when populating required
	 *                              fields for the Page.
	 * @param array   $post_params  Optional post parameters to override when
	 *                              creating the Page.
	 */
	private function output_content_as_page( $content, $post_to_copy, $post_params = array() ) {
		global $post, $wp_query;

		// Set up dummy post for rendering.
		$dummy_post_properties = array_merge( array(
			'ID'                    => 0,
			'post_status'           => 'publish',
			'post_author'           => $post_to_copy->post_author,
			'post_parent'           => 0,
			'post_type'             => 'page',
			'post_date'             => $post_to_copy->post_date,
			'post_date_gmt'         => $post_to_copy->post_date_gmt,
			'post_modified'         => $post_to_copy->post_modified,
			'post_modified_gmt'     => $post_to_copy->post_modified_gmt,
			'post_title'            => $post_to_copy->post_title,
			'post_excerpt'          => '',
			'post_content_filtered' => '',
			'post_mime_type'        => '',
			'post_password'         => '',
			'post_name'             => $post_to_copy->post_name,
			'guid'                  => '',
			'menu_order'            => 0,
			'pinged'                => '',
			'to_ping'               => '',
			'ping_status'           => '',
			'comment_status'        => 'closed',
			'comment_count'         => 0,
			'filter'                => 'raw',
		), $post_params, array( 'post_content' => $content ) );

		// Set up new global $post and set it on $wp_query.
		$post            = new WP_Post( (object) $dummy_post_properties );
		$wp_query->post  = $post;
		$wp_query->posts = array( $post );

		// Prevent comments form from appearing.
		$wp_query->post_count = 1;
		$wp_query->is_404     = false;
		$wp_query->is_page    = true;
		$wp_query->is_single  = true;
		$wp_query->is_archive = false;
		$wp_query->is_tax     = false;

		/*
		 * Prevent the title from appearing, since it's assumed that the
		 * rendered content will take care of that.
		 */
		add_filter( 'the_title', array( $this, 'hide_dummy_post_title' ), 10, 2 );

		// Prepare everything for rendering.
		setup_postdata( $post );
		remove_all_filters( 'the_content' );
		remove_all_filters( 'the_excerpt' );
		add_filter( 'template_include', array( $this, 'force_single_template_filter' ) );
	}

	/**
	 * Run the sensei_pagination action. This can be used in a hook.
	 */
	public function do_sensei_pagination() {
		do_action( 'sensei_pagination' );
	}

	/**
	 * Return empty string for the dummy post so we don't show its title.
	 *
	 * @param string $title
	 * @param string $id
	 *
	 * @return string|bool
	 */
	public function hide_dummy_post_title( $title, $id ) {
		if ( 0 === $id ) {
			return '';
		}
		return $title;
	}

	/**
	 * Force loading a single template.
	 *
	 * @since 1.12.0
	 *
	 * @param string $template Path to original template.
	 * @return string
	 */
	public function force_single_template_filter( $template ) {
		$possible_templates = array(
			'page',
			'single',
			'singular',
			'index',
		);

		foreach ( $possible_templates as $possible_template ) {
			$path = get_query_template( $possible_template );
			if ( $path ) {
				return $path;
			}
		}

		return $template;
	}

}
