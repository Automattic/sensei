<?php
/**
 * This file contains the Sensei_Home_Sensei_Pro_Promo_Provider_Test class.
 *
 * @package sensei
 */


/**
 * Tests for Sensei_Home_Sensei_Pro_Promo_Provider class.
 *
 * @covers Sensei_Home_Sensei_Pro_Promo_Provider
 */
class Sensei_Home_Sensei_Pro_Promo_Provider_Test extends WP_UnitTestCase {

	/**
	 * The provider under test.
	 *
	 * @var Sensei_Home_Sensei_Pro_Promo_Provider
	 */
	private $provider;

	/** Sensei Pro detector mock.
	 *
	 * @var \PHPUnit\Framework\MockObject\MockObject|Sensei_Pro_Detector
	 */
	private $pro_detector_mock;

	/**
	 * Setup.
	 */
	public function setUp() {
		parent::setUp();

		$this->pro_detector_mock = $this->createMock( Sensei_Pro_Detector::class );
		$this->provider          = new Sensei_Home_Sensei_Pro_Promo_Provider( $this->pro_detector_mock );
	}

	/**
	 * Assert that promo provider returns true if Sensei Pro is not loaded.
	 */
	public function testProviderReturnsTrueIfSenseiProNotLoaded() {
		$this->pro_detector_mock->expects( $this->once() )
			->method( 'is_loaded' )
			->willReturn( false );

		$this->assertTrue( $this->provider->get() );
	}

	/**
	 * Assert that promo provider returns false if Sensei Pro is loaded.
	 */
	public function testProviderReturnsFalseIfSenseiProIsLoaded() {
		$this->pro_detector_mock->expects( $this->once() )
			->method( 'is_loaded' )
			->willReturn( true );

		$this->assertFalse( $this->provider->get() );
	}
}
