<?php
/**
 * File containing the Sensei_REST_API_Home_Controller_Test class.
 *
 * @package sensei-lms
 * @since   $$next-version$$
 */

/**
 * Class Sensei_REST_API_Home_Controller tests.
 *
 * @covers Sensei_REST_API_Home_Controller
 */
class Sensei_REST_API_Home_Controller_Test extends WP_UnitTestCase {

	/**
	 * Quick Links provider mock.
	 *
	 * @var \PHPUnit\Framework\MockObject\MockObject|Sensei_Home_Quick_Links_Provider
	 */
	private $quick_links_provider_mock;

	/**
	 * Help provider mock.
	 *
	 * @var \PHPUnit\Framework\MockObject\MockObject|Sensei_Home_Help_Provider
	 */
	private $help_provider_mock;

	/**
	 * Promo provider mock.
	 *
	 * @var \PHPUnit\Framework\MockObject\MockObject|Sensei_Home_Promo_Banner_Provider
	 */
	private $promo_provider_mock;

	/**
	 * Tasks provider mock.
	 *
	 * @var \PHPUnit\Framework\MockObject\MockObject|Sensei_Home_Tasks_Provider
	 */
	private $tasks_provider_mock;

	/**
	 * News provider mock.
	 *
	 * @var \PHPUnit\Framework\MockObject\MockObject|Sensei_Home_News_Provider
	 */
	private $news_provider_mock;

	/**
	 * Guides provider mock.
	 *
	 * @var \PHPUnit\Framework\MockObject\MockObject|Sensei_Home_Guides_Provider
	 */
	private $guides_provider_mock;

	/**
	 * Controller under test.
	 *
	 * @var Sensei_REST_API_Home_Controller
	 */
	private $controller;


	public function setUp() {
		parent::setUp();

		$this->quick_links_provider_mock = $this->createMock( Sensei_Home_Quick_Links_Provider::class );
		$this->help_provider_mock        = $this->createMock( Sensei_Home_Help_Provider::class );
		$this->promo_provider_mock       = $this->createMock( Sensei_Home_Promo_Banner_Provider::class );
		$this->tasks_provider_mock       = $this->createMock( Sensei_Home_Tasks_Provider::class );
		$this->news_provider_mock        = $this->createMock( Sensei_Home_News_Provider::class );
		$this->guides_provider_mock      = $this->createMock( Sensei_Home_Guides_Provider::class );

		$this->controller = new Sensei_REST_API_Home_Controller(
			'namespace',
			$this->quick_links_provider_mock,
			$this->help_provider_mock,
			$this->promo_provider_mock,
			$this->tasks_provider_mock,
			$this->news_provider_mock,
			$this->guides_provider_mock
		);
	}

	public function testGetData_GivenAMockedQuickLinksProvider_ReturnsQuickLinksSection() {
		// Arrange
		$mocked_response = [ 'mocked_response' ];
		$this->quick_links_provider_mock->expects( $this->once() )
			->method( 'get' )
			->willReturn( $mocked_response );

		// Act
		$result = $this->controller->get_data();

		// Assert
		$this->assertArrayHasKey( 'quick_links', $result );
		$this->assertEquals( $mocked_response, $result['quick_links'] );
	}

	public function testGetData_GivenAMockedNewsProvider_ReturnsNewsSection() {
		// Arrange
		$mocked_response = [ 'mocked_response' ];
		$this->news_provider_mock->expects( $this->once() )
			->method( 'get' )
			->willReturn( $mocked_response );

		// Act
		$result = $this->controller->get_data();

		// Assert
		$this->assertArrayHasKey( 'news', $result );
		$this->assertEquals( $mocked_response, $result['news'] );
	}

	public function testGetData_GivenAMockedGuidesProvider_ReturnsGuidesSection() {
		// Arrange
		$mocked_response = [ 'mocked_response' ];
		$this->guides_provider_mock->expects( $this->once() )
			->method( 'get' )
			->willReturn( $mocked_response );

		// Act
		$result = $this->controller->get_data();

		// Assert
		$this->assertArrayHasKey( 'guides', $result );
		$this->assertEquals( $mocked_response, $result['guides'] );
	}

	public function testGetData_GivenAMockedHelpProvider_ReturnsHelpSection() {
		// Arrange
		$mocked_response = [ 'mocked_response' ];
		$this->help_provider_mock->expects( $this->once() )
			->method( 'get' )
			->willReturn( $mocked_response );

		// Act
		$result = $this->controller->get_data();

		// Assert
		$this->assertArrayHasKey( 'help', $result );
		$this->assertEquals( $mocked_response, $result['help'] );
	}

	public function testGetData_GivenAMockedPromoProvider_ReturnsPromoSection() {
		// Arrange
		$mocked_response = [ 'mocked_response' ];
		$this->promo_provider_mock->expects( $this->once() )
			->method( 'get' )
			->willReturn( $mocked_response );

		// Act
		$result = $this->controller->get_data();

		// Assert
		$this->assertArrayHasKey( 'promo_banner', $result );
		$this->assertEquals( $mocked_response, $result['promo_banner'] );
	}

	public function testGetData_GivenAMockedTasksProvider_ReturnsTasksSection() {
		// Arrange
		$mocked_response = [ 'mocked_response' ];
		$this->tasks_provider_mock->expects( $this->once() )
			->method( 'get' )
			->willReturn( $mocked_response );

		// Act
		$result = $this->controller->get_data();

		// Assert
		$this->assertArrayHasKey( 'tasks', $result );
		$this->assertEquals( $mocked_response, $result['tasks'] );
	}
}
