<?php
/**
 * Temporary User
 *
 * Handles operations related to allowing Temporary users take a course.
 *
 * @package Sensei\Frontend
 * @since   1.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Sensei Temporary User Class.
 *
 * @author  Automattic
 *
 * @since   $$next-version$$
 * @package Core
 */
class Sensei_Temporary_User {

	/**
	 * Sensei_Temporary_User constructor.
	 *
	 * @since $$next-version$$
	 */
	public function __construct() {

	}

	/**
	 * Create a user without triggering user registration hooks.
	 *
	 * @param mixed $userdata wp_insert_user options.
	 *
	 * @return int|WP_Error
	 */
	public static function create_user( $userdata ) {
		remove_all_filters( 'user_register' );
		return wp_insert_user( $userdata );
	}

	/**
	 * Deletes a user.
	 *
	 * @param int $user_id User ID to delete.
	 *
	 * @return void
	 */
	public static function delete_user( int $user_id ): void {
		if ( is_multisite() ) {
			if ( ! function_exists( 'wpmu_delete_user' ) ) {
				require_once ABSPATH . '/wp-admin/includes/ms.php';
			}
			wpmu_delete_user( $user_id );
		} else {
			if ( ! function_exists( 'wp_delete_user' ) ) {
				require_once ABSPATH . 'wp-admin/includes/user.php';
			}
			wp_delete_user( $user_id );
		}
	}

}
