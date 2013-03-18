<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Sensei Utilities Class
 *
 * Common utility functions for Sensei.
 *
 * @package WordPress
 * @subpackage Sensei
 * @category Utilities
 * @author WooThemes
 * @since 1.0.0
 *
 * TABLE OF CONTENTS
 *
 * - get_placeholder_image()
 * - sensei_is_woocommerce_present()
 * - sensei_is_woocommerce_activated()
 * - sensei_log_Activity()
 * - sensei_check_for_activity()
 * - sensei_activity_ids()
 * - sensei_delete_activites()
 * - sensei_ get_activity_value()
 * - sensei_customer_bought_product()
 */
class WooThemes_Sensei_Utils {


	/**
	 * Get the placeholder thumbnail image.
	 * @since  1.0.0
	 * @return string The URL to the placeholder thumbnail image.
	 */
	public static function get_placeholder_image () {
		global $woothemes_sensei;
		return esc_url( apply_filters( 'sensei_placeholder_thumbnail', $woothemes_sensei->plugin_url . 'assets/images/placeholder.png' ) );
	} // End get_placeholder_image()

	/**
	 * sensei_is_woocommerce_present function.
	 * @since  1.0.2
	 * @access public
	 * @static
	 * @return void
	 */
	public static function sensei_is_woocommerce_present() {
		if ( class_exists( 'Woocommerce' ) ) {
			return true;
		} else {
			$active_plugins = apply_filters( 'active_plugins', get_option('active_plugins' ) );
			if ( in_array( 'woocommerce/woocommerce.php', $active_plugins ) ) {
				return true;
			} else {
				return false;
			} // End If Statement
		} // End If Statement
	} // End sensei_is_woocommerce_present()

	/**
	 * sensei_is_woocommerce_activated function.
	 *
	 * @access public
	 * since 1.0.2
	 * @static
	 * @return void
	 */
	public static function sensei_is_woocommerce_activated() {
		global $woothemes_sensei;
		if ( WooThemes_Sensei_Utils::sensei_is_woocommerce_present() && isset( $woothemes_sensei->settings->settings[ 'woocommerce_enabled' ] ) && $woothemes_sensei->settings->settings[ 'woocommerce_enabled' ] ) { return true; } else { return false; }
	} // End sensei_is_woocommerce_activated()

	/*-----------------------------------------------------------------------------------*/
	/* Activity Log Functions */
	/*-----------------------------------------------------------------------------------*/


	/**
	 * sensei_log_activity function.
	 *
	 * @access public
	 * @param array $args (default: array())
	 * @return void
	 */
	public static function sensei_log_activity( $args = array() ) {
		// Setup & Prep Data
		$time = current_time('mysql');
		// Args
		$data = array(
					    'comment_post_ID' => $args['post_id'],
					    'comment_author' => $args['username'],
					    'comment_author_email' => $args['user_email'],
					    'comment_author_url' => $args['user_url'],
					    'comment_content' => $args['data'],
					    'comment_type' => $args['type'],
					    'comment_parent' => $args['parent'],
					    'user_id' => $args['user_id'],
					    'comment_date' => $time,
					    'comment_approved' => 1,
					);
		// Custom Logic

		// Check if comment exists first
		if ( isset( $args['action'] ) && 'update' == $args['action'] ) {
			// Get existing comments ids
			$activity_ids = WooThemes_Sensei_Utils::sensei_activity_ids( array( 'post_id' => $args['post_id'], 'user_id' => $args['user_id'], 'type' => $args['type'], 'field' => 'comment' ) );
			if ( isset( $activity_ids[0] ) && 0 < $activity_ids[0] ) {
				$comment_id = $activity_ids[0];
			} // End If Statement
			$commentarr = array();
			if ( isset( $comment_id ) && 0 < $comment_id ) {
				// Get the comment
				$commentarr = get_comment( $comment_id, ARRAY_A);
			} // End If Statement
			if ( isset( $commentarr['comment_ID'] ) && 0 < $commentarr['comment_ID'] ) {
				// Update the comment
				$data['comment_ID'] = $commentarr['comment_ID'];
				$comment_id = wp_update_comment($data);
			} else {
				// Add the comment
				$comment_id = wp_insert_comment($data);
			} // End If Statement
		} else {
			// Add the comment
			$comment_id = wp_insert_comment($data);
		} // End If Statement
		// Manually Flush the Cache
		wp_cache_flush();

		// TODO - add action here for dev's to hook in to

		if ( 0 < $comment_id ) {
			return true;
		} else {
			return false;
		} // End If Statement
	} // End sensei_add_user_to_course()


	/**
	 * sensei_check_for_activity function.
	 *
	 * @access public
	 * @param array $args (default: array())
	 * @param bool $return_comments (default: false)
	 * @return void
	 */
	public static function sensei_check_for_activity( $args = array(), $return_comments = false ) {
		// Get comments
		$comments = get_comments( $args );
		// Return comments
		if ( $return_comments ) {
			return $comments;
		} // End If Statement
		// Count comments
		if ( is_array( $comments ) && ( 0 < intval( count( $comments ) ) ) ) {
			return true;
		} else {
			return false;
		} // End If Statement
	} // End sensei_check_for_activity()


	/**
	 * sensei_activity_ids function.
	 *
	 * @access public
	 * @param array $args (default: array())
	 * @return void
	 */
	public static function sensei_activity_ids( $args = array() ) {
		// Get comments
		$comments = get_comments( $args );
		$post_ids = array();
		// Count comments
		if ( is_array( $comments ) && ( 0 < intval( count( $comments ) ) ) ) {
			foreach ( $comments as $key => $value  ) {
				// Add matches to id array
				if ( isset( $args['field'] ) && 'comment' == $args['field'] ) {
					array_push( $post_ids, $value->comment_ID );
				} else {
					array_push( $post_ids, $value->comment_post_ID );
				} // End If Statement
			} // End For Loop
			// Reset array indexes
			$post_ids = array_unique( $post_ids );
			$post_ids = array_values( $post_ids );
		} // End If Statement
		return $post_ids;
	} // End sensei_activity_ids()


	/**
	 * sensei_delete_activities function.
	 *
	 * @access public
	 * @param array $args (default: array())
	 * @return void
	 */
	public static function sensei_delete_activities( $args = array() ) {
		$dataset_changes = false;
		// If activity exists
		if ( WooThemes_Sensei_Utils::sensei_check_for_activity( array( 'post_id' => $args['post_id'], 'user_id' => $args['user_id'], 'type' => $args['type'] ) ) ) {
    		// Remove activity from log
    	    $comments = WooThemes_Sensei_Utils::sensei_check_for_activity( array( 'post_id' => $args['post_id'], 'user_id' => $args['user_id'], 'type' => $args['type'] ), true );
    	    foreach ( $comments as $key => $value  ) {
		    	if ( isset( $value->comment_ID ) && 0 < $value->comment_ID ) {
		    		$dataset_changes = wp_delete_comment( $value->comment_ID, true );
		    		// Manually flush the cache
		    		wp_cache_flush();
		    	} // End If Statement
		    } // End For Loop
    	} // End If Statement
    	return $dataset_changes;
    } // End sensei_delete_activities()


	/**
	 * sensei_get_activity_value function.
	 *
	 * @access public
	 * @param array $args (default: array())
	 * @return void
	 */
	public static function sensei_get_activity_value( $args = array() ) {
		$activity_value = false;
		if ( isset( $args['user_id'] ) && 0 < intval( $args['user_id'] ) ) {
			// Get activities
			$comments = WooThemes_Sensei_Utils::sensei_check_for_activity( array( 'post_id' => $args['post_id'], 'user_id' => $args['user_id'], 'type' => $args['type'] ), true );
			foreach ( $comments as $key => $value  ) {
				// Get the activity value
			    if ( isset( $value->{$args['field']} ) && '' != $value->{$args['field']} ) {
			    	$activity_value = $value->{$args['field']};
			    } // End If Statement
			} // End For Loop
		} // End If Statement
		return $activity_value;
	} // End sensei_get_activity_value()

	/**
	 * sensei_customer_bought_product
	 *
	 * Checks if a user (by email) has bought an item
	 *
	 * @access public
	 * @param string $customer_email
	 * @param int $user_id
	 * @param int $product_id
	 * @return bool
	 */
	public static function sensei_customer_bought_product( $customer_email, $user_id, $product_id ) {
		global $wpdb;

		$emails = array();

		if ( $user_id ) {
			$user = get_user_by( 'id', $user_id );
			$emails[] = $user->user_email;
		}

		if ( is_email( $customer_email ) )
			$emails[] = $customer_email;

		if ( sizeof( $emails ) == 0 )
			return false;

		$orders = get_posts( array(
		    'numberposts' => -1,
		    'meta_key'    => '_customer_user',
		    'meta_value'  => $user_id,
		    'post_type'   => 'shop_order',
		    'post_status' => 'publish'
		) );
		
		foreach ( $orders as $order_id ) {
			$order = new WC_Order( $order_id->ID );
			if ( $order->status == 'completed' ) {
				if (sizeof($order->get_items())>0) {
					foreach($order->get_items() as $item) {
						if ( $item['product_id'] == $product_id || $item['variation_id'] == $product_id ) {
							return true;
						} // End If Statement
					} // End For Loop
				} // End If Statement

			} // End If Statement

		} // End For Loop
	} // End sensei_customer_bought_product()

} // End Class
?>