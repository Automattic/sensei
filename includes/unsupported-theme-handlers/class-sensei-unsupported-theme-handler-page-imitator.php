<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sensei Unsupported Theme Handler that imitates a theme
 *
 * Handles rendering the Sensei pages inside a theme's standard page template.
 *
 * @author Automattic
 *
 * @since 1.12.0
 */
abstract class Sensei_Unsupported_Theme_Handler_Page_Imitator {
	/**
	 * @var WP_Query The original query for the query.
	 */
	private $original_query;

	/**
	 * @var WP_Post The original post for the query.
	 */
	private $original_post;

	/**
	 * Prepare the WP query object for the imitated request.
	 *
	 * @param WP_Query $wp_query
	 * @param WP_Post  $post_to_copy
	 * @param array    prepare_wp_query$post_params
	 */
	protected function prepare_wp_query( $wp_query, $post_to_copy, $post_params ) {
		// For use in sub-classes.
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
	protected function output_content_as_page( $content, $post_to_copy, $post_params = array() ) {
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

		// Save the current query and post for the view.
		$this->original_query = $wp_query;
		$this->original_post  = $post;

		/*
		 * Set up new global $post and set it on $wp_query. Set $wp_the_query
		 * as well so we can reset it back to this later.
		 */
		$post            = new WP_Post( (object) $dummy_post_properties );
		$wp_query        = clone $wp_query;
		$wp_query->post  = $post;
		$wp_query->posts = array( $post );
		$wp_the_query    = $wp_query;

		// Prevent comments form from appearing.
		$wp_query->post_count    = 1;
		$wp_query->is_404        = false;
		$wp_query->is_page       = true;
		$wp_query->is_single     = true;
		$wp_query->is_archive    = false;
		$wp_query->max_num_pages = 0;

		$this->prepare_wp_query( $wp_query, $post_to_copy, $post_params );

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

		// Ensure the sidebar widgets see this as the original page.
		add_action( 'dynamic_sidebar_before', array( $this, 'setup_original_query' ) );
		add_action( 'dynamic_sidebar_after', 'wp_reset_query' );
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

	/**
	 * Set the globals back to the Module query and post. This is important
	 * when running code that may check to see if this is a Module page.
	 *
	 * @since 1.12.0
	 */
	public function setup_original_query() {
		global $wp_query, $post;

		// Just to be sure.
		if ( ! $this->original_query ) {
			throw new Exception( 'setup_original_query cannot be called before output_content_as_page' );
		}

		$wp_query = $this->original_query;
		$post     = $this->original_post;
	}

}
