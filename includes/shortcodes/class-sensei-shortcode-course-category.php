<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
/**
 *
 * Renders the [sensei_course_category] shortcode
 *
 * This class is loaded int WP by the shortcode loader class.
 *
 * @class Sensei_Shortcode_User_Courses
 * @since 1.9.0
 * @package Sensei
 * @category Classes
 * @author 	WooThemes
 */
class Sensei_Shortcode_Course_Category implements Sensei_Shortcode_Interface {

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
     * @var category can be completed or active or all
     */
    protected $category;

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
        $this->order = isset( $attributes['order'] ) ? $attributes['order'] : 'ASC';

        $category = isset( $attributes['category'] ) ? $attributes['category'] : '';
        $this->category = is_numeric( $category ) ? intval( $category ) : $category;

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

        $term_id = term_exists( $this->category );
        if( ! $term_id ){
            return '';
        }

        $query_args = array(
            'post_type'        => 'course',
            'post_status'      => 'publish',
            'orderby'          => $this->orderby,
            'order'            => $this->order,
            'posts_per_page'   => $this->number,
            'tax_query' => array(
                array(
                    'taxonomy' => 'course-category',
                    'field' => 'id',
                    'terms'    => $term_id,
                ),
            ),
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

        if( !term_exists( $this->category ) ){
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