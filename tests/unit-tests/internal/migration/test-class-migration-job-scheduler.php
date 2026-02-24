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

	public function testRunJob_WhenJobIsComplete_LogsEvent() {
		/* Arrange. */
		$action_scheduler = $this->createMock( Action_Scheduler::class );
		$migration_job    = $this->createMock( Migration_Job::class );
		$job_scheduler    = new Migration_Job_Scheduler( $action_scheduler );

		$migration_job->method( 'is_complete' )
			->willReturn( true );
		$migration_job->method( 'get_name' )
			->willReturn( 'foo' );

		$job_scheduler->register_job( $migration_job );

		$has_logged_event = false;
		$sensei_log_event = function( $log_event, $event_name, $event_properties ) use ( &$has_logged_event ) {
			if ( 'hpps_migration_complete' === $event_name ) {
				$has_logged_event = true;
			}
		};
		add_action( 'sensei_log_event', $sensei_log_event, 10, 3 );

		/* Act. */
		$job_scheduler->run_job( $migration_job->get_name() );

		/* Assert. */
		$this->assertTrue( $has_logged_event );
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
		Sensei()->settings->set( 'experimental_progress_storage_synchronization', true );
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
		update_option( Migration_Job_Scheduler::STATUS_OPTION_NAME, Migration_Job_Scheduler::STATUS_IN_PROGRESS );

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

		$action_scheduler = $this->createMock( Action_Scheduler::class );
		$job_scheduler    = new Migration_Job_Scheduler( $action_scheduler );

		/* Act. */
		$actual = $job_scheduler->is_in_progress();

		/* Assert. */
		$this->assertFalse( $actual );
	}

	public function testIsInProgress_HasFinished_ReturnsFalse(): void {
		/* Arrange. */
		update_option( Migration_Job_Scheduler::STATUS_OPTION_NAME, Migration_Job_Scheduler::STATUS_COMPLETE );

		$action_scheduler = $this->createMock( Action_Scheduler::class );
		$job_scheduler    = new Migration_Job_Scheduler( $action_scheduler );

		/* Act. */
		$actual = $job_scheduler->is_in_progress();

		/* Assert. */
		$this->assertFalse( $actual );
	}

	public function testIsInProgress_HasFailed_ReturnsFalse(): void {
		/* Arrange. */
		update_option( Migration_Job_Scheduler::STATUS_OPTION_NAME, Migration_Job_Scheduler::STATUS_FAILED );

		$action_scheduler = $this->createMock( Action_Scheduler::class );
		$job_scheduler    = new Migration_Job_Scheduler( $action_scheduler );

		/* Act. */
		$actual = $job_scheduler->is_in_progress();

		/* Assert. */
		$this->assertFalse( $actual );
	}

	public function testIsComplete_WasComplete_ReturnsTrue(): void {
		/* Arrange. */
		update_option( Migration_Job_Scheduler::STATUS_OPTION_NAME, Migration_Job_Scheduler::STATUS_COMPLETE );

		$action_scheduler = $this->createMock( Action_Scheduler::class );
		$job_scheduler    = new Migration_Job_Scheduler( $action_scheduler );

		/* Act. */
		$actual = $job_scheduler->is_complete();

		/* Assert. */
		$this->assertTrue( $actual );
	}

	/**
	 * Check if the migration job is not complete.
	 *
	 * @dataProvider providerIsComplete_WasNotComplete_ReturnsFalse
	 */
	public function testIsComplete_WasNotComplete_ReturnsFalse( $status ): void {
		/* Arrange. */
		update_option( Migration_Job_Scheduler::STATUS_OPTION_NAME, $status );

		$action_scheduler = $this->createMock( Action_Scheduler::class );
		$job_scheduler    = new Migration_Job_Scheduler( $action_scheduler );

		/* Act. */
		$actual = $job_scheduler->is_complete();

		/* Assert. */
		$this->assertFalse( $actual );
	}

	public function providerIsComplete_WasNotComplete_ReturnsFalse(): array {
		return array(
			'not started' => array( Migration_Job_Scheduler::STATUS_NOT_STARTED ),
			'in progress' => array( Migration_Job_Scheduler::STATUS_IN_PROGRESS ),
			'failed'      => array( Migration_Job_Scheduler::STATUS_FAILED ),
		);
	}

	public function testIsFailed_WasFailed_ReturnsTrue(): void {
		/* Arrange. */
		update_option( Migration_Job_Scheduler::STATUS_OPTION_NAME, Migration_Job_Scheduler::STATUS_FAILED );

		$action_scheduler = $this->createMock( Action_Scheduler::class );
		$job_scheduler    = new Migration_Job_Scheduler( $action_scheduler );

		/* Act. */
		$actual = $job_scheduler->is_failed();

		/* Assert. */
		$this->assertTrue( $actual );
	}

	/**
	 * Check if the migration job is not failed.
	 *
	 * @dataProvider providerIsFailed_WasNotFailed_ReturnsFalse
	 */
	public function testIsFailed_WasNotFailed_ReturnsFalse( $status ): void {
		/* Arrange. */
		update_option( Migration_Job_Scheduler::STATUS_OPTION_NAME, $status );

		$action_scheduler = $this->createMock( Action_Scheduler::class );
		$job_scheduler    = new Migration_Job_Scheduler( $action_scheduler );

		/* Act. */
		$actual = $job_scheduler->is_failed();

		/* Assert. */
		$this->assertFalse( $actual );
	}

	public function providerIsFailed_WasNotFailed_ReturnsFalse(): array {
		return array(
			'not started' => array( Migration_Job_Scheduler::STATUS_NOT_STARTED ),
			'in progress' => array( Migration_Job_Scheduler::STATUS_IN_PROGRESS ),
			'complete'    => array( Migration_Job_Scheduler::STATUS_COMPLETE ),
		);
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
		$action_scheduler->method( 'get_scheduled_actions' )->willReturn( array( 'b' ) );

		$job = $this->createMock( Migration_Job::class );
		$job->method( 'get_name' )->willReturn( 'x' );

		$job_scheduler = new Migration_Job_Scheduler( $action_scheduler );
		$job_scheduler->register_job( $job );

		/* Act. */
		$job_scheduler->collect_failed_job_errors( 'b', array( 'message' => 'c' ) );

		/* Assert. */
		$actual   = get_option( Migration_Job_Scheduler::ERRORS_OPTION_NAME );
		$expected = array( 'a', 'c' );
		$this->assertSame( $expected, $actual );
	}

	public function testCollectFailedJobErrors_Always_UpdatesErrorsOptionWithEmptyArray(): void {
		/* Arrange. */
		delete_option( Migration_Job_Scheduler::ERRORS_OPTION_NAME );

		$action_scheduler = $this->createMock( Action_Scheduler::class );
		$action_scheduler->method( 'get_scheduled_actions' )->willReturn( array( 'b' ) );

		$job = $this->createMock( Migration_Job::class );
		$job->method( 'get_name' )->willReturn( 'x' );

		$job_scheduler = new Migration_Job_Scheduler( $action_scheduler );
		$job_scheduler->register_job( $job );

		/* Act. */
		$job_scheduler->collect_failed_job_errors( 'b', array( 'message' => 'c' ) );
		$actual = get_option( Migration_Job_Scheduler::ERRORS_OPTION_NAME );

		/* Assert. */
		$expected = array( 'c' );
		$this->assertSame( $expected, $actual );
	}

	public function testRunJob_Always_SetsTimeBudgetOnJob(): void {
		/* Arrange. */
		$action_scheduler = $this->createMock( Action_Scheduler::class );
		$migration_job    = $this->createMock( Migration_Job::class );
		$job_scheduler    = new Migration_Job_Scheduler( $action_scheduler );

		$job_scheduler->register_job( $migration_job );

		/* Assert. */
		$migration_job
			->expects( $this->once() )
			->method( 'set_time_budget' )
			->with( 20.0 );

		/* Act. */
		$job_scheduler->run_job( $migration_job->get_name() );
	}

	public function testRunJob_WithFilteredBudget_UsesFilteredValue(): void {
		/* Arrange. */
		$action_scheduler = $this->createMock( Action_Scheduler::class );
		$migration_job    = $this->createMock( Migration_Job::class );
		$job_scheduler    = new Migration_Job_Scheduler( $action_scheduler );

		$job_scheduler->register_job( $migration_job );

		$filter = function () {
			return 45.0;
		};
		add_filter( 'sensei_hpps_migration_time_budget', $filter );

		/* Assert. */
		$migration_job
			->expects( $this->once() )
			->method( 'set_time_budget' )
			->with( 45.0 );

		/* Act. */
		$job_scheduler->run_job( $migration_job->get_name() );

		/* Cleanup. */
		remove_filter( 'sensei_hpps_migration_time_budget', $filter );
	}

	public function testRun_DefaultBatchSize_UsesReducedDefaults(): void {
		/* Arrange. */
		$migration = new Student_Progress_Migration();
		$reflection = new \ReflectionClass( $migration );

		$batch_size_prop = $reflection->getProperty( 'batch_size' );
		$batch_size_prop->setAccessible( true );

		$batch_count_prop = $reflection->getProperty( 'batch_count' );
		$batch_count_prop->setAccessible( true );

		/* Assert. */
		$this->assertSame( 50, $batch_size_prop->getValue( $migration ) );
		$this->assertSame( 5, $batch_count_prop->getValue( $migration ) );
	}

	public function testRun_BatchSizeFilter_UsesFilteredValues(): void {
		/* Arrange. */
		$size_filter = function () {
			return 10;
		};
		$count_filter = function () {
			return 2;
		};
		add_filter( 'sensei_hpps_student_progress_batch_size', $size_filter );
		add_filter( 'sensei_hpps_student_progress_batch_count', $count_filter );

		$migration  = new Student_Progress_Migration();
		$reflection = new \ReflectionClass( $migration );

		$batch_size_prop = $reflection->getProperty( 'batch_size' );
		$batch_size_prop->setAccessible( true );

		$batch_count_prop = $reflection->getProperty( 'batch_count' );
		$batch_count_prop->setAccessible( true );

		/* Assert. */
		$this->assertSame( 10, $batch_size_prop->getValue( $migration ) );
		$this->assertSame( 2, $batch_count_prop->getValue( $migration ) );

		/* Cleanup. */
		remove_filter( 'sensei_hpps_student_progress_batch_size', $size_filter );
		remove_filter( 'sensei_hpps_student_progress_batch_count', $count_filter );
	}

	public function testRun_TimeExceeded_StopsEarlyAndReturnsPartialCount(): void {
		/* Arrange. */
		$course_id = $this->factory->course->create();
		$lesson_id = $this->factory->lesson->create(
			array(
				'post_parent' => $course_id,
			)
		);

		// Create progress for multiple users to ensure multiple batches.
		for ( $i = 0; $i < 5; $i++ ) {
			$user_id = $this->factory->user->create();
			Sensei_Utils::start_user_on_course( $user_id, $course_id );
		}

		update_option( 'sensei_migrated_progress_last_comment_id', 0 );

		// Use batch_size=1, batch_count=10 so each row is a batch.
		$migration = new Student_Progress_Migration( 1, 10 );
		// Set a zero time budget so it stops after the first batch.
		$migration->set_time_budget( 0.0 );

		/* Act. */
		$result = $migration->run( false );

		/* Assert. */
		// Should have inserted some rows but not all 5.
		$this->assertGreaterThan( 0, $result );
		$this->assertLessThan( 5, $result );
	}

	public function testLastCommentIdConstant_BackwardCompat_OldConstantMatchesNew(): void {
		/* Assert. */
		$this->assertSame(
			Student_Progress_Migration::LAST_COMMENT_ID_OPTION_NAME,
			Student_Progress_Migration::LARST_COMMENT_ID_OPTION_NAME
		);
	}


	public function testCollectFailedJobErrors_FirstFailure_ReschedulesInsteadOfFailing(): void {
		/* Arrange. */
		update_option( Migration_Job_Scheduler::STATUS_OPTION_NAME, Migration_Job_Scheduler::STATUS_IN_PROGRESS );

		$action_scheduler = $this->createMock( Action_Scheduler::class );
		$action_scheduler->method( 'get_scheduled_actions' )->willReturn( array( 'action_1' ) );

		$job = $this->createMock( Migration_Job::class );
		$job->method( 'get_name' )->willReturn( 'test_job' );

		$job_scheduler = new Migration_Job_Scheduler( $action_scheduler );
		$job_scheduler->register_job( $job );

		/* Assert. */
		$action_scheduler
			->expects( $this->once() )
			->method( 'schedule_single_action' )
			->with( 'sensei_lms_migration_job_test_job', [ 'job_name' => 'test_job' ], false );

		/* Act. */
		$job_scheduler->collect_failed_job_errors( 'action_1', array( 'message' => 'Timeout' ) );

		/* Assert - status should still be in_progress, not failed. */
		$this->assertSame(
			Migration_Job_Scheduler::STATUS_IN_PROGRESS,
			get_option( Migration_Job_Scheduler::STATUS_OPTION_NAME )
		);
	}

	public function testCollectFailedJobErrors_AfterMaxRetries_MarksAsFailed(): void {
		/* Arrange. */
		update_option( Migration_Job_Scheduler::STATUS_OPTION_NAME, Migration_Job_Scheduler::STATUS_IN_PROGRESS );
		update_option( Migration_Job_Scheduler::RETRY_COUNT_OPTION_NAME, 3 );

		$action_scheduler = $this->createMock( Action_Scheduler::class );
		$action_scheduler->method( 'get_scheduled_actions' )->willReturn( array( 'action_1' ) );

		$job = $this->createMock( Migration_Job::class );
		$job->method( 'get_name' )->willReturn( 'test_job' );

		$job_scheduler = new Migration_Job_Scheduler( $action_scheduler );
		$job_scheduler->register_job( $job );

		/* Act. */
		$job_scheduler->collect_failed_job_errors( 'action_1', array( 'message' => 'Timeout' ) );

		/* Assert. */
		$this->assertSame(
			Migration_Job_Scheduler::STATUS_FAILED,
			get_option( Migration_Job_Scheduler::STATUS_OPTION_NAME )
		);
	}

	public function testCollectFailedJobErrors_MaxRetriesFilterable_RespectsFilter(): void {
		/* Arrange. */
		update_option( Migration_Job_Scheduler::STATUS_OPTION_NAME, Migration_Job_Scheduler::STATUS_IN_PROGRESS );
		update_option( Migration_Job_Scheduler::RETRY_COUNT_OPTION_NAME, 1 );

		$action_scheduler = $this->createMock( Action_Scheduler::class );
		$action_scheduler->method( 'get_scheduled_actions' )->willReturn( array( 'action_1' ) );

		$job = $this->createMock( Migration_Job::class );
		$job->method( 'get_name' )->willReturn( 'test_job' );

		$job_scheduler = new Migration_Job_Scheduler( $action_scheduler );
		$job_scheduler->register_job( $job );

		// Set max retries to 1, so retry count of 1 means we've exhausted retries.
		$filter = function () {
			return 1;
		};
		add_filter( 'sensei_hpps_migration_max_retries', $filter );

		/* Act. */
		$job_scheduler->collect_failed_job_errors( 'action_1', array( 'message' => 'Timeout' ) );

		/* Assert. */
		$this->assertSame(
			Migration_Job_Scheduler::STATUS_FAILED,
			get_option( Migration_Job_Scheduler::STATUS_OPTION_NAME )
		);

		/* Cleanup. */
		remove_filter( 'sensei_hpps_migration_max_retries', $filter );
	}

	public function testRunJob_SuccessfulCompletion_ResetsRetryCount(): void {
		/* Arrange. */
		update_option( Migration_Job_Scheduler::RETRY_COUNT_OPTION_NAME, 2 );

		$action_scheduler = $this->createMock( Action_Scheduler::class );
		$migration_job    = $this->createMock( Migration_Job::class );
		$job_scheduler    = new Migration_Job_Scheduler( $action_scheduler );

		$migration_job->method( 'is_complete' )->willReturn( true );
		$migration_job->method( 'get_name' )->willReturn( 'foo' );

		$job_scheduler->register_job( $migration_job );

		/* Act. */
		$job_scheduler->run_job( $migration_job->get_name() );

		/* Assert. */
		$this->assertFalse( get_option( Migration_Job_Scheduler::RETRY_COUNT_OPTION_NAME ) );
	}

	public function testClearState_Always_DeletesRetryCount(): void {
		/* Arrange. */
		update_option( Migration_Job_Scheduler::RETRY_COUNT_OPTION_NAME, 2 );

		$action_scheduler = $this->createMock( Action_Scheduler::class );
		$job_scheduler    = new Migration_Job_Scheduler( $action_scheduler );

		/* Act. */
		$job_scheduler->clear_state();

		/* Assert. */
		$this->assertFalse( get_option( Migration_Job_Scheduler::RETRY_COUNT_OPTION_NAME ) );
	}
}
