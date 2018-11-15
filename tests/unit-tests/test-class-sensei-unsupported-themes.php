<?php

class Sensei_Unsupported_Themes_Test extends WP_UnitTestCase {

	public function tearDown() {
		Sensei_Unsupported_Themes::reset();

		parent::tearDown();
	}

	/**
	 * Ensure that unsupported theme request handling is disabled by default.
	 *
	 * @since 1.12.0
	 */
	public function testShouldDisableRequestHandlingByDefault() {
		Sensei_Unsupported_Themes::init();
		$this->assertFalse( Sensei_Unsupported_Themes::get_instance()->is_handling_request() );
	}

	/**
	 * Ensure that request handling is disabled when the theme is supported.
	 *
	 * @since 1.12.0
	 */
	public function testShouldDisableRequestHandlingForSupportedTheme() {
		// Set current theme to be twentysixteen, which is supported by Sensei.
		add_filter( 'template', array( $this, 'setThemeTwentySixteen' ) );
		add_filter( 'stylesheet', array( $this, 'setThemeTwentySixteen' ) );

		$this->setupCoursePage();

		Sensei_Unsupported_Themes::init();
		$this->assertFalse( Sensei_Unsupported_Themes::get_instance()->is_handling_request() );
	}

	/**
	 * Ensure that request handling is enabled for unsupported themes.
	 *
	 * @since 1.12.0
	 */
	public function testShouldEnableRequestHandlingForUnsupportedTheme() {
		// Default theme does not support Sensei.
		$this->setupCoursePage();

		Sensei_Unsupported_Themes::init();
		$this->assertTrue( Sensei_Unsupported_Themes::get_instance()->is_handling_request() );
	}

	/**
	 * Helper to set up the current request to be a Course page. This request
	 * will be handled by the unsupported theme handler if the theme is not
	 * supported.
	 *
	 * @since 1.12.0
	 */
	private function setupCoursePage() {
		global $post, $wp_query;

		$course = $this->factory->post->create_and_get(
			array(
				'post_status' => 'publish',
				'post_type'   => 'course',
			)
		);

		// Setup globals.
		$post                = $course;
		$wp_query->post      = $course;
		$wp_query->is_single = true;
	}

	/**
	 * Filter for setting theme to Twenty Sixteen.
	 *
	 * @since 1.12.0
	 */
	public function setThemeTwentySixteen() {
		return 'twentysixteen';
	}
}
