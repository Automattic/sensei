<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sensei Unsupported Themes class.
 *
 * Handles all content rendering for themes that do not declare support for
 * Sensei.
 *
 * @author Automattic
 *
 * @since 1.11.0
 */
class Sensei_Unsupported_Themes {

	/**
	 * Singleton instance.
	 *
	 * @var string
	 */
	private static $_instance;

	/**
	 * Whether we are handling the request.
	 *
	 * @var bool
	 */
	protected $_is_handling_request = false;

	/**
	 * Initialize rendering system for unsupported themes.
	 *
	 * @since 1.11.0
	 */
	public static function init() {
		$instance = self::get_instance();
		$instance->maybe_handle_request();
	}

	/**
	 * Get the singleton instance.
	 *
	 * @since 1.11.0
	 */
	public static function get_instance() {
		if ( ! self::$_instance ) {
			self::$_instance = new Sensei_Unsupported_Themes();
		}
		return self::$_instance;
	}

	/**
	 * Private constructor.
	 *
	 * @since 1.11.0
	 */
	public function __construct() {
	}

	/**
	 * Determine whether this class is handling the rendering for this
	 * request.
	 *
	 * @since 1.11.0
	 *
	 * @return bool
	 */
	public function is_handling_request() {
		return $this->_is_handling_request;
	}

	/**
	 * Set up handling for this request if possible. If the request is
	 * handled here, sets the instance variable $_is_handling_request.
	 *
	 * @since 1.11.0
	 */
	protected function maybe_handle_request() {
		// Do nothing if this theme supports Sensei.
		if ( sensei_does_theme_support_templates() ) {
			return;
		}

		if ( is_single() && get_post_type() == 'course' ) {
			$this->_is_handling_request = true;
			$this->handle_course_page();

		} else if ( is_tax( Sensei()->modules->taxonomy ) ) {
			$this->_is_handling_request = true;
			$this->handle_module_page();
		}
	}

	/**
	 * Set up handling for a single course page.
	 *
	 * @since 1.11.0
	 */
	private function handle_course_page() {
		add_filter( 'the_content', array( $this, 'course_page_content_filter' ) );
	}

	/**
	 * Set up handling for a module page. This is done by manually rendering
	 * the content for the page, creating a dummy post object, setting its
	 * content to the rendering content we generated, and then forcing
	 * WordPress to render that post. Adapted from WooCommerce and bbPress.
	 *
	 * @since 1.11.0
	 */
	private function handle_module_page() {
		global $post, $wp_query;

		// The post is always a lesson. This is because a Module query is
		// always limited to lessons (see function `module_archive_filter` in
		// Sensei_Modules).
		$lesson_id = $post->ID;
		$course_id = Sensei()->lesson->get_course_id( $lesson_id );
		$course    = get_post( $course_id );
		$module    = get_queried_object();

		// Set up dummy post for rendering.
		$dummy_post_properties = array(
			'ID'                    => 0,
			'post_status'           => 'publish',
			'post_author'           => $course->post_author,
			'post_parent'           => 0,
			'post_type'             => 'page',
			'post_date'             => $course->post_date,
			'post_date_gmt'         => $course->post_date_gmt,
			'post_modified'         => $course->post_modified,
			'post_modified_gmt'     => $course->post_modified_gmt,
			'post_content'          => $this->module_page_render_content(),
			'post_title'            => sanitize_text_field( $module->name ),
			'post_excerpt'          => '',
			'post_content_filtered' => '',
			'post_mime_type'        => '',
			'post_password'         => '',
			'post_name'             => $module->slug,
			'guid'                  => '',
			'menu_order'            => 0,
			'pinged'                => '',
			'to_ping'               => '',
			'ping_status'           => '',
			'comment_status'        => 'closed',
			'comment_count'         => 0,
			'filter'                => 'raw',
		);

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

		// Prepare everything for rendering.
		setup_postdata( $post );
		remove_all_filters( 'the_content' );
		remove_all_filters( 'the_excerpt' );
		add_filter( 'template_include', array( $this, 'force_single_template_filter' ) );
	}

	/**
	 * Filter the content and insert Sensei course content.
	 *
	 * @since 1.11.0
	 * @param $content The existing content.
	 * @return string
	 */
	public function course_page_content_filter( $content ) {
		if ( ! is_main_query() ) {
			return $content;
		}

		// Remove the filter we're in to avoid nested calls.
		remove_filter( 'the_content', array( $this, 'course_page_content_filter' ) );

		$content = do_shortcode( '[sensei_course_page id=' . get_the_ID() . ']' );

		return $content;
	}

	/**
	 * Return the content for the course module page.
	 *
	 * @since 1.11.0
	 * @return string
	 */
	public function module_page_render_content() {
		ob_start();
		add_filter( 'sensei_show_main_header', '__return_false' );
		add_filter( 'sensei_show_main_footer', '__return_false' );
		add_filter( 'the_title', '__return_false' );
		Sensei_Templates::get_template( 'taxonomy-module.php' );
		$content = ob_get_clean();

		return $content;
	}

	/**
	 * Force loading a single template.
	 *
	 * @since 1.11.0
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
