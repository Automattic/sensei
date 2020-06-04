<?php
/**
 * File containing the Sensei_Import_Job class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * This class represents a data import job.
 */
class Sensei_Import_Job extends Sensei_Data_Port_Job {
	const MAPPED_ID_STATE_KEY = '_map';

	/**
	 * The array of the import tasks.
	 *
	 * @var Sensei_Data_Port_Task_Interface[]
	 */
	private $tasks;

	/**
	 * Sensei_Import_Job constructor.
	 *
	 * @param string $job_id  The job id.
	 * @param array  $args    Arguments for the import job.
	 * @param string $json    JSON string to restore the state from.
	 */
	public function __construct( $job_id, $args = [], $json = '' ) {
		parent::__construct( $job_id, $args, $json );

		$this->import_task_files();

		// TODO: Generate the tasks from the arguments and/or from the internal state.
		$this->tasks              = [];
		$this->tasks['questions'] = $this->initialize_task( Sensei_Import_Questions::class );
		$this->tasks['lessons']   = $this->initialize_task( Sensei_Import_Lessons::class );
		$this->tasks['courses']   = $this->initialize_task( Sensei_Import_Courses::class );
	}

	/**
	 * Ensure the task files have been included.
	 */
	private function import_task_files() {
		require_once __DIR__ . '/import-tasks/class-sensei-import-questions.php';
		require_once __DIR__ . '/import-tasks/class-sensei-import-courses.php';
		require_once __DIR__ . '/import-tasks/class-sensei-import-lessons.php';
	}

	/**
	 * Get the tasks of this import job.
	 *
	 * @return Sensei_Data_Port_Task_Interface[]
	 */
	public function get_tasks() {
		return $this->tasks;
	}

	/**
	 * Get the configuration for expected files.
	 *
	 * @return array {
	 *    @type array $$component {
	 *        @type callable $validator  Callback to handle validating the file before save (optional).
	 *        @type array    $mime_types Expected mime-types for the file.
	 *    }
	 * }
	 */
	public static function get_file_config() {
		$files = [];

		$csv_mime_types = [
			'csv' => 'text/csv',
			'txt' => 'text/plain',
		];

		$files['questions'] = [
			'validator'  => [ Sensei_Import_Questions::class, 'validate_source_file' ],
			'mime_types' => $csv_mime_types,
		];

		$files['courses'] = [
			'validator'  => [ Sensei_Import_Courses::class, 'validate_source_file' ],
			'mime_types' => $csv_mime_types,
		];

		$files['lessons'] = [
			'validator'  => [ Sensei_Import_Lessons::class, 'validate_source_file' ],
			'mime_types' => $csv_mime_types,
		];

		return $files;
	}

	/**
	 * Check if a job is ready to be started.
	 *
	 * @return bool
	 */
	public function is_ready() {
		$files = $this->get_files();

		return isset( $files['questions'] ) || isset( $files['courses'] ) || isset( $files['lessons'] );
	}

	/**
	 * Save a file associated with this job. If this is an uploaded file, `is_uploaded_file()` check should
	 * occur prior to this method.
	 *
	 * @param string $file_key  Key for the file being saved.
	 * @param string $tmp_file  Temporary path where the file is stored.
	 * @param string $file_name File name.
	 *
	 * @return true|WP_Error
	 */
	public function save_file( $file_key, $tmp_file, $file_name ) {
		$files = $this->get_files();

		// Make sure to clean up any previous file.
		if ( isset( $files[ $file_key ] ) ) {
			$this->delete_file( $file_key );
		}

		$check_file = $this->check_file( $file_key, $tmp_file, $file_name );

		if ( is_wp_error( $check_file ) ) {
			return $check_file;
		}

		return parent::save_file( $file_key, $tmp_file, $file_name );
	}

	/**
	 * Check a file before saving it.
	 *
	 * @param string $file_key  Key for the file being saved.
	 * @param string $tmp_file  Temporary path where the file is stored.
	 * @param string $file_name File name.
	 *
	 * @return true|WP_Error
	 */
	private function check_file( $file_key, $tmp_file, $file_name ) {
		$file_configs = static::get_file_config();

		if ( ! isset( $file_configs[ $file_key ] ) ) {
			return new WP_Error(
				'sensei_data_port_unknown_file_key',
				__( 'Unexpected file key used.', 'sensei-lms' )
			);
		}

		$file_config = $file_configs[ $file_key ];

		if ( isset( $file_config['validator'] ) ) {
			$validation_result = call_user_func( $file_config['validator'], $tmp_file );
			if ( is_wp_error( $validation_result ) ) {
				return $validation_result;
			}
		}

		if ( isset( $file_config['mime_types'] ) ) {
			$wp_filetype = wp_check_filetype_and_ext( $tmp_file, $file_name, $file_config['mime_types'] );

			$valid_mime_type  = $wp_filetype['type'] && in_array( $wp_filetype['type'], $file_config['mime_types'], true );
			$valid_extensions = $this->mime_types_extensions( $file_config['mime_types'] );

			// If we cannot determine the type, allow check based on extension for administrators.
			if ( ! $wp_filetype['type'] && current_user_can( 'unfiltered_upload' ) ) {
				$valid_mime_type = in_array( pathinfo( $file_name, PATHINFO_EXTENSION ), $valid_extensions, true );
			}

			if ( ! $valid_mime_type ) {
				return new WP_Error(
					'sensei_data_port_unexpected_file_type',
					// translators: Placeholder is list of file extensions.
					sprintf( __( 'File type is not supported. Must be one of the following: %s.', 'sensei-lms' ), implode( ', ', $valid_extensions ) ),
					[ 'status' => 400 ]
				);
			}
		}

		return true;
	}

	/**
	 * Get an array of extensions.
	 *
	 * @param array $mime_types Array of mime types.
	 *
	 * @return array Array of valid extensions.
	 */
	private function mime_types_extensions( $mime_types ) {
		$extensions = [];
		foreach ( array_keys( $mime_types ) as $ext_list ) {
			$extensions = array_merge( $extensions, explode( '|', $ext_list ) );
		}

		return array_unique( $extensions );
	}

	/**
	 * Retrieves the post ID for the imported item based on the ID in the source file.
	 *
	 * @param string $post_type   Post type for the imported object.
	 * @param int    $original_id ID that was provided in the source file.
	 *
	 * @return int|null
	 */
	public function get_import_id( $post_type, $original_id ) {
		$map = $this->get_state( self::MAPPED_ID_STATE_KEY );

		if ( isset( $map[ $post_type ][ $original_id ] ) ) {
			return $map[ $post_type ][ $original_id ];
		}

		return null;
	}

	/**
	 * Store the post ID for the imported item with the ID in the source file.
	 *
	 * @param string $post_type   Post type for the imported object.
	 * @param int    $original_id ID that was provided in the source file.
	 * @param int    $post_id     Post ID that was created during the import.
	 */
	public function set_import_id( $post_type, $original_id, $post_id ) {
		$map = $this->get_state( self::MAPPED_ID_STATE_KEY );

		if ( ! isset( $map[ $post_type ] ) ) {
			$map[ $post_type ] = [];
		}

		$map[ $post_type ][ $original_id ] = $post_id;

		$this->set_state( self::MAPPED_ID_STATE_KEY, $map );
	}



}
