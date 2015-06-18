<?php
if ( ! defined( 'ABSPATH' ) ) exit; // security check
/**
 * Sensei Shortcodes Class
 *
 * This class handles making shortcodes function available. It reaches
 * into Sensei to find the functionality and should never really contain advanced
 * functionality, but rather call it from within Sensei.
 *
 * @package Sensei
 * @category Shortcodes
 * @since 1.9.0
 */
class Sensei_Shortcodes{

    /**
     * Run all the functions that needs to be hooked into WordPress
     *
     * @since 1.9.0
     */

    public static function init(){

        // load all the hooks
        Sensei_Shortcodes::add_hooks();

        // setup all the shortcodes
        Sensei_Shortcodes::load_shortcodes();
    }

    /**
     * Add all shortcodes here
     *
     * This function adds shortcodes to WP that links to other functionality.
     * @since 1.9.0
     */
    public static function add_hooks(){

        add_action('pre_get_posts',  array( 'Sensei_Shortcodes','filter_courses_archive' ) );

    }

    /**
     * Add all shortcodes here
     *
     * This function adds shortcodes to WP that links to other functionality.
     * @since 1.9.0
     */
    public static function load_shortcodes(){

        add_shortcode( 'allcourses',      array( 'Sensei_Shortcodes', 'all_courses' ) );
        add_shortcode( 'newcourses',      array( 'Sensei_Shortcodes','new_courses' ) );
        add_shortcode( 'featuredcourses', array( 'Sensei_Shortcodes','featured_courses') );
        add_shortcode( 'freecourses',     array( 'Sensei_Shortcodes','free_courses') );
        add_shortcode( 'paidcourses',     array( 'Sensei_Shortcodes','paid_courses') );
        add_shortcode( 'usercourses',     array( 'Sensei_Shortcodes','user_courses' ) );

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