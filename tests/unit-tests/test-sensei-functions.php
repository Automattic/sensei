<?php

class Sensei_Functions_Test extends WP_UnitTestCase {

	public function tearDown(): void {
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

	public function testIsSenseiReturnsFalseWhenPostIdEmpty() {
		global $post;

		$post = $this->factory->post->create_and_get();
		Sensei()->settings->settings['course_completed_page'] = $post->ID;

		$post->ID = 0;
		$this->assertFalse( is_sensei() );

		$post->ID = '';
		$this->assertFalse( is_sensei() );

		$post->ID = Sensei()->settings->settings['course_completed_page'];
		$this->assertTrue( is_sensei() );
	}

	/**
	 * Test user registration URL.
	 *
	 * @since 3.15.0
	 */
	public function testSenseiUserRegistrationUrl() {
		$wp_registration_url = wp_registration_url();

		Sensei()->settings->set( 'my_course_page', false );

		$this->assertEquals(
			$wp_registration_url,
			sensei_user_registration_url(),
			'Should return WP registration URL when My Course page is not set'
		);

		$this->assertNull(
			sensei_user_registration_url( false ),
			'Should return NULL when My Course page is not set and `$return_wp_registration_url` is `false`'
		);

		$my_courses_page_id = $this->factory->post->create(
			[
				'post_type'  => 'page',
				'post_title' => 'My Courses',
				'post_name'  => 'my-courses',
			]
		);
		Sensei()->settings->set( 'my_course_page', $my_courses_page_id );

		$this->assertEquals(
			get_permalink( $my_courses_page_id ),
			sensei_user_registration_url(),
			'Should get the my courses page permalink as registration page'
		);

		tests_add_filter( 'sensei_use_wp_register_link', '__return_true' );

		$this->assertEquals(
			$wp_registration_url,
			sensei_user_registration_url(),
			'Should return NULL when filter is set to use wp registration link'
		);
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
