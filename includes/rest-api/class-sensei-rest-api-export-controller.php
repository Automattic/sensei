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
			return new WP_Error(
				'sensei_export_no_content_types',
				__( 'Please choose at least one type of content to export.', 'sensei-lms' ),
				array( 'status' => 400 )
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
			$job->persist();
		}

		return parent::request_post_start_job( $request );
	}
}
