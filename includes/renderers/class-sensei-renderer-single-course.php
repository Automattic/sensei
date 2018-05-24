<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 *
 * Renders a single Sensei course based on the given ID. The rendered result is
 * meant to be displayed on the frontend, and may be used by shortcodes or
 * other rendering code.
 *
 * @author Automattic
 *
 * @since 1.11.0
 */
class Sensei_Renderer_Single_Course {

	/**
	 * @var array $course_page_query {
	 *   @type WP_Post
	 * }
	 * The courses query.
	 */
	protected $course_page_query;

	/**
	 * Setup the renderer object
	 *
	 * @since 1.11.0
	 * @param array $attributes
	 * @param string $content
	 * @param string $shortcode the shortcode that was called for this instance
	 */
	public function __construct( $attributes ) {
		$this->id = isset( $attributes['id'] ) ? $attributes['id'] : '';
		$this->setup_course_query();
	}

	/**
	 * Create the courses query.
	 */
	public function setup_course_query(){
		if ( empty( $this->id ) ) {
			return;
		}

		$args = array(
			'post_type'      => 'course',
			'posts_per_page' => 1,
			'post_status'    => 'publish',
			'post__in'       => array( $this->id ),
		);

		$this->course_page_query = new WP_Query( $args );
	}

	/**
	 * Render and return the content. This will use the 'single-course.php'
	 * template, and will use an overridden version if it exists.
	 *
	 * @return string
	 */
	public function render() {
		if( empty( $this->id ) ){
			throw new Sensei_Renderer_Missing_Fields_Exception( array( 'id' ) );
		}

		// Set the wp_query to the current courses query.
		global $wp_query, $post, $pages;

		// backups
		$global_post_ref     = clone $post;
		$global_wp_query_ref = clone $wp_query;
		$global_pages_ref    = $pages;

		$this->set_global_vars();

		// Capture output.
		ob_start();
		add_filter( 'sensei_show_main_footer', '__return_false' );
		add_filter( 'sensei_show_main_header', '__return_false' );
		add_action( 'sensei_single_course_lessons_before', array( $this, 'set_global_vars' ), 1, 0 );
		Sensei_Templates::get_template( 'single-course.php' );
		$output = ob_get_clean();

		// set back the global query and post
		// restore global backups
		$wp_query       = $global_wp_query_ref;
		$post           = $global_post_ref;
		$wp_query->post = $global_post_ref;
		$pages          = $global_pages_ref;

		return $output;
	}// end render

	/**
	 * Set global variables to the currently requested course.
	 */
	public function set_global_vars() {
		global $wp_query, $post, $pages;

		// Alter global var states.
		$post           = get_post( $this->id );
		$pages          = array( $post->post_content );
		$wp_query       = $this->course_page_query;
		$wp_query->post = get_post( $this->id );
	}
}
