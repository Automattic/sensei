<?php
/**
 * File containing the class Sensei_Deactivation_Survey_Form.
 *
 * @package sensei
 * @since   3.0.2
 */

/**
 * Class for displaying the modal deactivation survey form.
 *
 * @class Sensei_Deactivation_Survey_Form
 */
class Sensei_Deactivation_Survey_Form {

	/**
	 * Instance of class.
	 *
	 * @var self
	 */
	private static $instance;

	/**
	 * Sensei_Deactivation_Survey_Form constructor. Prevents other instances from being
	 * created outside of `Sensei_Deactivation_Survey_Form::instance()`.
	 */
	private function __construct() {}

	/**
	 * Initializes the class and adds all filters and actions.
	 *
	 * @access public
	 */
	public function init() {
		// Add actions for displaying the deactivation survey modal.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		add_action( 'admin_footer', array( $this, 'output_modal' ) );
	}

	/**
	 * Enqueue the required JS assets for the modal dialog.
	 *
	 * @access public
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( 'jquery-modal' );

		// Load JS for the form.
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		wp_enqueue_script(
			'sensei-deactivation-survey-js',
			Sensei()->plugin_url . 'assets/js/admin/deactivation-survey' . $suffix . '.js',
			[ 'jquery-modal' ],
			Sensei()->version,
			false
		);
	}

	/**
	 * Enqueue the required CSS assets for the modal dialog.
	 *
	 * @access public
	 */
	public function enqueue_styles() {
		wp_enqueue_style( 'jquery-modal' );

		// Load CSS for the form.
		wp_enqueue_style(
			'sensei-deactivation-survey-css',
			Sensei()->plugin_url . 'assets/css/admin/deactivation-survey.css',
			[ 'jquery-modal' ],
			Sensei()->version
		);
	}

	/**
	 * Load and output the code for the modal window.
	 *
	 * @access public
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
