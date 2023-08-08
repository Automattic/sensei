<?php
/**
 * This file contains the Sensei_Home_Task_Customize_Course_Theme class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Tests for Sensei_Home_Task_Customize_Course_Theme class.
 *
 * @covers Sensei_Home_Task_Customize_Course_Theme
 */
class Sensei_Home_Task_Customize_Course_Theme_Test extends WP_UnitTestCase {

	/**
	 * The task under test.
	 *
	 * @var Sensei_Home_Task_Customize_Course_Theme
	 */
	private $task;

	/**
	 * The original theme.
	 */
	protected $original_theme;

	public function setUp(): void {
		parent::setUp();
		$this->task           = new Sensei_Home_Task_Customize_Course_Theme();
		$this->original_theme = get_stylesheet();
	}

	public function tearDown(): void {
		delete_option( Sensei_Home_Task_Customize_Course_Theme::CUSTOMIZED_COURSE_THEME_OPTION_KEY );
		switch_theme( $this->original_theme );
		parent::tearDown();
	}

	public function testIsCompleted_WhenCalledInitially_ReturnsFalse() {
		// Assert.
		$this->assertFalse( $this->task->is_completed() );
	}

	public function testIsCompleted_AfterMarkCompletedIsCalled_ReturnsTrue() {
		// Act.
		$this->task->mark_completed();

		// Assert.
		$this->assertTrue( $this->task->is_completed() );
	}

	public function testIsActive_WhenCourseThemeIsNotInstalled_ReturnsFalse() {
		// Assert.
		$this->assertFalse( $this->task->is_active() );
	}

	public function testIsActive_WhenCourseThemeIsInstalled_ReturnsTrue() {
		// Arrange.
		$course_theme = 'course';
		switch_theme( $course_theme );

		// Assert.
		$this->assertTrue( $this->task->is_active() );
	}
}
