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
     * @var array $messages{
     *     @type WP_Post
     * }
     * messages for the current user
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
     * create the messages query .
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

            return __( 'Please supply a course ID for this shortcode.', 'woothemes-sensei' );

        }

        //set the wp_query to the current messages query
        global $wp_query;
        $wp_query = $this->course_page_query;

        if( have_posts() ){

            the_post();

        }else{

            return __('No posts found.', 'woothemes-sensei');

        }

        ob_start();
        Sensei()->frontend->sensei_get_template('content-single-course.php');
        $messages_html = ob_get_clean();

        // set back the global query
        wp_reset_query();

        return $messages_html;

    }// end render

}// end class

