<?php
/**
 * This file contains the Sensei_Course_Theme_Option_Test class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tests for Sensei_Course_Theme_Option class.
 *
 * @group course-theme
 */
class Sensei_Course_Theme_Option_Test extends WP_UnitTestCase {

	private Sensei_Factory $factory;
	private Sensei_Course_Theme_Option $instance;

	public function setup() {
		parent::setup();
		$this->factory  = new Sensei_Factory();
		$this->instance = Sensei_Course_Theme_Option::instance();
	}

	/**
	 * Testing the Sensei_Course_Theme_Option class to make sure it is loaded.
	 */
	public function testClassInstance() {
		$this->assertTrue( class_exists( 'Sensei_Course_Theme_Option' ), 'Sensei Course Theme class should exist' );
	}

	public function testHasSenseiThemeEnabledReturnsFalseByDefault() {
		$course_id = $this->factory->course->create();

		$output = $this->instance->has_sensei_theme_enabled( $course_id );

		$this->assertFalse( $output, 'By default the `has_sensei_theme_enabled` method must return false.' );
	}

	public function testHasSenseiThemeEnabledReturnsFalseWhenUsingWordpressTheme() {
		$course_id = $this->factory->course->create();
		update_post_meta( $course_id, Sensei_Course_Theme_Option::THEME_POST_META_NAME, Sensei_Course_Theme_Option::WORDPRESS_THEME );

		$output = $this->instance->has_sensei_theme_enabled( $course_id );

		$this->assertFalse( $output, '`has_sensei_theme_enabled` method must return false when WordPress theme is enabled.' );
	}

	public function testHasSenseiThemeEnabledReturnsTrueWhenUsingSenseiTheme() {
		$course_id = $this->factory->course->create();
		update_post_meta( $course_id, Sensei_Course_Theme_Option::THEME_POST_META_NAME, Sensei_Course_Theme_Option::SENSEI_THEME );

		$output = $this->instance->has_sensei_theme_enabled( $course_id );

		$this->assertTrue( $output, '`has_sensei_theme_enabled` method must return true when Sensei theme is enabled.' );
	}
}
