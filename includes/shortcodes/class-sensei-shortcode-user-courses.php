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
     * @var status can be completed or active or all
     */
    protected $status;

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
        $this->status = isset( $attributes['status'] ) ? $attributes['status'] : 'all';

        // set the default for menu_order to be ASC
        if( 'menu_order' == $this->orderby && !isset( $attributes['order']  ) ){

            $this->order =  'ASC';

        }else{

            // for everything else use the value passed or the default DESC
            $this->order = isset( $attributes['order']  ) ? $attributes['order'] : 'ASC';

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

        if( 'completed' == $this->status ){

            $included_courses =  $completed_ids;

        }elseif( 'active'==$this->status ){

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

        // attach progress meter below course
        add_action( 'sensei_course_content_inside_after', array( __CLASS__, 'attach_course_progress' ) );
        add_action( 'sensei_course_content_inside_after', array( __CLASS__, 'attach_course_buttons' ) );

        ob_start();
        include('templates/loop.php');
        $shortcode_output =  ob_get_clean();

        //remove progress meter as we only want it to show in this shortcode
        remove_action( 'sensei_course_content_inside_after', array( __CLASS__, 'attach_course_progress' ) );
        remove_action( 'sensei_course_content_inside_after', array( __CLASS__, 'attach_course_buttons' ) );

        //restore old query
        $wp_query = $current_global_query;

        return $shortcode_output;

    }// end render


    /**
     * Hooks into sensei_course_content_inside_after
     *
     * @param $course
     */
    public static function attach_course_progress( $course ){

        $percentage = Sensei()->course->get_completion_percentage( $course->ID, get_current_user_id() );
        echo Sensei()->course->get_progress_meter( $percentage );

    }// attach_course_progress


    /**
     * Hooked into sensei_course_content_inside_after
     *
     * Prints out the course action buttons
     *
     * @param $course
     */
    public static function attach_course_buttons( $course ){

        Sensei()->course->the_course_action_buttons( $course );

    }// attach_course_buttons

}