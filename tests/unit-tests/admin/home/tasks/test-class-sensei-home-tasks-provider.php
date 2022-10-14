<?php
/**
 * This file contains the Sensei_Home_Tasks_Provider_Test class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Tests for Sensei_Home_Tasks_Provider class.
 *
 * @covers Sensei_Home_Tasks_Provider
 */
class Sensei_Home_Tasks_Provider_Test extends WP_UnitTestCase {

	const FAKE_TASK_ID = 'fake-task-id';

	/**
	 * The provider under test.
	 *
	 * @var Sensei_Home_Tasks_Provider
	 */
	private $provider;

	public function setUp() {
		parent::setUp();
		$this->provider = new Sensei_Home_Tasks_Provider();
	}

	public function tearDown() {
		remove_filter( 'sensei_home_tasks', [ $this, 'overrideWithFakeTask' ] );
		parent::tearDown();
	}

	public function testGet_WhenCalled_ReturnsReturnsExpectedTasks() {
		// Act
		$result = $this->provider->get();

		// Assert
		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'items', $result );
		$items = $result['items'];
		$this->assertIsArray( $items );
		$this->assertCount( 4, $items );
		$this->assertArrayHasKey( Sensei_Home_Task_Setup_Site::get_id(), $items );
		$this->assertArrayHasKey( Sensei_Home_Task_Create_First_Course::get_id(), $items );
		$this->assertArrayHasKey( Sensei_Home_Task_Configure_Learning_Mode::get_id(), $items );
		$this->assertArrayHasKey( Sensei_Home_Task_Publish_First_Course::get_id(), $items );
	}

	public function testGet_GivenAFilterThatOverridesTasks_ReturnSingleOverriddenResult() {
		// Arrange
		add_filter( 'sensei_home_tasks', [ $this, 'overrideWithFakeTask' ] );

		// Act
		$result = $this->provider->get();

		// Assert
		$items = $result['items'];
		$this->assertCount( 1, $items );
		$this->assertArrayHasKey( self::FAKE_TASK_ID, $items );
	}

	/**
	 * Override tasks with a single fake one.
	 *
	 * @return array
	 */
	public function overrideWithFakeTask() : array {
		return [
			self::FAKE_TASK_ID => [ 'fake' ],
		];
	}

	public function testGet_WhenCalled_ReturnsIsCompletedFalseAsDefault() {
		// Act
		$result = $this->provider->get();

		// Assert
		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'is_completed', $result );
		$this->assertFalse( $result['is_completed'] );
	}

	public function testGet_WhenCalled_ReturnsIsCompletedFromOption() {
		// Arrange
		update_option( Sensei_Home_Tasks_Provider::COMPLETED_TASKS_OPTION_KEY, true );

		// Act
		$result = $this->provider->get();

		// Assert
		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'is_completed', $result );
		$this->assertTrue( $result['is_completed'] );
	}

	public function testMarkAsCompleted_WhenCalled_SetsExpectedOption() {
		// Pre-Assert
		$this->assertFalse( get_option( Sensei_Home_Tasks_Provider::COMPLETED_TASKS_OPTION_KEY, false ) );

		// Act
		$this->provider->mark_as_completed( true );

		// Assert
		$this->assertTrue( get_option( Sensei_Home_Tasks_Provider::COMPLETED_TASKS_OPTION_KEY, false ) );
	}

}
