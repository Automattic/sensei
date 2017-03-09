<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // security check, don't load file outside WP
}

class Sensei_Autoloader_Bundle {
    /**
     * @var path to the includes directory within Sensei.
     */
    private $include_path = 'includes';
    private $bundle_identifier = 'sensei';

    /**
     * Sensei_Autoloader_Bundle constructor.
     * @param string $bundle_identifier
     * @param string $namespace_path path relative to includes
     */
    public function __construct( $bundle_identifier = 'sensei', $bundle_identifier_path = '' ) {
        $this->bundle_identifier = $bundle_identifier;
        // setup a relative path for the current autoload instance
        $this->include_path = trailingslashit( trailingslashit(untrailingslashit(dirname(__FILE__))) . $bundle_identifier_path );
    }

    private function format_namespace() {
        return strtolower( $this->bundle_identifier );
    }

    /**
     * @param $class string
     * @return bool
     */
    public function load_class( $class ) {

        if( ! is_numeric( strpos ( strtolower( $class ), $this->format_namespace() ) ) ) {
            return false;
        }

        // check for file in the main includes directory
        $class_file_path = $this->include_path . 'class-'.str_replace( '_','-', strtolower( $class ) ) . '.php';
        if( file_exists( $class_file_path ) ){

            require_once( $class_file_path );
            return true;
        }

        // lastly check legacy types
        $stripped_woothemes_from_class = str_replace( 'woothemes_','', strtolower( $class ) ); // remove woothemes
        $legacy_class_file_path = $this->include_path . 'class-'.str_replace( '_','-', strtolower( $stripped_woothemes_from_class ) ) . '.php';
        if( file_exists( $legacy_class_file_path ) ){

            require_once( $legacy_class_file_path );
            return true;
        }

        return false;

    }// end autoload
}

/**
 * Loading all class files within the Sensei/includes directory
 *
 * The auto loader class listens for calls to classes within Sensei and loads
 * the file containing the class.
 *
 * @package Core
 * @since 1.9.0
 */
class Sensei_Autoloader {

    /**
     * @var path to the includes directory within Sensei.
     */
    private $include_path = 'includes';

    /**
     * @var array $class_file_map. List of classes mapped to their files
     */
    private $class_file_map = array();

    private $autoloader_bundles = array();

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


        $this->autoloader_bundles = array(
            new Sensei_Autoloader_Bundle( 'Sensei_REST_API'     , 'rest-api'      ),
            new Sensei_Autoloader_Bundle( 'Sensei_Domain_Models', 'domain-models' ),
            new Sensei_Autoloader_Bundle( 'Sensei'              , ''              )
        );

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

            /**
             * Main Sensei class
             */
            'Sensei_Main' => 'class-sensei.php',

            /**
             * Admin
             */
            'Sensei_Welcome'            => 'admin/class-sensei-welcome.php' ,
            'Sensei_Learner_Management' => 'admin/class-sensei-learner-management.php' ,

            /**
             * Shortcodes
             */
            'Sensei_Shortcode_Loader'              => 'shortcodes/class-sensei-shortcode-loader.php',
            'Sensei_Shortcode_Interface'           => 'shortcodes/interface-sensei-shortcode.php',
            'Sensei_Shortcode_Featured_Courses'    => 'shortcodes/class-sensei-shortcode-featured-courses.php',
            'Sensei_Shortcode_User_Courses'        => 'shortcodes/class-sensei-shortcode-user-courses.php',
            'Sensei_Shortcode_Courses'             => 'shortcodes/class-sensei-shortcode-courses.php',
            'Sensei_Shortcode_Teachers'            => 'shortcodes/class-sensei-shortcode-teachers.php',
            'Sensei_Shortcode_User_Messages'       => 'shortcodes/class-sensei-shortcode-user-messages.php',
            'Sensei_Shortcode_Course_Page'         => 'shortcodes/class-sensei-shortcode-course-page.php',
            'Sensei_Shortcode_Lesson_Page'         => 'shortcodes/class-sensei-shortcode-lesson-page.php',
            'Sensei_Shortcode_Course_Categories'   => 'shortcodes/class-sensei-shortcode-course-categories.php',
            'Sensei_Shortcode_Unpurchased_Courses' => 'shortcodes/class-sensei-shortcode-unpurchased-courses.php',
            'Sensei_Legacy_Shortcodes'             => 'shortcodes/class-sensei-legacy-shortcodes.php',

            /**
             * Built in theme integration support
             */
            'Sensei_Theme_Integration_Loader' => 'theme-integrations/theme-integration-loader.php',
            'Sensei__S'                       => 'theme-integrations/_s.php',
            'Sensei_Twentyeleven'             => 'theme-integrations/twentyeleven.php',
            'Sensei_Twentytwelve'             => 'theme-integrations/twentytwelve.php',
            'Sensei_Twentythirteen'           => 'theme-integrations/Twentythirteen.php',
            'Sensei_Twentyfourteen'           => 'theme-integrations/Twentyfourteen.php',
            'Sensei_Twentyfifteen'            => 'theme-integrations/Twentyfifteen.php',
            'Sensei_Twentysixteen'            => 'theme-integrations/Twentysixteen.php',
            'Sensei_Storefront'               => 'theme-integrations/Storefront.php',

            /**
             * WooCommerce
             */
            'Sensei_WC' => 'class-sensei-wc.php',

            /**
             * WooCommerce Memberships
             */
            'Sensei_WC_Memberships' => 'class-sensei-wc-memberships.php',

            /**
            * WPML
            */
            'Sensei_WPML' => 'wpml/class-sensei-wpml.php'

        );
    }

    /**
     * Autoload all sensei files as the class names are used.
     */
    public function autoload( $class ){

        // only handle classes with the word `sensei` in it
        if( ! is_numeric( strpos ( strtolower( $class ), 'sensei') ) ){

            return;

        }

        // exit if we didn't provide mapping for this class
        if( isset( $this->class_file_map[ $class ] ) ){

            $file_location = $this->include_path . $this->class_file_map[ $class ];
            require_once( $file_location);
            return;

        }

        foreach ($this->autoloader_bundles as $bundle ) {
            if (true === $bundle->load_class( $class ) ) {
                return;
            }
        }

        return;

    }// end autoload

}
