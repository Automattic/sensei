<?php
/**
 * This file contains the Sensei_Home_Promo_Banner_Test class.
 *
 * @package sensei
 */


/**
 * Tests for Sensei_Home_Promo_Banner class.
 *
 * @covers Sensei_Home_Promo_Banner
 */
class Sensei_Home_Promo_Banner_Test extends WP_UnitTestCase {

	/**
	 * Assert that attributes are correctly handled.
	 *
	 * @dataProvider dataForTestPromoBannerKeepsAttributes
	 */
	public function testPromoBannerKeepsAttributes( $expected ) {
		$banner = new Sensei_Home_Promo_Banner( $expected );
		$this->assertEquals( $expected, $banner->is_visible() );
	}

	public function dataForTestPromoBannerKeepsAttributes() {
		return [
			[ true ],
			[ false ],
		];
	}

}
