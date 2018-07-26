<?php

class Sensei_Functions_Test extends WP_UnitTestCase {

	public function tearDown() {
		// Ensure explicit theme support is removed.
		remove_theme_support( 'sensei' );

		parent::tearDown();
	}

	/**
	 * Testing function `sensei_does_theme_support_templates` checks for
	 * themes supported by Sensei.
	 *
	 * @since 1.12.0
	 */
	public function testDoesThemeSupportTemplatesChecksSenseiThemes() {
		// Set current theme to be twentysixteen, which is supported by Sensei.
		add_filter( 'template', array( $this, 'setThemeTwentySixteen' ) );
		add_filter( 'stylesheet', array( $this, 'setThemeTwentySixteen' ) );

		$this->assertTrue( sensei_does_theme_support_templates() );
	}

	/**
	 * Testing function `sensei_does_theme_support_templates` returns false if
	 * theme is not supported.
	 *
	 * @since 1.12.0
	 */
	public function testDoesThemeSupportTemplatesReturnsFalseOnUnsupported() {
		// Default theme is not supported.
		$this->assertFalse( sensei_does_theme_support_templates() );
	}

	/**
	 * Testing function `sensei_does_theme_support_templates` checks for
	 * explicitly declared theme support for Sensei.
	 *
	 * @since 1.12.0
	 */
	public function testDoesThemeSupportTemplatesChecksExplicitThemeSupport() {
		// Explicitly set theme support.
		add_theme_support( 'sensei' );

		$this->assertTrue( sensei_does_theme_support_templates() );
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
