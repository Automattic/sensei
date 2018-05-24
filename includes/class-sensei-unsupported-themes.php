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
	private function __construct() {
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

		$renderer = new Sensei_Renderer_Single_Course( array( 'id' => get_the_ID() ) );
		$content = $renderer->render();

		return $content;
	}

}
