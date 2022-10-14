<?php
/**
 * This file contains the Sensei_Home_Task_Create_First_Course_Test class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Tests for Sensei_Home_Tasks_Provider class.
 *
 * @covers Sensei_Home_Task_Create_First_Course
 */
class Sensei_Home_Task_Create_First_Course_Test  extends WP_UnitTestCase {
	/**
	 * The task under test
	 *
	 * @var Sensei_Home_Task_Create_First_Course
	 */
	private $task;

	/**
	 * Factory to create courses for testing.
	 *
	 * @var Sensei_Factory
	 */
	protected $factory;

	/**
	 * Setup.
	 */
	public function setUp() {
		parent::setUp();
		$this->task    = new Sensei_Home_Task_Create_First_Course();
		$this->factory = new Sensei_Factory();
		self::flush_cache();
		$this->assertFalse( $this->task->is_completed() );
	}

	/**
	 * Verify if is_completed returns true when a course that doesn't have the sample course slug is registered.
	 */
	public function testIsCompleted_NonSampleCourseExists_ReturnsTrue() {
		// Arrange
		$this->factory->course->create(
			[
				'post_name' => 'testing',
			]
		);
		self::flush_cache();

		// Act
		$is_completed = $this->task->is_completed();

		// Assert
		$this->assertTrue( $is_completed );
	}


	/**
	 * Verify if is_completed still returns false when a course that has the sample course slug is registered.
	 */
	public function testIsCompleted_OnlyASampleCourseExists_ReturnsFalse() {
		// Arrange
		$this->factory->course->create(
			[
				'post_name' => Sensei_Data_Port_Manager::SAMPLE_COURSE_SLUG,
			]
		);
		self::flush_cache();

		// Act
		$is_completed = $this->task->is_completed();

		// Assert
		$this->assertFalse( $is_completed );
	}

	/**
	 * Verify if is_completed returns true when a course that doesn't have the sample course slug is registered.
	 */
	public function testIsCompleted_ASampleAndNonSampleCourseExists_ReturnsTrue() {
		// Arrange
		$this->factory->course->create(
			[
				'post_name' => Sensei_Data_Port_Manager::SAMPLE_COURSE_SLUG,
			]
		);
		$this->factory->course->create(
			[
				'post_name' => 'test',
			]
		);
		self::flush_cache();

		// Act
		$is_completed = $this->task->is_completed();

		// Assert
		$this->assertTrue( $is_completed );
	}
}
