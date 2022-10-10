<?php
/**
 * This file contains the Sensei_Home_Extensions_Provider_Test class.
 *
 * @package sensei
 */


/**
 * Tests for Sensei_Home_Extensions_Provider class.
 *
 * @covers Sensei_Home_Extensions_Provider
 */
class Sensei_Home_Extensions_Provider_Test extends WP_UnitTestCase {

	/**
	 * The remote data API mock.
	 *
	 * @var \PHPUnit\Framework\MockObject\MockObject|Sensei_Home_Remote_Data_API
	 */
	private $api_mock;


	/**
	 * The provider under test.
	 *
	 * @var Sensei_Home_Extensions_Provider
	 */
	private $provider;

	public function setUp() {
		parent::setUp();

		$this->api_mock = $this->createMock( Sensei_Home_Remote_Data_API::class );
		$this->provider = new Sensei_Home_Extensions_Provider( $this->api_mock );
	}

	public function tearDown() {
		remove_filter( 'sensei_home_extensions', [ $this, 'overrideWithSingleFakeExtension' ] );
		parent::tearDown();
	}

	public function testFetch_GivenEmptyPluginsResponseFromApi_ReturnsEmptyArray() {
		// Arrange
		$this->api_mock->expects( $this->once() )
			->method( 'fetch' )
			->willReturn( [ 'plugins' => [] ] );

		// Act
		$response = $this->provider->get();

		// Assert
		$this->assertIsArray( $response );
		$this->assertEmpty( $response );
	}

	public function testFetch_GivenMinimalPluginsResponse_ReturnsExpectedFullStructure() {
		// Arrange
		$this->api_mock->expects( $this->once() )
			->method( 'fetch' )
			->willReturn(
				[
					'plugins' => [
						[
							'title'        => 'plugin title',
							'product_slug' => 'plugin-slug',
						],
					],
				]
			);

		// Act
		$response = $this->provider->get();

		// Assert
		$this->assertIsArray( $response );
		$this->assertCount( 1, $response );
		$this->assertEquals(
			[
				'title'        => 'plugin title',
				'product_slug' => 'plugin-slug',
				'description'  => null,
				'image'        => null,
				'price'        => null,
				'more_url'     => null,
			],
			$response[0]
		);
	}

	public function testFetch_GivenFullPluginsResponse_ReturnsCorrectlyMappedStructure() {
		// Arrange
		$this->api_mock->expects( $this->once() )
			->method( 'fetch' )
			->willReturn(
				[
					'plugins' => [
						[
							'title'        => 'plugin title',
							'product_slug' => 'plugin-slug',
							'excerpt'      => 'plugin description',
							'image'        => 'https://image',
							'price'        => 1.23,
							'link'         => 'https://info',

						],
					],
				]
			);

		// Act
		$response = $this->provider->get();

		// Assert
		$this->assertIsArray( $response );
		$this->assertCount( 1, $response );
		$this->assertEquals(
			[
				'title'        => 'plugin title',
				'product_slug' => 'plugin-slug',
				'description'  => 'plugin description',
				'image'        => 'https://image',
				'price'        => 1.23,
				'more_url'     => 'https://info',
			],
			$response[0]
		);
	}

	public function testFetch_HookingFilter_ReturnsOverriddenResponse() {
		// Arrange
		$this->api_mock->expects( $this->once() )
			->method( 'fetch' )
			->willReturn(
				[
					'plugins' => [
						[
							'title'        => 'original title',
							'product_slug' => 'original-plugin-slug',
						],
					],
				]
			);

		// Act
		add_filter( 'sensei_home_extensions', [ $this, 'overrideWithSingleFakeExtension' ] );
		$response = $this->provider->get();

		// Assert
		$this->assertEquals(
			[
				[
					'title'        => 'fake title',
					'product_slug' => 'fake-plugin-slug',
				],
			],
			$response
		);
	}

	public function overrideWithSingleFakeExtension() {
		return [
			[
				'title'        => 'fake title',
				'product_slug' => 'fake-plugin-slug',
			],
		];
	}

}
