<?php
/**
 * File containing Sensei_Exit_Survey class.
 *
 * @package Sensei\Admin
 * @since   3.5.3
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Exit survey upon plugin deactivation.
 *
 * @since 3.5.3
 */
class Sensei_Exit_Survey {


	/**
	 * Sensei_Exit_Survey constructor.
	 */
	public function __construct() {
		add_action( 'wp_ajax_exit_survey', array( $this, 'save_exit_survey' ) );

	}

	/**
	 * Save feedback from exit survey AJAX request.
	 */
	public function save_exit_survey() {
		check_ajax_referer( 'sensei_exit_survey' );

		$feedback = [
			'reason'  => isset( $_POST['reason'] ) ? sanitize_text_field( wp_unslash( $_POST['reason'] ) ) : null,
			'details' => isset( $_POST['details'] ) ? sanitize_text_field( wp_unslash( $_POST['details'] ) ) : null,
		];

		update_option( 'sensei_exit_survey_data', $feedback );

		sensei_log_event( 'deactivate', $feedback );
	}

}
