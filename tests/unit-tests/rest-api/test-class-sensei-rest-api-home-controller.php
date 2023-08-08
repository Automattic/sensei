<?php
/**
 * File containing the Sensei_REST_API_Home_Controller_Test class.
 *
 * @package sensei-lms
 * @since   4.8.0
 */

/**
 * Class Sensei_REST_API_Home_Controller tests.
 *
 * @covers Sensei_REST_API_Home_Controller
 */
class Sensei_REST_API_Home_Controller_Test extends WP_UnitTestCase {
	use Sensei_Test_Login_Helpers;

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
	 * Notices provider mock.
	 *
	 * @var \PHPUnit\Framework\MockObject\MockObject|Sensei_Home_Notices_Provider
	 */
	private $notices_provider_mock;

	/**
	 * Controller under test.
	 *
	 * @var Sensei_REST_API_Home_Controller
	 */
	private $controller;


	public function setUp(): void {
		parent::setUp();

		$this->quick_links_provider_mock = $this->createMock( Sensei_Home_Quick_Links_Provider::class );
		$this->help_provider_mock        = $this->createMock( Sensei_Home_Help_Provider::class );
		$this->promo_provider_mock       = $this->createMock( Sensei_Home_Promo_Banner_Provider::class );
		$this->tasks_provider_mock       = $this->createMock( Sensei_Home_Tasks_Provider::class );
		$this->news_provider_mock        = $this->createMock( Sensei_Home_News_Provider::class );
		$this->guides_provider_mock      = $this->createMock( Sensei_Home_Guides_Provider::class );
		$this->notices_provider_mock     = $this->createMock( Sensei_Home_Notices_Provider::class );

		$this->controller = new Sensei_REST_API_Home_Controller(
			'namespace',
			$this->quick_links_provider_mock,
			$this->help_provider_mock,
			$this->promo_provider_mock,
			$this->tasks_provider_mock,
			$this->news_provider_mock,
			$this->guides_provider_mock,
			$this->notices_provider_mock
		);
	}

	public function testGetData_GivenAMockedQuickLinksProviderAsAdmin_ReturnsQuickLinksSection() {
		// Arrange
		$this->login_as_admin();
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

	public function testGetData_GivenAMockedQuickLinksProviderAsTeacher_NotIncluded() {
		// Arrange
		$this->login_as_teacher();

		// Act
		$result = $this->controller->get_data();

		// Assert
		$this->assertArrayNotHasKey( 'quick_links', $result );
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

	public function testGetData_GivenAMockedHelpProviderAsAdmin_ReturnsHelpSection() {
		// Arrange
		$this->login_as_admin();
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

	public function testGetData_GivenAMockedHelpProviderAsTeacher_NotIncluded() {
		// Arrange
		$this->login_as_teacher();

		// Act
		$result = $this->controller->get_data();

		// Assert
		$this->assertArrayNotHasKey( 'help', $result );
	}

	public function testGetData_GivenAMockedPromoProviderAsAdmin_ReturnsPromoSection() {
		// Arrange
		$this->login_as_admin();
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

	public function testGetData_GivenAMockedPromoProviderAsTeacher_NotIncluded() {
		// Arrange
		$this->login_as_teacher();

		// Act
		$result = $this->controller->get_data();

		// Assert
		$this->assertArrayNotHasKey( 'promo_banner', $result );
	}

	public function testGetData_GivenAMockedTasksProviderAsAdmin_ReturnsTasksSection() {
		// Arrange
		$this->login_as_admin();
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

	public function testGetData_GivenAMockedTasksProvideAsTeacher_NotIncluded() {
		// Arrange
		$this->login_as_teacher();

		// Act
		$result = $this->controller->get_data();

		// Assert
		$this->assertArrayNotHasKey( 'tasks', $result );
	}

	public function testGetData_GivenAMockedNoticesProviderAsAdmin_ReturnsNoticesSection() {
		// Arrange
		$this->login_as_admin();
		$mocked_response = [ 'mocked_response' ];
		$this->notices_provider_mock->expects( $this->once() )
			->method( 'get' )
			->willReturn( $mocked_response );

		// Act
		$result = $this->controller->get_data();

		// Assert
		$this->assertArrayHasKey( 'notices', $result );
		$this->assertEquals( $mocked_response, $result['notices'] );
	}

	public function testGetData_GivenAMockedNoticesProviderAsTeacher_Included() {
		// Arrange
		$this->login_as_teacher();

		// Act
		$result = $this->controller->get_data();

		// Assert
		$this->assertArrayHasKey( 'notices', $result );
	}
}
