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
		add_action( 'admin_menu', array( $this, 'remove_meta_box' ) );

		// Save new private message
		add_action( 'init', array( $this, 'save_new_message' ), 1 );

		// Monitor when new reply is posted
		add_action( 'comment_post', array( $this, 'message_reply_received' ), 10, 1 );

		// Force comments open for all messages
		add_action( 'save_post', array( $this, 'open_message_comments' ) );

		// Add message links to courses & lessons
		add_action( 'sensei_course_single_lessons', array( $this, 'send_message_link' ), 1 );
		add_action( 'sensei_lesson_back_link', array( $this, 'send_message_link' ), 1 );

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

	public function send_message_link() {
		global $woothemes_sensei, $post;

		$html = '';

		if( ! isset( $woothemes_sensei->settings->settings['messages_disable'] ) || ! $woothemes_sensei->settings->settings['messages_disable'] ) {

			if( ! is_user_logged_in() ) return;

			if( isset( $_GET['contact'] ) ) {
				$html .= $this->teacher_contact_form( $post );
			} else {
				$href = add_query_arg( array( 'contact' => $post->post_type ) );
				$html .= '<p><a class="button" href="' . $href . '#private_message">' . sprintf( __( 'Contact %1$s Teacher', 'woothemes-sensei' ), ucfirst( $post->post_type ) ) . '</a></p>';
			}

			if( isset( $this->message_notice ) && isset( $this->message_notice['type'] ) && isset( $this->message_notice['notice'] ) ) {
				$html .= '<div class="sensei-message ' . $this->message_notice['type'] . '">' . $this->message_notice['notice'] . '</div>';
			}

		}

		echo $html;
	}

	public function teacher_contact_form( $post ) {

		if( ! is_user_logged_in() ) return;

		global $current_user;
		wp_get_current_user();

		$html = '';

		if( ! isset( $post->ID ) ) return $html;

		$html .= '<h3 id="private_message">' . __( 'Send Private Message', 'woothemes-sensei' ) . '</h3>';

		$html .= '<form name="contact-teacher" action="" method="post">';
			$html .= '<p class="form-row">';
				$html .= '<textarea name="contact_message" placeholder="' . __( 'Enter your private message.', 'woothemes-sensei' ) . '"></textarea>';
			$html .= '</p>';
			$html .= '<p class="form-row">';
				$html .= '<input type="hidden" name="post_id" value="' . $post->ID . '" />';
				$html .= '<input type="hidden" name="sender_id" value="' . $current_user->ID . '" />';
				$html .= '<input type="hidden" name="receiver_id" value="' . $post->post_author . '" />';
				$html .= wp_nonce_field( 'message_teacher', 'sensei_message_teacher_nonce', true, false );
				$html .= '<input type="submit" class="send_message" value="' . __( 'Send Message', 'woothemes-sensei' ) . '" />';
			$html .= '</p>';
		$html .= '</form>';

		return $html;
	}

	public function save_new_message() {

		if( ! isset( $_POST['sensei_message_teacher_nonce'] ) ) return;

		if( ! wp_verify_nonce( $_POST['sensei_message_teacher_nonce'], 'message_teacher' ) ) return;

		$message_id = $this->save_new_message_post( $_POST['sender_id'], $_POST['receiver_id'], $_POST['contact_message'], $_POST['post_id'] );

		if( $message_id ) {

			$message_url = get_permalink( $message_id );

			$this->message_notice['type'] = 'tick';
			$this->message_notice['notice'] = sprintf( __( 'Your private message has been sent - you can view the message and its replies %1$shere%2$s.', 'woothemes-sensei' ), '<a href="' . esc_url( $message_url ) . '">', '</a>' );
		} else {
			$this->message_notice['type'] = 'alert';
			$this->message_notice['notice'] = __( 'There was an error sending your message - please try again.', 'woothemes-sensei' );
		}

	}

	public function message_reply_received( $comment_id = 0 ) {

		// Get comment object
    	$comment = get_comment( $comment_id );

		if( is_null( $comment ) ) return;

		// Get message post object
		$message = get_post( $comment->comment_post_ID );

		if( $message->post_type != $this->post_type ) return;

		// Force comment to be approved
		wp_set_comment_status( $comment_id, 'approve' );

		do_action( 'sensei_private_message_reply', $comment, $message );

		// // Get message permalink
		// $message_url = get_permalink( $message->ID );

  //   	// Get commenter data
  //   	$commenter = $comment->comment_author;

  //   	// Get original sender & receiver data
  //   	$message_sender = get_post_meta( $message->ID, 'sender', true );
  //   	$message_receiver = get_post_meta( $message->ID, 'receiver', true );

  //   	$comment_link = get_comment_link( $comment );
	}

	/**
     * Save new message post
     * @param  integer $sender_id   ID of sender
     * @param  integer $receiver_id ID of receiver
     * @param  string  $message     Message content
     * @param  string  $post_id     ID of post related to message
     * @return mixed                Message ID on success, boolean false on failure
     */
    private function save_new_message_post( $sender_id = 0, $receiver_id = 0, $message = '', $post_id = 0 ) {

    	$message_id = false;

    	if( $sender_id && $receiver_id && $message && $post_id ) {

    		$title = wp_trim_words( $message, 8, '...' );

    		// Set up post data for message
	    	$message_data = array(
	            'post_type'      => $this->post_type,
	            'post_title'     => esc_html( $title ),
	            'post_content'   => esc_html( $message ),
	            'post_status'    => 'publish',
	            'ping_status'    => 'closed',
	            'comment_status' => 'open',
	            'post_excerpt'   => '',
	            'post_author'	 => intval( $sender_id )
	        );

	    	// Insert post
	        $message_id = wp_insert_post( $message_data );

	        if( ! is_wp_error( $message_id ) ) {

	        	// Add sender to message meta
	        	$sender = get_userdata( $sender_id );
	        	add_post_meta( $message_id, 'sender', $sender->user_login );

	        	// Add receiver to message meta
	        	$receiver = get_userdata( $receiver_id );
		        add_post_meta( $message_id, 'receiver', $receiver->user_login );

		        // Add lesson/course ID to message meta
		        $post = get_post( $post_id );
		        add_post_meta( $message_id, $post->post_type, $post_id );

		        do_action( 'sensei_new_private_message', $message_id );

		    } else {
		    	$message_id = false;
		    }
	    }

	    return $message_id;
    }

	/**
	 * Check if user has access to view this message
	 * @param  integer $message_id Post ID of message
	 * @param  integer $user_id    ID of user
	 * @return boolean             True if user has access to this message
	 */
	private function view_message( $message_id, $user_id = 0) {

		if( ! is_user_logged_in() ) return false;

		if( $user_id == 0 ) {
			global $current_user;
			wp_get_current_user();
			$user_login = $current_user->user_login;
		}

		// Get allowed users
		$receiver = get_post_meta( $message_id, 'receiver', true );
		$sender = get_post_meta( $message_id, 'sender', true );

		// Check if user is allowed to view the message
		if( in_array( $user_login, array( $receiver, $sender ) ) ) {
			return true;
		}

		// Return false if user is not allowed access
		return false;
	}

	/**
	 * Remove unneeded meta boxes from Messages posts
	 * @return void
	 */
	public function remove_meta_box() {
		remove_meta_box('commentstatusdiv', $this->post_type, 'normal');
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