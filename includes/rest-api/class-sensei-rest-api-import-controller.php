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
			$this->rest_base . '/(?P<job_id>[0-9a-z]+)/file/(?P<file_key>[a-z-]+)',
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

		// Endpoint to start the job that imports a sample course.
		register_rest_route(
			$this->namespace,
			$this->rest_base . '/start-sample',
			[
				[
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => [ $this, 'request_post_start_sample_job' ],
					'permission_callback' => [ $this, 'can_user_access_rest_api' ],
				],
				'schema' => [ $this, 'get_item_schema' ],
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
			return $this->report_upload_file_failed(
				$request,
				new WP_Error(
					'sensei_data_port_missing_file',
					__( 'No file was uploaded.', 'sensei-lms' ),
					[ 'status' => 400 ]
				)
			);
		}

		$job = $this->resolve_job( sanitize_text_field( $request->get_param( 'job_id' ) ), true );
		if ( ! $job ) {
			$job = $this->create_job();
		}

		// Check if the job has started or if there was an error creating the job.
		if ( ! $job || $job->is_started() ) {
			return $this->report_upload_file_failed(
				$request,
				new WP_Error(
					'sensei_data_port_job_started',
					__( 'Job has already been started.', 'sensei-lms' ),
					[ 'status' => 400 ]
				)
			);
		}

		// Check to make sure the upload succeeded.
		if (
			! isset( $files['file']['tmp_name'], $files['file']['error'] )
			|| ! $this->is_uploaded_file( $files['file']['tmp_name'] )
			|| UPLOAD_ERR_OK !== $files['file']['error']
		) {
			return $this->report_upload_file_failed( $request, $this->describe_upload_error( $files['file'] ) );
		}

		$result = $job->save_file( $request->get_param( 'file_key' ), $files['file']['tmp_name'], $files['file']['name'] );

		if ( is_wp_error( $result ) ) {
			return $this->report_upload_file_failed( $request, $result );
		}

		$response = new WP_REST_Response();
		$response->set_status( 200 );
		$response->set_data( $this->prepare_to_serve_job( $job ) );

		return $response;
	}

	/**
	 * Logs errors during file upload.
	 *
	 * @param WP_REST_Request $request  Request object.
	 * @param WP_Error        $response Error response object.
	 *
	 * @return WP_Error
	 */
	private function report_upload_file_failed( WP_REST_Request $request, WP_Error $response ) {
		sensei_log_event(
			'import_upload_error',
			[
				'type'  => $request->get_param( 'file_key' ),
				'error' => $response->get_error_code(),
			]
		);

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
	 * Describe an upload error.
	 *
	 * @param array $file Array entry from `$_FILES`.
	 *
	 * @return WP_Error
	 */
	private function describe_upload_error( $file ) {
		switch ( $file['error'] ) {
			case 1:
			case 2:
				$error_code    = 'sensei_import_upload_failed_max_file_size';
				$error_message = __( 'The file uploaded exceeds the maximum file size allowed.', 'sensei-lms' );
				break;
			case 3:
				$error_code    = 'sensei_import_upload_failed_partial_upload';
				$error_message = __( 'The file was only partially uploaded. Please try again.', 'sensei-lms' );
				break;
			case 4:
				$error_code    = 'sensei_import_upload_failed_no_file';
				$error_message = __( 'No file was uploaded.', 'sensei-lms' );
				break;
			case 6:
				$error_code    = 'sensei_import_upload_failed_missing_tmp_folder';
				$error_message = __( 'Missing a temporary folder to store the uploaded file.', 'sensei-lms' );
				break;
			case 7:
				$error_code    = 'sensei_import_upload_failed_unwritable';
				$error_message = __( 'Failed to write the uploaded file to disk. Please contact your host to fix a possible permissions issue.', 'sensei-lms' );
				break;
			case 8:
				$error_code    = 'sensei_import_upload_failed_php_extension';
				$error_message = __( 'A PHP Extension prevented the file upload. Please contact your host.', 'sensei-lms' );
				break;
			default:
				$error_code    = 'sensei_import_upload_failed_unknown';
				$error_message = __( 'File upload error.', 'sensei-lms' );
		}

		return new WP_Error(
			$error_code,
			$error_message
		);
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
	 * Handle the request to start importing sample data.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function request_post_start_sample_job( $request ) {
		$files = [
			'courses' => Sensei()->plugin_path() . 'sample-data/courses.csv',
			'lessons' => Sensei()->plugin_path() . 'sample-data/lessons.csv',
		];

		$job = Sensei_Data_Port_Manager::instance()->create_import_job( get_current_user_id() );
		$job->set_is_sample_data( true );

		foreach ( $files as $file_key => $file_path ) {
			$result = $job->save_file( $file_key, $file_path, basename( $file_path ) );

			if ( is_wp_error( $result ) ) {
				Sensei_Data_Port_Manager::instance()->cancel_job( $job );
				return $result;
			}
		}

		if ( ! Sensei_Data_Port_Manager::instance()->start_job( $job ) ) {
			return new WP_Error(
				'sensei_data_port_job_could_not_be_started',
				__( 'Job could not be started', 'sensei-lms' ),
				array( 'status' => 500 )
			);
		}

		$response = new WP_REST_Response();
		$response->set_data( $this->prepare_to_serve_job( $job ) );

		return $response;
	}

	/**
	 * Get the job schema for the client.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$schema = parent::get_item_schema();

		$results_schema = [];
		$result_keys    = [
			'error',
			'warning',
			'success',
		];

		foreach ( Sensei_Import_Job::get_default_results() as $model_key => $results ) {
			$results_schema[ $model_key ] = [
				'type'       => 'object',
				'properties' => [],
			];

			foreach ( $result_keys as $result_key => $count ) {
				$results_schema[ $model_key ]['properties'][ $result_key ] = [
					// translators: %1$s placeholder is object type; %2$s is result descriptor (success, error).
					'description' => sprintf( __( 'Number of %1$s items with %2$s result', 'sensei-lms' ), $model_key, $result_key ),
					'type'        => 'integer',
				];
			}
		}

		$schema['properties']['results']['properties'] = $results_schema;

		return $schema;
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
