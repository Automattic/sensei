<?php
/**
 * This file contains the Sensei_Home_Quick_Links_Provider_Test class.
 *
 * @package sensei
 */


/**
 * Tests for Sensei_Home_Quick_Links_Provider class.
 *
 * @covers Sensei_Home_Quick_Links_Provider
 */
class Sensei_Home_Quick_Links_Provider_Test extends WP_UnitTestCase {

	/**
	 * The class under test.
	 *
	 * @var Sensei_Home_Quick_Links_Provider
	 */
	private $provider;

	/**
	 * Setup.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->provider = new Sensei_Home_Quick_Links_Provider();
	}

	/**
	 * Assert that all elements returned by the provider are a correct Sensei_Home_Quick_Links_Category.
	 */
	public function testAllOutputAreCorrectQuickLinksCategories() {
		$categories = $this->provider->get();

		foreach ( $categories as $category ) {
			$this->assertIsArray( $category );
			$this->assertArrayHasKey( 'title', $category );
			$this->assertIsString( $category['title'] );
			$this->assertArrayHasKey( 'items', $category );
			$this->assertIsArray( $category['items'] );
			foreach ( $category['items'] as $item ) {
				$this->assertArrayHasKey( 'title', $item );
				$this->assertIsString( $item['title'] );
				$this->assertArrayHasKey( 'url', $item );
				$this->assertIsString( $item['url'] );
			}
		}
	}
}
