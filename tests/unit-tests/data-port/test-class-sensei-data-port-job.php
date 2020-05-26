<?php
/**
 * This file contains the Sensei_Data_Port_Job_Test class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once SENSEI_TEST_FRAMEWORK_DIR . '/data-port/class-sensei-data-port-job-mock.php';
require_once SENSEI_TEST_FRAMEWORK_DIR . '/data-port/class-sensei-data-port-task-mock.php';

/**
 * Tests for Sensei_Data_Port_Job class.
 *
 * @group data-port
 */
class Sensei_Data_Port_Job_Test extends WP_UnitTestCase {

	/**
	 * Set up the tests.
	 */
	public function setUp() {
		// Make sure CSVs are allowed on WordPress multi-site.
		update_site_option( 'upload_filetypes', 'csv' );

		return parent::setUp();
	}

	public function testJobWithCompletedTasksIsCompleted() {
		$first_completed  = $this->mock_task_method( true, 100, 100, 'run' );
		$second_completed = $this->mock_task_method( true, 100, 100, 'run' );

		$job = new Sensei_Data_Port_Job_Mock( 'test-job', [ $first_completed, $second_completed ] );
		$job->start();

		$first_completed->expects( $this->never() )->method( 'run' );
		$second_completed->expects( $this->never() )->method( 'run' );

		$this->assertFalse( $job->is_complete(), 'Job should not be completed until all tasks are completed.' );
		$job->run();
		$this->assertTrue( $job->is_complete(), 'Job should be completed until all tasks are completed.' );

		$this->assertEquals(
			[
				'status'     => 'completed',
				'percentage' => 100,
			],
			$job->get_status()
		);
	}

	public function testJobWithNotCompletedTasksIsNotCompleted() {
		$completed = $this->mock_task_method( true, 100, 100, 'run' );
		$pending   = $this->mock_task_method( false, 25, 100, 'run' );

		$job = new Sensei_Data_Port_Job_Mock( 'test-job', [ $completed, $pending ] );
		$job->start();

		$completed->expects( $this->never() )->method( 'run' );
		$pending->expects( $this->once() )->method( 'run' );

		$this->assertFalse( $job->is_complete(), 'Job should not be completed until all tasks are completed.' );
		$job->run();
		$this->assertFalse( $job->is_complete(), 'Job should not be completed until all tasks are completed.' );

		$this->assertEquals(
			[
				'status'     => 'pending',
				'percentage' => 62.5,
			],
			$job->get_status()
		);
	}

	public function testOnlyOnePendingTaskIsExecuted() {
		$first_pending  = $this->mock_task_method( false, 50, 100, 'run' );
		$second_pending = $this->mock_task_method( false, 0, 100, 'run' );

		$job = new Sensei_Data_Port_Job_Mock( 'test-job', [ $first_pending, $second_pending ] );
		$job->start();

		$first_pending->expects( $this->once() )->method( 'run' );
		$second_pending->expects( $this->never() )->method( 'run' );

		$this->assertFalse( $job->is_complete(), 'Job should not be completed until all tasks are completed.' );
		$job->run();
		$this->assertFalse( $job->is_complete(), 'Job should not be completed until all tasks are completed.' );

		$this->assertEquals(
			[
				'status'     => 'pending',
				'percentage' => 25,
			],
			$job->get_status()
		);
	}


	public function testCleanupCallsAllTaskCleanup() {
		$first_completed  = $this->mock_task_method( true, 100, 100, 'clean_up' );
		$second_completed = $this->mock_task_method( true, 100, 100, 'clean_up' );

		$job = new Sensei_Data_Port_Job_Mock( 'test-job', [ $first_completed, $second_completed ] );

		$first_completed->expects( $this->once() )->method( 'clean_up' );
		$second_completed->expects( $this->once() )->method( 'clean_up' );

		$job->clean_up();
	}

	public function testLogMessagePagination() {
		$job = new Sensei_Data_Port_Job_Mock( 'test-job', [ new Sensei_Data_Port_Task_Mock( true, 100, 100 ) ] );

		$job->log( 'First course', 'First failed', 'course', '1' );
		$job->log( 'First lesson', 'First failed', 'lesson', '1' );
		$job->log( 'Third course', 'Third failed', 'course', '3' );
		$job->log( 'Second lesson', 'Second failed', 'lesson', '2' );

		$expected = [
			'course' => [
				[
					'title' => 'First course',
					'msg'   => 'First failed',
					'id'    => '1',
				],
				[
					'title' => 'Third course',
					'msg'   => 'Third failed',
					'id'    => '3',
				],
			],
			'lesson' => [
				[
					'title' => 'First lesson',
					'msg'   => 'First failed',
					'id'    => '1',
				],
				[
					'title' => 'Second lesson',
					'msg'   => 'Second failed',
					'id'    => '2',
				],
			],
		];

		$this->assertEquals( $expected, $job->get_logs() );

		$expected = [
			'course' => [
				[
					'title' => 'First course',
					'msg'   => 'First failed',
					'id'    => '1',
				],
				[
					'title' => 'Third course',
					'msg'   => 'Third failed',
					'id'    => '3',
				],
			],
			'lesson' => [
				[
					'title' => 'First lesson',
					'msg'   => 'First failed',
					'id'    => '1',
				],
			],
		];

		$this->assertEquals( $expected, $job->get_logs( 0, 3 ) );

		$expected = [
			'course' => [
				[
					'title' => 'Third course',
					'msg'   => 'Third failed',
					'id'    => '3',
				],
			],
			'lesson' => [
				[
					'title' => 'First lesson',
					'msg'   => 'First failed',
					'id'    => '1',
				],
			],
		];

		$this->assertEquals( $expected, $job->get_logs( 1, 2 ) );

		$expected = [
			'lesson' => [
				[
					'title' => 'First lesson',
					'msg'   => 'First failed',
					'id'    => '1',
				],
				[
					'title' => 'Second lesson',
					'msg'   => 'Second failed',
					'id'    => '2',
				],
			],
		];

		$this->assertEquals( $expected, $job->get_logs( 2, 10 ) );

		$expected = [
			'lesson' => [
				[
					'title' => 'Second lesson',
					'msg'   => 'Second failed',
					'id'    => '2',
				],
			],
		];

		$this->assertEquals( $expected, $job->get_logs( 3, 10 ) );
	}

	public function testStateIsPersisted() {
		$job = new Sensei_Data_Port_Job_Mock( 'test-job', [ new Sensei_Data_Port_Task_Mock( true, 100, 100 ) ] );

		$this->assertFalse( get_option( $job->get_name() ), 'Option should not be stored if persist is not called.' );

		$job->persist();

		$this->assertNotFalse( get_option( Sensei_Data_Port_Job::OPTION_PREFIX . 'test-job' ), 'Option should be stored if persist is called.' );

		$job->clean_up();
		$job->persist();

		$this->assertFalse( get_option( $job->get_name() ), 'Option should not be stored if persist is not called.' );
	}

	/**
	 * Test saving a valid file to the job.
	 */
	public function testSaveFileValid() {
		if ( ! version_compare( get_bloginfo( 'version' ), '5.0.0', '>=' ) ) {
			$this->markTestSkipped( 'Test fails with 4.9 due to text/csv getting interpretted as text/plain.' );
		}

		$test_file = SENSEI_TEST_FRAMEWORK_DIR . '/data-port/data-files/questions.csv';
		$test_file = $this->get_tmp_file( $test_file );
		$job       = new Sensei_Data_Port_Job_Mock( 'test-job' );

		$result = $job->save_file( 'questions', $test_file, basename( $test_file ) );

		$this->assertTrue( $result, 'Valid file with a valid file key should be stored' );
		$this->assertTrue( isset( $job->get_files()['questions'] ) );

		$attachment = get_post( $job->get_files()['questions'] );
		$this->assertTrue( $attachment instanceof WP_Post );

		$this->assertEquals( basename( $test_file ), $attachment->post_title );
	}

	/**
	 * Test saving a non CSV file to a job.
	 */
	public function testSaveFileBadFile() {
		$test_file = SENSEI_TEST_FRAMEWORK_DIR . '/data-port/data-files/invalid_file_type.tsv';
		$test_file = $this->get_tmp_file( $test_file );
		$job       = new Sensei_Data_Port_Job_Mock( 'test-job' );

		$result = $job->save_file( 'questions', $test_file, basename( $test_file ) );

		$this->assertWPError( $result, 'Invalid file should result in a WP Error' );
		$this->assertEquals( 'sensei_data_port_unexpected_file_type', $result->get_error_code() );
	}

	/**
	 * Test saving a unknown file key to a job.
	 */
	public function testSaveFileBadFileKey() {
		$test_file = SENSEI_TEST_FRAMEWORK_DIR . '/data-port/data-files/questions.csv';
		$test_file = $this->get_tmp_file( $test_file );
		$job       = new Sensei_Data_Port_Job_Mock( 'test-job' );

		$result = $job->save_file( 'dinosaurs', $test_file, basename( $test_file ) );

		$this->assertWPError( $result, 'Invalid file key should result in a WP Error' );
		$this->assertEquals( 'sensei_data_port_unknown_file_key', $result->get_error_code() );
	}

	/**
	 * Test deleting an existing file from a job.
	 */
	public function testDeleteFileExists() {
		if ( ! version_compare( get_bloginfo( 'version' ), '5.0.0', '>=' ) ) {
			$this->markTestSkipped( 'Test fails with 4.9 due to text/csv getting interpretted as text/plain.' );
		}

		$test_file = SENSEI_TEST_FRAMEWORK_DIR . '/data-port/data-files/questions.csv';
		$test_file = $this->get_tmp_file( $test_file );
		$job       = new Sensei_Data_Port_Job_Mock( 'test-job' );

		$job->save_file( 'questions', $test_file, basename( $test_file ) );
		$result = $job->delete_file( 'questions' );

		$this->assertNotFalse( $result, 'File should be deleted' );
		$this->assertFalse( isset( $job->get_files()['questions'] ) );
	}


	/**
	 * Test deleting an non-existing file from a job.
	 */
	public function testDeleteFileNotExists() {
		$job = new Sensei_Data_Port_Job_Mock( 'test-job' );

		$result = $job->delete_file( 'questions' );

		$this->assertFalse( $result, 'Should pass back false as there was no file' );
		$this->assertFalse( isset( $job->get_files()['questions'] ) );
	}

	private function mock_task_method( $is_complete, $completed_cycles, $total_cycles, $method ) {
		return $this->getMockBuilder( Sensei_Data_Port_Task_Mock::class )
			->setConstructorArgs( [ $is_complete, $completed_cycles, $total_cycles ] )
			->setMethods( [ $method ] )
			->getMock();
	}

	/**
	 * Get a temporary file from a source file.
	 *
	 * @param string $file_path File to copy.
	 *
	 * @return string
	 */
	private function get_tmp_file( $file_path ) {
		$tmp = wp_tempnam( basename( $file_path ) ) . '.' . pathinfo( $file_path, PATHINFO_EXTENSION );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_file_put_contents, WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		file_put_contents( $tmp, file_get_contents( $file_path ) );

		return $tmp;
	}
}
