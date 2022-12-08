<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Functions related to allowing guest users take a course.
 *
 * @author Automattic
 *
 * @since $$next-version$$
 * @package Core
 */
class Sensei_Guest_User {

	/**
	 * Sensei_Guest_User constructor.
	 *
	 * @since $$next-version$$
	 */
	function __construct() {
		add_action( 'wp', array( $this, 'sensei_create_guest_user_for_open_course' ), 9 );
	}

	/**
	 * Create a guest user for open access courses if no user is logged in.
	 *
	 * @since  $$next-version$$
	 */
	public function sensei_create_guest_user_for_open_course() {
		global $post;

		// Create guest user for open course.
		if (
			is_singular( 'course' )
			&& isset( $_POST['course_start'] )
			&& wp_verify_nonce( $_POST['woothemes_sensei_start_course_noonce'], 'woothemes_sensei_start_course_noonce' )
			&& ! post_password_required( $post->ID )
			&& ! is_user_logged_in()
			&& get_post_meta( $post->ID, 'open_access', true )
		) {
			error_log('wtf1');
			$user_name = 'guest_user_'. rand( 10000000, 99999999 ) . '_' . get_user_count();
			$user_id = wp_create_user( $user_name, $user_name, $user_name . '@senseiguest.senseiguest' );
			error_log( $user_id );
			wp_set_current_user( $user_id );
			wp_set_auth_cookie( $user_id, true );
		}
	}
}
