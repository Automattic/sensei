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
	use Sensei_Test_Login_Helpers;

	/**
	 * The class under test.
	 *
	 * @var Sensei_Home_Quick_Links_Provider
	 */
	private $provider;

	/**
	 * Setup.
	 */
	public function setUp() {
		parent::setUp();
		$this->provider = new Sensei_Home_Quick_Links_Provider();
	}

	public function testGet_AsTeacher_ReturnsEmptyArray() {
		// Arrange
		$this->login_as_teacher();

		// Act
		$categories = $this->provider->get();

		// Assert
		$this->assertEquals( [], $categories );
	}

	public function testGet_AsAdmin_ReturnsCorrectFormat() {
		// Arrange
		$this->login_as_admin();

		// Act
		$categories = $this->provider->get();

		// Assert
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
