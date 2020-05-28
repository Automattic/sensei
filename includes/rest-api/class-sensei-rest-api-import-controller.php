<?php
/**
 * Import REST API Controller.
 *
 * @package Sensei\DataPort
 * @since   3.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Sensei Import REST API endpoints.
 *
 * @package Sensei
 * @author  Automattic
 * @since   3.1.0
 */
class Sensei_REST_API_Import_Controller extends Sensei_REST_API_Data_Port_Controller {
	/**
	 * Routes prefix.
	 *
	 * @var string
	 */
	protected $rest_base = 'import';

	/**
	 * Get the handler class job this REST API controller handles.
	 *
	 * @return string
	 */
	protected function get_handler_class() {
		return Sensei_Import_Job::class;
	}

	/**
	 * Create a data port job for the current user.
	 *
	 * @return Sensei_Data_Port_Job
	 */
	protected function create_job() {
		return Sensei_Data_Port_Manager::instance()->create_import_job( get_current_user_id() );
	}

	/**
	 * Register the REST API endpoints for the class.
	 */
	public function register_routes() {
		parent::register_routes();

		register_rest_route(
			$this->namespace,
			$this->rest_base . '/file/(?P<file_key>[a-z-]+)',
			[
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'request_post_file' ],
					'permission_callback' => [ $this, 'can_user_access_rest_api' ],
				],
				[
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => [ $this, 'request_delete_file' ],
					'permission_callback' => [ $this, 'can_user_access_rest_api' ],
				],
				'schema' => [ $this, 'get_file_item_schema' ],
			]
		);
	}

	/**
	 * Handle the request to upload a file.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function request_post_file( $request ) {
		$files = $request->get_file_params();
		if ( ! isset( $files['file'] ) ) {
			return new WP_Error(
				'sensei_data_port_invalid_file',
				__( 'No file was uploaded.', 'sensei-lms' ),
				[ 'status' => 400 ]
			);
		}

		$data_port_manager = Sensei_Data_Port_Manager::instance();
		$job               = $data_port_manager->get_active_job( $this->get_handler_class(), get_current_user_id() );
		if ( ! $job ) {
			$job = $this->create_job();
		}

		// Check if the job has started or if there was an error creating the job.
		if ( ! $job || $job->is_started() ) {
			return new WP_Error(
				'sensei_data_port_job_started',
				__( 'Job has already been started.', 'sensei-lms' ),
				[ 'status' => 400 ]
			);
		}

		// Check to make sure the upload succeeded.
		if (
			! isset( $files['file']['tmp_name'], $files['file']['error'] )
			|| ! $this->is_uploaded_file( $files['file']['tmp_name'] )
			|| UPLOAD_ERR_OK !== $files['file']['error']
		) {
			return new WP_Error(
				'sensei_data_port_job_upload_failed',
				__( 'Upload was not successful.', 'sensei-lms' )
			);
		}

		$result = $job->save_file( $request->get_param( 'file_key' ), $files['file']['tmp_name'], $files['file']['name'] );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$response = new WP_REST_Response();
		$response->set_status( 200 );
		$response->set_data( $this->prepare_to_serve_job( $job ) );

		return $response;
	}

	/**
	 * Check if a file was uploaded.
	 *
	 * @param string $filename Temporary file path to check.
	 *
	 * @return bool
	 */
	private function is_uploaded_file( $filename ) {
		// Disable this check in tests as it isn't possible to bypass. This is the constant
		// WordPress core uses to see if we're within tests.
		if ( defined( 'DIR_TESTDATA' ) && DIR_TESTDATA ) {
			return true;
		}

		return is_uploaded_file( $filename );
	}

	/**
	 * Handle the request to delete a file.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function request_delete_file( $request ) {
		$data_port_manager = Sensei_Data_Port_Manager::instance();
		$job               = $data_port_manager->get_active_job( $this->get_handler_class(), get_current_user_id() );
		if ( ! $job ) {
			return new WP_Error(
				'sensei_data_port_job_does_not_exist',
				__( 'No active job has been found.', 'sensei-lms' ),
				[ 'status' => 404 ]
			);
		}

		if ( ! $job->get_file_path( $request->get_param( 'file_key' ) ) ) {
			return new WP_Error(
				'sensei_data_port_job_file_not_found',
				__( 'File does not exist.', 'sensei-lms' ),
				[ 'status' => 404 ]
			);
		}

		if ( $job->is_started() ) {
			return new WP_Error(
				'sensei_data_port_job_started',
				__( 'Job has already been started.', 'sensei-lms' ),
				[ 'status' => 400 ]
			);
		}

		$result = $job->delete_file( $request->get_param( 'file_key' ) );

		if ( ! $result ) {
			return new WP_Error(
				'sensei_data_port_unable_to_delete_file',
				__( 'Job file could not be deleted.', 'sensei-lms' ),
				[ 'status' => 500 ]
			);
		}

		$response = new WP_REST_Response();
		$response->set_status( 200 );
		$response->set_data( $this->prepare_to_serve_job( $job ) );

		return $response;
	}

	/**
	 * Get the schema for the client on file related requests.
	 *
	 * @return array
	 */
	public function get_file_item_schema() {
		return [
			'job' => $this->get_item_schema(),
		];
	}

}
