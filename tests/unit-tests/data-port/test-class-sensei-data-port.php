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

	public function testJobWithCompletedTasksIsCompleted() {
		$first_completed  = $this->mock_task_method( true, 100, 100, 'run' );
		$second_completed = $this->mock_task_method( true, 100, 100, 'run' );

		$job = new Sensei_Data_Port_Job_Mock( 'test-job', [ $first_completed, $second_completed ] );

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

		$this->assertNotFalse( get_option( $job->get_name() ), 'Option should be stored if persist is called.' );

		$job->clean_up();
		$job->persist();

		$this->assertFalse( get_option( $job->get_name() ), 'Option should not be stored if persist is not called.' );
	}

	private function mock_task_method( $is_complete, $completed_cycles, $total_cycles, $method ) {
		return $this->getMockBuilder( Sensei_Data_Port_Task_Mock::class )
			->setConstructorArgs( [ $is_complete, $completed_cycles, $total_cycles ] )
			->setMethods( [ $method ] )
			->getMock();
	}
}
