<?php
/**
 * This file contains the Sensei_Course_Theme_Test class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tests for Sensei_Course_Theme_Test class.
 *
 * @group course-theme
 */
class Sensei_Course_Theme_Test extends WP_UnitTestCase {

	use Sensei_Test_Login_Helpers;

	/**
	 * Sensei Factory helper class - useful to create objects for testing.
	 *
	 * @var Sensei_Factory
	 */
	private $factory;

	/**
	 * Instance of `Sensei_Course_Theme_Option` under test.
	 *
	 * @var Sensei_Course_Theme_Theme
	 */
	private $instance;

	/**
	 * Setup method. Run first on every test execution.
	 */
	public function setup() {
		parent::setup();
		$this->factory  = new Sensei_Factory();
		$this->instance = Sensei_Course_Theme::instance();
	}

	/**
	 * Testing the Course Theme class to make sure it is loaded.
	 */
	public function testClassInstance() {
		$this->assertTrue( class_exists( 'Sensei_Course_Theme' ), 'Sensei Course Theme class should exist' );
	}

	public function testPreviewModeNotAllowedToNonPreviliged() {
		$course          = $this->factory->course->create_and_get();
		$GLOBALS['post'] = $course;

		$this->login_as_student();

		$_GET[ Sensei_Course_Theme::PREVIEW_QUERY_VAR ] = (string) $course->ID;
		$allowed                                        = Sensei_Course_Theme::is_preview_mode( $course->ID );
		$this->assertFalse( $allowed, 'Should not allow preview to students.' );
	}

	public function testPreviewModeNotAllowedIfNotCoursePage() {
		$post            = $this->factory->post->create_and_get();
		$GLOBALS['post'] = $post;

		$this->login_as_admin();

		$allowed = Sensei_Course_Theme::is_preview_mode( $post->ID );
		$this->assertFalse( $allowed, 'Should not allow preview if not a course related page.' );
	}

	public function testPreviewModeNotAllowedIfPreviewQueryNotCurrentCourse() {
		$course          = $this->factory->course->create_and_get();
		$another_course  = $this->factory->course->create_and_get();
		$GLOBALS['post'] = $course;

		$this->login_as_admin();

		$_GET[ Sensei_Course_Theme::PREVIEW_QUERY_VAR ] = (string) $another_course->ID;
		$allowed                                        = Sensei_Course_Theme::is_preview_mode( $course->ID );
		$this->assertFalse( $allowed, 'Should not allow preview if preview query id is not current course page.' );
	}
}
