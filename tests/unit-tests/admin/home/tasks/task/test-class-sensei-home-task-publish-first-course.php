<?php
/**
 * This file contains the Sensei_Home_Task_Publish_First_Course_Test class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Tests for Sensei_Home_Tasks_Provider class.
 *
 * @covers Sensei_Home_Task_Publish_First_Course
 */
class Sensei_Home_Task_Publish_First_Course_Test  extends WP_UnitTestCase {
	/**
	 * The task under test
	 *
	 * @var Sensei_Home_Task_Publish_First_Course
	 */
	private $task;

	/**
	 * Factory to create courses for testing.
	 *
	 * @var Sensei_Factory
	 */
	protected $factory;

	/**
	 * Set up the tests.
	 */
	public function setUp() {
		parent::setUp();
		$this->task    = new Sensei_Home_Task_Publish_First_Course();
		$this->factory = new Sensei_Factory();
		self::flush_cache();
		$this->assertFalse( $this->task->is_completed() );
	}

	/*
	 * Tests for the method isCompleted:
	 */

	/**
	 * Verifies if isCompleted returns false when there is no course registered.
	 *
	 * @covers Sensei_Home_Task_Publish_First_Course::is_completed
	 */
	public function testIsCompleted_NoCourseIsRegistered_ReturnsFalse() {
		// Arrange
		self::flush_cache();

		// Act
		$is_completed = $this->task->is_completed();

		// Assert
		$this->assertFalse( $is_completed );
	}

	/**
	 * Verifies if isCompleted returns false when a non-sample course is a draft.
	 *
	 * @covers Sensei_Home_Task_Publish_First_Course::is_completed
	 */
	public function testIsCompleted_NonSampleCourseIsDraft_ReturnsFalse() {
		// Arrange
		$this->factory->course->create(
			[
				'post_name'   => 'testing',
				'post_status' => 'draft',
			]
		);
		self::flush_cache();

		// Act
		$is_completed = $this->task->is_completed();

		// Assert
		$this->assertFalse( $is_completed );
	}


	/**
	 * Verifies if isCompleted returns false when both the sample course is a draft.
	 *
	 * @covers Sensei_Home_Task_Publish_First_Course::is_completed
	 */
	public function testIsCompleted_SampleCourseIsDraft_ReturnsFalse() {
		// Arrange
		$this->factory->course->create(
			[
				'post_name'   => Sensei_Data_Port_Manager::SAMPLE_COURSE_SLUG,
				'post_status' => 'draft',
			]
		);
		self::flush_cache();

		// Act
		$is_completed = $this->task->is_completed();

		// Assert
		$this->assertFalse( $is_completed );
	}

	/**
	 * Verifies if isCompleted returns false when both a sample and a non-sample course is published.
	 *
	 * @covers Sensei_Home_Task_Publish_First_Course::is_completed
	 */
	public function testIsCompleted_ASampleAndNonSampleCourseAsDraft_ReturnsFalse() {
		// Arrange
		$this->factory->course->create(
			[
				'post_name'   => Sensei_Data_Port_Manager::SAMPLE_COURSE_SLUG,
				'post_status' => 'draft',
			]
		);
		$this->factory->course->create(
			[
				'post_name'   => 'test',
				'post_status' => 'draft',
			]
		);
		self::flush_cache();

		// Act
		$is_completed = $this->task->is_completed();

		// Assert
		$this->assertFalse( $is_completed );
	}

	/**
	 * Verifies if isCompleted returns false when a non-sample course is published.
	 *
	 * @covers Sensei_Home_Task_Publish_First_Course::is_completed
	 */
	public function testIsCompleted_NonSampleCourseIsPublish_ReturnsTrue() {
		// Arrange
		$this->factory->course->create(
			[
				'post_name'   => 'testing',
				'post_status' => 'publish',
			]
		);
		self::flush_cache();

		// Act
		$is_completed = $this->task->is_completed();

		// Assert
		$this->assertTrue( $is_completed );
	}

	/**
	 * Verifies if isCompleted returns false when the sample course is published.
	 *
	 * @covers Sensei_Home_Task_Publish_First_Course::is_completed
	 */
	public function testIsCompleted_SampleCourseIsPublish_ReturnsFalse() {
		// Arrange
		$this->factory->course->create(
			[
				'post_name'   => Sensei_Data_Port_Manager::SAMPLE_COURSE_SLUG,
				'post_status' => 'publish',
			]
		);
		self::flush_cache();

		// Act
		$is_completed = $this->task->is_completed();

		// Assert
		$this->assertFalse( $is_completed );
	}

	/**
	 * Verifies if isCompleted returns true when the sample course is a draft, but the non-sample course is published.
	 *
	 * @covers Sensei_Home_Task_Publish_First_Course::is_completed
	 */
	public function testIsCompleted_ASampleAsDraftAndNonSampleCourseAsPublish_ReturnsTrue() {
		// Arrange
		$this->factory->course->create(
			[
				'post_name'   => Sensei_Data_Port_Manager::SAMPLE_COURSE_SLUG,
				'post_status' => 'draft',
			]
		);
		$this->factory->course->create(
			[
				'post_name'   => 'test',
				'post_status' => 'publish',
			]
		);
		self::flush_cache();

		// Act
		$is_completed = $this->task->is_completed();

		// Assert
		$this->assertTrue( $is_completed );
	}

	/**
	 * Verifies if isCompleted returns false when the sample course is published, but the non-sample course is still
	 * a draft.
	 *
	 * @covers Sensei_Home_Task_Publish_First_Course::is_completed
	 */
	public function testIsCompleted_ASampleAsPublishAndNonSampleCourseAsDraft_ReturnsFalse() {
		// Arrange
		$this->factory->course->create(
			[
				'post_name'   => Sensei_Data_Port_Manager::SAMPLE_COURSE_SLUG,
				'post_status' => 'publish',
			]
		);
		$this->factory->course->create(
			[
				'post_name'   => 'test',
				'post_status' => 'draft',
			]
		);
		self::flush_cache();

		// Act
		$is_completed = $this->task->is_completed();

		// Assert
		$this->assertFalse( $is_completed );
	}


	/*
	 * Tests for the method getUrl:
	 */


	/***
	 * Verifies if getUrl returns null when there's a non-sample course that is published.
	 *
	 * @covers Sensei_Home_Task_Publish_First_Course::get_url
	 */
	public function testGetUrl_ANonSampleCourseIsPublished_ReturnsNull() {
		// Arrange
		$this->factory->course->create(
			[
				'post_name'   => 'test',
				'post_status' => 'publish',
			]
		);
		self::flush_cache();

		// Act
		$url = $this->task->get_url();

		// Assert
		$this->assertNull( $url );
	}

	/***
	 * Verifies if getUrl returns the link to create a course when the sample course is published.
	 *
	 * @covers Sensei_Home_Task_Publish_First_Course::get_url
	 */
	public function testGetUrl_ASampleCourseIsPublished_ReturnsCreateANewPost() {
		// Arrange
		$this->factory->course->create(
			[
				'post_name'   => Sensei_Data_Port_Manager::SAMPLE_COURSE_SLUG,
				'post_status' => 'publish',
			]
		);
		self::flush_cache();

		// Act
		$url = $this->task->get_url();

		// Assert
		$this->assertContains( 'post-new.php', $url );
	}

	/***
	 * Verifies if getUrl returns the link to create a course when there is no courses registered.
	 *
	 * @covers Sensei_Home_Task_Publish_First_Course::get_url
	 */
	public function testGetUrl_NoCourseIsRegistered_ReturnsCreateANewPost() {
		// Arrange
		self::flush_cache();

		// Act
		$url = $this->task->get_url();

		// Assert
		$this->assertContains( 'post-new.php', $url );
	}

	/***
	 * Verifies if getUrl returns the link to edit the course when a non-sample course is a draft.
	 *
	 * @covers Sensei_Home_Task_Publish_First_Course::get_url
	 */
	public function testGetUrl_NonSampleCourseIsDraft_ReturnsEditPost() {
		// Arrange
		$admin_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );
		$this->factory->course->create(
			[
				'post_name'   => 'testing',
				'post_status' => 'draft',
			]
		);
		self::flush_cache();

		// Act
		$url = $this->task->get_url();

		// Assert
		$this->assertContains( 'post.php', $url );
		$this->assertContains( 'action=edit', $url );
	}

	/**
	 * Verifies if getUrl returns the link to create a course when the sample course is a draft.
	 *
	 * @covers Sensei_Home_Task_Publish_First_Course::get_url
	 */
	public function testGetUrl_SampleCourseIsDraft_ReturnsNewPost() {
		// Arrange
		$this->factory->course->create(
			[
				'post_name'   => Sensei_Data_Port_Manager::SAMPLE_COURSE_SLUG,
				'post_status' => 'draft',
			]
		);
		self::flush_cache();

		// Act
		$url = $this->task->get_url();

		// Assert
		$this->assertContains( 'post-new.php', $url );
	}
}
