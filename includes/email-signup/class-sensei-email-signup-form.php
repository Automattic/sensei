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
	const MC_USER_ID          = '7a061a9141b0911d6d9bafe3a';
	const MC_LIST_ID          = '4fa225a515';
	const GDPR_EMAIL_FIELD_ID = '23563';

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
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_styles' ] );
		add_action( 'admin_footer', [ $this, 'output_modal' ] );
	}

	/**
	 * Enqueue the required JS assets for the modal dialog.
	 *
	 * @access private
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( 'jquery-modal' );

		// Load JS for the form.
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		wp_enqueue_script(
			'sensei-email-signup-js',
			Sensei()->plugin_url . 'assets/js/admin/email-signup' . $suffix . '.js',
			[ 'jquery-modal' ],
			Sensei()->version,
			false
		);
	}

	/**
	 * Enqueue the required CSS assets for the modal dialog.
	 *
	 * @access private
	 */
	public function enqueue_styles() {
		wp_enqueue_style( 'jquery-modal' );

		// Load CSS for the form.
		wp_enqueue_style(
			'sensei-email-signup-css',
			Sensei()->plugin_url . 'assets/css/admin/email-signup.css',
			[ 'jquery-modal' ],
			Sensei()->version
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
