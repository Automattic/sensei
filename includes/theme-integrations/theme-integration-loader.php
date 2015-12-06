<?php

/**
 * Class Sensei_Theme_Integration_Loader
 *
 * Responsible for loading the corrent supported theme if a
 * support theme is installed.
 *
 * @since 1.9.0
 */
class Sensei_Theme_Integration_Loader {

    /**
     * @var array
     * Holding a reference core supported themes
     */
    protected $themes;

    /**
     * @var string
     * reference to the theme currently active on this site
     */
    protected $active_theme;

    public function __construct() {

        $this->setup_themes();
        $this->setup_currently_active_theme();
        $this->possibly_load_supported_theme_wrappers();

    }

    /**
     * Setup the theme slugs supported by Sensei Core
     *
     * @since 1.9.0
     */
    private function setup_themes(){

        $this->themes = array(
            'twentyeleven',
            'twentytwelve',
            'twentythirteen',
            'twentyfourteen',
            'twentyfifteen',
            'twentysixteen',
            'storefront',
        );

    }// end setup themes

    /**
     * Setup the currently active theme
     *
     * @since 1.9.0
     */
    private function setup_currently_active_theme(){

        $this->active_theme = get_option('template');

    }

    /**
     * Remove default Sensei wrappers and load
     * supported wrappers if the current theme is
     * a theme we have an integration for within core.
     *
     * @since 1.9.0
     */
    private function possibly_load_supported_theme_wrappers(){

        if ( in_array( $this->active_theme, $this->themes ) ){

            // setup file and class names
            $supported_theme_class_file = trailingslashit( Sensei()->plugin_path ) . 'includes/theme-integrations/' . $this->active_theme . '.php';
            $supported_theme_class_name  = 'Sensei_'. ucfirst( $this->active_theme  );

            // load the file or exit if there is no file for this theme
            if( ! file_exists( $supported_theme_class_file ) ){
                return;
            }
            include_once( $supported_theme_class_file );
            include_once( 'twentytwelve.php' );
            //initialize the class or exit if there is no class for this theme
            if( ! class_exists( $supported_theme_class_name ) ){
                return;
            }
            $supported_theme = new $supported_theme_class_name;

            // remove default wrappers
            remove_action( 'sensei_before_main_content', array( Sensei()->frontend, 'sensei_output_content_wrapper' ), 10 );
            remove_action( 'sensei_after_main_content', array( Sensei()->frontend, 'sensei_output_content_wrapper_end' ), 10 );

            // load the supported theme wrappers
            add_action( 'sensei_before_main_content', array( $supported_theme, 'wrapper_start' ), 10 );
            add_action( 'sensei_after_main_content', array( $supported_theme, 'wrapper_end' ), 10 );
        }
    }

} /// end class