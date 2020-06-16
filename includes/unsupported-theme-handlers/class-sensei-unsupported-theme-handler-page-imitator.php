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
	 * The original query for the query.
	 *
	 * @var WP_Query
	 */
	private $original_query;

	/**
	 * The original post for the query.
	 *
	 * @var WP_Post
	 */
	private $original_post;

	/**
	 * The dummy post to be rendered.
	 *
	 * @var WP_Post
	 */
	private $dummy_post;

	/**
	 * Prepare the WP query object for the imitated request.
	 *
	 * @param WP_Query $wp_query
	 * @param object   $object_to_copy
	 * @param array    $post_params
	 */
	protected function prepare_wp_query( $wp_query, $object_to_copy, $post_params ) {
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
	 * @param string $content        The content to output as a Page.
	 * @param object $object_to_copy Optional WP_Post to use when populating
	 *                               fields for the Page.
	 * @param array  $post_params    Optional post parameters to override when
	 *                               creating the Page.
	 */
	protected function output_content_as_page( $content, $object_to_copy = null, $post_params = array() ) {
		global $post, $wp_query, $wp_the_query;

		// Set up dummy post for rendering.
		$dummy_post_properties = array_merge( $this->generate_dummy_post_args( $object_to_copy ), $post_params, array( 'post_content' => $content ) );

		// Save the current query and post for the view.
		$this->original_query = $wp_query;
		$this->original_post  = $post;

		// Create the dummy post object.
		$this->dummy_post = new WP_Post( (object) $dummy_post_properties );

		/*
		 * Set up new global $post and set it on $wp_query. Set $wp_the_query
		 * as well so we can reset it back to this later.
		 */
		// phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited -- Used to mock our own page within a custom loop. Reset afterwards.
		$post            = $this->dummy_post;
		$wp_query        = clone $wp_query;
		$wp_query->post  = $post;
		$wp_query->posts = array( $post );
		// On taxonomy pages the queried_object must remain a WP_Term object.
		if ( ! is_tax() ) {
			$wp_query->queried_object    = $post;
			$wp_query->queried_object_id = $post->ID;
		}
		$wp_the_query = $wp_query;
		// phpcs:enable WordPress.WP.GlobalVariablesOverride.Prohibited

		// Prevent comments form from appearing.
		$wp_query->post_count    = 1;
		$wp_query->is_404        = false;
		$wp_query->is_page       = true;
		$wp_query->is_single     = true;
		$wp_query->is_archive    = false;
		$wp_query->max_num_pages = 0;

		Sensei_Unsupported_Theme_Handler_Utils::disable_comments();

		$this->prepare_wp_query( $wp_query, $object_to_copy, $post_params );

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
	 * Generate dummy post args.
	 *
	 * @param object $object_to_copy
	 * @return array
	 */
	private function generate_dummy_post_args( $object_to_copy ) {
		global $wpdb;

		$default_args = array(
			'ID'                    => absint( $wpdb->get_var( "SELECT MAX( ID ) from {$wpdb->prefix}posts" ) ) + 1,
			'post_status'           => 'publish',
			'post_author'           => 0,
			'post_parent'           => 0,
			'post_type'             => 'page',
			'post_date'             => current_time( 'mysql' ),
			'post_date_gmt'         => current_time( 'mysql' ),
			'post_modified'         => current_time( 'mysql' ),
			'post_modified_gmt'     => current_time( 'mysql' ),
			'post_title'            => '',
			'post_excerpt'          => '',
			'post_content_filtered' => '',
			'post_mime_type'        => '',
			'post_password'         => '',
			'post_name'             => '',
			'guid'                  => '',
			'menu_order'            => 0,
			'pinged'                => '',
			'to_ping'               => '',
			'ping_status'           => '',
			'comment_status'        => 'closed',
			'comment_count'         => 0,
			'filter'                => 'raw',
		);

		if ( $object_to_copy instanceof WP_Term ) {
			return array_merge( $default_args, $this->generate_post_args_from_term( $object_to_copy ) );
		}
		if ( $object_to_copy instanceof WP_User ) {
			return array_merge( $default_args, $this->generate_post_args_from_user( $object_to_copy ) );
		}
		if ( $object_to_copy instanceof WP_Post ) {
			return array_merge( $default_args, $this->generate_post_args_from_post( $object_to_copy ) );
		}
		if ( $object_to_copy instanceof WP_Post_Type ) {
			return array_merge( $default_args, $this->generate_post_args_from_post_type( $object_to_copy ) );
		}

		return $default_args;
	}

	/**
	 * Generate dummy post args from term object.
	 *
	 * @param WP_Term $term_to_copy
	 * @return array
	 */
	private function generate_post_args_from_term( $term_to_copy ) {
		return array(
			'post_title' => $term_to_copy->name,
			'post_name'  => $term_to_copy->slug,
		);
	}

	/**
	 * Generate dummy post args from user object.
	 *
	 * @param WP_User $user_to_copy
	 * @return array
	 */
	private function generate_post_args_from_user( $user_to_copy ) {
		return array(
			'post_author'       => $user_to_copy->ID,
			'post_date'         => $user_to_copy->user_registered,
			'post_date_gmt'     => $user_to_copy->user_registered,
			'post_modified'     => $user_to_copy->user_registered,
			'post_modified_gmt' => $user_to_copy->user_registered,
			'post_title'        => $user_to_copy->display_name,
			'post_name'         => $user_to_copy->user_nicename,
		);
	}

	/**
	 * Generate dummy post args from another post object.
	 *
	 * @param WP_Post $post_to_copy
	 * @return array
	 */
	private function generate_post_args_from_post( $post_to_copy ) {
		return array(
			'post_author'       => $post_to_copy->post_author,
			'post_date'         => $post_to_copy->post_date,
			'post_date_gmt'     => $post_to_copy->post_date_gmt,
			'post_modified'     => $post_to_copy->post_modified,
			'post_modified_gmt' => $post_to_copy->post_modified_gmt,
			'post_title'        => $post_to_copy->post_title,
			'post_name'         => $post_to_copy->post_name,
		);
	}


	/**
	 * Generate dummy post args from a post type object.
	 *
	 * @param WP_Post_Type $post_type_to_copy
	 * @return array
	 */
	private function generate_post_args_from_post_type( $post_type_to_copy ) {
		return array(
			'post_title' => $post_type_to_copy->label,
			'post_name'  => $post_type_to_copy->name,
		);
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
		if ( $this->dummy_post->ID === $id ) {
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

		// phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited -- Resetting to the original query from self::output_content_as_page().
		$wp_query = $this->original_query;
		$post     = $this->original_post;
		// phpcs:enable WordPress.WP.GlobalVariablesOverride.Prohibited
	}

}
