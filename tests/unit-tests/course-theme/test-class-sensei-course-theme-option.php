<?php
/**
 * This file contains the Sensei_Course_Theme_Option_Test class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once SENSEI_TEST_FRAMEWORK_DIR . '/trait-sensei-file-system-helper.php';

/**
 * Tests for Sensei_Course_Theme_Option class.
 *
 * @group course-theme
 */
class Sensei_Course_Theme_Option_Test extends WP_UnitTestCase {
	use Sensei_Test_Login_Helpers;
	use \Sensei_File_System_Helper;

	/**
	 * Sensei Factory helper class - useful to create objects for testing.
	 *
	 * @var Sensei_Factory
	 */
	protected $factory;

	/**
	 * Setup method. Run first on every test execution.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->factory = new Sensei_Factory();
	}

	/**
	 * Cleanup/teardown after class.
	 */
	public static function tearDownAfterClass(): void {
		parent::tearDownAfterClass();
		\Sensei()->settings->set( 'sensei_learning_mode_all', false );
	}

	/**
	 * Testing the Sensei_Course_Theme_Option class to make sure it is loaded.
	 */
	public function testClassInstance() {
		$this->assertTrue( class_exists( 'Sensei_Course_Theme_Option' ), 'Sensei Course Theme class should exist' );
	}

	/**
	 * Ensure `has_sensei_theme_enabled` returns false by default.
	 */
	public function testHasSenseiThemeEnabledReturnsFalseByDefault() {
		$course_id = $this->factory->course->create();

		$output = Sensei_Course_Theme_Option::has_learning_mode_enabled( $course_id );

		$this->assertFalse( $output, 'By default the `has_sensei_theme_enabled` method must return false.' );
	}

	/**
	 * Ensure `has_sensei_theme_enabled` returns false if WordPress theme is enabled.
	 */
	public function testHasSenseiThemeEnabledReturnsFalseWhenUsingWordpressTheme() {
		$course_id = $this->factory->course->create();
		update_post_meta( $course_id, Sensei_Course_Theme_Option::THEME_POST_META_NAME, Sensei_Course_Theme_Option::WORDPRESS_THEME );

		$output = Sensei_Course_Theme_Option::has_learning_mode_enabled( $course_id );

		$this->assertFalse( $output, '`has_sensei_theme_enabled` method must return false when WordPress theme is enabled.' );
	}

	/**
	 * Ensure `has_sensei_theme_enabled` returns true if Sensei theme is enabled.
	 */
	public function testHasSenseiThemeEnabledReturnsTrueWhenUsingSenseiTheme() {
		$course_id = $this->factory->course->create();
		update_post_meta( $course_id, Sensei_Course_Theme_Option::THEME_POST_META_NAME, Sensei_Course_Theme_Option::SENSEI_THEME );

		$output = Sensei_Course_Theme_Option::has_learning_mode_enabled( $course_id );

		$this->assertTrue( $output, '`has_sensei_theme_enabled` method must return true when Sensei theme is enabled.' );
	}

	/**
	 * Ensure `has_sensei_theme_enabled` returns false if Sensei theme is globally off and for course null.
	 */
	public function testSenseiThemeGloballyOffAndCourseNull() {
		\Sensei()->settings->set( 'sensei_learning_mode_all', false );
		$course_id = $this->factory->course->create();
		$output    = Sensei_Course_Theme_Option::has_learning_mode_enabled( $course_id );
		$this->assertFalse( $output, '`has_sensei_theme_enabled` method must return false when Sensei theme is globally off and for course null.' );
	}

	/**
	 * Ensure `has_sensei_theme_enabled` returns false if Sensei theme is globally off and for course off.
	 */
	public function testSenseiThemeGloballyOffAndCourseOff() {
		\Sensei()->settings->set( 'sensei_learning_mode_all', false );
		$course_id = $this->factory->course->create();
		update_post_meta( $course_id, Sensei_Course_Theme_Option::THEME_POST_META_NAME, Sensei_Course_Theme_Option::WORDPRESS_THEME );
		$output = Sensei_Course_Theme_Option::has_learning_mode_enabled( $course_id );
		$this->assertFalse( $output, '`has_sensei_theme_enabled` method must return false when Sensei theme is globally off and for course off.' );
	}

	/**
	 * Ensure `has_sensei_theme_enabled` returns true if Sensei theme is globally off and for course on.
	 */
	public function testSenseiThemeGloballyOffAndCourseOn() {
		\Sensei()->settings->set( 'sensei_learning_mode_all', false );
		$course_id = $this->factory->course->create();
		update_post_meta( $course_id, Sensei_Course_Theme_Option::THEME_POST_META_NAME, Sensei_Course_Theme_Option::SENSEI_THEME );
		$output = Sensei_Course_Theme_Option::has_learning_mode_enabled( $course_id );
		$this->assertTrue( $output, '`has_sensei_theme_enabled` method must return false when Sensei theme is globally off and for course on.' );
	}

	/**
	 * Ensure `has_sensei_theme_enabled` returns true if Sensei theme is globally on and for course null.
	 */
	public function testSenseiThemeGloballyOnAndCourseNull() {
		\Sensei()->settings->set( 'sensei_learning_mode_all', true );
		$course_id = $this->factory->course->create();
		$output    = Sensei_Course_Theme_Option::has_learning_mode_enabled( $course_id );
		$this->assertTrue( $output, '`has_sensei_theme_enabled` method must return true when Sensei theme is globally on and for course null.' );
	}

	/**
	 * Ensure `has_sensei_theme_enabled` returns true if Sensei theme is globally on and for course off.
	 */
	public function testSenseiThemeGloballyOnAndCourseOff() {
		\Sensei()->settings->set( 'sensei_learning_mode_all', true );
		$course_id = $this->factory->course->create();
		update_post_meta( $course_id, Sensei_Course_Theme_Option::THEME_POST_META_NAME, Sensei_Course_Theme_Option::WORDPRESS_THEME );
		$output = Sensei_Course_Theme_Option::has_learning_mode_enabled( $course_id );
		$this->assertTrue( $output, '`has_sensei_theme_enabled` method must return true when Sensei theme is globally on and for course off.' );
	}

	/**
	 * Ensure `has_sensei_theme_enabled` returns true if Sensei theme is globally on and for course on.
	 */
	public function testSenseiThemeGloballyOnAndCourseOn() {
		\Sensei()->settings->set( 'sensei_learning_mode_all', true );
		$course_id = $this->factory->course->create();
		update_post_meta( $course_id, Sensei_Course_Theme_Option::THEME_POST_META_NAME, Sensei_Course_Theme_Option::SENSEI_THEME );
		$output = Sensei_Course_Theme_Option::has_learning_mode_enabled( $course_id );
		$this->assertTrue( $output, '`has_sensei_theme_enabled` method must return true when Sensei theme is globally on and for course on.' );
	}

	/**
	 * Ensure `has_sensei_theme_enabled` returns true if enabled via filter.
	 */
	public function testSenseiThemeGloballyOffAndCourseOffAndFilterOn() {
		\Sensei()->settings->set( 'sensei_learning_mode_all', false );
		$course_id = $this->factory->course->create();
		update_post_meta( $course_id, Sensei_Course_Theme_Option::THEME_POST_META_NAME, Sensei_Course_Theme_Option::WORDPRESS_THEME );
		add_filter( 'sensei_course_learning_mode_enabled', '__return_true' );
		$output = Sensei_Course_Theme_Option::has_learning_mode_enabled( $course_id );
		$this->assertTrue( $output, '`has_sensei_theme_enabled` method must return true when Sensei theme is globally off for course off and via filter on.' );
	}

	/**
	 * Test that admin bar is displayed only for editor users.
	 */
	public function testShowAdminBarOnlyForEditors() {
		$lesson_id = $this->factory->get_random_lesson_id();
		$course_id = Sensei()->lesson->get_course_id( $lesson_id );

		update_post_meta( $course_id, Sensei_Course_Theme_Option::THEME_POST_META_NAME, Sensei_Course_Theme_Option::SENSEI_THEME );

		global $post;
		$post = get_post( $lesson_id );

		// Student on learning mode.
		$this->login_as_student();
		$this->assertFalse( Sensei_Course_Theme_Option::instance()->show_admin_bar_only_for_editors( true ), 'Should return `false` to hide admin bar on learning mode.' );

		// Admin on learning mode.
		$this->login_as_admin();
		$this->assertTrue( Sensei_Course_Theme_Option::instance()->show_admin_bar_only_for_editors( false ), 'Should return `true` to hide admin bar on learning mode.' );

		// Student outside of learning mode.
		$post = $this->factory->post->create_and_get();
		$this->login_as_student();
		$this->assertTrue( Sensei_Course_Theme_Option::instance()->show_admin_bar_only_for_editors( true ), 'Should return the default value to hide admin bar outside of learning mode.' );
	}

	/**
	 * Classic theme without support should load the compatibility styles.
	 */
	public function testShouldLoadLearningModeCompat_WhenClassicThemeAndNoSupport_ReturnsTrue() {
		$result = Sensei_Course_Theme_Option::should_load_learning_mode_compat();

		$this->assertTrue( $result, 'Compat styles should load for classic themes without support.' );
	}

	/**
	 * Themes declaring sensei-learning-mode support should skip the compatibility styles.
	 */
	public function testShouldLoadLearningModeCompat_WhenThemeSupportsSenseiLearningMode_ReturnsFalse() {
		add_theme_support( 'sensei-learning-mode' );

		$result = Sensei_Course_Theme_Option::should_load_learning_mode_compat();

		$this->assertFalse( $result, 'Compat styles should be skipped when the theme supports sensei-learning-mode.' );
	}

	/**
	 * Block themes should inherit their own styles and skip the compatibility styles.
	 */
	public function testShouldLoadLearningModeCompat_WhenBlockTheme_ReturnsFalse() {
		$theme_directory = get_template_directory() . '/block-templates';
		$index_file      = $theme_directory . '/index.html';

		if ( ! is_dir( $theme_directory ) ) {
			mkdir( $theme_directory );
		}

		$this->create_index_file( $index_file );

		$result = Sensei_Course_Theme_Option::should_load_learning_mode_compat();

		$this->assertFalse( $result, 'Compat styles should be skipped for block themes.' );

		unlink( $index_file );
		rmdir( $theme_directory );
	}
}
