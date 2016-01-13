<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
/**
 *
 * Renders the [sensei_lesson_page] shortcode. Display a single lesson based on the ID parameter given.
 *
 * This class is loaded int WP by the shortcode loader class.
 *
 * @class Sensei_Shortcode_Lesson_Page
 *
 * @package Content
 * @subpackage Shortcode
 * @author Automattic
 *
 * @since 1.9.0
 */
class Sensei_Shortcode_Lesson_Page implements Sensei_Shortcode_Interface {

    /**
     * @var array $lesson_page_query {
     *     @type WP_Post
     * }
     * The lessons query
     */
    protected $lesson_page_query;

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
        $this->setup_lesson_query();

    }

    /**
     * create the lessons query .
     *
     * @return mixed
     */
    public function setup_lesson_query(){

        if( empty( $this->id ) ){
            return;
        }

        $args = array(
            'post_type' => 'lesson',
            'posts_per_page' => 1,
            'post_status' => 'publish',
            'post__in' => array( $this->id ),
        );

        $this->lesson_page_query  = new WP_Query( $args );

    }

    /**
     * Rendering the shortcode this class is responsible for.
     *
     * @return string $content
     */
    public function render(){

        if( empty(  $this->id  ) ){

            return __( 'Please supply a lesson ID for this shortcode.', 'woothemes-sensei' );

        }

        //set the wp_query to the current lessons query
        global $wp_query;
        $wp_query = $this->lesson_page_query;

        if( have_posts() ){

            the_post();

        }else{

            return __('No posts found.', 'woothemes-sensei');

        }

        ob_start();
        Sensei_Templates::get_template('content-single-lesson.php');
        $shortcode_output = ob_get_clean();

        // set back the global query
        wp_reset_query();

        return $shortcode_output;

    }// end render

}// end class

