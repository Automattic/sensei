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
	public function testInit_Always_AddsMigrationJobHook() {
		/* Arrange. */
		$action_scheduler = $this->createMock( Action_Scheduler::class );
		$migration_job    = $this->createMock( Migration_Job::class );
		$job_scheduler    = new Migration_Job_Scheduler( $action_scheduler, $migration_job );

		$migration_job->method( 'get_job_name' )
			->willReturn( 'foo' );

		/* Act. */
		$job_scheduler->init();

		/* Assert. */
		$this->assertSame(
			10,
			has_action( 'sensei_lms_jobs_foo', [ $job_scheduler, 'run_job' ] )
		);
	}

	public function testSchedule_Always_SchedulesAction() {
		/* Arrange. */
		$action_scheduler = $this->createMock( Action_Scheduler::class );
		$migration_job    = $this->createMock( Migration_Job::class );
		$job_scheduler    = new Migration_Job_Scheduler( $action_scheduler, $migration_job );

		$migration_job->method( 'get_job_name' )
			->willReturn( 'foo' );

		/* Assert. */
		$action_scheduler
			->expects( $this->once() )
			->method( 'schedule_single_action' )
			->with( 'sensei_lms_jobs_foo', [], false );

		/* Act. */
		$job_scheduler->schedule();
	}

	public function testRunJob_WhenFirstRun_UpdatesStartedOption() {
		/* Arrange. */
		$action_scheduler = $this->createMock( Action_Scheduler::class );
		$migration_job    = $this->createMock( Migration_Job::class );
		$job_scheduler    = new Migration_Job_Scheduler( $action_scheduler, $migration_job );

		/* Act. */
		$job_scheduler->run_job();

		/* Assert. */
		$this->assertIsFloat(
			get_option( Migration_Job_Scheduler::STARTED_OPTION_NAME )
		);
	}

	public function testRunJob_Always_RunsJob() {
		/* Arrange. */
		$action_scheduler = $this->createMock( Action_Scheduler::class );
		$migration_job    = $this->createMock( Migration_Job::class );
		$job_scheduler    = new Migration_Job_Scheduler( $action_scheduler, $migration_job );

		/* Assert. */
		$migration_job
			->expects( $this->once() )
			->method( 'run' );

		/* Act. */
		$job_scheduler->run_job();
	}

	public function testRunJob_WhenJobHasErrors_UpdatesErrorsOption() {
		/* Arrange. */
		$action_scheduler = $this->createMock( Action_Scheduler::class );
		$migration_job    = $this->createMock( Migration_Job::class );
		$job_scheduler    = new Migration_Job_Scheduler( $action_scheduler, $migration_job );

		$migration_job->method( 'get_errors' )
			->willReturn( [ 'error 1', 'error 2' ] );

		/* Act. */
		$job_scheduler->run_job();

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
		$job_scheduler    = new Migration_Job_Scheduler( $action_scheduler, $migration_job );

		$migration_job->method( 'is_complete' )
			->willReturn( true );

		/* Act. */
		$job_scheduler->run_job();

		/* Assert. */
		$this->assertIsFloat(
			get_option( Migration_Job_Scheduler::COMPLETED_OPTION_NAME )
		);
	}

	public function testRunJob_WhenJobIsComplete_DoesntScheduleAction() {
		/* Arrange. */
		$action_scheduler = $this->createMock( Action_Scheduler::class );
		$migration_job    = $this->createMock( Migration_Job::class );
		$job_scheduler    = new Migration_Job_Scheduler( $action_scheduler, $migration_job );

		$migration_job->method( 'is_complete' )
			->willReturn( true );
		$migration_job->method( 'get_job_name' )
			->willReturn( 'foo' );

		/* Assert. */
		$action_scheduler
			->expects( $this->never() )
			->method( 'schedule_single_action' )
			->with( 'sensei_lms_jobs_foo', [], false );

		/* Act. */
		$job_scheduler->run_job();
	}

	public function testRunJob_WhenJobIsNotComplete_SchedulesAction() {
		/* Arrange. */
		$action_scheduler = $this->createMock( Action_Scheduler::class );
		$migration_job    = $this->createMock( Migration_Job::class );
		$job_scheduler    = new Migration_Job_Scheduler( $action_scheduler, $migration_job );

		$migration_job->method( 'get_job_name' )
			->willReturn( 'foo' );

		/* Assert. */
		$action_scheduler
			->expects( $this->once() )
			->method( 'schedule_single_action' )
			->with( 'sensei_lms_jobs_foo', [], false );

		/* Act. */
		$job_scheduler->run_job();
	}
}
