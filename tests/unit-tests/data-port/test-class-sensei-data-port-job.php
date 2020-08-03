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

	/**
	 * Tear down after tests.
	 */
	public function tearDown() {
		parent::tearDown();

		delete_site_option( 'upload_filetypes' );
	}

	public function testJobWithCompletedTasksIsCompleted() {
		$first_completed  = $this->mock_task_method( true, 100, 100, 'run' );
		$second_completed = $this->mock_task_method( true, 100, 100, 'run' );

		$job = Sensei_Data_Port_Job_Mock::create_with_tasks( 'test-job', [ $first_completed, $second_completed ] );
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

		$job = Sensei_Data_Port_Job_Mock::create_with_tasks( 'test-job', [ $completed, $pending ] );
		$job->start();

		$completed->expects( $this->never() )->method( 'run' );
		$pending->expects( $this->once() )->method( 'run' );

		$this->assertFalse( $job->is_complete(), 'Job should not be completed until all tasks are completed.' );
		$job->run();
		$this->assertFalse( $job->is_complete(), 'Job should not be completed until all tasks are completed.' );

		$this->assertEquals(
			[
				'status'     => 'pending',
				'percentage' => 56.25,
			],
			$job->get_status()
		);
	}

	public function testOnlyOnePendingTaskIsExecuted() {
		$first_pending  = $this->mock_task_method( false, 50, 100, 'run' );
		$second_pending = $this->mock_task_method( false, 0, 100, 'run' );

		$job = Sensei_Data_Port_Job_Mock::create_with_tasks( 'test-job', [ $first_pending, $second_pending ] );
		$job->start();

		$first_pending->expects( $this->once() )->method( 'run' );
		$second_pending->expects( $this->never() )->method( 'run' );

		$this->assertFalse( $job->is_complete(), 'Job should not be completed until all tasks are completed.' );
		$job->run();
		$this->assertFalse( $job->is_complete(), 'Job should not be completed until all tasks are completed.' );

		$this->assertEquals(
			[
				'status'     => 'pending',
				'percentage' => 22.5,
			],
			$job->get_status()
		);
	}


	public function testCleanupCallsAllTaskCleanup() {
		$first_completed  = $this->mock_task_method( true, 100, 100, 'clean_up' );
		$second_completed = $this->mock_task_method( true, 100, 100, 'clean_up' );

		$job = Sensei_Data_Port_Job_Mock::create_with_tasks( 'test-job', [ $first_completed, $second_completed ] );

		$first_completed->expects( $this->once() )->method( 'clean_up' );
		$second_completed->expects( $this->once() )->method( 'clean_up' );

		$job->clean_up();
	}

	public function testGetLogs() {
		$job = Sensei_Data_Port_Job_Mock::create_with_tasks( 'test-job', [ new Sensei_Data_Port_Task_Mock( true, 100, 100 ) ] );
		$job->add_log_entry( 'First log entry', Sensei_Data_Port_Job::LOG_LEVEL_NOTICE, [ 'line' => 1 ] );
		$job->add_log_entry( 'Second log entry', Sensei_Data_Port_Job::LOG_LEVEL_ERROR, [ 'line' => 3 ] );
		$job->add_log_entry(
			'Third log entry',
			Sensei_Data_Port_Job::LOG_LEVEL_INFO,
			[
				'line' => 2,
				'test' => true,
			]
		);

		$expected = [
			[
				'message' => 'First log entry',
				'level'   => Sensei_Data_Port_Job::LOG_LEVEL_NOTICE,
				'data'    => [
					'line' => 1,
				],
			],
			[
				'message' => 'Third log entry',
				'level'   => Sensei_Data_Port_Job::LOG_LEVEL_INFO,
				'data'    => [
					'test' => true,
					'line' => 2,
				],
			],
			[
				'message' => 'Second log entry',
				'level'   => Sensei_Data_Port_Job::LOG_LEVEL_ERROR,
				'data'    => [
					'line' => 3,
				],
			],
		];

		$this->assertEquals( $expected, $job->get_logs() );
	}

	public function testLogOrder() {
		$job = Sensei_Data_Port_Job_Mock::create_with_tasks( 'test-job', [ new Sensei_Data_Port_Task_Mock( true, 100, 100 ) ] );

		$job->add_log_entry(
			'Third Question log entry',
			Sensei_Data_Port_Job::LOG_LEVEL_INFO,
			[
				'type' => Sensei_Import_Question_Model::MODEL_KEY,
				'line' => 2,
			]
		);
		$job->add_log_entry(
			'Second Question log entry',
			Sensei_Data_Port_Job::LOG_LEVEL_INFO,
			[
				'type' => Sensei_Import_Question_Model::MODEL_KEY,
				'line' => 1,
			]
		);
		$job->add_log_entry(
			'Fourth Question log entry',
			Sensei_Data_Port_Job::LOG_LEVEL_INFO,
			[
				'type' => Sensei_Import_Question_Model::MODEL_KEY,
				'line' => 3,
			]
		);

		$job->add_log_entry( 'Lesson log entry', Sensei_Data_Port_Job::LOG_LEVEL_ERROR, [ 'type' => Sensei_Import_Lesson_Model::MODEL_KEY ] );
		$job->add_log_entry( 'Course log entry', Sensei_Data_Port_Job::LOG_LEVEL_NOTICE, [ 'type' => Sensei_Import_Course_Model::MODEL_KEY ] );
		$job->add_log_entry( 'Generic log entry', Sensei_Data_Port_Job::LOG_LEVEL_INFO );
		$job->add_log_entry( 'First Question log entry', Sensei_Data_Port_Job::LOG_LEVEL_INFO, [ 'type' => Sensei_Import_Question_Model::MODEL_KEY ] );

		$expected = [
			[
				'message' => 'Generic log entry',
				'level'   => Sensei_Data_Port_Job::LOG_LEVEL_INFO,
				'data'    => [],
			],
			[
				'message' => 'Course log entry',
				'level'   => Sensei_Data_Port_Job::LOG_LEVEL_NOTICE,
				'data'    => [
					'type' => Sensei_Import_Course_Model::MODEL_KEY,
				],
			],
			[
				'message' => 'Lesson log entry',
				'level'   => Sensei_Data_Port_Job::LOG_LEVEL_ERROR,
				'data'    => [
					'type' => Sensei_Import_Lesson_Model::MODEL_KEY,
				],
			],
			[
				'message' => 'First Question log entry',
				'level'   => Sensei_Data_Port_Job::LOG_LEVEL_INFO,
				'data'    => [
					'type' => Sensei_Import_Question_Model::MODEL_KEY,
				],
			],
			[
				'message' => 'Second Question log entry',
				'level'   => Sensei_Data_Port_Job::LOG_LEVEL_INFO,
				'data'    => [
					'type' => Sensei_Import_Question_Model::MODEL_KEY,
					'line' => 1,
				],
			],
			[
				'message' => 'Third Question log entry',
				'level'   => Sensei_Data_Port_Job::LOG_LEVEL_INFO,
				'data'    => [
					'type' => Sensei_Import_Question_Model::MODEL_KEY,
					'line' => 2,
				],
			],
			[
				'message' => 'Fourth Question log entry',
				'level'   => Sensei_Data_Port_Job::LOG_LEVEL_INFO,
				'data'    => [
					'type' => Sensei_Import_Question_Model::MODEL_KEY,
					'line' => 3,
				],
			],
		];

		$this->assertEquals( $expected, $job->get_logs() );
	}

	public function testStateIsPersisted() {
		$job = Sensei_Data_Port_Job_Mock::create_with_tasks( 'test-job', [ new Sensei_Data_Port_Task_Mock( true, 100, 100 ) ] );

		$this->assertFalse( get_option( $job->get_name() ), 'Option should not be stored if persist is not called.' );

		$job->persist();

		$this->assertNotFalse( get_option( Sensei_Data_Port_Job::OPTION_PREFIX . 'test-job' ), 'Option should be stored if persist is called.' );

		$job->clean_up();
		$job->persist();

		$this->assertFalse( get_option( $job->get_name() ), 'Option should not be stored if persist is not called.' );
	}

	/**
	 * Test saving a unknown file key to a job.
	 */
	public function testSaveFileBadFileKey() {
		$test_file = SENSEI_TEST_FRAMEWORK_DIR . '/data-port/data-files/questions.csv';
		$test_file = $this->get_tmp_file( $test_file );
		$job       = Sensei_Data_Port_Job_Mock::create( 'test-job', 0 );

		$result = $job->save_file( 'dinosaurs', $test_file, basename( $test_file ) );

		$this->assertWPError( $result, 'Invalid file key should result in a WP Error' );
		$this->assertEquals( 'sensei_data_port_unknown_file_key', $result->get_error_code() );
	}

	/**
	 * Test deleting an existing file from a job.
	 */
	public function testDeleteFileExists() {
		if ( ! version_compare( get_bloginfo( 'version' ), '5.0.0', '>=' ) ) {
			$this->markTestSkipped( 'Test fails with 4.9 due to text/csv getting interpreted as text/plain.' );
		}

		$test_file = SENSEI_TEST_FRAMEWORK_DIR . '/data-port/data-files/questions.csv';
		$test_file = $this->get_tmp_file( $test_file );
		$job       = Sensei_Data_Port_Job_Mock::create( 'test-job', 0 );

		$job->save_file( 'questions', $test_file, basename( $test_file ) );
		$result = $job->delete_file( 'questions' );

		$this->assertNotFalse( $result, 'File should be deleted' );
		$this->assertFalse( isset( $job->get_files()['questions'] ) );
	}


	/**
	 * Test deleting an non-existing file from a job.
	 */
	public function testDeleteFileNotExists() {
		$job = Sensei_Data_Port_Job_Mock::create( 'test-job', 0 );

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
