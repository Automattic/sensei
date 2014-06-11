<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Sensei Messages Class
 *
 * All functionality pertaining to the Messages post type in Sensei.
 *
 * @package WordPress
 * @subpackage Sensei
 * @category Core
 * @author WooThemes
 * @since 1.6.0
 *
 * TABLE OF CONTENTS
 *
 * - __construct()
 */
class WooThemes_Sensei_Messages {
	public $token;
	public $post_type;
	public $meta_fields;

	/**
	 * Constructor.
	 * @since  1.6.0
	 */
	public function __construct () {
		$this->post_type = 'sensei_message';
		$this->meta_fields = array( 'sender', 'receiver' );

		// Add Messages page to admin menu
		add_action( 'admin_menu', array( $this, 'add_menu_item' ), 11 );

		// Force comments open for all messages
		add_action( 'save_post', array( $this, 'open_message_comments' ) );

		// Hide messages and replies from users who do not have access
        add_action( 'pre_get_posts', array( $this, 'message_list' ), 10, 1 );
        add_filter( 'the_title', array( $this, 'message_title' ), 10, 2 );
        add_filter( 'the_content', array( $this, 'message_content' ), 10, 1 );
        add_filter( 'comments_array', array( $this, 'message_replies' ), 100, 1 );
        add_filter( 'get_comments_number', array( $this, 'message_reply_count' ), 100, 2 );
        add_filter( 'comments_open', array( $this, 'message_replies_open' ), 100, 2 );
	} // End __construct()

	public function add_menu_item() {
		global $woothemes_sensei;
		if( ! isset( $woothemes_sensei->settings->settings['messages_disable'] ) || ! $woothemes_sensei->settings->settings['messages_disable'] ) {
			add_submenu_page( 'sensei', __( 'Messages', 'woothemes-sensei'),  __( 'Messages', 'woothemes-sensei') , 'manage_sensei', 'edit.php?post_type=sensei_message' );
		}
	}

	public function open_message_comments( $post_id = 0 ) {

		if( $this->post_type != $_REQUEST['post_type'] ) return;

		remove_action( 'save_post', array( $this, 'open_message_comments' ) );

		wp_update_post( array( 'ID' => $post_id, 'comment_status' => 'open' ) );

		add_action( 'save_post', array( $this, 'open_message_comments' ) );
	}

	/**
	 * Check if user has access to view this message
	 * @param  integer $message_id Post ID of message
	 * @param  integer $user_id    ID of user
	 * @return boolean             True if user has access to this message
	 */
	private function view_message( $message_id, $user_id = 0) {
		if( $user_id == 0 ) {
			global $current_user;
			wp_get_current_user();
			$user_id = $current_user->ID;
		}

		// Get allowed users
		$receiver_id = get_post_meta( $message_id, 'receiver', true );
		$sender_id = get_post_meta( $message_id, 'sender', true );

		// Check if user is allowed to view the message
		if( in_array( $user_id, array( $receiver_id, $sender_id ) ) ) {
			return true;
		}

		// Return false if user is not allowed access
		return false;
	}

	/**
     * Only show allowed messages in messages archive
     * @param  array $query Original query
     * @return void
     */
	public function message_list( $query ) {
		global $current_user;

		if( is_admin() ) return;

		if( is_post_type_archive( $this->post_type ) && $query->is_main_query() ) {
			wp_get_current_user();
			$user_id = $current_user->ID;

			$meta_query['relation'] = 'OR';

			$meta_query[] = array(
				'key' => 'sender',
				'value' => $user_id,
				'compare' => '='
			);

			$meta_query[] = array(
				'key' => 'receiver',
				'value' => $user_id,
				'compare' => '='
			);

			$query->set( 'meta_query', $meta_query );

			return;
		}
	}

	/**
	 * Hide message title
	 * @param  string $title    Original message title
	 * @param  integer $post_id ID of post
	 * @return string           Modified string if user does not have access to this message
	 */
	public function message_title( $title, $post_id ) {

		if( is_single() && is_singular( $this->post_type ) && in_the_loop() ) {
			if( ! is_user_logged_in() || ! $this->view_message( $post_id ) ) {
				$title = __( 'You are not allowed to view this message.', 'woothemes-sensei' );
			}
		}

		return $title;
	}

	/**
	 * Hide content of message
	 * @param  string $content Original message content
	 * @return string          Empty string if user does not have access to this message
	 */
	public function message_content( $content ) {
		global $post;

		if( is_single() && is_singular( $this->post_type ) && in_the_loop() ) {
			if( ! is_user_logged_in() || ! $this->view_message( $post->ID ) ) {
				$content = __( 'Please log in to to view your messages.', 'woothemes-sensei' );
			}
		}

		return $content;
	}

	/**
	 * Hide all replies
	 * @param  array $comments Array of replies
	 * @return array           Empty array if user does not have access to this message
	 */
	public function message_replies( $comments ) {
		global $post;

		if( is_single() && is_singular( $this->post_type ) && in_the_loop() ) {
			if( ! is_user_logged_in() || ! $this->view_message( $post->ID ) ) {
				$comments = array();
			}
		}

		return $comments;
	}

	/**
	 * Set message reply count to 0
	 * @param  integer $count   Default count
	 * @param  integer $post_id ID of post
	 * @return integer          0 if user does not have access to this message
	 */
	public function message_reply_count( $count, $post_id ) {
		global $post;

		if( is_single() && is_singular( $this->post_type ) && in_the_loop() ) {
			if( ! is_user_logged_in() || ! $this->view_message( $post->ID ) ) {
				$count = 0;
			}
		}

		return $count;
	}

	/**
	 * Close replies for messages
	 * @param  boolean $open    Current comment open status
	 * @param  integer $post_id ID of post
	 * @return boolean          False if user does not have access to this message
	 */
	public function message_replies_open( $open, $post_id ) {
		global $post;

		if( is_single() && is_singular( $this->post_type ) && in_the_loop() ) {
			if( ! is_user_logged_in() || ! $this->view_message( $post->ID ) ) {
				$open = false;
			}
		}

		return $open;
	}

} // End Class
?>