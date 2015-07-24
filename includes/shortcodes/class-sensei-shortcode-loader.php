<?php
if ( ! defined( 'ABSPATH' ) ) exit; // security check
/**
 * Sensei Shortcode Loader Class
 *
 * This class handles the api for all Sensei shortcodes. It does not
 * execute on the shortcodes directly but relies on a class that responds
 * to each shortcode. Whe WordPress calls do_shortcode for a shortcode registered
 * in this function, the functions load_shortcode will be called and it will
 * instantiate the correct shortcode handling class as it was registered.
 *
 * @package Sensei
 * @category Shortcodes
 * @since 1.9.0
 */
class Sensei_Shortcode_Loader{

    /**
     * @var array {
     *  type string $shortcode
     *  type Sensei_Shortcode
     * } all the shortcodes and which class to instantiate when they are called from
     * WordPress's do_shortcode() function.
     *
     */
    protected $shortcode_classes;

    /**
     * Run all the functions that needs to be hooked into WordPress
     *
     * @since 1.9.0
     */
    public function __construct(){

        // load all the hooks
        $this->add_hooks();

        // create a list of shortcodes and the class that handles them
        $this->setup_shortcode_class_map();

        // setup all the shortcodes and load the listener into WP
        $this->initialize_shortcodes();
    }

    /**
     * Add all shortcodes here
     *
     * This function adds shortcodes to WP that links to other functionality.
     * @since 1.9.0
     */
    public function add_hooks(){

        add_action('pre_get_posts',  array( $this, 'filter_courses_archive' ) );

    }

    /**
     * Array of shortcode classes that should be instantiated when WordPress loads
     * a Sensei specific shortcode.
     * This list contains:
     * $shortcode => $class_name
     *
     * $shortcode is the actual shortcode the user will add to the editor
     * $class_name is the name of the class that will be instantiated to handle
     * the rendering of the shortcode.
     *
     * NOTE: When adding a new shortcode here be sure to load your shortcodes class
     * in class-sensei-autoloader class_file_map function
     */
    public function setup_shortcode_class_map(){

        $this->shortcode_classes = array(
            'sensei_featured_courses'    => 'Sensei_Shortcode_Featured_Courses',
            'sensei_user_courses'        => 'Sensei_Shortcode_User_Courses',
            'sensei_courses'             => 'Sensei_Shortcode_Courses',
            'sensei_teachers'            => 'Sensei_Shortcode_Teachers',
            'sensei_user_messages'       => 'Sensei_Shortcode_User_Messages',
            'sensei_course_page'         => 'Sensei_Shortcode_Course_Page',
            'sensei_lesson_page'         => 'Sensei_Shortcode_Lesson_Page',
            'sensei_course_categories'   => 'Sensei_Shortcode_Course_Categories',
            'sensei_unpurchased_courses' => 'Sensei_Shortcode_Unpurchased_Courses',
        );

        // legacy shortcode handling:
        add_shortcode( 'allcourses',      array( __CLASS__, 'all_courses' ) );
        add_shortcode( 'newcourses',      array( __CLASS__,'new_courses' ) );
        add_shortcode( 'featuredcourses', array( __CLASS__,'featured_courses') );
        add_shortcode( 'freecourses',     array( __CLASS__,'free_courses') );
        add_shortcode( 'paidcourses',     array( __CLASS__,'paid_courses') );
        add_shortcode( 'usercourses',     array( __CLASS__,'user_courses' ) );

    }

    /**
     * Add all shortcodes here
     *
     * This function adds shortcodes to WP that links to other functionality.
     * @since 1.9.0
     */
    public function initialize_shortcodes(){

        // shortcodes should only respond to front end calls
        if( is_admin() || defined( 'DOING_AJAX' ) ){
            return;
        }

        /**
         * Tell WP to run this classes load_shortcode function for all the
         * shortcodes registered here in.
         *
         * With this method we only load shortcode classes when we need them.
         */
        foreach( $this->shortcode_classes as $shortcode => $class ){

            // all Sensei shortcodes are rendered by this loader class
            // it acts as an interface between wp and the shortcodes registered
            // above
            add_shortcode( $shortcode, array( $this,'render_shortcode' ) );

        }

    }

    /**
     * Respond to WordPress do_shortcode calls
     * for shortcodes registered in the initialize_shortcodes function.
     *
     * @since 1.8.0
     *
     * @param $attributes
     * @param $content
     * @param $code the shortcode that is being requested
     *
     * @return string
     */
    public function render_shortcode( $attributes='', $content='', $code ){

        // only respond if the shortcode that we've added shortcode
        // classes for.
        if( ! isset( $this->shortcode_classes[ $code ] ) ){
            return '';
        }

        // create an instances of the current requested shortcode
        $shortcode_handling_class = $this->shortcode_classes[ $code ];
        $shortcode = new $shortcode_handling_class( $attributes, $content, $code );

        // we expect the sensei class instantiated to implement the Sensei_Shortcode interface
        if( ! in_array( 'Sensei_Shortcode_Interface', class_implements( $shortcode) ) ){

            $message = "The rendering class for your shortcode: $code, must implement the Sensei_Shortcode interface";
            _doing_it_wrong('Sensei_Shortcode_Loader::render_shortcode',$message, '1.9.0' );

        }

        return $shortcode->render();

    }

    /**
     * sensei_filter_courses_archive function.
     *
     * @access public
     * @param WP_Query $query
     * @return void
     */
    public static function filter_courses_archive( $query ) {

        if ( ! $query->is_main_query() )
            return;

        $query_type = '';
        // Handle course archive page
        if ( is_post_type_archive( 'course' ) ) {

            if ( isset( $_GET[ 'action' ] ) && ( '' != esc_html( $_GET[ 'action' ] ) ) ) {
                $query_type = esc_html( $_GET[ 'action' ] );
            } // End If Statement

            switch ( $query_type ) {
                case 'newcourses':
                    set_query_var( 'orderby', 'date' );
                    set_query_var( 'order', 'DESC' );
                    break;
                case 'freecourses':
                    set_query_var( 'orderby', 'date' );
                    set_query_var( 'order', 'DESC' );
                    set_query_var( 'meta_value', '-' ); /* TODO - WC */
                    set_query_var( 'meta_key', '_course_woocommerce_product' );
                    set_query_var( 'meta_compare', '=' );
                    break;
                case 'paidcourses':
                    set_query_var( 'orderby', 'date' );
                    set_query_var( 'order', 'DESC' );
                    set_query_var( 'meta_value', '0' );
                    set_query_var( 'meta_key', '_course_woocommerce_product' );
                    set_query_var( 'meta_compare', '>' );
                    break;
                case 'featuredcourses':
                    set_query_var( 'orderby', 'date' );
                    set_query_var( 'order', 'DESC' );
                    set_query_var( 'meta_value', 'featured' );
                    set_query_var( 'meta_key', '_course_featured' );
                    set_query_var( 'meta_compare', '=' );
                    break;
                default:

                    break;

            } // End Switch Statement

        } // End If Statement
    } // End filter_courses_archive()

    /**
     * all_courses shortcode output function.
     *
     * The function should only be called indirectly through do_shortcode()
     *
     * @access public
     * @param mixed $atts
     * @param mixed $content (default: null)
     * @return void
     */
    public static function all_courses( $atts, $content = null ) {

        ob_start();
        Sensei()->frontend->sensei_get_template( 'loop-course.php' );
        $content = ob_get_clean();
        return $content;

    } // all_courses()

    /**
     * shortcode_new_courses function.
     *
     * @access public
     * @param mixed $atts
     * @param mixed $content (default: null)
     * @return void
     */
    public static function new_courses( $atts, $content = null ) {
        global $shortcode_override;
        extract( shortcode_atts( array(	'amount' => 0 ), $atts ) );

        $shortcode_override = 'newcourses';

        ob_start();
        Sensei()->frontend->sensei_get_template( 'loop-course.php' );
        $content = ob_get_clean();
        return $content;

    } // End new_courses()

    /**
     * featured_courses function.
     *
     * @access public
     * @param mixed $atts
     * @param mixed $content (default: null)
     * @return void
     */
    public static function featured_courses( $atts, $content = null ) {

        global  $shortcode_override;
        extract( shortcode_atts( array(	'amount' => 0 ), $atts ) );

        if ( isset( Sensei()->settings->settings[ 'course_archive_featured_enable' ] ) && Sensei()->settings->settings[ 'course_archive_featured_enable' ] ) {
            $shortcode_override = 'featuredcourses';
            ob_start();
            Sensei()->frontend->sensei_get_template( 'loop-course.php' );
            $content = ob_get_clean();
        } // End If Statement
        return $content;
    } // End featured_courses()


    /**
     * shortcode_free_courses function.
     *
     * @access public
     * @param mixed $atts
     * @param mixed $content (default: null)
     * @return void
     */
    public static function free_courses( $atts, $content = null ) {
        global  $shortcode_override;
        extract( shortcode_atts( array(	'amount' => 0 ), $atts ) );

        if ( isset( Sensei()->settings->settings[ 'course_archive_free_enable' ] ) && Sensei()->settings->settings[ 'course_archive_free_enable' ] ) {
            $shortcode_override = 'freecourses';
            ob_start();
            Sensei()->frontend->sensei_get_template( 'loop-course.php' );
            $content = ob_get_clean();
            return $content;
        } // End If Statement
        return $content;
    } // End free_courses()


    /**
     * paid_courses function.
     *
     * @access public
     * @param mixed $atts
     * @param mixed $content (default: null)
     * @return void
     */
    public static function paid_courses( $atts, $content = null ) {
        global $shortcode_override;
        extract( shortcode_atts( array(	'amount' => 0 ), $atts ) );

        if ( isset( Sensei()->settings->settings[ 'course_archive_paid_enable' ] ) && Sensei()->settings->settings[ 'course_archive_paid_enable' ] ) {
            $shortcode_override = 'paidcourses';
            ob_start();
            Sensei()->frontend->sensei_get_template( 'loop-course.php' );
            $content = ob_get_clean();
            return $content;
        } // End If Statement
        return $content;
    } // End paid_courses()


    /**
     * user_courses function.
     *
     * @access public
     * @param mixed $atts
     * @param mixed $content (default: null)
     * @return void
     */
    public static function user_courses( $atts, $content = null ) {
        global $shortcode_override;
        extract( shortcode_atts( array(	'amount' => 0 ), $atts ) );

        $shortcode_override = 'usercourses';

        ob_start();
        Sensei()->frontend->sensei_get_template( 'user/my-courses.php' );
        $content = ob_get_clean();
        return $content;

    } // End user_courses()

} // end class Sensei_Shortcodes
new Sensei_Shortcode_Loader();