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
abstract class Sensei_Export_Task_Tests extends WP_UnitTestCase {

	/**
	 * Content type.
	 *
	 * @return string
	 */
	abstract protected function get_task_class();

	/**
	 * Find a question line by ID.
	 *
	 * @param array $result      Result data.
	 * @param int   $question_id The question id.
	 *
	 * @return array The line for the question.
	 */
	protected static function get_by_id( array $result, $question_id ) {
		$key = array_search( strval( $question_id ), array_column( $result, 'id' ), true );
		return $result[ $key ];
	}

	/**
	 * Run the export job and read back the created CSV.
	 *
	 * @return array The exported data as read from the CSV file.
	 */
	public function export() {
		$job  = Sensei_Export_Job::create( 'test', 0 );
		$export_task_class = $this->get_task_class();
		$task = new $export_task_class( $job );
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
