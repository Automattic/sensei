<?php
/**
 * This file contains the Sensei_Usage_Tracking_Test class.
 *
 * @package sensei
 */

/**
 * Tests for the class `Sensei_Usage_Tracking`.
 *
 * @group usage-tracking
 */
class Sensei_Usage_Tracking_Test extends WP_UnitTestCase {

	/**
	 * Set up before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		Sensei_Test_Events::reset();
	}

	/**
	 * Tests to ensure legacy flags are reported in usage tracking.
	 */
	public function testLegacyFlagsReported() {
		$usage_tracking = Sensei_Usage_Tracking::get_instance();
		$test_key       = 'legacy_flag_with_front';

		$this->assertArrayNotHasKey( $test_key, $usage_tracking->get_system_data() );

		Sensei()->set_legacy_flag( Sensei_Main::LEGACY_FLAG_WITH_FRONT, true );

		$data = $usage_tracking->get_system_data();
		$this->assertArrayHasKey( $test_key, $data );
		$this->assertEquals( 1, $data[ $test_key ] );
	}
}
