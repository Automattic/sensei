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
	const RESULT_ERROR        = -1;
	const RESULT_WARNING      = 0;
	const RESULT_SUCCESS      = 1;

	/**
	 * The array of the import tasks.
	 *
	 * @var Sensei_Data_Port_Task_Interface[]
	 */
	private $tasks;

	/**
	 * Sensei_Import_Job constructor.
	 *
	 * @param string $job_id Unique job id.
	 * @param string $json   A json string to restore internal state from.
	 */
	public function __construct( $job_id, $json = '' ) {
		parent::__construct( $job_id, $json );

		if ( null === $this->results ) {
			$this->results = self::get_default_results();
		}
	}

	/**
	 * Get the tasks of this import job.
	 *
	 * @return Sensei_Data_Port_Task_Interface[]
	 */
	public function get_tasks() {
		if ( ! isset( $this->tasks ) ) {
			$this->tasks              = [];
			$this->tasks['questions'] = $this->initialize_task( Sensei_Import_Questions::class );
			$this->tasks['courses']   = $this->initialize_task( Sensei_Import_Courses::class );
			$this->tasks['lessons']   = $this->initialize_task( Sensei_Import_Lessons::class );
		}

		return $this->tasks;
	}

	/**
	 * Set a line result value.
	 *
	 * @param string $model_key   Model key.
	 * @param int    $line_number Line number.
	 * @param int    $result      Result value from class constants RESULT_ERROR, RESULT_WARNING, RESULT_SUCCESS.
	 */
	public function set_line_result( $model_key, $line_number, $result ) {
		if (
			! isset( $this->results[ $model_key ][ $line_number ] )
			|| $this->results[ $model_key ][ $line_number ] > $result
		) {
			// Once a result is set, it can only get worse.
			$this->has_changed                           = true;
			$this->results[ $model_key ][ $line_number ] = $result;
		}
	}

	/**
	 * Add warning for a line.
	 *
	 * @param string $model_key   Model key.
	 * @param int    $line_number Line number.
	 * @param string $message     Warning message.
	 * @param array  $log_data    Log data.
	 */
	public function add_line_warning( $model_key, $line_number, $message, $log_data = [] ) {
		$log_data['line'] = $line_number;

		$this->set_line_result( $model_key, $line_number, self::RESULT_WARNING );
		$this->add_log_entry( $message, self::LOG_LEVEL_NOTICE, $log_data );
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

		if ( isset( $file_config['validator'] ) ) {
			$validation_result = call_user_func( $file_config['validator'], $tmp_file );
			if ( is_wp_error( $validation_result ) ) {
				return $validation_result;
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
	 * @param string $original_id ID that was provided in the source file.
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
	 * @param string $original_id ID that was provided in the source file.
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

	/**
	 * Get the result counts for each model.
	 */
	public function get_result_counts() {
		$model_keys = [
			Sensei_Import_Question_Model::MODEL_KEY,
			Sensei_Import_Course_Model::MODEL_KEY,
			Sensei_Import_Lesson_Model::MODEL_KEY,
		];

		$result_keys = [
			'error'   => self::RESULT_ERROR,
			'warning' => self::RESULT_WARNING,
			'success' => self::RESULT_SUCCESS,
		];

		$results = [];
		foreach ( $model_keys as $model_key ) {
			if ( ! isset( $this->results[ $model_key ] ) ) {
				$this->results[ $model_key ] = [];
			}

			$results[ $model_key ] = [];
			$value_counts          = array_count_values( $this->results[ $model_key ] );

			foreach ( $result_keys as $friendly_name => $result_value ) {
				$results[ $model_key ][ $friendly_name ] = isset( $value_counts[ $result_value ] ) ? $value_counts[ $result_value ] : 0;
			}
		}

		return $results;
	}

	/**
	 * Get the default results array.
	 *
	 * @return array
	 */
	public static function get_default_results() {
		return [
			Sensei_Import_Question_Model::MODEL_KEY => [],
			Sensei_Import_Course_Model::MODEL_KEY   => [],
			Sensei_Import_Lesson_Model::MODEL_KEY   => [],
		];
	}

	/**
	 * Returns the post id for an import id or check if the post exists.
	 *
	 * @param string $post_type  The post type.
	 * @param string $import_id  The import id.
	 *
	 * @return int|null The post id if the post exists, null otherwise.
	 */
	public function translate_import_id( $post_type, $import_id ) {
		if ( empty( $import_id ) ) {
			return null;
		}

		if ( 0 === strpos( $import_id, 'id:' ) ) {
			return $this->get_import_id( $post_type, substr( $import_id, 3 ) );
		}

		if ( 0 === strpos( $import_id, 'slug:' ) ) {
			$post = get_posts(
				[
					'post_type'      => $post_type,
					'post_name__in'  => [ substr( $import_id, 5 ) ],
					'posts_per_page' => 1,
					'post_status'    => 'any',
					'fields'         => 'ids',
				]
			);

			return empty( $post ) ? null : $post[0];
		}

		if ( null !== get_post( (int) $import_id ) ) {
			return (int) $import_id;
		}

		return null;
	}

}
