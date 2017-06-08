<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
/**
 *
 * Renders the [sensei_courses] shortcode
 *
 * This class is loaded int WP by the shortcode loader class.
 *
 * @class Sensei_Shortcode_User_Courses
 *
 * @package Content
 * @subpackage Shortcode
 * @author Automattic
 *
 * @since 1.9.0
 */
class Sensei_Shortcode_Courses implements Sensei_Shortcode_Interface {

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
     * @var string teacher id to limit the courses to
     */
    protected $teacher;

    /**
     * @var string csv of course ids to limit the search to
     */
    protected $ids;

    /**
     * @var exclude courses by id
     */
    protected $exclude;

	private $global_product = null;

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

        $category = isset( $attributes['category'] ) ? $attributes['category'] : '';
        $this->category = is_numeric( $category ) ? intval( $category ) : $category;

        $ids =  isset( $attributes['ids'] ) ? $attributes['ids'] : '';
        $this->ids = empty( $ids ) ? '' : explode( ',', $ids );

        $exclude =  isset( $attributes['exclude'] ) ? $attributes['exclude'] : '';
        $this->exclude = empty( $exclude ) ? '' : explode( ',', $exclude );

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

        // query defaults
        $query_args = array(
            'post_type'        => 'course',
            'post_status'      => 'publish',
            'orderby'          => $this->orderby,
            'order'            => $this->order,
            'posts_per_page'   => $this->number,

        );

        // setup the teacher query if any teacher was specified
        if( !empty( $this->teacher )){

            // when users passed in a csv
            if( strpos( $this->teacher, ',' ) ){

                $teachers = explode( ',', $this->teacher );

                // for all user names given convert them to user ID's
                foreach( $teachers as $index => $teacher  ){

                    //replace the teacher value with the teachers ID
                    if( ! is_numeric( $teacher ) ){

                        $user = get_user_by('login', $teacher);
                        $teachers[$index] = $user->ID;

                    }

                } // end for each

                $teacher_query_by = 'author__in';
                $this->teacher = $teachers;

            }else{
                // when users passed in a single teacher value
                $teacher_query_by = is_numeric( $this->teacher )? 'author':'author_name';

            }

            // attach teacher query by and teacher query value to the course query
            $query_args[ $teacher_query_by ] = $this->teacher;

        }// end if empty teacher


        // add the course category taxonomy query
        if( ! empty( $this->category ) ) {

            $tax_query = array();
            $term_id = intval( term_exists($this->category) );

            if (! empty( $term_id) ) {

                $tax_query = array(
                    'taxonomy' => 'course-category',
                    'field' => 'id',
                    'terms' => $term_id,
                );

            }

            $query_args['tax_query'] = array($tax_query);

        }

        // limit the query if the user supplied ids
        if( ! empty( $this->ids ) && is_array( $this->ids ) ) {

            $query_args['post__in'] = $this->ids;

        }

        // exclude the course by id fromt he query
        if( ! empty( $this->exclude ) && is_array( $this->exclude ) ) {

            $query_args['post__not_in'] = $this->exclude;

        }

        $this->query = new WP_Query( $query_args );

    }// end setup _course_query

    /**
     * Rendering the shortcode this class is responsible for.
     *
     * @return string $content
     */
    public function render(){

        global $wp_query;
		$this->maybe_store_global_product();

        // keep a reference to old query
        $current_global_query = $wp_query;

        // assign the query setup in $this-> setup_course_query
        $wp_query = $this->query;

        ob_start();
        Sensei_Templates::get_template('loop-course.php');
        $shortcode_output =  ob_get_clean();

        //restore old query
        $wp_query = $current_global_query;

		$this->restore_global_product();

        return $shortcode_output;

    }// end render

	private function maybe_store_global_product() {
		global $product;
		if ( ! Sensei_WC::is_woocommerce_active() ) {
			return;
		}
		if ( isset( $product ) && $product ) {
			$this->global_product = $product;
		} else {
			$this->global_product = null;
		}
	}

	private function restore_global_product() {
		global $product;
		if ( ! Sensei_WC::is_woocommerce_active() ) {
			return;
		}
		if ( isset( $this->global_product ) && $this->global_product ) {
			$product = $this->global_product;
		}
	}

}