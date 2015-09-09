<?php
if ( ! defined( 'ABSPATH' ) ) exit; // security check, don't load file outside WP
/**
 * Sensei Autoloader Class
 *
 * Loading all class files within the Sensei/includes directory
 *
 * The autoloader depends on the class and file name matching.
 *
 * @package Sensei
 * @category Autoloader
 * @since 1.9.0
 */
class Sensei_Autoloader {

    /**
     * @var path to the includes directory within Sensei.
     */
    private $include_path = 'includes';

    /**
     * @var class file map. List of classes mapped to their files
     */
    private $class_file_map = array();

    /**
     * Constructor
     * @since 1.9.0
     */
    public function __construct(){

        // make sure we do not override an existing autoload function
        if( function_exists('__autoload') ){
           spl_autoload_register( '__autoload' );
        }

        // setup a relative path for the current autoload instance
        $this->include_path = trailingslashit( untrailingslashit( dirname( __FILE__ ) ) );

        //setup the class file map
        $this->initialize_class_file_map();

        // add Sensei custom auto loader
        spl_autoload_register( array( $this, 'autoload' )  );

    }

    /**
     * Generate a list of Sensei class and map them the their respective
     * files within the includes directory
     *
     * @since 1.9.0
     */
    public function initialize_class_file_map(){

        $this->class_file_map = array(

            'WooThemes_Sensei' => 'class-woothemes-sensei.php',
            'WooThemes_Sensei_Updates' => 'class-woothemes-sensei-updates.php',

            /* Shortcode specific */
            'Sensei_Shortcode_Loader'           => 'shortcodes/class-sensei-shortcode-loader.php',
            'Sensei_Shortcode_Interface'        => 'shortcodes/interface-sensei-shortcode.php',
            'Sensei_Shortcode_Featured_Courses' => 'shortcodes/class-sensei-shortcode-featured-courses.php',
            'Sensei_Shortcode_User_Courses'     => 'shortcodes/class-sensei-shortcode-user-courses.php',
            'Sensei_Shortcode_Courses'          => 'shortcodes/class-sensei-shortcode-courses.php',
            'Sensei_Shortcode_Teachers'         => 'shortcodes/class-sensei-shortcode-teachers.php',
            'Sensei_Shortcode_User_Messages'    => 'shortcodes/class-sensei-shortcode-user-messages.php',
            'Sensei_Shortcode_Course_Page'      => 'shortcodes/class-sensei-shortcode-course-page.php',
            'Sensei_Shortcode_Lesson_Page'      => 'shortcodes/class-sensei-shortcode-lesson-page.php',
            'Sensei_Shortcode_Course_Categories' => 'shortcodes/class-sensei-shortcode-course-categories.php',
            'Sensei_Shortcode_Unpurchased_Courses' => 'shortcodes/class-sensei-shortcode-unpurchased-courses.php',

            /**
             * WooCommerce
             */
            'Sensei_WC' => 'woocommerce/class-sensei-wc.php',

        );
    }

    /**
     * Autoload all sensei files as the class names are used.
     */
    public function autoload( $class ){

        // exit if we didn't provide mapping for this class
        if( ! isset( $this->class_file_map[ $class ]  ) ){

            return;

        }

        $file_location = $this->include_path . $this->class_file_map[ $class ];
        require_once( $file_location);

    }// end autoload

}
new Sensei_Autoloader();
