<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
/**
 * Sensei Shortcodes Interface
 *
 * Renders the [sensei_recent_courses] shortcode to Display recently published courses.
 *
 * @class Sensei_Shortcode_Recent_Courses
 * @since 1.9.0
 * @package Sensei
 * @category Classes
 * @author 	WooThemes
 */
class Sensei_Shortcode_Recent_Courses implements Sensei_Shortcode_Interface {

    /**
     * @var WP_Query to help setup the query needed by the render method.
     */
    protected $query;

    /**
     * @var string number of items to show on the current page
     * Default: -1.
     */
    protected $number = '10';

    /**
     * @var string ordery by course field
     * Default: date
     */
    protected $orderby = 'date';

    /**
     * @var string ASC or DESC
     * Default: 'DESC'
     */
    protected  $order = 'DESC';

    /**
     * @var string teacher id to limit the courses to
     */
    protected $teacher = '';

    /**
     * Setup the shortcode object
     *
     * @since 1.9.0
     * @param array $attributes
     * @param string $content
     */
    public function __construct( $attributes, $content, $shortcode ){

        // set up the instance properties
        $this->number = isset( $attributes['number'] ) ? $attributes['number'] : '10';
        $this->teacher = isset( $attributes['teacher'] ) ? $attributes['teacher'] : '';

        //setup the query for this function
        $this->setup_course_query();

    }// end __construct

    /**
     * Rendering the html
     *
     * @since 1.9.0
     * @return string $html
     */
    public function render(){

        global $wp_query;

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

    /**
     * Sets up the object course query
     * that will be used int he render method.
     *
     * @since 1.9.0
     */
    protected function setup_course_query(){

        //for non numeric teacher arguments value query by author_name and not author
        $teacher_query_by = is_numeric( $this->teacher )? 'author':'author_name';

        $query_args = array(
            'post_type' => 'course',
            'post_status'=> 'publish',
            'orderby'=>'date',
            'order'=>'DESC',
            'posts_per_page'=> $this->number,
            $teacher_query_by=> $this->teacher,

        );

        $this->query = new WP_Query( $query_args );

    }// end setup _course_query

}// end Sensei_Shortcode_Recent_Courses
