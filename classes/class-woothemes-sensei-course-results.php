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
 * - learner_profile_content()
 * - learner_profile_courses_heading()
 * - learner_profile_user_info()
 * - learner_profile_menu_item()
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

		// Load content for learner profiles
		add_action( 'sensei_course_results_content', array( $this, 'content' ), 10 );

		// Load course results
		add_action( 'sensei_course_results_info', array( $this, 'course_info' ), 10 );

		add_action( 'sensei_course_results_lessons', array( $this, 'course_lessons' ), 10 );
	} // End __construct()

	/**
	 * Setup permalink structure for course results
	 * @since  1.4.0
	 * @return void
	 */
	public function setup_permastruct() {
		add_rewrite_rule( '^' . $this->courses_url_base . '/([^/]*)/results/?', 'index.php?course_results_slug=$matches[1]', 'top' );
		add_rewrite_tag( '%course_results_slug%', '([^&]+)' );
	}

	/**
	 * Load content for course results
	 * @since  1.4.0
	 * @return void
	 */
	public function content() {
		global $wp_query, $woothemes_sensei, $current_user;

		if( isset( $wp_query->query_vars['course_results_slug'] ) ) {
			$woothemes_sensei->frontend->sensei_get_template( 'course-results/course-info.php' );
		}

	}

	/**
	 * Load course results info
	 * @since  1.4.0
	 * @param  object $course Queried course object
	 * @return void
	 */
	public function course_info() {
		global $course, $current_user;

		do_action( 'sensei_course_image', $course->ID );

        ?>
        <header><h1><?php echo $course->post_title; ?></h1></header>
        <?php
        $course_status = WooThemes_Sensei_Utils::sensei_user_course_status_message( $course->ID, $current_user->ID );

        echo '<div class="woo-sc-box ' . $course_status['box_class'] . '">' . $course_status['message'] . '</div>';

        do_action( 'sensei_course_results_lessons', $course );
	}

	public function course_lessons( $course ) {
		global $course, $woothemes_sensei, $current_user;

		$started_course = sensei_has_user_started_course( $course->ID, $current_user->ID );
		if( $started_course ) {
	 		$woothemes_sensei->frontend->sensei_get_template( 'course-results/course-lessons.php' );
	 	}
	}

} // End Class
?>