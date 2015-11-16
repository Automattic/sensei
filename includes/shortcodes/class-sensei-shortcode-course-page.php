<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
/**
 *
 * Renders the [sensei_course_page] shortcode. Display a single course based on the ID parameter given
 *
 * This class is loaded int WP by the shortcode loader class.
 *
 * @class Sensei_Shortcode_Course_Page
 * @since 1.9.0
 * @package Sensei
 * @category Shortcodes
 * @author 	WooThemes
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

        //set the wp_query to the current courses query
        global $wp_query, $post;

        // backups
        $global_post_ref = $post;
        $global_wp_query_ref = $wp_query;

        $post = get_post( $this->id );
        $wp_query->post = get_post( $this->id ); //  set this in case some the course hooks resets the query
        $wp_query = $this->course_page_query;

        ob_start();
        self::the_single_course_content();
        $shortcode_output = ob_get_clean();

        // set back the global query and post
        // restore global backups
        $wp_query       = $global_wp_query_ref;
        $post           = $global_post_ref;
        $wp_query->post = $global_post_ref;
        wp_reset_query();

        return $shortcode_output;

    }// end render

    /**
     * Print out the single course content markup
     *
     * @since 1.9.0
     */
    public static function the_single_course_content(){
        ?>

        <article <?php post_class( array( 'course', 'post' ) ); ?> >


            <?php  do_action( 'sensei_single_course_content_inside_before' );  ?>

            <section class="entry fix">

                <?php //the_content(); ?>

            </section>

            <?php  do_action( 'sensei_single_course_content_inside_after' );  ?>

        </article>

        <?php
    }// end the_single_course_content

}// end class
