<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * All functionality pertaining to the course results pages in Sensei.
 *
 * @package Views
 * @author Automattic
 *
 * @since 1.4.0
 */
class Sensei_Course_Results {

	/**
	 * @var string
	 */
	public $courses_url_base;

	/**
	 * Constructor.
	 *
	 * @since  1.4.0
	 */
	public function __construct() {

		// Setup learner profile URL base
		$this->courses_url_base = apply_filters( 'sensei_course_slug', _x( 'course', 'post type single url slug', 'sensei-lms' ) );

		// Setup permalink structure for course results
		add_action( 'init', array( $this, 'setup_permastruct' ) );

		// Support older WordPress theme (< 4.4)
		add_filter( 'wp_title', array( $this, 'page_title' ), 10, 2 );

		// Support newer WordPress theme (>= 4.4)
		add_filter( 'document_title_parts', array( $this, 'page_title' ), 10, 2 );

		// Add class to body tag
		add_filter( 'body_class', array( $this, 'body_class' ), 10, 1 );

	}

	/**
	 * Setup permalink structure for course results
	 *
	 * @since  1.4.0
	 * @return void
	 */
	public function setup_permastruct() {
		add_rewrite_rule( '^' . $this->courses_url_base . '/([^/]*)/results/?', 'index.php?course_results=$matches[1]', 'top' );
		add_rewrite_tag( '%course_results%', '([^&]+)' );
	}

	/**
	 * Adding page title for course results page
	 *
	 * @param  mixed  $title Original title
	 * @param  string $sep   Seeparator string
	 * @return string        Modified title
	 */
	public function page_title( $title, $sep = null ) {
		global $wp_query;
		if ( isset( $wp_query->query_vars['course_results'] ) ) {
			$course         = get_page_by_path( $wp_query->query_vars['course_results'], OBJECT, 'course' );
			$modified_title = __( 'Course Results: ', 'sensei-lms' ) . $course->post_title . ' ' . $sep . ' ';
			if ( is_array( $title ) ) {
				$title['title'] = $modified_title;
			} else {
				$title = $modified_title;
			}
		}
		return $title;
	}

	/**
	 * Get permalink for course results based on course ID
	 *
	 * @since  1.4.0
	 * @param  integer $course_id ID of course
	 * @return string             The course results page permalink
	 */
	public function get_permalink( $course_id = 0 ) {

		$permalink = '';

		if ( $course_id > 0 ) {

			$course = get_post( $course_id );

			if ( get_option( 'permalink_structure' ) ) {
				$permalink = trailingslashit( get_home_url() ) . $this->courses_url_base . '/' . $course->post_name . '/results/';
			} else {
				$permalink = trailingslashit( get_home_url() ) . '?course_results=' . $course->post_name;
			}
		}

		return $permalink;
	}

	/**
	 * Load content for course results
	 *
	 * @since  1.4.0
	 * @return void
	 */
	public function content() {
		global $wp_query;

		_deprecated_function( __METHOD__, '2.2.0' );

		if ( isset( $wp_query->query_vars['course_results'] ) ) {
			Sensei_Templates::get_template( 'course-results/course-info.php' );
		}
	}

	/**
	 * Load course results info
	 *
	 * @since  1.4.0
	 * @return void
	 */
	public function course_info() {

		global $course;

		Sensei_Utils::sensei_user_course_status_message( $course->ID, get_current_user_id() );

	}

	/**
	 * Adding class to body tag
	 *
	 * @param  array $classes Existing classes
	 * @return array          Modified classes
	 */
	public function body_class( $classes ) {
		global $wp_query;
		if ( isset( $wp_query->query_vars['course_results'] ) ) {
			$classes[] = 'course-results';
		}
		return $classes;
	}

	/**
	 * Fire the sensei frontend message hook
	 *
	 * @since 1.9.0
	 */
	public static function fire_sensei_message_hook() {

		do_action( 'sensei_frontend_messages' );

	}

}

/**
 * Class WooThemes_Sensei_Course_Results
 *
 * @ignore only for backward compatibility
 * @since 1.9.0
 */
class WooThemes_Sensei_Course_Results extends Sensei_Course_Results{}
