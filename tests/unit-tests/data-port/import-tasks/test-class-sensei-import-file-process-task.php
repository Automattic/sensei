<?php
/**
 * This file contains the Sensei_Import_File_Process_Task_Tests class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tests for Sensei_Import_File_Process_Task class.
 *
 * @group data-port
 */
class Sensei_Import_File_Process_Task_Tests extends WP_UnitTestCase {


	/**
	 * Set up before class.
	 */
	public static function setUpBeforeClass() {
		new Sensei_Import_Job( '' );

		require_once SENSEI_TEST_FRAMEWORK_DIR . '/data-port/class-sensei-import-file-process-task-mock.php';
		require_once SENSEI_TEST_FRAMEWORK_DIR . '/data-port/class-sensei-data-port-job-mock.php';
	}

	/**
	 * Tets that process_line is called with correct data.
	 */
	public function testProcessLineIsCalled() {
		$attachment_id     = wp_insert_attachment( [], SENSEI_TEST_FRAMEWORK_DIR . '/data-port/data-files/test_csv_reader.csv' );
		$file_process_task = $this->mock_task_method(
			$this->create_job_stub(
				[ 'mock-key' => $attachment_id ],
				[ 'completed-lines' => 10 ]
			),
			'process_line'
		);

		$file_process_task->expects( $this->exactly( 3 ) )
			->method( 'process_line' )
			->withConsecutive(
				[
					10,
					[
						'first column'  => 'first data 6',
						'second column' => 'second data 6',
						'third column'  => 'third data 6',
					],
				],
				[
					11,
					[
						'first column'  => 'first data 7',
						'second column' => 'second data 7',
						'third column'  => 'third data 7',
					],
				],
				[
					12,
					[],
				]
			);

		$file_process_task->run();
	}

	/**
	 * Tests that process_line is not called when the whole file has been processed.
	 */
	public function testProcessLineIsNotCalledWhenTaskIsCompleted() {
		$attachment_id     = wp_insert_attachment( [], SENSEI_TEST_FRAMEWORK_DIR . '/data-port/data-files/test_csv_reader.csv' );
		$file_process_task = $this->mock_task_method(
			$this->create_job_stub(
				[ 'mock-key' => $attachment_id ],
				[ 'completed-lines' => 13 ]
			),
			'process_line'
		);

		$file_process_task->expects( $this->never() )
			->method( 'process_line' );

		$file_process_task->run();
	}

	private function mock_task_method( $job, $method ) {
		return $this->getMockBuilder( Sensei_Import_File_Process_Task_Mock::class )
			->setConstructorArgs( [ $job ] )
			->setMethods( [ $method ] )
			->getMock();
	}

	private function create_job_stub( $files, $state ) {
		$stub = $this->createMock( Sensei_Data_Port_Job_Mock::class, [ 'mock-job' ] );

		$stub->method( 'get_files' )->willReturn( $files );
		$stub->method( 'get_state' )->willReturn( $state );

		return $stub;
	}
}
