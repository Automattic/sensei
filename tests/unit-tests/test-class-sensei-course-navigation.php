<?php
/**
 * This file contains the Sensei_Course_Navigation_Test class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tests for Sensei_Course_Navigation_Test class.
 *
 * @group course-navigation
 */
class Sensei_Course_Navigation_Test extends WP_UnitTestCase {
	/**
	 * Set up the tests.
	 */
	public function setUp() {
		parent::setUp();
		$this->factory = new Sensei_Factory();
	}

	/**
	 * Testing the Course Navigation class to make sure it is loaded.
	 */
	public function testClassInstance() {
		$this->assertTrue( class_exists( 'Sensei_Course_Navigation' ), 'Sensei Course Navigation class should exist' );
	}

	/**
	 * Test default template for Sensei installation before Course Navigation.
	 */
	public function testDefaultTemplateForInstallationBeforeCourseNavigation() {
		Sensei_Course_Navigation::instance()->register_post_meta();

		$course_id = $this->factory->course->create();

		$this->assertEquals(
			'default-post-template',
			get_post_meta( $course_id, Sensei_Course_Navigation::TEMPLATE_POST_META_NAME, true )
		);
	}

	/**
	 * Test default template for Sensei installation after Course Navigation.
	 */
	public function testDefaultTemplateForInstallationAfterCourseNavigation() {
		update_option( Sensei_Course_Navigation::INSTALLED_AFTER_COURSE_NAVIGATION_OPTION_NAME, 1 );
		Sensei_Course_Navigation::instance()->register_post_meta();

		$course_id = $this->factory->course->create();

		$this->assertEquals(
			'sensei-lesson-template',
			get_post_meta( $course_id, Sensei_Course_Navigation::TEMPLATE_POST_META_NAME, true )
		);
	}
}
