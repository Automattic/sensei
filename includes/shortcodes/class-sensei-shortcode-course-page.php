<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
/**
 *
 * Renders the [sensei_course_page] shortcode. Display a single course based on the ID parameter given
 *
 * This class is loaded int WP by the shortcode loader class.
 *
 * @class Sensei_Shortcode_Course_Page
 * @package Content
 * @subpackage Shortcode
 * @author Automattic
 *
 * @since 1.9.0
 */
class Sensei_Shortcode_Course_Page implements Sensei_Shortcode_Interface {

    /**
     * @var array $course_page_query{
     *     @type WP_Post
     * }
     * The courses query
     */
    protected $course_page_query;

    /**
     * Setup the shortcode object
     *
     * @since 1.9.0
     * @param array $attributes
     * @param string $content
     * @param string $shortcode the shortcode that was called for this instance
     */
    public function __construct( $attributes, $content, $shortcode ){

        $this->id = isset( $attributes['id'] ) ? $attributes['id'] : '';
        $this->setup_course_query();

    }

    /**
     * create the courses query .
     *
     * @return mixed
     */
    public function setup_course_query(){

        if( empty( $this->id ) ){
            return;
        }

        $args = array(
            'post_type' => 'course',
            'posts_per_page' => 1,
            'post_status' => 'publish',
            'post__in' => array( $this->id ),
        );

        $this->course_page_query  = new WP_Query( $args );

    }

    /**
     * Rendering the shortcode this class is responsible for.
     *
     * @return string $content
     */
    public function render(){

        if( empty(  $this->id  ) ){

            return sprintf( __( 'Please supply a course ID for the shortcode: %s', 'woothemes-sensei' ),'[sensei_course_page id=""]') ;

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
        $shortcode_output = ob_get_clean();

        // set back the global query and post
        // restore global backups
        $wp_query       = $global_wp_query_ref;
        $post           = $global_post_ref;
        $wp_query->post = $global_post_ref;
	    $pages          = $global_pages_ref;

        return $shortcode_output;

    }// end render

	/**
	 * Set global variables to the currently requested course.
	 *
	 * @since 1.9.5 introduced
	 */
	public function set_global_vars() {

		global $wp_query, $post, $pages;

		// Alter global var states.
		$post                                = get_post( $this->id );
		$pages                               = array( $post->post_content );
		$wp_query                            = $this->course_page_query;
		$wp_query->post                      = get_post( $this->id ); //  set this in case some the course hooks resets the query
	}



}// end class
