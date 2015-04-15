<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Sensei Course Results Class
 *
 * All functionality pertaining to the course results pages in Sensei.
 *
 * @package WordPress
 * @subpackage Sensei
 * @category Core
 * @author WooThemes
 * @since 1.4.0
 *
 * TABLE OF CONTENTS
 *
 * - __construct()
 * - setup_permastruct()
 * - get_permalink()
 * - content()
 * - course_info()
 * - course_lessons()
 */
class WooThemes_Sensei_Course_Results {
	private $course_results_url_base;
	public $token;

	/**
	 * Constructor.
	 * @since  1.4.0
	 */
	public function __construct () {
		global $woothemes_sensei;

		// Setup learner profile URL base
		$this->courses_url_base = apply_filters( 'sensei_course_slug', _x( 'course', 'post type single url slug', 'woothemes-sensei' ) );

		// Setup permalink structure for course results
		add_action( 'init', array( $this, 'setup_permastruct' ) );
		add_filter( 'wp_title', array( $this, 'page_title' ), 10, 2 );

		// Load content for learner profiles
		add_action( 'sensei_course_results_content', array( $this, 'content' ), 10 );

		// Load course results
		add_action( 'sensei_course_results_info', array( $this, 'course_info' ), 10 );

		add_action( 'sensei_course_results_lessons', array( $this, 'course_lessons' ), 10 );

		// Add class to body tag
		add_filter( 'body_class', array( $this, 'body_class' ), 10, 1 );
	} // End __construct()

	/**
	 * Setup permalink structure for course results
	 * @since  1.4.0
	 * @return void
	 */
	public function setup_permastruct() {
		add_rewrite_rule( '^' . $this->courses_url_base . '/([^/]*)/results/?', 'index.php?course_results=$matches[1]', 'top' );
		add_rewrite_tag( '%course_results%', '([^&]+)' );
	}

	/**
	 * Adding page title for course results page
	 * @param  string $title Original title
	 * @param  string $sep   Seeparator string
	 * @return string        Modified title
	 */
	public function page_title( $title, $sep = null ) {
		global $wp_query;
		if( isset( $wp_query->query_vars['course_results'] ) ) {
			$course = get_page_by_path( $wp_query->query_vars['course_results'], OBJECT, 'course' );
			$title = __( 'Course Results: ', 'woothemes-sensei' ) . $course->post_title . ' ' . $sep . ' ';
		}
		return $title;
	}

	/**
	 * Get permalink for course results based on course ID
	 * @since  1.4.0
	 * @param  integer $course_id ID of course
	 * @return string             The course results page permalink
	 */
	public function get_permalink( $course_id = 0 ) {

		$permalink = '';

		if( $course_id > 0 ) {

			$course = get_post( $course_id );

			if ( get_option('permalink_structure') ) {
				$permalink = trailingslashit( get_home_url() ) . $this->courses_url_base . '/' . $course->post_name . '/results/';
			} else {
				$permalink = trailingslashit( get_home_url() ) . '?course_results=' . $course->post_name;
			}
		}

		return $permalink;
	}

	/**
	 * Load content for course results
	 * @since  1.4.0
	 * @return void
	 */
	public function content() {
		global $wp_query, $woothemes_sensei, $current_user;

		if( isset( $wp_query->query_vars['course_results'] ) ) {
			$woothemes_sensei->frontend->sensei_get_template( 'course-results/course-info.php' );
		}

	}

	/**
	 * Load course results info
	 * @since  1.4.0
	 * @return void
	 */
	public function course_info() {
		global $course, $current_user;

		do_action( 'sensei_course_results_top', $course->ID );

		do_action( 'sensei_course_image', $course->ID );

		?>
		<header><h1><?php echo $course->post_title; ?></h1></header>
		<?php

		$course_status = WooThemes_Sensei_Utils::sensei_user_course_status_message( $course->ID, $current_user->ID );
		echo '<div class="sensei-message ' . $course_status['box_class'] . '">' . $course_status['message'] . '</div>';

		do_action( 'sensei_course_results_lessons', $course );

		do_action( 'sensei_course_results_bottom', $course->ID );

	}

	/**
	 * Load template for displaying course lessons
	 * @since  1.4.0
	 * @return void
	 */
	public function course_lessons() {
		global $course, $woothemes_sensei, $current_user;

		$started_course = WooThemes_Sensei_Utils::user_started_course( $course->ID, $current_user->ID );
		if( $started_course ) {
			$woothemes_sensei->frontend->sensei_get_template( 'course-results/course-lessons.php' );
		}

	}

	/**
	 * Adding class to body tag
	 * @param  array $classes Existing classes
	 * @return array          Modified classes
	 */
	public function body_class( $classes ) {
		global $wp_query;
		if( isset( $wp_query->query_vars['course_results'] ) ) {
			$classes[] = 'course-results';
		}
		return $classes;
	}

} // End Class