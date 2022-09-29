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

	/**
	 * Setup.
	 */
	public function setUp() {
		parent::setUp();
		$this->provider = new Sensei_Home_Sensei_Pro_Promo_Provider();
	}

	/**
	 * Assert that promo provider returns true if Sensei Pro is not loaded.
	 */
	public function testProviderReturnsTrueIfSenseiProNotLoaded() {
		$this->markTestSkipped();

		$this->assertTrue( $this->provider->get() );
	}

	/**
	 * Assert that promo provider returns false if Sensei Pro is loaded.
	 */
	public function testProviderReturnsFalseIfSenseiProIsLoaded() {
		$this->markTestSkipped();

		$this->assertFalse( $this->provider->get() );
	}
}
