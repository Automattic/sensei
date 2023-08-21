<?php

namespace SenseiTest\Internal\Action_Scheduler;

use Sensei\Internal\Action_Scheduler\Action_Scheduler;

/**
 * Class Action_Scheduler_Test
 *
 * @covers \Sensei\Internal\Action_Scheduler\Action_Scheduler
 */
class Action_Scheduler_Test extends \WP_UnitTestCase {
	public function tearDown(): void {
		parent::tearDown();

		_as_reset();
	}

	public function testScheduleRecurringAction_WhenCalled_AddsRecurringAction() {
		/* Arrange. */
		$scheduler = new Action_Scheduler();

		/* Act. */
		$scheduler->schedule_recurring_action( 1, 1, 'foo', [ 'bar' ] );

		/* Assert. */
		$result = _as_get_schedule_recurring_action( 1, 'foo', [ 'bar' ], Action_Scheduler::GROUP_ID );
		$this->assertCount( 1, $result );
	}

	public function testScheduleSingleAction_WhenCalled_AddsSingleAction() {
		/* Arrange. */
		$scheduler = new Action_Scheduler();

		/* Act. */
		$scheduler->schedule_single_action( 'foo', [ 'bar' ] );

		/* Assert. */
		$result = _as_get_scheduled_actions( 'foo', [ 'bar' ], Action_Scheduler::GROUP_ID );
		$this->assertCount( 1, $result );
	}

	public function testUnscheduleAction_WhenCalled_UnschedulesAction() {
		/* Arrange. */
		$scheduler = new Action_Scheduler();
		$scheduler->schedule_single_action( 'foo', [ 'bar' ] );

		/* Act. */
		$scheduler->unschedule_action( 'foo', [ 'bar' ] );

		/* Assert. */
		$result = _as_get_scheduled_actions( 'foo', [ 'bar' ], Action_Scheduler::GROUP_ID );
		$this->assertCount( 0, $result );
	}

	public function testUnscheduleAllActions_WhenCalled_UnschedulesAllActions() {
		/* Arrange. */
		$scheduler = new Action_Scheduler();
		$scheduler->schedule_single_action( 'foo' );

		/* Act. */
		$scheduler->unschedule_all_actions();

		/* Assert. */
		$result = _as_get_scheduled_actions( 'foo', [], Action_Scheduler::GROUP_ID );
		$this->assertCount( 0, $result );
	}

	public function testHasScheduledAction_WhenHasAction_ReturnsTrue() {
		/* Arrange. */
		$scheduler = new Action_Scheduler();
		$scheduler->schedule_single_action( 'foo', [ 'bar' ] );

		/* Act. */
		$result = $scheduler->has_scheduled_action( 'foo', [ 'bar' ] );

		/* Assert. */
		$this->assertTrue( $result );
	}

	public function testHasScheduledAction_WhenHasNoAction_ReturnsFalse() {
		/* Arrange. */
		$scheduler = new Action_Scheduler();

		/* Act. */
		$result = $scheduler->has_scheduled_action( 'foo', [ 'bar' ] );

		/* Assert. */
		$this->assertFalse( $result );
	}
}
