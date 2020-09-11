<?php
/**
 * Import/Export REST API Base Controller.
 *
 * @package Sensei\DataPort
 * @since   3.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Base class for Sensei's Import/Export REST API controllers.
 *
 * @package Sensei
 * @author  Automattic
 * @since   3.1.0
 */
abstract class Sensei_REST_API_Data_Port_Controller extends \WP_REST_Controller {
	const LOG_PAGE_SIZE = 1000;

	/**
	 * Routes namespace.
	 *
	 * @var string
	 */
	protected $namespace;

	/**
	 * Sensei_REST_API_Data_Port_Base constructor.
	 *
	 * @param string $namespace Routes namespace.
	 */
	public function __construct( $namespace ) {
		$this->namespace = $namespace;
	}

	/**
	 * Get the handler class job this REST API controller handles.
	 *
	 * @return string
	 */
	abstract protected function get_handler_class();

	/**
	 * Create a data port job for the current user.
	 *
	 * @return Sensei_Data_Port_Job
	 */
	abstract protected function create_job();

	/**
	 * Register the REST API endpoints for the class..
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			$this->rest_base,
			[
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'request_post_job' ],
					'permission_callback' => [ $this, 'can_user_access_rest_api' ],
				],
				'schema' => [ $this, 'get_item_schema' ],
			]
		);

		register_rest_route(
			$this->namespace,
			$this->rest_base . '/(?P<job_id>[0-9a-z]+)',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'request_get_job' ],
					'permission_callback' => [ $this, 'can_user_access_rest_api' ],
				],
				[
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => [ $this, 'request_delete_job' ],
					'permission_callback' => [ $this, 'can_user_access_rest_api' ],
				],
				'schema' => [ $this, 'get_item_schema' ],
			]
		);

		// Endpoint to start the job.
		register_rest_route(
			$this->namespace,
			$this->rest_base . '/(?P<job_id>[0-9a-z]+)/start',
			[
				[
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => [ $this, 'request_post_start_job' ],
					'permission_callback' => [ $this, 'can_user_access_rest_api' ],
				],
				'schema' => [ $this, 'get_item_schema' ],
			]
		);

		// Endpoint to run the job.
		register_rest_route(
			$this->namespace,
			$this->rest_base . '/(?P<job_id>[0-9a-z]+)/process',
			[
				[
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => [ $this, 'request_process_job' ],
					'permission_callback' => [ $this, 'can_user_access_rest_api' ],
				],
				'schema' => [ $this, 'get_item_schema' ],
			]
		);

		// Endpoint to get the logs.
		register_rest_route(
			$this->namespace,
			$this->rest_base . '/(?P<job_id>[0-9a-z]+)/logs',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'request_get_job_logs' ],
					'permission_callback' => [ $this, 'can_user_access_rest_api' ],
				],
				'schema' => [ $this, 'get_logs_schema' ],
			]
		);
	}

	/**
	 * Resolve the job ID parameter.
	 *
	 * @param int  $job_id        Job ID.
	 * @param bool $allow_current Allow special ID of `active` to get the active job.
	 *
	 * @return Sensei_Data_Port_Job|null
	 */
	protected function resolve_job( $job_id, $allow_current = false ) {
		if ( $allow_current && 'active' === $job_id ) {
			return $this->get_active_job();
		}

		return $this->get_job( $job_id );
	}

	/**
	 * Get the current import job.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function request_get_job( $request ) {
		$job_id = $this->get_job_id_param( $request );
		$job    = $this->resolve_job( $job_id, true );

		if ( ! $job ) {
			if ( 'active' === $job_id ) {
				return [ 'id' => null ];
			}
			return new WP_Error(
				'sensei_data_port_job_not_found',
				__( 'Job not found.', 'sensei-lms' ),
				array( 'status' => 404 )
			);
		}

		$response = new WP_REST_Response();
		$response->set_data( $this->prepare_to_serve_job( $job ) );

		return $response;
	}

	/**
	 * Process a batch for the current job.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function request_process_job( $request ) {
		$job = $this->get_job( $this->get_job_id_param( $request ) );

		if ( ! $job ) {
			return new WP_Error(
				'sensei_data_port_job_not_found',
				__( 'No job could be found.', 'sensei-lms' ),
				array( 'status' => 404 )
			);
		}

		Sensei_Data_Port_Manager::instance()->run_data_port_job( $job );

		$response = new WP_REST_Response();
		$response->set_data( $this->prepare_to_serve_job( $job ) );

		return $response;
	}

	/**
	 * Get the current import job logs.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function request_get_job_logs( $request ) {
		$job = $this->resolve_job( sanitize_text_field( $request->get_param( 'job_id' ) ), true );

		if ( ! $job ) {
			return new WP_Error(
				'sensei_data_port_no_active_job',
				__( 'No job could be found.', 'sensei-lms' ),
				array( 'status' => 404 )
			);
		}

		$offset = isset( $request['offset'] ) ? absint( $request['offset'] ) : 0;

		$response = new WP_REST_Response();
		$response->set_data( $this->prepare_to_serve_job_logs( $job, $offset ) );

		return $response;
	}

	/**
	 * Initialize an import job.
	 *
	 * @return WP_REST_Response
	 */
	public function request_post_job() {
		$job     = $this->get_active_job();
		$created = false;
		if ( ! $job ) {
			$job     = $this->create_job();
			$created = true;
		}

		$response = new WP_REST_Response();
		$response->set_data( $this->prepare_to_serve_job( $job ) );
		if ( $created ) {
			$response->set_status( 201 );
		}

		return $response;
	}

	/**
	 * Cancel a job.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function request_delete_job( $request ) {
		$job = $this->resolve_job( sanitize_text_field( $request->get_param( 'job_id' ) ), false );
		if ( ! $job ) {
			return new WP_Error(
				'sensei_data_port_job_not_found',
				__( 'Job not found.', 'sensei-lms' ),
				array( 'status' => 404 )
			);
		}

		Sensei_Data_Port_Manager::instance()->cancel_job( $job->get_job_id() );

		$response = new WP_REST_Response();
		$response->set_data(
			[
				'deleted'  => true,
				'previous' => $this->prepare_to_serve_job( $job ),
			]
		);

		return $response;
	}

	/**
	 * Start the currently active job.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function request_post_start_job( $request ) {
		$job = $this->resolve_job( sanitize_text_field( $request->get_param( 'job_id' ) ), false );
		if ( ! $job ) {
			return new WP_Error(
				'sensei_data_port_job_not_found',
				__( 'No job has been created.', 'sensei-lms' ),
				array( 'status' => 404 )
			);
		}

		if ( ! $job->is_ready() ) {
			return new WP_Error(
				'sensei_data_port_job_not_ready',
				__( 'Job is not ready to be started.', 'sensei-lms' ),
				array( 'status' => 400 )
			);
		}

		if ( $job->is_started() ) {
			return new WP_Error(
				'sensei_data_port_job_already_started',
				__( 'Job has already been started.', 'sensei-lms' ),
				array( 'status' => 400 )
			);
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
	 * Get the active job for this user.
	 *
	 * @return Sensei_Data_Port_Job|null
	 */
	protected function get_active_job() {
		return Sensei_Data_Port_Manager::instance()->get_active_job( $this->get_handler_class(), get_current_user_id() );
	}

	/**
	 * Get a specific job for this user.
	 *
	 * @param string $job_id The job ID.
	 *
	 * @return Sensei_Data_Port_Job|null
	 */
	protected function get_job( $job_id ) {
		return Sensei_Data_Port_Manager::instance()->get_job_for_user( $this->get_handler_class(), $job_id, get_current_user_id() );
	}

	/**
	 * Check user permission for REST API access.
	 *
	 * @return bool Whether the user can access the Setup Wizard REST API.
	 */
	public function can_user_access_rest_api() {
		return current_user_can( 'manage_sensei' );
	}

	/**
	 * Prepare a job to be sent to the client.
	 *
	 * @param Sensei_Data_Port_Job $job Job to be prepared for the client.
	 *
	 * @return array
	 */
	public function prepare_to_serve_job( Sensei_Data_Port_Job $job ) {
		return [
			'id'      => $job->get_job_id(),
			'status'  => $job->get_status(),
			'files'   => $job->get_files_data(),
			'results' => $job->get_result_counts(),
		];
	}

	/**
	 * Prepare job logs to be sent to the client.
	 *
	 * @param Sensei_Data_Port_Job $job    Job to be prepared for the client.
	 * @param int                  $offset Offset.
	 *
	 * @return array
	 */
	public function prepare_to_serve_job_logs( Sensei_Data_Port_Job $job, $offset = 0 ) {
		$log_entries = $job->get_logs();

		$logs_refactored = array_map(
			function( $entry ) {
				$data = ! empty( $entry['data'] ) ? $entry['data'] : [];

				return [
					'type'       => isset( $data['type'] ) ? $data['type'] : null,
					'line'       => isset( $data['line'] ) ? $data['line'] : null,
					'severity'   => Sensei_Data_Port_Job::translate_log_severity_level( $entry['level'] ),
					'descriptor' => Sensei_Data_Port_Job::get_log_entry_descriptor( $entry ),
					'message'    => $entry['message'],
					'post'       => [
						'id'        => isset( $data['post_id'] ) ? $data['post_id'] : null,
						'title'     => isset( $data['entry_title'] ) ? $data['entry_title'] : null,
						'edit_link' => isset( $data['post_id'] ) ? get_edit_post_link( $data['post_id'], '&' ) : null,
					],
				];
			},
			array_slice( $log_entries, $offset, self::LOG_PAGE_SIZE )
		);

		return [
			'offset' => $offset,
			'total'  => count( $log_entries ),
			'items'  => $logs_refactored,
		];
	}

	/**
	 * Get the job schema for the client.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$file_properties = [];

		$handler_class = $this->get_handler_class();
		$files         = $handler_class::get_file_config();

		foreach ( $files as $file_key => $file_config ) {
			$file_properties[ $file_key ] = [
				'type'       => 'object',
				'properties' => [
					'name' => [
						'description' => __( 'File name', 'sensei-lms' ),
						'type'        => 'string',
					],
					'url'  => [
						'description' => __( 'Direct download URL for the file', 'sensei-lms' ),
						'type'        => 'string',
					],
				],
			];
		}

		return [
			'type'       => 'object',
			'properties' => [
				'id'      => [
					'description' => __( 'Unique identifier for the job', 'sensei-lms' ),
					'type'        => 'string',
					'readonly'    => true,
				],
				'status'  => [
					'type'       => 'object',
					'properties' => [
						'status'     => [
							'description' => __( 'Status of the job (setup, pending, or complete)', 'sensei-lms' ),
							'type'        => 'string',
						],
						'percentage' => [
							'description' => __( 'Percent complete', 'sensei-lms' ),
							'type'        => 'integer',
						],
					],
				],
				'files'   => [
					'type'       => 'object',
					'properties' => $file_properties,
				],
				'results' => [
					'type' => 'object',
				],
			],
		];
	}

	/**
	 * Get the logs schema for the client.
	 *
	 * @return array
	 */
	public function get_logs_schema() {
		return [
			'type'       => 'object',
			'properties' => [
				'items' => [
					'type'  => 'array',
					'items' => [
						'type'       => 'object',
						'properties' => [
							'type'       => [
								'description' => __( 'Object type', 'sensei-lms' ),
								'type'        => 'string',
								'readonly'    => true,
							],
							'line'       => [
								'description' => __( 'Source file line', 'sensei-lms' ),
								'type'        => 'integer',
								'readonly'    => true,
							],
							'severity'   => [
								'description' => __( 'Log severity level ', 'sensei-lms' ),
								'type'        => 'string',
								'enum'        => [ 'info', 'notice', 'error' ],
								'readonly'    => true,
							],
							'descriptor' => [
								'description' => __( 'Object descriptor', 'sensei-lms' ),
								'type'        => 'string',
								'readonly'    => true,
							],
							'message'    => [
								'description' => __( 'Log message', 'sensei-lms' ),
								'type'        => 'string',
								'readonly'    => true,
							],
						],
					],
				],
			],
		];
	}

	/**
	 * Get the job_id parameter from the request.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return string Job ID
	 */
	protected static function get_job_id_param( $request ) {
		return sanitize_text_field( $request->get_param( 'job_id' ) );
	}
}
