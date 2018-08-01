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
 * @since 1.12.0
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
	 * @since 1.12.0
	 */
	public static function init() {
		$instance = self::get_instance();
		$instance->maybe_handle_request();
	}

	/**
	 * Get the singleton instance.
	 *
	 * @since 1.12.0
	 */
	public static function get_instance() {
		if ( ! self::$_instance ) {
			self::$_instance = new Sensei_Unsupported_Themes();
		}
		return self::$_instance;
	}

	/**
	 * Reset the singleton instance (used for testing).
	 *
	 * @since 1.12.0
	 */
	public static function reset() {
		self::$_instance = null;
	}

	/**
	 * Private constructor.
	 *
	 * @since 1.12.0
	 */
	private function __construct() {
	}

	/**
	 * Determine whether this class is handling the rendering for this
	 * request.
	 *
	 * @since 1.12.0
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
	 * @since 1.12.0
	 */
	protected function maybe_handle_request() {
		// Do nothing if this theme supports Sensei.
		if ( sensei_does_theme_support_templates() ) {
			return;
		}

		if ( is_single() && 'course' === get_post_type() ) {
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
	 * @since 1.12.0
	 */
	private function handle_course_page() {
		add_filter( 'the_content', array( $this, 'course_page_content_filter' ) );
	}

	/**
	 * Filter the content and insert Sensei course content.
	 *
	 * @since 1.12.0
	 *
	 * @param string $content The raw post content.
	 *
	 * @return string The content to be displayed on the page.
	 */
	public function course_page_content_filter( $content ) {
		if ( ! is_main_query() ) {
			return $content;
		}

		// Remove the filter we're in to avoid nested calls.
		remove_filter( 'the_content', array( $this, 'course_page_content_filter' ) );

		$course_id = get_the_ID();

		/**
		 * Whether to show pagination on the course page when displaying on a
		 * theme that does not explicitly support Sensei.
		 *
		 * @param  bool $show_pagination The initial value.
		 * @param  int  $course_id       The course ID.
		 * @return bool
		 */
		$show_pagination = apply_filters( 'sensei_course_page_show_pagination', true, $course_id );

		$renderer = new Sensei_Renderer_Single_Course( $course_id, array(
			'show_pagination' => $show_pagination,
		) );
		$content = $renderer->render();

		return $content;
	}

	/**
	 * Set up handling for a module page. This is done by manually rendering
	 * the content for the page, creating a dummy post object, setting its
	 * content to the rendered content we generated, and then forcing WordPress
	 * to render that post. Adapted from WooCommerce and bbPress.
	 *
	 * @since 1.12.0
	 */
	private function handle_module_page() {
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

		// Prepare everything for rendering.
		setup_postdata( $post );
		remove_all_filters( 'the_content' );
		remove_all_filters( 'the_excerpt' );
		add_filter( 'template_include', array( $this, 'force_single_template_filter' ) );
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
		add_filter( 'the_title', '__return_false' );
		Sensei_Templates::get_template( 'taxonomy-module.php' );
		$content = ob_get_clean();

		return $content;
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
