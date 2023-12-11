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

	public function testGetSystemData_Always_ContainsHppsStatus() {
		/* Arrrange. */
		$usage_tracking = Sensei_Usage_Tracking::get_instance();

		/* Act. */
		$data = $usage_tracking->get_system_data();

		/* Assert. */
		$this->assertArrayHasKey( 'is_hpps_enabled', $data );
	}

	public function testGetSystemData_HppsEnabled_IsHppsEnabledIsOne() {
		/* Arrrange. */
		Sensei()->settings->settings['experimental_progress_storage'] = true;
		$usage_tracking = Sensei_Usage_Tracking::get_instance();

		/* Act. */
		$data = $usage_tracking->get_system_data();

		/* Assert. */
		$this->assertSame( 1, $data['is_hpps_enabled'] );
	}

	public function testGetSystemData_HppsDisabled_IsHppsEnabledIsZero() {
		/* Arrrange. */
		Sensei()->settings->settings['experimental_progress_storage'] = false;
		$usage_tracking = Sensei_Usage_Tracking::get_instance();

		/* Act. */
		$data = $usage_tracking->get_system_data();

		/* Assert. */
		$this->assertSame( 0, $data['is_hpps_enabled'] );
	}

	public function testGetSystemData_HppsRepositoryNotSet_HppsRepositoryHasDefaultCommentsValue() {
		/* Arrrange. */
		unset( Sensei()->settings->settings['experimental_progress_storage_repository'] );
		$usage_tracking = Sensei_Usage_Tracking::get_instance();

		/* Act. */
		$data = $usage_tracking->get_system_data();

		/* Assert. */
		$this->assertSame( 'comments', $data['hpps_repository'] );
	}

	public function testGetSystemData_HppsRepositorySet_HppsRepositoryHasSameValue() {
		/* Arrrange. */
		Sensei()->settings->settings['experimental_progress_storage_repository'] = 'a';
		$usage_tracking = Sensei_Usage_Tracking::get_instance();

		/* Act. */
		$data = $usage_tracking->get_system_data();

		/* Assert. */
		$this->assertSame( 'a', $data['hpps_repository'] );
	}
}
