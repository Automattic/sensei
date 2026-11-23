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
	 * The nonce name when submitting a new message.
	 *
	 * @var string
	 */
	const NONCE_FIELD_NAME = 'sensei_message_teacher_nonce';

	/**
	 * The nonce action name when submitting a new message.
	 */
	const NONCE_ACTION_NAME = 'message_teacher';

	/**
	 * Constructor.
	 *
	 * @since  1.6.0
	 */
	public function __construct() {
		$this->token       = 'messages';
		$this->post_type   = 'sensei_message';
		$this->meta_fields = array( 'sender', 'receiver' );

		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ), 10, 2 );
		add_action( 'admin_menu', array( $this, 'remove_meta_box' ) );

		// Save new private message (priority low to ensure sensei_message post type is
		// registered.
		add_action( 'init', array( $this, 'save_new_message' ), 101 );

		// Monitor when new reply is posted.
		add_action( 'comment_post', [ $this, 'message_reply_received' ], 10, 1 );
		add_action( 'rest_insert_comment', [ $this, 'message_rest_insert' ], 10, 3 );

		// Block WordPress from sending comment update emails for the messages post type.
		add_filter( 'comment_notification_recipients', array( $this, 'stop_wp_comment_emails' ), 20, 2 );

		// Block WordPress from sending comment moderator emails on the sensei messages post types.
		add_filter( 'comment_moderation_recipients', array( $this, 'stop_wp_comment_emails' ), 20, 2 );

		// Add message link to lesson.
		add_action( 'sensei_single_lesson_content_inside_before', array( $this, 'send_message_link' ), 30, 2 );

		// Add message link to lesson.
		add_action( 'sensei_single_quiz_questions_before', array( $this, 'send_message_link' ), 10, 2 );

		// Hide messages and replies from users who do not have access.
		add_filter( 'request', array( $this, 'remove_sensei_message_from_post_type_array' ) );
		add_action( 'template_redirect', array( $this, 'message_login' ), 10, 1 );
		add_filter( 'redirect_canonical', array( $this, 'prevent_message_canonical_redirect' ), 10, 1 );
		add_action( 'pre_get_posts', array( $this, 'message_list' ), 10, 1 );
		add_filter( 'the_title', array( $this, 'message_title' ), 10, 2 );
		add_filter( 'the_content', array( $this, 'message_content' ), 10, 1 );
		add_filter( 'comments_array', array( $this, 'message_replies' ), 100, 1 );
		add_filter( 'get_comments_number', array( $this, 'message_reply_count' ), 100, 2 );
		add_filter( 'comments_open', array( $this, 'message_replies_open' ), 100, 2 );
		add_action( 'pre_get_posts', array( $this, 'only_show_messages_to_owner' ) );
		add_filter( 'posts_where', array( $this, 'exclude_unreadable_messages' ), 10, 2 );
		add_action( 'pre_get_comments', array( $this, 'exclude_unreadable_message_replies' ) );
		add_filter( 'wp_count_comments', array( $this, 'count_comments_for_current_user' ), 10, 2 );
		add_filter( 'get_comment', array( $this, 'hide_unreadable_message_reply' ) );
		add_filter( 'comment_feed_where', array( $this, 'exclude_message_comments_from_feed_where' ) );
		add_filter( 'user_has_cap', [ $this, 'user_messages_cap_check' ], 10, 3 );
		add_action( 'load-edit-comments.php', [ $this, 'check_permissions_edit_comments' ] );
		add_action( 'comment_form', [ $this, 'add_nonce_to_comment_form' ] );

		// Redirect and show a success notice.
		add_action( 'sensei_new_private_message', [ $this, 'show_success_notice' ], 999 );
	}

	/**
	 * Remove 'sensei_message' from an array `post_type`.
	 *
	 * The checks that keep messages private only run when `post_type` is the string
	 * `sensei_message`. `?post_type[]=sensei_message` skips them and exposes every
	 * message, so never let messages be queried through an array.
	 *
	 * @since 4.26.4
	 *
	 * @internal
	 *
	 * @param array $query_vars The main request's query vars.
	 * @return array
	 */
	public function remove_sensei_message_from_post_type_array( $query_vars ) {
		if ( is_array( $query_vars['post_type'] ?? null ) ) {
			$query_vars['post_type'] = array_values( array_diff( $query_vars['post_type'], array( $this->post_type ) ) );
		}

		return $query_vars;
	}

	/**
	 * Exclude the messages the current user cannot read from post queries.
	 *
	 * Runs for every WP_Query that applies filters, not only the main query, so messages stay
	 * private in queries built by other plugins and APIs. Excluding them in the SQL keeps the
	 * page sizes and totals of those queries correct.
	 *
	 * @since 4.28.0
	 *
	 * @internal
	 *
	 * @param string        $where The WHERE clause of the query.
	 * @param WP_Query|null $query The query.
	 * @return string
	 */
	public function exclude_unreadable_messages( $where, $query = null ) {
		global $wpdb;

		// Other code can apply this filter without the query.
		if ( ! $query instanceof WP_Query || current_user_can( 'manage_sensei' ) ) {
			return $where;
		}

		// A query for other post types cannot return messages. Neither can a query with no post type, which is for
		// posts, pages or attachments, unless it is a taxonomy query, which searches the post types of the taxonomy.
		$post_types = array_filter( (array) $query->get( 'post_type' ) );
		if ( ! array_intersect( $post_types, array( $this->post_type, 'any' ) ) && ( $post_types || ! $query->is_tax ) ) {
			return $where;
		}

		if ( ! is_user_logged_in() ) {
			return $where . $wpdb->prepare( ' AND %i.post_type != %s', $wpdb->posts, $this->post_type );
		}

		$user_login = wp_get_current_user()->user_login;

		// One subquery per meta key, so that each one uses the meta key index.
		return $where . $wpdb->prepare(
			' AND ( %i.post_type != %s OR %i.ID IN ( SELECT post_id FROM %i WHERE meta_key = %s AND meta_value = %s ) OR %i.ID IN ( SELECT post_id FROM %i WHERE meta_key = %s AND meta_value = %s ) )',
			$wpdb->posts,
			$this->post_type,
			$wpdb->posts,
			$wpdb->postmeta,
			'_sender',
			$user_login,
			$wpdb->posts,
			$wpdb->postmeta,
			'_receiver',
			$user_login
		);
	}

	/**
	 * Exclude the replies to the messages the current user cannot read from comment queries.
	 *
	 * Excluding them before the query runs keeps its page sizes and totals correct.
	 *
	 * @since 4.28.0
	 *
	 * @internal
	 *
	 * @param WP_Comment_Query $query The comment query.
	 */
	public function exclude_unreadable_message_replies( $query ) {
		if ( ! $query instanceof WP_Comment_Query ) {
			return;
		}

		// A query for one post that is not a message cannot return replies.
		$post_id = absint( $query->query_vars['post_id'] );
		if ( $post_id && get_post_type( $post_id ) !== $this->post_type ) {
			return;
		}

		$message_ids = $this->get_unreadable_message_ids();
		if ( $message_ids ) {
			$query->query_vars['post__not_in'] = array_merge( wp_parse_id_list( $query->query_vars['post__not_in'] ), $message_ids );
		}
	}

	/**
	 * Count the comments for a user who cannot read every message, without the count cache of WordPress.
	 *
	 * That cache holds one count for all users, so it must only hold the count of the users who read
	 * every reply.
	 *
	 * @since 4.28.0
	 *
	 * @internal
	 *
	 * @param array|object $comment_counts The counts set by other filters, empty to let WordPress count.
	 * @param int          $post_id        The post the comments are counted for, 0 for the whole site.
	 * @return array|object
	 */
	public function count_comments_for_current_user( $comment_counts, $post_id = 0 ) {
		$post_id = absint( $post_id );
		if ( ! empty( $comment_counts ) || ( $post_id && get_post_type( $post_id ) !== $this->post_type ) ) {
			return $comment_counts;
		}

		if ( current_user_can( 'manage_sensei' ) || current_user_can( 'manage_options' ) ) {
			return $comment_counts;
		}

		// The same shape that wp_count_comments() returns.
		$comment_counts              = get_comment_count( $post_id );
		$comment_counts['moderated'] = $comment_counts['awaiting_moderation'];
		unset( $comment_counts['awaiting_moderation'] );

		return (object) $comment_counts;
	}

	/**
	 * Get the IDs of the messages whose replies the current user cannot read.
	 *
	 * @return int[]
	 */
	private function get_unreadable_message_ids() {
		// Background jobs have no current user and still need the replies, for example to delete them with their message.
		if ( wp_doing_cron() || ( defined( 'WP_CLI' ) && WP_CLI ) || current_user_can( 'manage_sensei' ) || current_user_can( 'manage_options' ) ) {
			return array();
		}

		$query_args = array(
			'post_type'      => $this->post_type,
			'post_status'    => array_keys( get_post_stati() ),
			'fields'         => 'ids',
			'posts_per_page' => -1,
			'orderby'        => 'none',
		);

		// A logged-out visitor cannot read any message. Other users cannot read the ones they did not send or receive.
		if ( is_user_logged_in() ) {
			$user_login               = wp_get_current_user()->user_login;
			$query_args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Limited to the message post type.
				array(
					'key'     => '_sender',
					'value'   => $user_login,
					'compare' => '!=',
				),
				array(
					'key'     => '_receiver',
					'value'   => $user_login,
					'compare' => '!=',
				),
			);
		}

		return get_posts( $query_args );
	}

	/**
	 * Hide the replies to messages the current user cannot read.
	 *
	 * Runs wherever a comment is loaded, by ID or through a comment query, so replies stay
	 * private in routes built by other plugins and APIs.
	 *
	 * @since 4.28.0
	 *
	 * @internal
	 *
	 * @param WP_Comment|null $comment The comment.
	 * @return WP_Comment|null
	 */
	public function hide_unreadable_message_reply( $comment ) {
		if ( ! $comment instanceof WP_Comment || get_post_type( (int) $comment->comment_post_ID ) !== $this->post_type ) {
			return $comment;
		}

		return $this->can_read_message_replies( (int) $comment->comment_post_ID ) ? $comment : null;
	}

	/**
	 * Check if the replies to a message can be read in the current request.
	 *
	 * @param int $message_id The message ID.
	 * @return bool
	 */
	private function can_read_message_replies( int $message_id ) {
		// Background jobs have no current user and still need the replies, for example to delete them with their message.
		if ( wp_doing_cron() || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
			return true;
		}

		if ( current_user_can( 'manage_sensei' ) || $this->view_message( $message_id ) ) {
			return true;
		}

		// The REST API also lets site administrators post a reply, and they must be able to load it.
		return current_user_can( 'manage_options' );
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

		if ( current_user_can( 'manage_sensei' ) ) {
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

			add_submenu_page(
				'sensei',
				__( 'Messages', 'sensei-lms' ),
				__( 'Messages', 'sensei-lms' ),
				'edit_courses',
				'edit.php?post_type=sensei_message'
			);
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
				'description' => __( 'The username of the student who sent this message.', 'sensei-lms' ),
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
		 * @hook sensei_messages_send_message_link
		 *
		 * @param {string} $html           The HTML for the send message link.
		 * @param {array}  $message_notice The message notice.
		 * @param {int}    $post_id        The post ID.
		 * @param {int}    $user_id        The user ID.
		 * @return {string} Filtered HTML.
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

		$html         .= '<h3 id="private_message">' . esc_html__( 'Send Private Message', 'sensei-lms' ) . '</h3>';
		$html         .= '<p>';
		$html         .= '</p>';
		$html         .= '<form name="contact-teacher" action="" method="post" class="contact-teacher">';
			$html     .= '<p class="form-row form-row-wide">';
				$html .= '<textarea name="contact_message" placeholder="' . esc_attr__( 'Enter your private message.', 'sensei-lms' ) . '"></textarea>';
			$html     .= '</p>';
			$html     .= '<p class="form-row">';
				$html .= '<input type="hidden" name="post_id" value="' . esc_attr( absint( $post->ID ) ) . '" />';
				$html .= wp_nonce_field( self::NONCE_ACTION_NAME, self::NONCE_FIELD_NAME, true, false );
				$html .= '<input type="submit" class="send_message" value="' . esc_attr__( 'Send Message', 'sensei-lms' ) . '" />';
			$html     .= '</p>';
			$html     .= '<div class="fix"></div>';
		$html         .= '</form>';

		return $html;
	}

	public function save_new_message() {

		if ( ! isset( $_POST[ self::NONCE_FIELD_NAME ] ) || ! isset( $_POST['post_id'] ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Argument is used for comparison only.
		if ( ! wp_verify_nonce( wp_unslash( $_POST[ self::NONCE_FIELD_NAME ] ), self::NONCE_ACTION_NAME ) ) {
			return;
		}

		$post         = get_post( absint( $_POST['post_id'] ) );
		$current_user = wp_get_current_user();

		// Only a user enrolled in the related course may message the teacher.
		// This also rejects a post_id that does not resolve to a post at all.
		if ( ! $this->can_current_user_send_message_about( $post ) ) {
			return;
		}

		$message = empty( $_POST['contact_message'] )
			? ''
			: sensei_request_text( $_POST['contact_message'] );

		$this->save_new_message_post( $current_user->ID, $post->post_author, $message, $post->ID );
	}

	/**
	 * Check whether the current user may send a private message about the given post.
	 *
	 * Mirrors the enrolment check the REST endpoint enforces: the user must be enrolled
	 * in the course the message is about, resolving quiz and lesson posts to their course.
	 *
	 * @since 4.26.4
	 *
	 * @param WP_Post|null $post The post the message is about.
	 * @return bool Whether the current user is allowed to send the message.
	 */
	private function can_current_user_send_message_about( $post ) {
		if ( ! ( $post instanceof WP_Post ) || ! is_user_logged_in() ) {
			return false;
		}

		$course = $post;

		if ( 'quiz' === $course->post_type ) {
			$lesson_id = (int) Sensei()->quiz->get_lesson_id( $course->ID );
			$course    = $lesson_id ? get_post( $lesson_id ) : null;
		}

		if ( $course instanceof WP_Post && 'lesson' === $course->post_type ) {
			$course_id = (int) Sensei()->lesson->get_course_id( $course->ID );
			$course    = $course_id ? get_post( $course_id ) : null;
		}

		if ( ! ( $course instanceof WP_Post ) || 'course' !== $course->post_type ) {
			return false;
		}

		return Sensei()->course->is_user_enrolled( $course->ID, get_current_user_id() );
	}

	public function message_reply_received( $comment_id = 0 ) {

		// get_comment() hides the reply from an author who cannot read the message, and that is the reply to mark as spam.
		$comment = WP_Comment::get_instance( (int) $comment_id );

		if ( ! $comment ) {
			return;
		}

		// Get message post object.
		$message = get_post( (int) $comment->comment_post_ID );

		if ( $message->post_type != $this->post_type ) {
			return;
		}

		$should_verify_nonce = ! defined( 'XMLRPC_REQUEST' );
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce verification.
		$nonce_verified = ! empty( $_POST['sensei_message_nonce'] ) && wp_verify_nonce( wp_unslash( $_POST['sensei_message_nonce'] ), 'sensei_post_message_reply' );

		$comment_author   = get_userdata( (int) $comment->user_id );
		$user_can_comment = in_array( $comment_author->user_login, [ get_post_meta( $message->ID, '_receiver', true ), get_post_meta( $message->ID, '_sender', true ) ], true );

		if ( ( $should_verify_nonce && ! $nonce_verified ) || ! $user_can_comment ) {
			$this->mark_reply_as_spam( (int) $comment_id );
			return;
		}

		// Force comment to be approved.
		wp_set_comment_status( $comment_id, 'approve' );

		/**
		 * Fires when a private message reply is received.
		 *
		 * @hook sensei_private_message_reply
		 *
		 * @param {WP_Comment} $comment The comment object.
		 * @param {WP_Post}    $message The message post object.
		 */
		do_action( 'sensei_private_message_reply', $comment, $message );
	}

	/**
	 * Marks comments created or updated by a REST call as spam, if the user is unauthorized.
	 *
	 * @access private
	 *
	 * @param WP_Comment      $comment  Inserted or updated comment object.
	 * @param WP_REST_Request $request  Request object.
	 * @param bool            $creating True when creating a comment, false
	 *                                  when updating.
	 */
	public function message_rest_insert( WP_Comment $comment, WP_REST_Request $request, bool $creating ) {
		$message = get_post( $comment->comment_post_ID );

		if ( $message->post_type !== $this->post_type ) {
			return;
		}

		$comment_author   = get_userdata( $comment->user_id );
		$user_can_comment = in_array( $comment_author->user_login, [ get_post_meta( $message->ID, '_receiver', true ), get_post_meta( $message->ID, '_sender', true ) ], true );

		if ( ! $user_can_comment ) {
			$this->mark_reply_as_spam( (int) $comment->comment_ID );
		}
	}

	/**
	 * Mark a message reply as spam.
	 *
	 * The author of a rejected reply cannot read the message, so the reply is hidden from them
	 * and WordPress could not load it to change its status.
	 *
	 * @param int $comment_id The reply ID.
	 */
	private function mark_reply_as_spam( int $comment_id ) {
		remove_filter( 'get_comment', array( $this, 'hide_unreadable_message_reply' ) );
		wp_set_comment_status( $comment_id, 'spam' );
		add_filter( 'get_comment', array( $this, 'hide_unreadable_message_reply' ) );
	}

	/**
	 * Adds a nonce to the sensei message comment form.
	 *
	 * @access private
	 *
	 * @return void
	 */
	public function add_nonce_to_comment_form() {
		if ( is_singular( $this->post_type ) ) {
			wp_nonce_field( 'sensei_post_message_reply', 'sensei_message_nonce' );
		}
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
	public function save_new_message_post( $sender_id = 0, $receiver_id = 0, $message = '', $post_id = 0 ) {

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

				/**
				 * Fires when a new private message is sent.
				 *
				 * @since 1.8.0
				 *
				 * @hook  sensei_new_private_message
				 *
				 * @param {int} $message_id The message ID.
				 */
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

		$user_login = null;
		if ( 0 === (int) $user_id ) {
			global $current_user;
			wp_get_current_user();
			$user_login = $current_user->user_login;
		}

		if ( empty( $user_login ) ) {
			return false;
		}

		// Get allowed users
		$receiver = get_post_meta( $message_id, '_receiver', true );
		$sender   = get_post_meta( $message_id, '_sender', true );

		// Check if user is allowed to view the message
		if ( in_array( $user_login, array( $receiver, $sender ), true ) ) {
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

		$my_courses_page_id = Sensei()->settings->get_my_courses_page_id();
		if ( 0 < $my_courses_page_id ) {
			$my_courses_url = get_permalink( $my_courses_page_id );
		}

		$message_id = $this->get_requested_message_id();

		if ( $message_id || is_post_type_archive( $this->post_type ) ) {

			// The message slug is built from the message body, so it must not reach a visitor who
			// cannot read the message. Send them back through the ID instead of the pretty permalink.
			// Name the post type, because WordPress resolves a bare `?p=ID` only for public post types.
			$permalink = $message_id
				? add_query_arg(
					array(
						'post_type' => $this->post_type,
						'p'         => $message_id,
					),
					home_url( '/' )
				)
				: get_post_type_archive_link( $this->post_type );

			if ( isset( $my_courses_url ) ) {
				wp_safe_redirect( add_query_arg( 'redirect_to', rawurlencode( (string) $permalink ), $my_courses_url ), 303 );
				exit;
			} else {
				wp_safe_redirect( home_url( '/wp-login.php' ), 303 );
				exit;
			}
		}
	}

	/**
	 * Get the ID of the single message requested by the main query, if any.
	 *
	 * A visitor who cannot read the message gets a 404 with no queried object, so read the
	 * request's query vars instead.
	 *
	 * @return int The message ID, or 0 when the request is not for a single message.
	 */
	private function get_requested_message_id() {
		if ( get_query_var( 'post_type' ) !== $this->post_type ) {
			return 0;
		}

		if ( get_query_var( 'p' ) ) {
			$message = get_post( (int) get_query_var( 'p' ) );
		} elseif ( get_query_var( 'name' ) ) {
			$message = get_page_by_path( get_query_var( 'name' ), OBJECT, $this->post_type );
		} else {
			return 0;
		}

		return $message instanceof WP_Post && $message->post_type === $this->post_type ? $message->ID : 0;
	}

	/**
	 * Stop the canonical redirect from revealing a message slug to users who cannot read the message.
	 *
	 * The slug is built from the message body, and core's redirect_canonical() sends `?p=ID` to the
	 * pretty permalink for any public post type before message_login() runs.
	 *
	 * @since 4.26.4
	 *
	 * @internal
	 *
	 * @param string|false $redirect_url The redirect URL, or false to skip the redirect.
	 * @return string|false
	 */
	public function prevent_message_canonical_redirect( $redirect_url ) {
		$post_id = (int) get_query_var( 'p' );
		if ( ! $post_id && is_singular() ) {
			$post_id = get_queried_object_id();
		}
		if ( ! $post_id ) {
			$post_id = $this->get_requested_message_id();
		}

		if ( ! $post_id || get_post_type( $post_id ) !== $this->post_type ) {
			return $redirect_url;
		}

		return current_user_can( 'manage_sensei' ) || $this->view_message( $post_id ) ? $redirect_url : false;
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

		if ( get_post_type( $post_id ) !== $this->post_type ) {
			return $title;
		}

		if ( is_single() && is_singular() && in_the_loop() ) {
			if ( ! is_user_logged_in() || ! $this->view_message( $post_id ) ) {
				return __( 'You are not allowed to view this message.', 'sensei-lms' );
			}
		}

		return $this->replace_brackets( $title );
	}

	/**
	 * Filter the content of a message.
	 *
	 * @param string $content The post content.
	 * @return string The filtered content.
	 */
	public function message_content( $content ) {
		$post = get_post();
		if ( ! ( $post instanceof WP_Post ) || get_post_type( $post->ID ) !== $this->post_type ) {
			return $content;
		}

		if ( is_single() && is_singular() && in_the_loop() ) {
			if ( ! is_user_logged_in() || ! $this->view_message( $post->ID ) ) {
				return __( 'Please log in to view your messages.', 'sensei-lms' );
			}
		}

		return $this->replace_brackets( $content );
	}

	/**
	 * Escape brackets to prevent shortcode loading in message content.
	 * This approach is adopted because strip_shortcodes() does not work for escaped shortcodes.
	 * For example, [shortcode] will be stripped but [[shortcode]] will become an active shortcode.
	 *
	 * @param  string $content Original message content.
	 * @return string          Modified message content
	 */
	private function replace_brackets( $content ) {
		$bracket_replaces = [
			'[' => '&#91;',
			']' => '&#93;',
		];

		return strtr( $content, $bracket_replaces );
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
			$title = wp_kses_post( sprintf( _x( 'Re: %1$s', 'message title with a link to the post', 'sensei-lms' ), '<a href="' . esc_url( get_permalink( $content_post_id ) ) . '">' . esc_html( get_the_title( $content_post_id ) ) . '</a>' ) );
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
				 *
				 * @hook sensei_single_title
				 *
				 * @param {string} $title     The title.
				 * @param {string} $post_type The post type.
				 * @return {string} Filtered title.
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
		 *
		 * @hook sensei_message_archive_title
		 *
		 * @param {string} $html The HTML for the archive title.
		 * @return {string} Filtered HTML.
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
			$title = sprintf( _x( 'Re: %1$s', 'message title without a link to the post', 'sensei-lms' ), get_the_title( $content_post_id ) );

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

	/**
	 * Redirect to a URL that will handle showing a success notice.
	 *
	 * @internal
	 *
	 * @since 4.10.0
	 */
	public function show_success_notice(): void {
		if ( ! Sensei_Utils::is_rest_request() ) {
			wp_safe_redirect( esc_url_raw( add_query_arg( [ 'send' => 'complete' ] ) ) );
			exit;
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
