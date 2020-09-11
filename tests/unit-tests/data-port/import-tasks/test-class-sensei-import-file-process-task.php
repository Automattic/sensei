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
		require_once SENSEI_TEST_FRAMEWORK_DIR . '/data-port/class-sensei-import-file-process-task-mock.php';
		require_once SENSEI_TEST_FRAMEWORK_DIR . '/data-port/class-sensei-data-port-job-mock.php';
	}


	/**
	 * Tests that post process tasks run in batches.
	 */
	public function testHandlesPostProcessTasks() {
		$attachment_id     = wp_insert_attachment( [], SENSEI_TEST_FRAMEWORK_DIR . '/data-port/data-files/test_csv_reader.csv' );
		$file_process_task = $this->mock_task_method(
			$this->create_job_stub(
				[ 'mock-key' => $attachment_id ],
				[ 'completed-lines' => 10 ]
			),
			[ 'process_line', 'handle_something' ]
		);

		$file_process_task
			->expects( $this->exactly( 70 ) )
			->method( 'handle_something' );

		$file_process_task
			->expects( $this->any() )
			->method( 'process_line' )
			->will(
				$this->returnCallback(
					function( $line_number, $line ) use ( $file_process_task ) {
						if ( 12 === $line_number ) {
							for ( $i = 0; $i < 70; $i++ ) {
								$file_process_task->add_post_process_task( 'something', [ $i ] );
							}
						}
					}
				)
			);

		$file_process_task->run();
		$this->assertFalse( $file_process_task->is_completed(), 'Lines have been processed, but post-process tasks have not' );

		$file_process_task->run();
		$this->assertFalse( $file_process_task->is_completed(), 'Post process tasks have not finished' );

		$file_process_task->run();
		$this->assertTrue( $file_process_task->is_completed(), 'Post process tasks have finished' );
	}

	/**
	 * Tests that process_line is called with correct data.
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
					12,
					[
						'first column'  => 'first data 6',
						'second column' => 'second data 6',
						'third column'  => 'third data 6',
					],
				],
				[
					13,
					[
						'first column'  => 'first data 7',
						'second column' => 'second data 7',
						'third column'  => 'third data 7',
					],
				],
				[
					14,
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
		if ( ! is_array( $method ) ) {
			$method = [ $method ];
		}
		return $this->getMockBuilder( Sensei_Import_File_Process_Task_Mock::class )
			->setConstructorArgs( [ $job ] )
			->setMethods( $method )
			->getMock();
	}

	private function create_job_stub( $files, $state ) {
		$stub = $this->createMock( Sensei_Data_Port_Job_Mock::class, [ 'mock-job' ] );

		$stub->method( 'get_files' )->willReturn( $files );
		$stub->method( 'get_state' )->willReturn( $state );

		return $stub;
	}
}
