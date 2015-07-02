<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
/**
 *
 * Renders the [sensei_user_courses] shortcode to all courses the current user is taking
 *
 * This class is loaded int WP by the shortcode loader class.
 *
 * @class Sensei_Shortcode_User_Courses
 * @since 1.9.0
 * @package Sensei
 * @category Classes
 * @author 	WooThemes
 */
class Sensei_Shortcode_User_Courses implements Sensei_Shortcode_Interface {

    /**
     * @var WP_Query to help setup the query needed by the render method.
     */
    protected $query;

    /**
     * @var string number of items to show on the current page
     * Default: -1.
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
     * @var type can be completed or active or all
     */
    protected $type;

    /**
     * Setup the shortcode object
     *
     * @since 1.9.0
     * @param array $attributes
     * @param string $content
     * @param string $shortcode the shortcode that was called for this instance
     */
    public function __construct( $attributes, $content, $shortcode ){

        if(!  is_user_logged_in() ) {
            return;
        }

        // set up all argument need for constructing the course query
        $this->number = isset( $attributes['number'] ) ? $attributes['number'] : '10';
        $this->orderby = isset( $attributes['orderby'] ) ? $attributes['orderby'] : 'title';
        $this->order = isset( $attributes['order'] ) ? $attributes['order'] : 'ASC';
        $this->type = isset( $attributes['type'] ) ? $attributes['type'] : 'all';

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

        $status_query = array( 'user_id' => get_current_user_id(), 'type' => 'sensei_course_status' );
        $course_statuses = WooThemes_Sensei_Utils::sensei_check_for_activity( $status_query , true );
        // User may only be on 1 Course
        if ( !is_array($course_statuses) ) {
            $course_statuses = array( $course_statuses );
        }
        $completed_ids = $active_ids = array();
        foreach( $course_statuses as $course_status ) {
            if ( WooThemes_Sensei_Utils::user_completed_course( $course_status, get_current_user_id() ) ) {
                $completed_ids[] = $course_status->comment_post_ID;
            } else {
                $active_ids[] = $course_status->comment_post_ID;
            }
        }

        if( 'completed' == $this->type ){

            $included_courses =  $completed_ids;

        }elseif( 'active'==$this->type ){

            $included_courses =  $active_ids;

        }else{

            $included_courses = array_merge( $active_ids, $completed_ids );

        }

        // course query parameters
        $query_args = array(
            'post_type'        => 'course',
            'post_status'      => 'publish',
            'orderby'          => $this->orderby,
            'order'            => $this->order,
            'posts_per_page'   => $this->number,
            'post__in'  => $included_courses,
        );

        $this->query = new WP_Query( $query_args );

    }// end setup _course_query

    /**
     * Rendering the shortcode this class is responsible for.
     *
     * @return string $content
     */
    public function render(){

        global $wp_query;

        if(!  is_user_logged_in() ) {
            return '';
        }

        // keep a reference to old query
        $current_global_query = $wp_query;

        // assign the query setup in $this-> setup_course_query
        $wp_query = $this->query;

        ob_start();
        include('templates/loop.php');
        $shortcode_output =  ob_get_clean();

        //restore old query
        $wp_query = $current_global_query;

        return $shortcode_output;

    }// end render

}