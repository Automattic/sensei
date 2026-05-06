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
	 * @param array $selections Optional per-type selections to apply before
	 *                          running the task. Keys are content types
	 *                          ('course', 'lesson', 'question'); values are
	 *                          arrays of post IDs to restrict the export to.
	 *
	 * @return array The exported data as read from the CSV file.
	 */
	public function export( array $selections = array() ) {
		$job = Sensei_Export_Job::create( 'test', 0 );

		if ( ! empty( $selections ) ) {
			$job->set_selections( $selections );
		}

		$export_task_class = $this->get_task_class();
		$task              = new $export_task_class( $job );
		$task->run();

		return self::read_csv( $job->get_file_path( $task->get_content_type() ) );
	}

	/**
	 * Run the export task across as many batches as it needs and read back the
	 * combined CSV.
	 *
	 * Each iteration constructs a fresh task so the constructor's WP_Query
	 * picks up the updated `completed-posts` offset persisted by the previous
	 * run — the same loop the real job runner performs.
	 *
	 * @param array $selections Optional per-type selections to apply before
	 *                          running the task.
	 *
	 * @return array The exported data as read from the CSV file.
	 */
	public function export_to_completion( array $selections = array() ) {
		$job = Sensei_Export_Job::create( 'test', 0 );

		if ( ! empty( $selections ) ) {
			$job->set_selections( $selections );
		}

		$export_task_class = $this->get_task_class();

		do {
			$task = new $export_task_class( $job );
			$task->run();
		} while ( ! $task->is_completed() );

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
