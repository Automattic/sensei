<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Sensei Messages Class
 *
 * All functionality pertaining to the Messages post type in Sensei.
 *
 * @package Users
 * @author Automattic
 *
 * @since 1.6.0
 */
class Sensei_Messages {
	public $token;
	public $post_type;
	public $meta_fields;

	/**
	 * Constructor.
	 *
	 * @since  1.6.0
	 */
	public function __construct() {
		$this->token       = 'messages';
		$this->post_type   = 'sensei_message';
		$this->meta_fields = array( 'sender', 'receiver' );

		// Add Messages page to admin menu
		add_action( 'admin_menu', array( $this, 'add_menu_item' ), 40 );
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ), 10, 2 );
		add_action( 'admin_menu', array( $this, 'remove_meta_box' ) );

		// Save new private message (priority low to ensure sensei_message post type is
		// registered
		add_action( 'init', array( $this, 'save_new_message' ), 101 );

		// Monitor when new reply is posted
		add_action( 'comment_post', array( $this, 'message_reply_received' ), 10, 1 );

		// Block WordPress from sending comment update emails for the messages post type
		add_filter( 'comment_notification_recipients', array( $this, 'stop_wp_comment_emails' ), 20, 2 );

		// Block WordPress from sending comment moderator emails on the sensei messages post types
		add_filter( 'comment_moderation_recipients', array( $this, 'stop_wp_comment_emails' ), 20, 2 );

		// add message link to lesson
		add_action( 'sensei_single_lesson_content_inside_before', array( $this, 'send_message_link' ), 30, 2 );

		// add message link to lesson
		add_action( 'sensei_single_quiz_questions_before', array( $this, 'send_message_link' ), 10, 2 );

		// Hide messages and replies from users who do not have access
		add_action( 'template_redirect', array( $this, 'message_login' ), 10, 1 );
		add_action( 'pre_get_posts', array( $this, 'message_list' ), 10, 1 );
		add_filter( 'the_title', array( $this, 'message_title' ), 10, 2 );
		add_filter( 'the_content', array( $this, 'message_content' ), 10, 1 );
		add_filter( 'comments_array', array( $this, 'message_replies' ), 100, 1 );
		add_filter( 'get_comments_number', array( $this, 'message_reply_count' ), 100, 2 );
		add_filter( 'comments_open', array( $this, 'message_replies_open' ), 100, 2 );
		add_action( 'pre_get_posts', array( $this, 'only_show_messages_to_owner' ) );
		add_filter( 'comment_feed_where', array( $this, 'exclude_message_comments_from_feed_where' ) );
		add_filter( 'user_has_cap', [ $this, 'user_messages_cap_check' ], 10, 3 );
		add_action( 'load-edit-comments.php', [ $this, 'check_permissions_edit_comments' ] );
	}

	public function only_show_messages_to_owner( $query ) {
		if ( is_admin() ) {
			return;
		}

		if ( ! $query->is_main_query() ) {
			return;
		}

		if ( $this->post_type !== $query->get( 'post_type' ) ) {
			return;
		}

		if ( current_user_can( 'manage_sensei_grades' ) ) {
			return;
		}

		if ( ! is_user_logged_in() ) {
			// Handled further down the hook chain.
			return;
		}

		$username = wp_get_current_user()->user_login;

		$meta_query = array(
			'relation' => 'OR',
		);

		$meta_query[] = array(
			'key'     => '_sender',
			'value'   => $username,
			'compare' => '=',
		);

		$meta_query[] = array(
			'key'     => '_receiver',
			'value'   => $username,
			'compare' => '=',
		);

		$query->set( 'meta_query', $meta_query );
	}

	public function add_menu_item() {

		if ( ! isset( Sensei()->settings->settings['messages_disable'] ) || ! Sensei()->settings->settings['messages_disable'] ) {
			add_submenu_page( 'sensei', __( 'Messages', 'sensei-lms' ), __( 'Messages', 'sensei-lms' ), 'edit_courses', 'edit.php?post_type=sensei_message' );
		}
	}

	public function add_meta_box( $post_type, $post ) {

		if ( ! $post_type == $this->post_type ) {
			return;
		}

		add_meta_box( $this->post_type . '-data', __( 'Message Information', 'sensei-lms' ), array( $this, 'meta_box_content' ), $this->post_type, 'normal', 'default' );

	}

	public function meta_box_content() {
		global  $post;

		$settings = array(
			array(
				'id'          => 'sender',
				'label'       => __( 'Message sent by:', 'sensei-lms' ),
				'description' => __( 'The username of the learner who sent this message.', 'sensei-lms' ),
				'type'        => 'plain-text',
				'default'     => get_post_meta( $post->ID, '_sender', true ),
			),
			array(
				'id'          => 'receiver',
				'label'       => __( 'Message received by:', 'sensei-lms' ),
				'description' => __( 'The username of the teacher who received this message.', 'sensei-lms' ),
				'type'        => 'plain-text',
				'default'     => get_post_meta( $post->ID, '_receiver', true ),
			),
		);

		$message_posttype = get_post_meta( $post->ID, '_posttype', true );

		if ( isset( $message_posttype ) && $message_posttype ) {

			$course      = get_post( get_post_meta( $post->ID, '_post', true ) );
			$course_name = $course->post_title;

			switch ( $message_posttype ) {
				case 'course':
					$label       = __( 'Message from course:', 'sensei-lms' );
					$description = __( 'The course to which this message relates.', 'sensei-lms' );
					break;
				case 'lesson':
					$label       = __( 'Message from lesson:', 'sensei-lms' );
					$description = __( 'The lesson to which this message relates.', 'sensei-lms' );
					break;
				case 'quiz':
					$label       = __( 'Message from quiz:', 'sensei-lms' );
					$description = __( 'The quiz to which this message relates.', 'sensei-lms' );
					break;
			}

			$settings[] = array(
				'id'          => 'post',
				'label'       => $label,
				'description' => $description,
				'type'        => 'plain-text',
				'default'     => $course_name,
			);
		}

		echo wp_kses(
			Sensei()->admin->render_settings( $settings, $post->ID, 'message-info' ),
			array_merge(
				wp_kses_allowed_html( 'post' ),
				array(
					'input'    => array(
						'checked'     => array(),
						'class'       => array(),
						'disabled'    => array(),
						'id'          => array(),
						'max'         => array(),
						'min'         => array(),
						'name'        => array(),
						'placeholder' => array(),
						'type'        => array(),
						'value'       => array(),
					),
					// Explicitly allow label tag for WP.com.
					'label'    => array(
						'for' => array(),
					),
					'option'   => array(
						'selected' => array(),
						'value'    => array(),
					),
					'select'   => array(
						'disabled' => array(),
						'id'       => array(),
						'multiple' => array(),
						'name'     => array(),
					),
					'textarea' => array(
						'cols'        => array(),
						'disabled'    => array(),
						'id'          => array(),
						'name'        => array(),
						'placeholder' => array(),
						'rows'        => array(),
					),
				)
			)
		);
	}

	public function send_message_link( $post_id = 0, $user_id = 0 ) {
		global  $post;

		// only show the link for the allowed post types:
		$allowed_post_types = array( 'lesson', 'course', 'quiz' );
		if ( ! in_array( get_post_type(), $allowed_post_types ) ) {

			return;

		}

		$html = '';

		if ( ! isset( Sensei()->settings->settings['messages_disable'] ) || ! Sensei()->settings->settings['messages_disable'] ) {

			if ( ! is_user_logged_in() ) {
				return;
			}

			if ( isset( $_GET['contact'] ) ) {
				$html .= $this->teacher_contact_form( $post );
			} else {
				$href = add_query_arg( array( 'contact' => $post->post_type ) );

				if ( 'lesson' == $post->post_type ) {
					$contact_button_text = __( 'Contact Lesson Teacher', 'sensei-lms' );
				} elseif ( 'course' == $post->post_type ) {
					$contact_button_text = __( 'Contact Course Teacher', 'sensei-lms' );
				} else {
					$contact_button_text = __( 'Contact Teacher', 'sensei-lms' );
				}

				$class = Sensei()->blocks->has_sensei_blocks() ? '' : 'button';

				$html .= '<p><a class="' . esc_attr( $class ) . ' send-message-button" href="' . esc_url( $href ) . '#private_message">' . esc_html( $contact_button_text ) . '</a></p>';
			}

			if ( isset( $this->message_notice ) && isset( $this->message_notice['type'] ) && isset( $this->message_notice['notice'] ) ) {
				$html .= '<div class="sensei-message ' . esc_attr( $this->message_notice['type'] ) . '">' . esc_html( $this->message_notice['notice'] ) . '</div>';
			}
		}

		/**
		 * Filter the send message link
		 *
		 * @since 1.9.18
		 *
		 * @param string    $html
		 * @param array     $this->message_notice
		 * @param int       $post_id
		 * @param int       $user_id
		 *
		 * @return string
		 */
		echo wp_kses(
			apply_filters( 'sensei_messages_send_message_link', $html, isset( $this->message_notice ) ? $this->message_notice : '', $post_id, $user_id ),
			array_merge(
				wp_kses_allowed_html( 'post' ),
				array(
					// Explicitly allow form tag for WP.com.
					'form'     => array(
						'action' => array(),
						'class'  => array(),
						'method' => array(),
						'name'   => array(),
					),
					'input'    => array(
						'class' => array(),
						'name'  => array(),
						'type'  => array(),
						'value' => array(),
					),
					'textarea' => array(
						'name'        => array(),
						'placeholder' => array(),
					),
				)
			)
		);
	}

	public function teacher_contact_form( $post ) {
		if ( ! is_user_logged_in() ) {
			return;
		}

		wp_get_current_user();

		$html = '';

		if ( ! isset( $post->ID ) ) {
			return $html;
		}

		// confirm private message
		$confirmation = '';
		if ( isset( $_GET['send'] ) && 'complete' == $_GET['send'] ) {

			$confirmation_message = __( 'Your private message has been sent.', 'sensei-lms' );
			$confirmation         = '<div class="sensei-message tick">' . esc_html( $confirmation_message ) . '</div>';

		}

		$html         .= '<h3 id="private_message">' . esc_html__( 'Send Private Message', 'sensei-lms' ) . '</h3>';
		$html         .= '<p>';
		$html         .= $confirmation;
		$html         .= '</p>';
		$html         .= '<form name="contact-teacher" action="" method="post" class="contact-teacher">';
			$html     .= '<p class="form-row form-row-wide">';
				$html .= '<textarea name="contact_message" placeholder="' . esc_attr__( 'Enter your private message.', 'sensei-lms' ) . '"></textarea>';
			$html     .= '</p>';
			$html     .= '<p class="form-row">';
				$html .= '<input type="hidden" name="post_id" value="' . esc_attr( absint( $post->ID ) ) . '" />';
				$html .= wp_nonce_field( 'message_teacher', 'sensei_message_teacher_nonce', true, false );
				$html .= '<input type="submit" class="send_message" value="' . esc_attr__( 'Send Message', 'sensei-lms' ) . '" />';
			$html     .= '</p>';
			$html     .= '<div class="fix"></div>';
		$html         .= '</form>';

		return $html;
	}

	public function save_new_message() {

		if ( ! isset( $_POST['sensei_message_teacher_nonce'] ) || ! isset( $_POST['post_id'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( $_POST['sensei_message_teacher_nonce'], 'message_teacher' ) ) {
			return;
		}

		$post         = get_post( absint( $_POST['post_id'] ) );
		$current_user = wp_get_current_user();

		if ( is_wp_error( $post ) ) {
			return false;
		}

		$this->save_new_message_post( $current_user->ID, $post->post_author, sanitize_text_field( $_POST['contact_message'] ), $post->ID );
	}

	public function message_reply_received( $comment_id = 0 ) {

		// Get comment object
		$comment = get_comment( $comment_id );

		if ( is_null( $comment ) ) {
			return;
		}

		// Get message post object
		$message = get_post( $comment->comment_post_ID );

		if ( $message->post_type != $this->post_type ) {
			return;
		}

		// Force comment to be approved
		wp_set_comment_status( $comment_id, 'approve' );

		do_action( 'sensei_private_message_reply', $comment, $message );
	}

	/**
	 * This function stops WordPress from sending the default comment update emails.
	 *
	 * This function is hooked into comment_notification_recipients. It will simply return
	 * an empty array if the current passed in comment is on a message post type.
	 *
	 * @param array $emails
	 * @param int   $comment_id
	 * @return array;
	 */
	public function stop_wp_comment_emails( $emails, $comment_id ) {

		$comment = get_comment( $comment_id );
		if ( isset( $comment->comment_post_ID ) &&
			'sensei_message' == get_post_type( $comment->comment_post_ID ) ) {

			// empty the emails array to ensure no emails are sent for this comment
			$emails = array();

		}
		return $emails;

	}

	/**
	 * Save new message post
	 *
	 * @param  integer $sender_id   ID of sender
	 * @param  integer $receiver_id ID of receiver
	 * @param  string  $message     Message content
	 * @param  string  $post_id     ID of post related to message
	 * @return mixed                Message ID on success, boolean false on failure
	 */
	private function save_new_message_post( $sender_id = 0, $receiver_id = 0, $message = '', $post_id = 0 ) {

		$message_id = false;

		if ( $sender_id && $receiver_id && $message && $post_id ) {

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
				'post_author'    => intval( $sender_id ),
			);

			// Insert post
			$message_id = wp_insert_post( $message_data );

			if ( ! is_wp_error( $message_id ) ) {

				// Add sender to message meta
				$sender = get_userdata( $sender_id );
				add_post_meta( $message_id, '_sender', $sender->user_login );

				// Add receiver to message meta
				$receiver = get_userdata( $receiver_id );
				add_post_meta( $message_id, '_receiver', $receiver->user_login );

				// Add lesson/course ID to message meta
				$post = get_post( $post_id );
				add_post_meta( $message_id, '_posttype', $post->post_type );
				add_post_meta( $message_id, '_post', $post->ID );

				do_action( 'sensei_new_private_message', $message_id );

			} else {

				$message_id = false;

			}
		}

		return $message_id;
	}

	/**
	 * Checks if a user is capable of seeing a particular message.
	 *
	 * @param array $allcaps All capabilities.
	 * @param array $caps    Capabilities.
	 * @param array $args    Arguments.
	 *
	 * @return array The filtered array of all capabilities.
	 */
	public function user_messages_cap_check( $allcaps, $caps, $args ) {
		if ( isset( $caps[0] ) && 'read' === $caps[0] ) {
			$user_id      = isset( $args[1] ) ? intval( $args[1] ) : false;
			$user         = $user_id ? get_user_by( 'ID', $user_id ) : false;
			$message_post = isset( $args[2] ) ? get_post( $args[2] ) : false;

			if ( $message_post && 'sensei_message' === $message_post->post_type ) {
				$receiver_username   = get_post_meta( $message_post->ID, '_receiver', true );
				$sender_username     = get_post_meta( $message_post->ID, '_sender', true );
				$is_user_participant = $user
										&& $receiver_username
										&& $sender_username
										&& in_array( $user->user_login, [ $receiver_username, $sender_username ], true );

				$allcaps['read'] = current_user_can( 'manage_sensei' ) || $is_user_participant;
			}
		}

		return $allcaps;
	}

	/**
	 * Make sure user has permission to see the post content of a private message.
	 */
	public function check_permissions_edit_comments() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- No action based on input.
		$post_id = isset( $_REQUEST['p'] ) ? (int) $_REQUEST['p'] : false;
		if ( ! $post_id || 'sensei_message' !== get_post_type( $post_id ) ) {
			return;
		}

		if ( ! current_user_can( 'read_post', $post_id ) ) {
			wp_die(
				'<h1>' . esc_html__( 'You need a higher level of permission.', 'sensei-lms' ) . '</h1>' .
				'<p>' . esc_html__( 'Sorry, you are not allowed to edit this post\'s comments.', 'sensei-lms' ) . '</p>',
				403
			);
		}
	}

	/**
	 * Check if user has access to view this message
	 *
	 * @param  integer $message_id Post ID of message
	 * @param  integer $user_id    ID of user
	 * @return boolean             True if user has access to this message
	 */
	private function view_message( $message_id, $user_id = 0 ) {

		if ( ! is_user_logged_in() ) {
			return false;
		}

		if ( $user_id == 0 ) {
			global $current_user;
			wp_get_current_user();
			$user_login = $current_user->user_login;
		}

		// Get allowed users
		$receiver = get_post_meta( $message_id, '_receiver', true );
		$sender   = get_post_meta( $message_id, '_sender', true );

		// Check if user is allowed to view the message
		if ( in_array( $user_login, array( $receiver, $sender ) ) ) {
			return true;
		}

		// Return false if user is not allowed access
		return false;
	}

	/**
	 * Remove unneeded meta boxes from Messages posts
	 *
	 * @return void
	 */
	public function remove_meta_box() {
		remove_meta_box( 'commentstatusdiv', $this->post_type, 'normal' );
	}

	/**
	 * Function message_login()
	 *
	 * Only show /messages/* to logged in users, and
	 * redirect logged out users to wp-login.php
	 *
	 * @since 1.9.0
	 * @param  none
	 * @return void
	 */

	public function message_login() {

		if ( is_user_logged_in() ) {

			return;
		}

		$settings = Sensei()->settings->get_settings();
		if ( isset( $settings['my_course_page'] )
			&& 0 < intval( $settings['my_course_page'] ) ) {

			$my_courses_page_id = $settings['my_course_page'];

			$my_courses_url = get_permalink( $my_courses_page_id );

		}

		if ( is_single() && is_singular( $this->post_type )
			|| is_post_type_archive( $this->post_type ) ) {

			if ( isset( $my_courses_url ) ) {

				wp_redirect( $my_courses_url, 303 );
				exit;
			} else {

				wp_redirect( home_url( '/wp-login.php' ), 303 );
				exit;
			}
		}
	}

	/**
	 * Exclude message comments from feed queries and RSS.
	 *
	 * @since 2.0.2
	 * @access private
	 *
	 * @param  string $where The WHERE clause of the query.
	 * @return string
	 */
	public function exclude_message_comments_from_feed_where( $where ) {
		if ( is_singular() ) {
			return $where;
		}

		return $where . ( $where ? ' AND ' : '' ) . " post_type != 'sensei_message' ";
	}

	/**
	 * Only show allowed messages in messages archive
	 *
	 * @param  WP_Query $query Original query
	 * @return void
	 */
	public function message_list( $query ) {
		global $current_user;

		if ( is_admin() ) {
			return;
		}

		$meta_query = [];

		if ( $query->is_main_query() && is_post_type_archive( $this->post_type ) ) {
			wp_get_current_user();
			$username = $current_user->user_login;

			$meta_query['relation'] = 'OR';

			$meta_query[] = array(
				'key'     => '_sender',
				'value'   => $username,
				'compare' => '=',
			);

			$meta_query[] = array(
				'key'     => '_receiver',
				'value'   => $username,
				'compare' => '=',
			);

			$query->set( 'meta_query', $meta_query );

			return;
		}
	}

	/**
	 * Hide message title
	 *
	 * @param  string  $title    Original message title
	 * @param  integer $post_id ID of post
	 * @return string           Modified string if user does not have access to this message
	 */
	public function message_title( $title = '', $post_id = null ) {

		if ( is_single() && is_singular( $this->post_type ) && in_the_loop() && get_post_type( $post_id ) == $this->post_type ) {
			if ( ! is_user_logged_in() || ! $this->view_message( $post_id ) ) {
				$title = __( 'You are not allowed to view this message.', 'sensei-lms' );
			}
		}

		return $title;
	}

	/**
	 * Hide content of message
	 *
	 * @param  string $content Original message content
	 * @return string          Empty string if user does not have access to this message
	 */
	public function message_content( $content ) {
		global $post;

		if ( is_single() && is_singular( $this->post_type ) && in_the_loop() ) {
			if ( ! is_user_logged_in() || ! $this->view_message( $post->ID ) ) {
				$content = __( 'Please log in to view your messages.', 'sensei-lms' );
			}
		}

		return $content;
	}

	/**
	 * Hide all replies
	 *
	 * @param  array $comments Array of replies
	 * @return array           Empty array if user does not have access to this message
	 */
	public function message_replies( $comments ) {
		global $post;

		if ( is_single() && is_singular( $this->post_type ) && in_the_loop() ) {
			if ( ! is_user_logged_in() || ! $this->view_message( $post->ID ) ) {
				$comments = array();
			}
		}

		return $comments;
	}

	/**
	 * Set message reply count to 0
	 *
	 * @param  integer $count   Default count
	 * @param  integer $post_id ID of post
	 * @return integer          0 if user does not have access to this message
	 */
	public function message_reply_count( $count, $post_id ) {
		global $post;

		if ( is_single() && is_singular( $this->post_type ) && in_the_loop() ) {
			if ( ! is_user_logged_in() || ! $this->view_message( $post->ID ) ) {
				$count = 0;
			}
		}

		return $count;
	}

	/**
	 * Close replies for messages
	 *
	 * @param  boolean $open    Current comment open status
	 * @param  integer $post_id ID of post
	 * @return boolean          False if user does not have access to this message
	 */
	public function message_replies_open( $open, $post_id ) {
		global $post;

		if ( is_single() && is_singular( $this->post_type ) && in_the_loop() ) {
			if ( ! is_user_logged_in() || ! $this->view_message( $post->ID ) ) {
				$open = false;
			}
		}

		return $open;
	}

	/**
	 * Print outthe message was sent by $sender_username on the
	 *
	 * @since 1.9.0
	 */
	public static function the_message_sent_by_title() {

		$sender_username = get_post_meta( get_the_ID(), '_sender', true );
		if ( $sender_username ) {

			$sender = get_user_by( 'login', $sender_username ); ?>

			<p class="message-meta">
				<small>
					<em>
						<?php
						// translators: Placeholders are the sender's display name and the date, respectively.
						echo esc_html( sprintf( __( 'Sent by %1$s on %2$s.', 'sensei-lms' ), $sender->display_name, get_the_date() ) );
						?>
					</em>
				</small>
			</p>

			<?php
		}

	}

	/**
	 * sensei_single_title output for single page title
	 *
	 * @since  1.1.0
	 * @return void
	 * @deprecate
	 */
	public static function the_title() {

		global $post;

		$content_post_id = get_post_meta( $post->ID, '_post', true );
		if ( $content_post_id ) {
			// translators: Placeholder is a link to post, with the post's title as the link text.
			$title = wp_kses_post( sprintf( __( 'Re: %1$s', 'sensei-lms' ), '<a href="' . esc_url( get_permalink( $content_post_id ) ) . '">' . esc_html( get_the_title( $content_post_id ) ) . '</a>' ) );
		} else {
			$title = esc_html( get_the_title( $post->ID ) );
		}

		?>
		<header>

			<h1>

				<?php
				/**
				 * Filter Sensei single title
				 *
				 * @since 1.8.0
				 * @param string $title
				 * @param string $template
				 * @param string $post_type
				 */
				echo wp_kses_post( apply_filters( 'sensei_single_title', $title, $post->post_type ) );
				?>

			</h1>

		</header>

		<?php

	}

	/**
	 * Generates the my messages
	 * archive header.
	 *
	 * @since 1.9.0
	 *
	 * @return string
	 */
	public static function the_archive_header() {

		$html  = '';
		$html .= '<header class="archive-header"><h1>';
		$html .= esc_html__( 'My Messages', 'sensei-lms' );
		$html .= '</h1></header>';

		/**
		 * Filter the sensei messages archive title.
		 *
		 * @since 1.0.0
		 */
		echo wp_kses_post( apply_filters( 'sensei_message_archive_title', $html ) );

	} // get_archive_header()

	/**
	 * Output the title for a message given the post_id.
	 *
	 * @since 1.9.0
	 * @param $post_id
	 */
	public static function the_message_title( $message_post_id ) {
		?>
		<h2>
			<a href="<?php echo esc_url_raw( get_the_permalink( $message_post_id ) ); ?>">
				<?php echo esc_html( self::get_the_message_title( $message_post_id ) ); ?>
			</a>

		</h2>

		<?php
	}

	/**
	 * Get the message title that is going to be displayed.
	 *
	 * @since 2.3.0
	 * @param integer $message_post_id The message id.
	 *
	 * @return string the message title
	 */
	public static function get_the_message_title( $message_post_id ) {
		$content_post_id = get_post_meta( $message_post_id, '_post', true );

		if ( $content_post_id ) {

			// translators: Placeholder is the post title.
			$title = sprintf( __( 'Re: %1$s', 'sensei-lms' ), get_the_title( $content_post_id ) );

		} else {

			$title = get_the_title( $message_post_id );

		}

		return $title;
	}

	/**
	 * Output the message sender given the post id.
	 *
	 * @param $message_post_id
	 */
	public static function the_message_sender( $message_post_id ) {

		$sender_username = get_post_meta( $message_post_id, '_sender', true );
		$sender          = get_user_by( 'login', $sender_username );

		if ( $sender_username && $sender instanceof WP_User ) {
			// translators: Placeholders are the sender's display name and the date, respectively.
			$sender_display_name = sprintf( __( 'Sent by %1$s on %2$s.', 'sensei-lms' ), $sender->display_name, get_the_date() );
			?>
			<p class="message-meta">
				<small>
					<em><?php echo esc_html( $sender_display_name ); ?></em>
				</small>
			</p>

			<?php
		}

	}

	/**
	 * Link to the users my messages page
	 *
	 * @since 1.9.0
	 */
	public static function the_my_messages_link() {
		if ( ! Sensei()->settings->get( 'messages_disable' ) ) {
			?>
			<p class="my-messages-link-container">
				<a class="my-messages-link" href="<?php echo esc_url( get_post_type_archive_link( 'sensei_message' ) ); ?>"
				   title="<?php esc_attr_e( 'View & reply to private messages sent to your course & lesson teachers.', 'sensei-lms' ); ?>">
					<?php esc_html_e( 'My Messages', 'sensei-lms' ); ?>
				</a>
			</p>
			<?php
		}
	}

}

/**
 * Class WooThemes_Sensei_Messages
 *
 * @ignore only for backward compatibility
 * @since 1.9.0
 */
class WooThemes_Sensei_Messages extends Sensei_Messages{}
