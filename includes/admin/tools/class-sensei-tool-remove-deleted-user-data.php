<?php
/**
 * File containing Sensei_Tool_Remove_Deleted_User_Data class.
 *
 * @package sensei-lms
 * @since 3.7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Sensei_Tool_Remove_Deleted_User_Data class.
 *
 * @since 3.7.0
 */
class Sensei_Tool_Remove_Deleted_User_Data implements Sensei_Tool_Interface {
	/**
	 * Get the ID of the tool.
	 *
	 * @return string
	 */
	public function get_id() {
		return 'remove-deleted-user-data';
	}

	/**
	 * Get the name of the tool.
	 *
	 * @return string
	 */
	public function get_name() {
		return __( 'Remove Deleted User Data', 'sensei-lms' );
	}

	/**
	 * Get the description of the tool.
	 *
	 * @return string
	 */
	public function get_description() {
		return __( 'Removes course, lesson, and quiz progress for deleted users. This should have been done automatically since Sensei LMS v3.0.', 'sensei-lms' );
	}

	/**
	 * Is the tool a single action?
	 *
	 * @return bool
	 */
	public function is_single_action() {
		return true;
	}

	/**
	 * Run the tool.
	 */
	public function run() {
		global $wpdb;

		$comment_ids = $wpdb->get_col( "SELECT c.`comment_ID` FROM {$wpdb->comments} c LEFT JOIN {$wpdb->users} u ON u.`ID` = c.`user_id` WHERE c.`user_id` > 0 AND u.`ID` IS NULL AND c.`comment_type` LIKE \"sensei_%\"");

		if ( ! empty( $comment_ids ) ) {
			$wpdb->query( "DELETE FROM {$wpdb->comments} WHERE `comment_ID` IN (" . implode( ',', $comment_ids ) . ")" );
			$wpdb->query( "DELETE FROM {$wpdb->commentmeta} WHERE `comment_id` IN (" . implode( ',', $comment_ids ) . ")" );
			Sensei_Tools::instance()->add_user_message( __( 'Progress data from deleted users was deleted.', 'sensei-lms' ) );
		} else {
			Sensei_Tools::instance()->add_user_message( __( 'No progress data was found from deleted users.', 'sensei-lms' ) );
		}
	}
}
