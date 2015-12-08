<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
/**
 * Sensei Feature Course rendering class
 *
 * Renders the [sensei_featured_courses] shortcode to display all featured courses
 *
 * @class Sensei_Shortcode_Recent_Courses
 *
 * @package Content
 * @subpackage Shortcode
 * @author Automattic
 *
 * @since 1.9.0
 */
class Sensei_Shortcode_Featured_Courses implements Sensei_Shortcode_Interface {

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
     * @var string teacher id to limit the courses to
     */
    protected $teacher;

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
        $this->teacher = isset( $attributes['teacher'] ) ? $attributes['teacher'] : '';
        $this->orderby = isset( $attributes['orderby'] ) ? $attributes['orderby'] : 'date';

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

        //for non numeric teacher arguments value query by author_name and not author
        $teacher_query_by = is_numeric( $this->teacher )? 'author':'author_name';

        $query_args = array(
            'post_type'        => 'course',
            'post_status'      => 'publish',
            'orderby'          => $this->orderby,
            'order'            => $this->order,
            'posts_per_page'   => $this->number,
            $teacher_query_by  => $this->teacher,
            'meta_value' 	   => 'featured',
            'meta_key' 		   => '_course_featured',
            'meta_compare' 	   => '=',
            'suppress_filters' => 0,
        );

        $this->query = new WP_Query( $query_args );

    }// end setup _course_query

    /**
     * Rendering the shortcode this class is responsible for.
     */
    public function render(){

        global $wp_query;

        // keep a reference to old query
        $current_global_query = $wp_query;

        // assign the query setup in $this-> setup_course_query
        $wp_query = $this->query;

        ob_start();
        Sensei_Templates::get_template('loop-course.php');
        $shortcode_output =  ob_get_clean();

        //restore old query
        $wp_query = $current_global_query;

        return $shortcode_output;

    }// end render

}