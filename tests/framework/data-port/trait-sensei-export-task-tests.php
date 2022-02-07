<?php
/**
 * This file contains the Sensei_Export_Task_Tests class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tests for Sensei_Export_Courses class.
 *
 * @group data-port
 */
trait Sensei_Export_Task_Tests {

	/**
	 * Find a line by ID.
	 *
	 * @param array $result Result data.
	 * @param int   $id     The id.
	 *
	 * @return array The found line.
	 */
	protected static function get_by_id( array $result, $id ) {
		$key = array_search( strval( $id ), array_column( $result, 'id' ), true );
		return $result[ $key ];
	}

	/**
	 * Run the export job and read back the created CSV.
	 *
	 * @return array The exported data as read from the CSV file.
	 */
	public function export() {
		$job               = Sensei_Export_Job::create( 'test', 0 );
		$export_task_class = $this->get_task_class();
		$task              = new $export_task_class( $job );
		$task->run();
			return self::read_csv( $job->get_file_path( $task->get_content_type() ) );
	}

	/**
	 * Read back an exported CSV file.
	 *
	 * @param string $filename
	 *
	 * @return array
	 */
	protected static function read_csv( $filename ) {
		$reader = new Sensei_Import_CSV_Reader( $filename, 0, 1000 );
		return $reader->read_lines();
	}
}
