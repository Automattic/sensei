<?php
/**
 * This file contains the Sensei_Home_Help_Provider_Test class.
 *
 * @package sensei
 */


/**
 * Tests for Sensei_Home_Help_Provider class.
 *
 * @covers Sensei_Home_Help_Provider
 */
class Sensei_Home_Help_Provider_Test extends WP_UnitTestCase {

	/**
	 * The class under test.
	 *
	 * @var Sensei_Home_Help_Provider
	 */
	private $provider;

	/**
	 * The Sensei_Pro_Detector mock.
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
		$this->provider          = new Sensei_Home_Help_Provider( $this->pro_detector_mock );
	}

	/**
	 * Assert that all elements returned by the provider are a correct Sensei_Home_Quick_Links_Category.
	 */
	public function testAllOutputAreCorrectQuickLinksCategories() {
		$this->pro_detector_mock->expects( $this->once() )
			->method( 'is_loaded' )
			->willReturn( true );

		$categories = $this->provider->get();

		foreach ( $categories as $category ) {
			$this->assertInstanceOf( Sensei_Home_Help_Category::class, $category );
			$this->assertIsString( $category->get_title() );
			$this->assertIsArray( $category->get_items() );
			foreach ( $category->get_items() as $item ) {
				$this->assertIsString( $item->get_title() );
			}
		}
	}

	public function testCreateSupportTicketIsDisabledAndHasExtraLinkWhenSenseiProNotLoaded() {
		$this->pro_detector_mock->expects( $this->once() )
			->method( 'is_loaded' )
			->willReturn( false );

		$categories = $this->provider->get();

		$create_ticket_item = $this->get_item_by_text( $categories, __( 'Create a support ticket', 'sensei-lms' ) );
		$this->assertNotNull( $create_ticket_item, 'Create suppoort ticket item could not be found!' );

		// Create ticket item is disabled.
		$this->assertNull( $create_ticket_item->get_url() );
		// Create ticket item contains extra link.
		$this->assertInstanceOf( Sensei_Home_Help_Extra_Link::class, $create_ticket_item->get_extra_link() );
	}

	public function testCreateSupportTicketIsEnabledAndWithoutExtralinkWhenSenseiProIsLoaded() {
		$this->pro_detector_mock->expects( $this->once() )
			->method( 'is_loaded' )
			->willReturn( true );

		$categories = $this->provider->get();

		$create_ticket_item = $this->get_item_by_text( $categories, __( 'Create a support ticket', 'sensei-lms' ) );
		$this->assertNotNull( $create_ticket_item, 'Create suppoort ticket item could not be found!' );

		// Create ticket item has a string as url.
		$this->assertIsString( $create_ticket_item->get_url() );
		// Create ticket item does not contain extra link.
		$this->assertNull( $create_ticket_item->get_extra_link() );
	}

	/**
	 * Given a list of categories retrieve an item matching by item's title.
	 *
	 * @param Sensei_Home_Help_Category[] $categories
	 * @param string $text
	 * @return Sensei_Home_Help_Item
	 */
	private function get_item_by_text( $categories, $text ) {
		foreach ( $categories as $category ) {
			foreach ( $category->get_items() as $item ) {
				if ( $item->get_title() === $text ) {
					return $item;
				}
			}
		}
		return null;
	}
}
