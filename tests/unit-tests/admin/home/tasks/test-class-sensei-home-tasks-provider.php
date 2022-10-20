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
	use Sensei_Test_Login_Helpers;

	const FAKE_TASK_ID = 'fake-task-id';

	/**
	 * The provider under test.
	 *
	 * @var Sensei_Home_Tasks_Provider
	 */
	private $provider;

	/**
	 * The factory to help with testing the course attribute.
	 *
	 * @var Sensei_Factory
	 */
	protected $factory;

	public function setUp() {
		parent::setUp();
		$this->provider = new Sensei_Home_Tasks_Provider();
		$this->factory  = new Sensei_Factory();
	}

	public function tearDown() {
		remove_filter( 'sensei_home_tasks', [ $this, 'overrideWithFakeTask' ] );
		remove_filter( 'wp_get_attachment_image_src', [ $this, 'overrideWithCustomImage' ] );
		parent::tearDown();
	}

	public function testGet_WhenCalledAsTeacher_ReturnsNull() {
		// Arrange
		$this->login_as_teacher();

		// Act
		$result = $this->provider->get();

		// Assert
		$this->assertNull( $result );
	}

	public function testGet_WhenCalledAsAdmin_ReturnsExpectedTasks() {
		// Arrange
		$this->login_as_admin();

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
		$this->login_as_admin();
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

	/**
	 * Callback to be used in filter, to return a custom placeholder image for the site.
	 *
	 * @return string[]
	 */
	public function overrideWithCustomImage() : array {
		return [ 'test-image.png' ];
	}

	public function testGet_WhenCalled_ReturnsSiteContainingSiteInfo() {
		// Arrange
		$this->login_as_admin();
		update_option( 'blogname', 'Test site' );
		add_filter( 'wp_get_attachment_image_src', [ $this, 'overrideWithCustomImage' ] );

		// Act
		$result = $this->provider->get();

		// Assert
		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'site', $result );
		$this->assertIsArray( $result['site'] );
		$this->assertEquals( 'Test site', $result['site']['title'] );
		$this->assertEquals( 'test-image.png', $result['site']['image'] );
	}

	public function testGet_WhenCalled_ReturnsSiteImageAsNullWhenNoCustomLogoIsDefined() {
		// Arrange
		$this->login_as_admin();
		update_option( 'blogname', 'Test site' );

		// Act
		$result = $this->provider->get();

		// Assert
		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'site', $result );
		$this->assertIsArray( $result['site'] );
		$this->assertEquals( 'Test site', $result['site']['title'] );
		$this->assertNull( $result['site']['image'] );
	}

	public function testGet_WhenCalled_ReturnsNullAsCourseWhenNoCourseIsFound() {
		// Arrange
		$this->login_as_admin();
		self::flush_cache();

		// Act
		$result = $this->provider->get();

		// Assert
		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'course', $result );
		$this->assertNull( $result['course'] );
	}

	public function testGet_WhenCalled_ReturnsCourseTitleWhenACourseIsFound() {
		// Arrange
		$this->login_as_admin();
		$course_id = $this->factory->course->create(
			[
				'post_name'   => 'testing',
				'post_title'  => 'Test Course',
				'post_status' => 'draft',
			]
		);
		self::flush_cache();

		// Act
		$result = $this->provider->get();

		// Assert
		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'course', $result );
		$this->assertIsArray( $result['course'] );
		$this->assertEquals( 'Test Course', $result['course']['title'] );
		$this->assertStringContainsString( 'p=' . $course_id, $result['course']['permalink'] );
		$this->assertNull( $result['course']['image'] );
	}

	public function testGet_WhenCalled_ReturnsCourseImageWhenACourseIsFound() {
		// Arrange
		$this->login_as_admin();
		$course_id = $this->factory->course->create(
			[
				'post_name'   => 'testing',
				'post_title'  => 'Test Course',
				'post_status' => 'publish',
			]
		);
		update_post_meta( $course_id, '_thumbnail_id', 42 );
		add_filter( 'wp_get_attachment_image_src', [ $this, 'overrideWithCustomImage' ] );
		self::flush_cache();

		// Act
		$result = $this->provider->get();

		// Assert
		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'course', $result );
		$this->assertIsArray( $result['course'] );
		$this->assertEquals( 'Test Course', $result['course']['title'] );
		$this->assertStringContainsString( 'testing', $result['course']['permalink'] );
		$this->assertEquals( 'test-image.png', $result['course']['image'] );
	}

	public function testGet_WhenCalled_ReturnsCourseAsNullWhenTheCourseIsOnTrash() {
		// Arrange
		$this->login_as_admin();
		$course_id = $this->factory->course->create(
			[
				'post_name'   => 'testing',
				'post_title'  => 'Test Course',
				'post_status' => 'trash',
			]
		);
		update_post_meta( $course_id, '_thumbnail_id', 42 );
		add_filter( 'post_thumbnail_url', [ $this, 'overrideWithCustomImage' ] );
		self::flush_cache();

		// Act
		$result = $this->provider->get();

		// Assert
		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'course', $result );
		$this->assertNull( $result['course'] );
	}

	public function testGet_WhenCalled_ReturnsIsCompletedFalseAsDefault() {
		// Arrange
		$this->login_as_admin();

		// Act
		$result = $this->provider->get();

		// Assert
		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'is_completed', $result );
		$this->assertFalse( $result['is_completed'] );
	}

	public function testGet_WhenCalled_ReturnsIsCompletedFromOption() {
		// Arrange
		$this->login_as_admin();
		update_option( Sensei_Home_Tasks_Provider::COMPLETED_TASKS_OPTION_KEY, true );

		// Act
		$result = $this->provider->get();

		// Assert
		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'is_completed', $result );
		$this->assertTrue( $result['is_completed'] );
	}

	public function testMarkAsCompleted_WhenCalled_SetsExpectedOption() {
		// Arrange
		$this->login_as_admin();

		// Pre-Assert
		$this->assertFalse( get_option( Sensei_Home_Tasks_Provider::COMPLETED_TASKS_OPTION_KEY, false ) );

		// Act
		$this->provider->mark_as_completed( true );

		// Assert
		$this->assertTrue( get_option( Sensei_Home_Tasks_Provider::COMPLETED_TASKS_OPTION_KEY, false ) );
	}

}
