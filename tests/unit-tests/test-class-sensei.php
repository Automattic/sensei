<?php

class Sensei_Globals_Test extends WP_UnitTestCase {
	/**
	 * Setup function.
	 */
	public function setUp() {
		parent::setUp();

		$this->factory = new Sensei_Factory();
	}

	/**
	 * Test the global $woothemes_sensei object
	 */
	function testSenseiGlobalObject() {
		// setup the test
		global $woothemes_sensei;

		// test if the global sensei object is loaded
		$this->assertTrue( isset( $woothemes_sensei ), 'Sensei global object loaded ' );

		// check if the version number is setup
		$this->assertTrue( isset( Sensei()->version ), 'Sensei version number is set' );
	}

	/**
	 * Test the Sensei() global function to ensure that it works and return and instance
	 * for the main Sensei object
	 */
	function testSenseiGlobalAccessFunction() {

		// make sure the function is loaded
		$this->assertTrue( function_exists( 'Sensei' ), 'The global Sensei() function does not exist.' );

		// make sure it return an instance of class WooThemes_Sensei
		$this->assertTrue(
			'Sensei_Main' == get_class( Sensei() ),
			'The Sensei() function does not return an instance of class WooThemes_Sensei'
		);

	}

	function testSenseiFunctionReturnSameSenseiInstance() {
		$this->assertSame( Sensei(), Sensei(), 'Sensei() should always return the same Sensei_Main instance' );
	}

	/**
	 * Tests to make sure the version is set on new installs but the legacy update flag option isn't set.
	 */
	public function testUpdateNewInstall() {
		$this->resetUpdateOptions();

		Sensei()->update();

		$this->assertEquals( Sensei()->version, get_option( 'sensei-version' ) );
		$this->assertEmpty( get_option( 'sensei_enrolment_legacy' ), 'Legacy update flag option should not be set on new installs' );
	}

	/**
	 * Tests to make sure the version and legacy update flag option are set when both course and progress
	 * artifacts exist.
	 */
	public function testUpdateOldInstallWithProgress() {
		$user_id   = $this->factory->user->create();
		$course_id = $this->factory->course->create();
		Sensei_Utils::user_start_course( $user_id, $course_id );

		$this->resetUpdateOptions();

		Sensei()->update();

		$this->assertEquals( Sensei()->version, get_option( 'sensei-version' ) );
		$this->assertNotEmpty( get_option( 'sensei_enrolment_legacy' ), 'Legacy update flag option should be set on updates even when course and progress artifacts exist' );
	}

	/**
	 * Tests to make sure the version is set on v1 updates and the legacy update flag option is set when there are
	 * progress artifacts.
	 */
	public function testUpdatev1UpdateWithProgress() {
		$user_id   = $this->factory->user->create();
		$course_id = $this->factory->course->create();
		Sensei_Utils::user_start_course( $user_id, $course_id );

		$this->resetUpdateOptions();

		update_option( 'woothemes-sensei-version', '1.9.0' );
		update_option( 'woothemes-sensei-settings', [ 'settings' => true ] );

		Sensei()->update();

		$this->assertEquals( Sensei()->version, get_option( 'sensei-version' ) );
		$this->assertNotEmpty( get_option( 'sensei_enrolment_legacy' ), 'Legacy update flag option should be set during v1 updates with progress artifacts' );
	}

	/**
	 * Tests to make sure the version is set on v1 updates and the legacy update flag option is NOT set when there are
	 * no progress artifacts.
	 */
	public function testUpdatev1UpdateWithoutProgress() {
		$this->resetUpdateOptions();

		update_option( 'woothemes-sensei-version', '1.9.0' );
		update_option( 'woothemes-sensei-settings', [ 'settings' => true ] );

		Sensei()->update();

		$this->assertEquals( Sensei()->version, get_option( 'sensei-version' ) );
		$this->assertEmpty( get_option( 'sensei_enrolment_legacy' ), 'Legacy update flag option should NOT be set during v1 updates without progress artifacts' );
	}

	/**
	 * Tests to make sure the version is set on v2 updates and the legacy update flag option is set, even without
	 * progress artifacts.
	 */
	public function testUpdatev2UpdateWithoutProgress() {
		$this->resetUpdateOptions();

		update_option( 'sensei-version', '2.4.0' );

		Sensei()->update();

		$this->assertEquals( Sensei()->version, get_option( 'sensei-version' ) );
		$this->assertNotEmpty( get_option( 'sensei_enrolment_legacy' ), 'Legacy update flag option should be set during v2 updates with known previous version' );
	}

	/**
	 * Tests to make sure the version is set on v2 updates and the legacy update flag option is set when the previous
	 * version wasn't known but there were progress artifacts.
	 */
	public function testUpdatev2UpdateWithProgress() {
		$user_id   = $this->factory->user->create();
		$course_id = $this->factory->course->create();
		Sensei_Utils::user_start_course( $user_id, $course_id );

		$this->resetUpdateOptions();

		Sensei()->update();

		$this->assertEquals( Sensei()->version, get_option( 'sensei-version' ) );
		$this->assertNotEmpty( get_option( 'sensei_enrolment_legacy' ), 'Legacy update flag option should be set during v2 updates with progress' );
	}

	/**
	 * Resets the update options.
	 */
	private function resetUpdateOptions() {
		delete_option( 'sensei-version' );
		delete_option( 'sensei_enrolment_legacy' );
	}

	/**
	 * Testing the version numbers before releasing the plugin.
	 *
	 * The version number in the plugin information block should match the version number specified in the code.
	 */
	function testVersionNumber() {

		// make sure the version number was set on the new sensei instance
		$this->assertTrue( isset( Sensei()->version ), 'The version number is not set on the global Sensei object' );
	}
}
