<?php
/**
 * Transactional Emails Controller
 *
 * Sensei Emails Class which handles the sending emails and email templates. This class loads in available emails.
 *
 * @class 		WooThemes_Sensei_Emails
 * @version		1.6.0
 * @package		Sensei/Classes/Emails
 * @category	Class
 * @author 		WooThemes
 */
class WooThemes_Sensei_Emails {

	/**
	 * @var array Array of email notification classes.
	 * @access public
	 */
	public $emails;

	/**
	 * @var string Stores the emailer's address.
	 * @access private
	 */
	private $_from_address;

	/**
	 * @var string Stores the emailer's name.
	 * @access private
	 */
	private $_from_name;

	/**
	 * @var mixed Content type for sent emails
	 * @access private
	 */
	private $_content_type;

	/**
	 * Constructor for the email class hooks in all emails that can be sent.
	 *
	 * @access public
	 * @return void
	 */
	function __construct( $file ) {

		$this->init();

		// Hooks for sending emails during Sensei events
		add_action( 'sensei_user_quiz_grade', array( $this, 'learner_graded_quiz' ), 10, 4 );
		add_action( 'sensei_course_status_updated', array( $this, 'learner_completed_course' ), 10, 4 );
		add_action( 'sensei_course_status_updated', array( $this, 'teacher_completed_course' ), 10, 4 );
		add_action( 'sensei_user_course_start', array( $this, 'teacher_started_course' ), 10, 2 );
		add_action( 'sensei_user_quiz_submitted', array( $this, 'teacher_quiz_submitted' ), 10, 5 );
		add_action( 'sensei_new_private_message', array( $this, 'teacher_new_message' ), 10, 1 );
		add_action( 'sensei_private_message_reply', array( $this, 'new_message_reply' ), 10, 2 );

		// Let 3rd parties unhook the above via this hook
		do_action( 'sensei_emails', $this );
	}

	/**
	 * Init email classes
	 */
	function init() {

		$this->emails['learner-graded-quiz'] = include( 'emails/class-woothemes-sensei-email-learner-graded-quiz.php' );
		$this->emails['learner-completed-course'] = include( 'emails/class-woothemes-sensei-email-learner-completed-course.php' );
		$this->emails['teacher-completed-course'] = include( 'emails/class-woothemes-sensei-email-teacher-completed-course.php' );
		$this->emails['teacher-started-course'] = include( 'emails/class-woothemes-sensei-email-teacher-started-course.php' );
		$this->emails['teacher-quiz-submitted'] = include( 'emails/class-woothemes-sensei-email-teacher-quiz-submitted.php' );
		$this->emails['teacher-new-message'] = include( 'emails/class-woothemes-sensei-email-teacher-new-message.php' );
		$this->emails['new-message-reply'] = include( 'emails/class-woothemes-sensei-email-new-message-reply.php' );
		$this->emails = apply_filters( 'sensei_email_classes', $this->emails );
	}

	/**
	 * Return the email classes - used in admin to load settings.
	 *
	 * @access public
	 * @return array
	 */
	function get_emails() {
		return $this->emails;
	}

	/**
	 * Get from name for email.
	 *
	 * @access public
	 * @return string
	 */
	function get_from_name() {
		global $woothemes_sensei;

		if ( ! $this->_from_name ) {
			if( isset( $woothemes_sensei->settings->settings['email_from_name'] ) && '' != $woothemes_sensei->settings->settings['email_from_name'] ) {
				$this->_from_name = $woothemes_sensei->settings->settings['email_from_name'];
			} else {
				$this->_from_name = get_bloginfo( 'name' );
			}
		}

		return wp_specialchars_decode( $this->_from_name );
	}

	/**
	 * Get from email address.
	 *
	 * @access public
	 * @return string
	 */
	function get_from_address() {
		global $woothemes_sensei;

		if ( ! $this->_from_address ) {
			if( isset( $woothemes_sensei->settings->settings['email_from_address'] ) && '' != $woothemes_sensei->settings->settings['email_from_address'] ) {
				$this->_from_address = $woothemes_sensei->settings->settings['email_from_address'];
			} else {
				$this->_from_address = get_bloginfo( 'admin_email' );
			}
		}

		return $this->_from_address;
	}

	/**
	 * Get the content type for the email.
	 *
	 * @access public
	 * @return string
	 */
	function get_content_type() {
		return $this->_content_type;
	}

	/**
	 * Wraps a message in the sensei mail template.
	 *
	 * @access public
	 * @param mixed $content
	 * @return string
	 */
	function wrap_message( $content ) {

		$html = '';

		$html .= $this->load_template( 'header' );
		$html .= wpautop( wptexturize( $content ) );
		$html .= $this->load_template( 'footer' );

		return $html;
	}

	/**
	 * Send the email.
	 *
	 * @access public
	 * @param mixed $to
	 * @param mixed $subject
	 * @param mixed $message
	 * @param string $headers (default: "Content-Type: text/html\r\n")
	 * @param string $attachments (default: "")
	 * @param string $content_type (default: "text/html")
	 * @return void
	 */
	function send( $to, $subject, $message, $headers = "Content-Type: text/html\r\n", $attachments = "", $content_type = 'text/html' ) {
		global $email_template;

		// Set content type
		$this->_content_type = $content_type;

		// Filters for the email
		add_filter( 'wp_mail_from', array( $this, 'get_from_address' ) );
		add_filter( 'wp_mail_from_name', array( $this, 'get_from_name' ) );
		add_filter( 'wp_mail_content_type', array( $this, 'get_content_type' ) );

        // Send
        $send_email = true;

        /**
         * Filter Sensei's ability to send out emails.
         *
         * @since 1.8.0
         * @param bool $send_email default true
         */
        if( apply_filters('sensei_send_emails', $send_email,$to, $subject, $message )  ){

            wp_mail( $to, $subject, $message, $headers, $attachments );

        }

		// Unhook filters
		remove_filter( 'wp_mail_from', array( $this, 'get_from_address' ) );
		remove_filter( 'wp_mail_from_name', array( $this, 'get_from_name' ) );
		remove_filter( 'wp_mail_content_type', array( $this, 'get_content_type' ) );
	}

	function get_content( $email_template ) {

		$message = $this->load_template( $email_template );

		$html = $this->wrap_message( $message );

		return apply_filters( 'sensei_email', $html, $email_template );
	}

	function load_template( $template = '' ) {
		global $woothemes_sensei, $email_template;

		if( ! $template ) return;

		$email_template = $template . '.php';
		$template = $woothemes_sensei->template_loader( '' );

		ob_start();

		do_action( 'sensei_before_email_template', $email_template );
		include( $template );
		do_action( 'sensei_after_email_template', $email_template );

		return ob_get_clean();
	}

	/**
	 * Send email to learner on quiz grading (auto or manual)
	 *
	 * @access public
	 * @return void
	 */
	function learner_graded_quiz( $user_id, $quiz_id, $grade, $passmark ) {
		global $woothemes_sensei;

		$send = false;

		if( isset( $woothemes_sensei->settings->settings['email_learners'] ) ) {
			if( in_array( 'learner-graded-quiz', (array) $woothemes_sensei->settings->settings['email_learners'] ) ) {
				$send = true;
			}
		} else {
			$send = true;
		}

		if( $send ) {
			$email = $this->emails['learner-graded-quiz'];
			$email->trigger( $user_id, $quiz_id, $grade, $passmark );
		}
	}

	/**
	 * Send email to learner on course completion
	 *
	 * @access public
	 * @return void
	 */
	function learner_completed_course( $status = 'in-progress', $user_id = 0, $course_id = 0, $comment_id = 0 ) {
		global $woothemes_sensei;

		if( 'complete' != $status ) {
			return;
		}

		$send = false;

		if( isset( $woothemes_sensei->settings->settings['email_learners'] ) ) {
			if( in_array( 'learner-completed-course', (array) $woothemes_sensei->settings->settings['email_learners'] ) ) {
				$send = true;
			}
		} else {
			$send = true;
		}

		if( $send ) {
			$email = $this->emails['learner-completed-course'];
			$email->trigger( $user_id, $course_id );
		}
	}

	/**
	 * Send email to teacher on course completion
	 *
	 * @access public
	 * @return void
	 */
	function teacher_completed_course( $status = 'in-progress', $learner_id = 0, $course_id = 0, $comment_id = 0 ) {
		global $woothemes_sensei;

		if( 'complete' != $status ) {
			return;
		}

		$send = false;

		if( isset( $woothemes_sensei->settings->settings['email_teachers'] ) ) {
			if( in_array( 'teacher-completed-course', (array) $woothemes_sensei->settings->settings['email_teachers'] ) ) {
				$send = true;
			}
		} else {
			$send = true;
		}

		if( $send ) {
			$email = $this->emails['teacher-completed-course'];
			$email->trigger( $learner_id, $course_id );
		}
	}

	/**
	 * Send email to teacher on course beginning
	 *
	 * @access public
	 * @return void
	 */
	function teacher_started_course( $learner_id = 0, $course_id = 0 ) {
		global $woothemes_sensei;

		$send = false;

		if( isset( $woothemes_sensei->settings->settings['email_teachers'] ) ) {
			if( in_array( 'teacher-started-course', (array) $woothemes_sensei->settings->settings['email_teachers'] ) ) {
				$send = true;
			}
		} else {
			$send = true;
		}

		if( $send ) {
			$email = $this->emails['teacher-started-course'];
			$email->trigger( $learner_id, $course_id );
		}
	}

	/**
	 * Send email to teacher on quiz submission
	 *
	 * @access public
	 * @return void
	 */
	function teacher_quiz_submitted( $learner_id = 0, $quiz_id = 0, $grade = 0, $passmark = 0, $quiz_grade_type = 'manual' ) {

		global $woothemes_sensei;

		$send = false;

		// Only trigger if the quiz was marked as manual grading, or auto grading didn't complete
		if( 'manual' == $quiz_grade_type || is_wp_error( $grade ) ) {
			if( isset( $woothemes_sensei->settings->settings['email_teachers'] ) ) {
				if( in_array( 'teacher-quiz-submitted', (array) $woothemes_sensei->settings->settings['email_teachers'] ) ) {
					$send = true;
				}
			} else {
				$send = true;
			}

			if( $send ) {
				$email = $this->emails['teacher-quiz-submitted'];
				$email->trigger( $learner_id, $quiz_id );
			}

		}
	}

	/**
	 * Send email to teacher when a new private message is received
	 *
	 * @access public
	 * @return void
	 */
	function teacher_new_message( $message_id = 0 ) {
		global $woothemes_sensei;

		$send = false;

		if( isset( $woothemes_sensei->settings->settings['email_teachers'] ) ) {
			if( in_array( 'teacher-new-message', (array) $woothemes_sensei->settings->settings['email_teachers'] ) ) {
				$send = true;
			}
		} else {
			$send = true;
		}

		if( $send ) {
			$email = $this->emails['teacher-new-message'];
			$email->trigger( $message_id );
		}
	}

	/**
	 * Send email to a user when their private message receives a reply
	 *
	 * @access public
	 * @return void
	 */
	function new_message_reply( $comment, $message ) {
		global $woothemes_sensei;

		$send = false;

		if( isset( $woothemes_sensei->settings->settings['email_global'] ) ) {
			if( in_array( 'new-message-reply', (array) $woothemes_sensei->settings->settings['email_global'] ) ) {
				$send = true;
			}
		} else {
			$send = true;
		}

		if( $send ) {
			$email = $this->emails['new-message-reply'];
			$email->trigger( $comment, $message );
		}
	}

}//end class
