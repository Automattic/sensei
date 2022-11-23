<?php
/**
 * File containing the Sensei_Export_Package class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Package CSVs into a Zip file.
 */
class Sensei_Export_Package
	extends Sensei_Data_Port_Task
	implements Sensei_Data_Port_Task_Interface {

	/**
	 * Simple flag for when the task has been executed.
	 *
	 * @var bool
	 */
	private $is_completed = false;

	/**
	 * Run this task.
	 */
	public function run() {
		$this->is_completed = true;

		$job            = $this->get_job();
		$exported_files = $job->get_files();
		$tmp_filename   = get_temp_dir() . DIRECTORY_SEPARATOR . 'sensei-lms-export-' . time() . '.zip';
		$zip            = new ZipArchive();
		$error          = false;

		$zip_open = $zip->open( $tmp_filename, ZipArchive::CREATE );
		if ( true !== $zip_open ) {
			// Unable to open the file. Just return the individual CSVs.
			return;
		}

		foreach ( $exported_files as $file_key => $file_post_id ) {
			$file_path = $job->get_file_path( $file_key );
			$file      = get_post( $file_post_id );
			if ( ! $file_path || ! $zip->addFile( $file_path, sanitize_file_name( $file->post_title ) ) ) {
				$error = true;
				break;
			}
		}

		$zip->close();

		if ( empty( $exported_files ) || $error ) {
			return;
		}

		$filename = 'sensei-lms-export-' . gmdate( 'Y-m-d' ) . '.zip';
		$result   = $job->save_file( 'package', $tmp_filename, $filename );
		if ( $result instanceof WP_Error ) {
			return;
		}

		// On success, delete the other files now that they are not needed.
		foreach ( array_keys( $exported_files ) as $file_key ) {
			$job->delete_file( $file_key );
		}
	}

	/**
	 * Returns true if the task is completed.
	 *
	 * @return boolean
	 */
	public function is_completed() {
		return $this->is_completed;
	}

	/**
	 * Returns the completion ratio of this task. The ration has the following format:
	 *
	 * {
	 *
	 *     @type integer $completed  Number of completed actions.
	 *     @type integer $total      Number of total actions.
	 * }
	 *
	 * @return array
	 */
	public function get_completion_ratio() {
		return [
			'completed' => $this->is_completed() ? 0 : 1,
			'total'     => 1,
		];
	}
}
