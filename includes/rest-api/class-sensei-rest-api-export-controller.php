<?php
/**
 * Export REST API Controller.
 *
 * @package Sensei\DataPort
 * @since   3.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Sensei Export REST API endpoints.
 *
 * @package Sensei
 * @author  Automattic
 * @since   3.1.0
 */
class Sensei_REST_API_Export_Controller extends Sensei_REST_API_Data_Port_Controller {
	/**
	 * Routes prefix.
	 *
	 * @var string
	 */
	protected $rest_base = 'export';

	/**
	 * Get the handler class job this REST API controller handles.
	 *
	 * @return string
	 */
	protected function get_handler_class() {
		return Sensei_Export_Job::class;
	}

	/**
	 * Create a data port job for the current user.
	 *
	 * @return Sensei_Data_Port_Job
	 */
	protected function create_job() {
		return Sensei_Data_Port_Manager::instance()->create_export_job( get_current_user_id() );
	}

	/**
	 * Start an export job.
	 *
	 * Request body should contain a `selections` object keyed by content type
	 * ('course', 'lesson', 'question'). Each value is an array of post IDs to
	 * restrict the export to, or an empty array for "export all of that type".
	 * Types omitted from the object are skipped.
	 *
	 * @param WP_REST_Request $request The REST request.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function request_post_start_job( $request ) {

		$params = $request->get_json_params();

		if ( empty( $params['selections'] ) || ! is_array( $params['selections'] ) ) {
			return $this->invalid_selections_error( __( 'Please choose at least one type of content to export.', 'sensei-lms' ) );
		}

		$unknown_keys = array_diff( array_keys( $params['selections'] ), self::ALLOWED_SELECTION_TYPES );
		if ( ! empty( $unknown_keys ) ) {
			return $this->invalid_selections_error(
				sprintf(
					/* translators: %s is a comma-separated list of unknown selection keys. */
					__( 'Unknown selection types: %s.', 'sensei-lms' ),
					implode( ', ', $unknown_keys )
				)
			);
		}

		/**
		 * Job instance.
		 *
		 * @var Sensei_Export_Job $job
		 */
		$job = $this->resolve_job( sanitize_text_field( $request->get_param( 'job_id' ) ), false );

		if ( $job && $job->is_ready() && ! $job->is_started() ) {
			$job->set_selections( $params['selections'] );

			// `set_selections()` also drops non-array values per type, so a
			// payload like `{ "course": "all" }` would survive the checks
			// above and normalise to nothing. Re-check after normalisation.
			if ( empty( $job->get_content_types() ) ) {
				return $this->invalid_selections_error( __( 'Please choose at least one type of content to export.', 'sensei-lms' ) );
			}

			$job->persist();
		}

		return parent::request_post_start_job( $request );
	}

	/**
	 * Selection keys the export endpoint accepts.
	 */
	const ALLOWED_SELECTION_TYPES = array( 'course', 'lesson', 'question' );

	/**
	 * Build the WP_Error returned when a start request has invalid selections.
	 *
	 * @param string $message Translated message describing what was wrong.
	 *
	 * @return WP_Error
	 */
	private function invalid_selections_error( $message ) {
		return new WP_Error(
			'sensei_export_no_content_types',
			$message,
			array( 'status' => 400 )
		);
	}
}
