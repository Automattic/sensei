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
	 * Request body should contain a list of selected content types.
	 *
	 * @param WP_REST_Request $request The REST request.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function request_post_start_job( $request ) {

		$params = $request->get_json_params();

		if ( empty( $params['content_types'] ) ) {
			return new WP_Error(
				'sensei_export_no_content_types',
				__( 'No content types selected.', 'sensei-lms' ),
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
			$job->set_content_types( $params['content_types'] );
			$job->set_mode( $params['mode'] ?? Sensei_Export_Job::MODE_BY_FILE_TYPE );
			$job->set_selections( $this->sanitize_selections( $params['selections'] ?? array() ) );
			$job->resolve_export_ids();
			$job->persist();
		}

		return parent::request_post_start_job( $request );
	}

	/**
	 * Sanitize the per-type item selections from the request payload.
	 *
	 * @since $$next-version$$
	 *
	 * @param mixed $selections Raw selections value from the request.
	 *
	 * @return array Per-type ID arrays keyed by 'course', 'lesson', 'question'.
	 */
	private function sanitize_selections( $selections ) {
		if ( ! is_array( $selections ) ) {
			return array();
		}

		$sanitized = array();
		foreach ( array( 'course', 'lesson', 'question' ) as $type ) {
			if ( ! isset( $selections[ $type ] ) || ! is_array( $selections[ $type ] ) ) {
				$sanitized[ $type ] = array();
				continue;
			}

			$ids = array_map( 'absint', $selections[ $type ] );
			$ids = array_values( array_filter( $ids ) );

			$sanitized[ $type ] = array_values( array_unique( $ids ) );
		}

		return $sanitized;
	}
}
