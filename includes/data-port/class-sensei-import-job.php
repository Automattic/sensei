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
	const RESULT_ERROR        = 'error';
	const RESULT_SUCCESS      = 'success';

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
	 * Increment result count for a model.
	 *
	 * @param string $model_key Model key.
	 * @param string $result    Result key (success, error).
	 */
	public function increment_result( $model_key, $result ) {
		if ( ! isset( $this->results[ $model_key ][ $result ] ) ) {
			return;
		}

		$this->has_changed = true;
		$this->results[ $model_key ][ $result ]++;
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

			$valid_mime_type = Sensei_Data_Port_Utilities::validate_file_mime_type( $wp_filetype['type'], $file_config['mime_types'], $file_name );

			if ( is_wp_error( $valid_mime_type ) ) {
				$valid_mime_type->add_data( [ 'status' => 400 ] );
				return $valid_mime_type;
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
	 * Get the default results array.
	 *
	 * @return array
	 */
	public static function get_default_results() {
		$model_keys = [
			Sensei_Import_Question_Model::MODEL_KEY,
			Sensei_Import_Course_Model::MODEL_KEY,
			Sensei_Import_Lesson_Model::MODEL_KEY,
		];

		$result_keys = [
			self::RESULT_SUCCESS,
			self::RESULT_ERROR,
		];

		$results = [];

		foreach ( $model_keys as $model_key ) {
			$results[ $model_key ] = [];

			foreach ( $result_keys as $result_key ) {
				$results[ $model_key ][ $result_key ] = 0;
			}
		}

		return $results;
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
