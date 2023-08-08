<?php
/**
 * This file contains the Sensei_Home_Promo_Banner_Provider_Test class.
 *
 * @package sensei
 */


/**
 * Tests for Sensei_Home_Promo_Banner_Provider class.
 *
 * @covers Sensei_Home_Promo_Banner_Provider
 */
class Sensei_Home_Promo_Banner_Provider_Test extends WP_UnitTestCase {

	/**
	 * The provider under test.
	 *
	 * @var Sensei_Home_Promo_Banner_Provider
	 */
	private $provider;

	/**
	 * Whether the promotional banner filter was initially overridden or not.
	 *
	 * @var bool
	 */
	private $had_promo_banner_filter_overridden;

	/**
	 * Setup.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->had_promo_banner_filter_overridden = has_filter( 'sensei_home_promo_banner_show', '__return_false' );
		$this->provider                           = new Sensei_Home_Promo_Banner_Provider();
	}

	/**
	 * Tear down.
	 */
	public function tearDown(): void {
		// Clean filter after test if it wasn't set initially.
		if ( ! $this->had_promo_banner_filter_overridden ) {
			remove_filter( 'sensei_home_promo_banner_show', '__return_false' );
		}

		parent::tearDown();
	}

	/**
	 * Assert that promo banner is visible by default.
	 */
	public function testProviderReturnsVisibleBannerByDefault() {

		$banner = $this->provider->get();

		$this->assertIsArray( $banner );
		$this->assertArrayHasKey( 'is_visible', $banner );
		$this->assertTrue( $banner['is_visible'], 'Promotional banner must be visible by default.' );
	}

	/**
	 * Assert that promo banner is not visible when filter overridden.
	 */
	public function testProviderReturnsNonVisibleBannerWhenFilterOverridden() {
		add_filter( 'sensei_home_promo_banner_show', '__return_false' );

		$banner = $this->provider->get();

		$this->assertIsArray( $banner );
		$this->assertArrayHasKey( 'is_visible', $banner );
		$this->assertFalse( $banner['is_visible'], 'Promotional banner must not be visible when filter overridden.' );
	}
}
