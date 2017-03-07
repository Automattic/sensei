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
 *
 * @package Content
 * @subpackage Shortcode
 * @author Automattic
 *
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

        // create a list of shortcodes and the class that handles them
        $this->setup_shortcode_class_map();

        // setup all the shortcodes and load the listener into WP
        $this->initialize_shortcodes();

        // add sensei body class for shortcodes
        add_filter( 'body_class', array( $this, 'possibly_add_body_class' ));

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
        Sensei_Legacy_Shortcodes::init();

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
     * @since 1.9.0
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
     * Add the Sensei body class if
     * the current page has a Sensei shortcode.
     *
     * Note: legacy shortcodes not supported here.
     *
     * @since 1.9.0
     *
     * @param array $classes
     * @return array
     */
    public function possibly_add_body_class ( $classes ) {

        global $post;

        $has_sensei_shortcode = false;

        if ( is_a( $post, 'WP_Post' ) ) {

            // check all registered Sensei shortcodes (not legacy shortcodes)
            foreach ( $this->shortcode_classes as $shortcode => $class ){

                if ( has_shortcode( $post->post_content, $shortcode ) ) {

                    $has_sensei_shortcode = true;
                }

            }
        }

        if( $has_sensei_shortcode ) {
            $classes[] = 'sensei' ;
        }


        return $classes;

    }

} // end class Sensei_Shortcodes
new Sensei_Shortcode_Loader();
