<?php
/**
 * File containing the class Sensei_Email_Signup_Form.
 *
 * @package sensei
 * @since   2.0.0
 */

/**
 * Class for displaying the modal email signup form.
 *
 * @class Sensei_Email_Signup_Form
 */
class Sensei_Email_Signup_Form {
	const MC_USER_ID = '7a061a9141b0911d6d9bafe3a';
	const MC_LIST_ID = '278a16a5ed';

	/**
	 * Instance of class.
	 *
	 * @var self
	 */
	private static $instance;

	/**
	 * Sensei_Email_Signup_Form constructor. Prevents other instances from being
	 * created outside of `Sensei_Email_Signup_Form::instance()`.
	 */
	private function __construct() {}

	/**
	 * Initializes the class and adds all filters and actions.
	 *
	 * @since 1.0.0
	 */
	public function init() {
		// Add actions for displaying the email signup modal.
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
		add_action( 'admin_footer', [ $this, 'output_modal' ] );
	}

	/**
	 * Enqueue the required JS and CSS assets for the modal dialog.
	 *
	 * @access private
	 */
	public function enqueue_assets() {
		wp_enqueue_script(
			'sensei-modal-js',
			'https://cdnjs.cloudflare.com/ajax/libs/jquery-modal/0.9.1/jquery.modal.min.js',
			false,
			'2.0.0',
			false
		);
		wp_enqueue_style(
			'sensei-modal-css',
			'https://cdnjs.cloudflare.com/ajax/libs/jquery-modal/0.9.1/jquery.modal.min.css',
			false,
			'2.0.0',
			false
		);
	}

	/**
	 * Load and output the code for the modal window.
	 *
	 * @access private
	 */
	public function output_modal() {
		include dirname( __FILE__ ) . '/template.php';
	}

	/**
	 * Fetches an instance of the class.
	 *
	 * @return self
	 */
	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}
}
