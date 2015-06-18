<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Sensei Rest API class
 *
 * All functionality pertaining to Sensei's integration with the WordPress Rest API
 *
 * @package WordPress
 * @subpackage Sensei
 * @category Core
 * @author WooThemes
 * @since 1.8.0
 *
 * TABLE OF CONTENTS
 *
 * - __construct()
 *
 *
 */
class WooThemes_Sensei_Rest_API extends WP_JSON_CustomPostType {

    public function __construct () {

        //register the endpoints
        add_filter( 'json_endpoints', array( $this , 'register_sensei_routes' ) );

    }// __construct

	/**
	 * Register the routes to enable Sensei to respond to jason API requests
	 *
	 * @since 1.7.0
	 * @param array $routes
	 * @return array $routes
	 */
    public function register_sensei_routes ( $routes ){

        // The main sensei rout that provides plugin information and sub routes
        $routes[ '/sensei' ] = array(
            array( array( $this, 'send_plugin_data'), WP_JSON_Server::READABLE ),
        );

	    // Todo:
	    // return  courses with pagination links
	    // $routes[ '/courses' ] = array(
	    //    array( array( $this, 'get_courses'), WP_JSON_Server::READABLE ),
	    //);
	    // return a course with lesson from a given id
	    //$routes[ '/courses/(?P<id>\d+)' ] = array(
	    //   array( array( $this, 'get_course_details'), WP_JSON_Server::READABLE ),
	    //);
	    // * return courses active and completed by teh current users
	    // * return all lessons with pagnination
	    // ( retrn the lesson details fro a specific ID
	    // * return lessons for the currnt users
	    //$routes[ '/(?P<username>\w+)/courses' ] = array(
		//	    array( array( $this, 'get_user_courses'), WP_JSON_Server::READABLE ),
	    //);


	    return $routes;

    }// end register_sensei_routes

	/**
	 * Return Sensei specific plugin data for the /sensei route
	 *
	 * @since 1.7.0
	 */
    public function send_plugin_data ( ) {

	    global $woothemes_sensei;
        $sensei_data = array( 'plugin'=>'Sensei', 'version' => $woothemes_sensei->version );

        // call the wordpress function that returns the data with a success header
	    wp_send_json_success( $sensei_data );

    }// end send plugin data

	public function get_user_courses () {
		//
	}// end get_user_courses
}// end class