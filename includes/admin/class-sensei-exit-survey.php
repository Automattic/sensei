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
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_action( 'wp_ajax_exit_survey', array( $this, 'save_exit_survey' ) );

	}

	/**
	 * Enqueues admin scripts when needed on different screens.
	 *
	 * @since  2.0.0
	 * @access private
	 */
	public function enqueue_admin_assets() {
		$screen = get_current_screen();
		if ( in_array( $screen->id, [ 'plugins', 'plugins-network' ], true ) ) {
			Sensei()->assets->enqueue( 'sensei-admin-exit-survey', 'admin/exit-survey/index.js', [], true );
			Sensei()->assets->enqueue( 'sensei-admin-exit-survey', 'admin/exit-survey/exit-survey.css', [], 'screen' );

			wp_localize_script(
				'sensei-admin-exit-survey',
				'sensei_exit_survey',
				[
					'nonce' => wp_create_nonce( 'sensei_exit_survey' ),
				]
			);
		}
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

		if ( Sensei()->usage_tracking->is_tracking_enabled() ) {
			sensei_log_event( 'plugin_deactivate', $feedback );
		} else {
			Sensei()->usage_tracking->send_anonymous_event( 'plugin_deactivate', $feedback );
		}
	}

}
