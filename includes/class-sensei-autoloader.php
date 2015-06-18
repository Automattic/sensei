<?php
if ( ! defined( 'ABSPATH' ) ) exit; // security check, don't load file outside WP
/**
 * Sensei Autoloader Class
 *
 * Loading all class files within the Sensei/includes directory
 *
 * @package Sensei
 * @category Autoloader
 * @since 1.9.0
 */
class Sensei_Autoloader {

    /**
     * @var path to the includes directory within Sensei.
     */
    private $include_path = 'includes' ;

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

        // add Sensei custom auto loader
        spl_autoload_register( array( $this, 'autoload' )  );

    }

    /**
     * Autoload all sensei files as the class names are used.
     */
    public function autoload( $class ){

        $file_name = $this->get_file_name( $class );

        if( 'WooThemes_Sensei'== $class ){

            require_once( $this->include_path . $file_name );

        }

        if( strpos( $file_name, 'sensei-shortcode' ) ){

            //exclude the interface
            if( 'Sensei_Shortcode_Interface' == $class ){

                require_once( $this->include_path . 'shortcodes/interface-sensei-shortcode.php');

            }else{

                require_once( $this->include_path . 'shortcodes/' . $file_name );

            }

        }


    }// end autoload

    /**
     * Convert class name into the respective file name
     *
     * @since 1.9.0
     * @param $class
     * @return string $filename
     */
    public function get_file_name( $class ){

        return "class-" . str_ireplace( '_', '-',  strtolower( $class )  ). ".php";
    }
}
new Sensei_Autoloader();