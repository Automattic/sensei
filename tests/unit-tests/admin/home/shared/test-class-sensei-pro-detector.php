<?php
/**
 * This file contains the Sensei_Pro_Detector_Test class.
 *
 * @package sensei
 */

/**
 * Tests for Sensei_Pro_Detector class.
 *
 * @covers Sensei_Pro_Detector
 */
class Sensei_Pro_Detector_Test extends WP_UnitTestCase {

	/**
	 * The class under test.
	 *
	 * @var Sensei_Pro_Detector
	 */
	private $pro_detector;

	/**
	 * Setup.
	 */
	public function setUp() {
		parent::setUp();
		$this->pro_detector = new Sensei_Pro_Detector();
		remove_filter( 'sensei_is_sensei_pro_active', '__return_true' );
	}

	/**
	 * Assert that `is_loaded` returns false by default.
	 */
	public function testIsLoadedReturnsFalseByDefault() {
		$is_pro_loaded = $this->pro_detector->is_loaded();

		$this->assertFalse( $is_pro_loaded, 'Sensei Pro detection must return false by default.' );
	}

	/**
	 * Assert that `is_loaded` returns true when filter overridden.
	 */
	public function testIsLoadedReturnsTrueWhenFilterOverridden() {
		add_filter( 'sensei_is_sensei_pro_active', '__return_true' );

		$is_pro_loaded = $this->pro_detector->is_loaded();

		$this->assertTrue( $is_pro_loaded, 'Sensei Pro detection must return true when filter is overridden.' );
	}
}
