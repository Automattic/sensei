<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
/**
 *
 * Renders the [sensei_unpurchased_courses] shortcode when a user is logged in. If the user is not logged in
 * it will show all courses.
 * This class is loaded int WP by the shortcode loader class.
 *
 * @class Sensei_Shortcode_Unpurchased_Courses
 *
 * @package Content
 * @subpackage Shortcode
 * @author Automattic
 *
 * @since 1.9.0
 */
class Sensei_Shortcode_Unpurchased_Courses implements Sensei_Shortcode_Interface {

    /**
     * @var WP_Query to help setup the query needed by the render method.
     */
    protected $query;

    /**
     * @var string number of items to show on the current page
     * Default: all.
     */
    protected $number;

    /**
     * @var string ordery by course field
     * Default: date
     */
    protected $orderby;

    /**
     * @var string ASC or DESC
     * Default: 'DESC'
     */
    protected  $order;

    /**
     * Setup the shortcode object
     *
     * @since 1.9.0
     * @param array $attributes
     * @param string $content
     * @param string $shortcode the shortcode that was called for this instance
     */
    public function __construct( $attributes, $content, $shortcode ){

        // set up all argument need for constructing the course query
        $this->number = isset( $attributes['number'] ) ? $attributes['number'] : '10';
        $this->orderby = isset( $attributes['orderby'] ) ? $attributes['orderby'] : 'title';

        // set the default for menu_order to be ASC
        if( 'menu_order' == $this->orderby && !isset( $attributes['order']  ) ){

            $this->order =  'ASC';

        }else{

            // for everything else use the value passed or the default DESC
            $this->order = isset( $attributes['order']  ) ? $attributes['order'] : 'DESC';

        }

        // setup the course query that will be used when rendering
        $this->setup_course_query();
    }

    /**
     * Sets up the object course query
     * that will be used int he render method.
     *
     * @since 1.9.0
     */
    protected function setup_course_query(){


        // course query parameters to be used for all courses
        $query_args = array(
            'post_type'        => 'course',
            'post_status'      => 'publish',
            // the number specified by the user will be used later in this function
            'posts_per_page' => 1000,
            'orderby'          => $this->orderby,
            'order'            => $this->order
        );

        // get all the courses that has a product attached
        $all_courses_query = new WP_Query( $query_args );

        $paid_courses_not_taken = array();
        // look through all course and find the purchasable ones that user has not purchased
        foreach( $all_courses_query->posts as $course ){

            // only keep the courses with a product including only  courses that the user not taking
            $course_product_id = get_post_meta( $course->ID, '_course_woocommerce_product',true );
            if( is_numeric( $course_product_id )
                &&
                ! Sensei_Utils::user_started_course( $course->ID , get_current_user_id()  )
            ){

                    $paid_courses_not_taken[] = $course->ID;

                }

        } // end foreach

        // setup the course query again and only use the course the user has not purchased.
        // this query will be loaded into the global WP_Query in the render function.
        $query_args[ 'post__in' ] = $paid_courses_not_taken;
        $query_args[ 'posts_per_page' ] = $this->number;

        $this->query = new WP_Query( $query_args );

    }// end setup _course_query

    /**
     * Rendering the shortcode this class is responsible for.
     *
     * @return string $content
     */
    public function render(){

        global $wp_query;

        if ( ! is_user_logged_in() ) {

            $anchor_before = '<a href="' . esc_url( sensei_user_login_url() ) . '" >';
            $anchor_after = '</a>';
            $notice = sprintf(
                __('You must be logged in to view the non-purchased courses. Click here to %slogin%s.', 'woothemes-sensei' ),
                $anchor_before,
                $anchor_after
            );

            Sensei()->notices->add_notice( $notice, 'info' );
            Sensei()->notices->maybe_print_notices();

            return '';

        }

        // keep a reference to old query
        $current_global_query = $wp_query;
        // assign the query setup in $this-> setup_course_query
        $wp_query = $this->query;

        ob_start();
        Sensei()->notices->maybe_print_notices();
        Sensei_Templates::get_template('loop-course.php');
        $shortcode_output =  ob_get_clean();

        //restore old query
        $wp_query = $current_global_query;

        return $shortcode_output;

    }// end render

}