<?php
/**
 * This file contains the Sensei_Status_Test class.
 *
 * @package sensei
 */

require_once SENSEI_TEST_FRAMEWORK_DIR . '/trait-sensei-course-enrolment-test-helpers.php';

/**
 * Tests for Sensei_Status_Test class.
 *
 * @group status
 */
class Sensei_Status_Test extends WP_UnitTestCase {
	use Sensei_Course_Enrolment_Test_Helpers;

	public function setUp(): void {
		parent::setUp();
		self::resetEnrolmentProviders();
		$this->prepareEnrolmentManager();
	}

	public static function tearDownAfterClass(): void {
		parent::tearDownAfterClass();
		self::resetEnrolmentProviders();
	}

	/**
	 * Tests to make sure legacy enrolment is not marked when fresh install.
	 */
	public function testLegacyEnrolmentInfoNoLegacy() {
		$debug_info = Sensei_Status::instance()->add_sensei_debug_info( [] );

		$this->assertEquals( 'Not applicable', $debug_info['sensei-lms']['fields']['legacy_enrolment']['value'] );
		$this->assertEquals( false, $debug_info['sensei-lms']['fields']['legacy_enrolment']['debug'] );
	}

	/**
	 * Tests to make sure legacy enrolment is not marked when fresh install.
	 */
	public function testLegacyEnrolmentInfoLegacyToday() {
		$time = time();
		update_option( 'sensei_enrolment_legacy', $time );

		$debug_info = Sensei_Status::instance()->add_sensei_debug_info( [] );

		$this->assertNotEquals( 'Not applicable', $debug_info['sensei-lms']['fields']['legacy_enrolment']['value'] );
		$this->assertEquals( $time, $debug_info['sensei-lms']['fields']['legacy_enrolment']['debug'] );
	}

	/**
	 * Show `Disabled` when learner calculation job is disabled.
	 */
	public function testIsCalculationPendingInfoDisabled() {
		add_filter( 'sensei_is_enrolment_background_job_enabled', '__return_false' );

		$debug_info = Sensei_Status::instance()->add_sensei_debug_info( [] );

		remove_filter( 'sensei_is_enrolment_background_job_enabled', '__return_false' );

		$this->assertEquals( 'Disabled', $debug_info['sensei-lms']['fields']['is_calculation_pending']['value'] );
	}

	/**
	 * Show `Pending` when learner calculation job is pending.
	 */
	public function testIsCalculationPendingInfoPending() {
		update_option( Sensei_Enrolment_Job_Scheduler::CALCULATION_VERSION_OPTION_NAME, md5( uniqid() ) );
		$debug_info = Sensei_Status::instance()->add_sensei_debug_info( [] );

		$this->assertEquals( 'Pending', $debug_info['sensei-lms']['fields']['is_calculation_pending']['value'] );
	}

	/**
	 * Show `Complete` when learner calculation job is completed.
	 */
	public function testIsCalculationPendingInfoCompleted() {
		$enrolment_manager = Sensei_Course_Enrolment_Manager::instance();
		$current_version   = $enrolment_manager->get_enrolment_calculation_version();

		update_option( Sensei_Enrolment_Job_Scheduler::CALCULATION_VERSION_OPTION_NAME, $current_version );
		$debug_info = Sensei_Status::instance()->add_sensei_debug_info( [] );

		$this->assertEquals( 'Complete', $debug_info['sensei-lms']['fields']['is_calculation_pending']['value'] );
	}
}
