<?php

namespace SenseiTest\Internal\Migration;

use Sensei\Internal\Action_Scheduler\Action_Scheduler;
use Sensei\Internal\Migration\Migration_Job;
use Sensei\Internal\Migration\Migration_Job_Scheduler;

/**
 * Class Migration_Job_Scheduler_Test
 *
 * @covers \Sensei\Internal\Migration\Migration_Job_Scheduler
 */
class Migration_Job_Scheduler_Test extends \WP_UnitTestCase {
	public function testRegisterJob_Always_AddsMigrationJobHook() {
		/* Arrange. */
		$action_scheduler = $this->createMock( Action_Scheduler::class );
		$migration_job    = $this->createMock( Migration_Job::class );
		$job_scheduler    = new Migration_Job_Scheduler( $action_scheduler );

		$migration_job->method( 'get_name' )
			->willReturn( 'foo' );

		/* Act. */
		$job_scheduler->register_job( $migration_job );

		/* Assert. */
		$this->assertSame(
			10,
			has_action( 'sensei_lms_migration_job_foo', [ $job_scheduler, 'run_job' ] )
		);
	}

	public function testSchedule_WhenMultipleJobs_SchedulesFirstJob() {
		/* Arrange. */
		$action_scheduler = $this->createMock( Action_Scheduler::class );
		$migration_job_1  = $this->createMock( Migration_Job::class );
		$migration_job_2  = $this->createMock( Migration_Job::class );
		$job_scheduler    = new Migration_Job_Scheduler( $action_scheduler );

		$migration_job_1->method( 'get_name' )
			->willReturn( 'foo' );
		$migration_job_2->method( 'get_name' )
			->willReturn( 'bar' );

		$job_scheduler->register_job( $migration_job_1 );
		$job_scheduler->register_job( $migration_job_2 );

		/* Assert. */
		$action_scheduler
			->expects( $this->once() )
			->method( 'schedule_single_action' )
			->with( 'sensei_lms_migration_job_foo', [ 'job_name' => 'foo' ], false );

		/* Act. */
		$job_scheduler->schedule();
	}

	public function testSchedule_WhenNoJobs_ThrowsException() {
		/* Arrange. */
		$action_scheduler = $this->createMock( Action_Scheduler::class );
		$job_scheduler    = new Migration_Job_Scheduler( $action_scheduler );

		/* Assert. */
		$this->expectException( \RuntimeException::class );
		$this->expectExceptionMessage( 'No jobs to schedule.' );

		/* Act. */
		$job_scheduler->schedule();
	}

	public function testRunJob_WhenFirstRun_UpdatesStartedOption() {
		/* Arrange. */
		$action_scheduler = $this->createMock( Action_Scheduler::class );
		$migration_job    = $this->createMock( Migration_Job::class );
		$job_scheduler    = new Migration_Job_Scheduler( $action_scheduler );

		$job_scheduler->register_job( $migration_job );

		/* Act. */
		$job_scheduler->run_job( $migration_job->get_name() );

		/* Assert. */
		$this->assertIsFloat(
			get_option( Migration_Job_Scheduler::STARTED_OPTION_NAME )
		);
	}

	public function testRunJob_Always_RunsJob() {
		/* Arrange. */
		$action_scheduler = $this->createMock( Action_Scheduler::class );
		$migration_job    = $this->createMock( Migration_Job::class );
		$job_scheduler    = new Migration_Job_Scheduler( $action_scheduler );

		$job_scheduler->register_job( $migration_job );

		/* Assert. */
		$migration_job
			->expects( $this->once() )
			->method( 'run' );

		/* Act. */
		$job_scheduler->run_job( $migration_job->get_name() );
	}

	public function testRunJob_WhenJobHasErrors_UpdatesErrorsOption() {
		/* Arrange. */
		$action_scheduler = $this->createMock( Action_Scheduler::class );
		$migration_job    = $this->createMock( Migration_Job::class );
		$job_scheduler    = new Migration_Job_Scheduler( $action_scheduler );

		$migration_job->method( 'get_errors' )
			->willReturn( [ 'error 1', 'error 2' ] );

		$job_scheduler->register_job( $migration_job );

		/* Act. */
		$job_scheduler->run_job( $migration_job->get_name() );

		/* Assert. */
		$this->assertSame(
			[ 'error 1', 'error 2' ],
			get_option( Migration_Job_Scheduler::ERRORS_OPTION_NAME )
		);
	}

	public function testRunJob_WhenJobIsComplete_UpdatesCompletedOption() {
		/* Arrange. */
		$action_scheduler = $this->createMock( Action_Scheduler::class );
		$migration_job    = $this->createMock( Migration_Job::class );
		$job_scheduler    = new Migration_Job_Scheduler( $action_scheduler );

		$migration_job->method( 'is_complete' )
			->willReturn( true );

		$job_scheduler->register_job( $migration_job );

		/* Act. */
		$job_scheduler->run_job( $migration_job->get_name() );

		/* Assert. */
		$this->assertIsFloat(
			get_option( Migration_Job_Scheduler::COMPLETED_OPTION_NAME )
		);
	}

	public function testRunJob_WhenJobIsComplete_DoesntScheduleAction() {
		/* Arrange. */
		$action_scheduler = $this->createMock( Action_Scheduler::class );
		$migration_job    = $this->createMock( Migration_Job::class );
		$job_scheduler    = new Migration_Job_Scheduler( $action_scheduler );

		$migration_job->method( 'is_complete' )
			->willReturn( true );
		$migration_job->method( 'get_name' )
			->willReturn( 'foo' );

		$job_scheduler->register_job( $migration_job );

		/* Assert. */
		$action_scheduler
			->expects( $this->never() )
			->method( 'schedule_single_action' )
			->with( 'sensei_lms_migration_job_foo', [], false );

		/* Act. */
		$job_scheduler->run_job( $migration_job->get_name() );
	}

	public function testRunJob_WhenJobIsNotComplete_SchedulesAction() {
		/* Arrange. */
		$action_scheduler = $this->createMock( Action_Scheduler::class );
		$migration_job    = $this->createMock( Migration_Job::class );
		$job_scheduler    = new Migration_Job_Scheduler( $action_scheduler );

		$migration_job->method( 'get_name' )
			->willReturn( 'foo' );

		$job_scheduler->register_job( $migration_job );

		/* Assert. */
		$action_scheduler
			->expects( $this->once() )
			->method( 'schedule_single_action' )
			->with( 'sensei_lms_migration_job_foo', [ 'job_name' => 'foo' ], false );

		/* Act. */
		$job_scheduler->run_job( $migration_job->get_name() );
	}

	public function testRunJob_WhenMultipleJobs_SchedulesNextJob() {
		/* Arrange. */
		$action_scheduler = $this->createMock( Action_Scheduler::class );
		$migration_job_1  = $this->createMock( Migration_Job::class );
		$migration_job_2  = $this->createMock( Migration_Job::class );
		$job_scheduler    = new Migration_Job_Scheduler( $action_scheduler );

		$migration_job_1->method( 'get_name' )
			->willReturn( 'foo' );
		$migration_job_1->method( 'is_complete' )
			->willReturn( true );
		$migration_job_2->method( 'get_name' )
			->willReturn( 'bar' );

		$job_scheduler->register_job( $migration_job_1 );
		$job_scheduler->register_job( $migration_job_2 );

		/* Assert. */
		$action_scheduler
			->expects( $this->once() )
			->method( 'schedule_single_action' )
			->with( 'sensei_lms_migration_job_bar', [ 'job_name' => 'bar' ], false );

		/* Act. */
		$job_scheduler->run_job( $migration_job_1->get_name() );
	}

	public function testIsInProgress_WasInProgress_ReturnsTrue(): void {
		/* Arrange. */
		update_option( Migration_Job_Scheduler::STARTED_OPTION_NAME, 1 );
		delete_option( Migration_Job_Scheduler::COMPLETED_OPTION_NAME );

		$action_scheduler = $this->createMock( Action_Scheduler::class );
		$job_scheduler    = new Migration_Job_Scheduler( $action_scheduler );

		/* Act. */
		$actual = $job_scheduler->is_in_progress();

		/* Assert. */
		$this->assertTrue( $actual );
	}

	public function testIsInProgress_WasNotStarted_ReturnsFalse(): void {
		/* Arrange. */
		delete_option( Migration_Job_Scheduler::STARTED_OPTION_NAME );
		delete_option( Migration_Job_Scheduler::COMPLETED_OPTION_NAME );

		$action_scheduler = $this->createMock( Action_Scheduler::class );
		$job_scheduler    = new Migration_Job_Scheduler( $action_scheduler );

		/* Act. */
		$actual = $job_scheduler->is_in_progress();

		/* Assert. */
		$this->assertFalse( $actual );
	}

	public function testIsInProgress_HasFinished_ReturnsFalse(): void {
		/* Arrange. */
		update_option( Migration_Job_Scheduler::STARTED_OPTION_NAME, 1 );
		update_option( Migration_Job_Scheduler::COMPLETED_OPTION_NAME, 2 );

		$action_scheduler = $this->createMock( Action_Scheduler::class );
		$job_scheduler    = new Migration_Job_Scheduler( $action_scheduler );

		/* Act. */
		$actual = $job_scheduler->is_in_progress();

		/* Assert. */
		$this->assertFalse( $actual );
	}

	public function testIsComplete_WasComplete_ReturnsTrue(): void {
		/* Arrange. */
		update_option( Migration_Job_Scheduler::COMPLETED_OPTION_NAME, 1 );

		$action_scheduler = $this->createMock( Action_Scheduler::class );
		$job_scheduler    = new Migration_Job_Scheduler( $action_scheduler );

		/* Act. */
		$actual = $job_scheduler->is_complete();

		/* Assert. */
		$this->assertTrue( $actual );
	}

	public function testIsComplete_WasNotComplete_ReturnsFalse(): void {
		/* Arrange. */
		delete_option( Migration_Job_Scheduler::COMPLETED_OPTION_NAME );

		$action_scheduler = $this->createMock( Action_Scheduler::class );
		$job_scheduler    = new Migration_Job_Scheduler( $action_scheduler );

		/* Act. */
		$actual = $job_scheduler->is_complete();

		/* Assert. */
		$this->assertFalse( $actual );
	}

	public function testClearState_DataExists_DeletesData(): void {
		/* Arrange. */
		update_option( Migration_Job_Scheduler::STARTED_OPTION_NAME, 1 );

		$action_scheduler = $this->createMock( Action_Scheduler::class );
		$job_scheduler    = new Migration_Job_Scheduler( $action_scheduler );

		/* Act. */
		$job_scheduler->clear_state();

		/* Assert. */
		$actual = get_option( Migration_Job_Scheduler::STARTED_OPTION_NAME );
		$this->assertFalse( $actual );
	}

	public function testGetErrors_WhenNoErrors_ReturnsEmptyArray(): void {
		/* Arrange. */
		delete_option( Migration_Job_Scheduler::ERRORS_OPTION_NAME );

		$action_scheduler = $this->createMock( Action_Scheduler::class );
		$job_scheduler    = new Migration_Job_Scheduler( $action_scheduler );
		$expected         = [];

		/* Act. */
		$actual = $job_scheduler->get_errors();

		/* Assert. */
		$this->assertSame( $expected, $actual );
	}

	public function testGetErrors_WhenErrorsExist_ReturnsErrors(): void {
		/* Arrange. */
		update_option( Migration_Job_Scheduler::ERRORS_OPTION_NAME, [ 'error 1', 'error 2' ] );

		$action_scheduler = $this->createMock( Action_Scheduler::class );
		$job_scheduler    = new Migration_Job_Scheduler( $action_scheduler );
		$expected         = [ 'error 1', 'error 2' ];

		/* Act. */
		$actual = $job_scheduler->get_errors();

		/* Assert. */
		$this->assertSame( $expected, $actual );
	}

	public function testInit_Always_AddsUnexpectedShutdownHook(): void {
		/* Arrange. */
		$action_scheduler = $this->createMock( Action_Scheduler::class );
		$job_scheduler    = new Migration_Job_Scheduler( $action_scheduler );

		/* Act. */
		$job_scheduler->init();

		/* Assert. */
		$actual   = has_action( 'action_scheduler_unexpected_shutdown', [ $job_scheduler, 'collect_failed_job_errors' ] );
		$expected = 10;
		$this->assertSame( $expected, $actual );
	}

	public function testCollectFailedJobErrors_Always_UpdatesErrorsOption(): void {
		/* Arrange. */
		update_option( Migration_Job_Scheduler::ERRORS_OPTION_NAME, array( 'a' ) );

		$action_scheduler = $this->createMock( Action_Scheduler::class );
		$job_scheduler    = new Migration_Job_Scheduler( $action_scheduler );

		/* Act. */
		$job_scheduler->collect_failed_job_errors( 'b', array( 'message' => 'c' ) );

		/* Assert. */
		$actual   = get_option( Migration_Job_Scheduler::ERRORS_OPTION_NAME );
		$expected = array( 'a', 'c' );
		$this->assertSame( $expected, $actual );
	}

	public function testIntegration_CollectFailedJobErrors_Always_UpdatesErrorsOptionWithEmptyArray(): void {
		/* Arrange. */
		delete_option( Migration_Job_Scheduler::ERRORS_OPTION_NAME );

		$action_scheduler = $this->createMock( Action_Scheduler::class );
		$job_scheduler    = new Migration_Job_Scheduler( $action_scheduler );

		/* Act. */
		$job_scheduler->collect_failed_job_errors( 'b', array( 'message' => 'c' ) );
		$actual = get_option( Migration_Job_Scheduler::ERRORS_OPTION_NAME );

		/* Assert. */
		$expected = array( 'c' );
		$this->assertSame( $expected, $actual );
	}
}
