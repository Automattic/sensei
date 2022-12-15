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
	 * Did the test have the upsell filter overridden?
	 *
	 * @var bool
	 */
	private $had_upsell_filter_overridden;

	/**
	 * Setup.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->had_upsell_filter_overridden = has_filter( 'sensei_home_support_ticket_creation_upsell_show', '__return_false' );
		$this->provider                     = new Sensei_Home_Help_Provider();
	}

	/**
	 * Tear down.
	 */
	public function tearDown(): void {
		// Clean filter after test if it wasn't set initially.
		if ( ! $this->had_upsell_filter_overridden ) {
			remove_filter( 'sensei_home_support_ticket_creation_upsell_show', '__return_false' );
		}

		parent::tearDown();
	}


	/**
	 * Assert that all elements returned by the provider have the correct structure.
	 */
	public function testAllResultsHaveCorrectHelpStructure() {
		$categories = $this->provider->get();

		foreach ( $categories as $category ) {
			$this->assertIsArray( $category );
			$this->assertArrayHasKey( 'title', $category );
			$this->assertIsString( $category['title'] );
			$this->assertArrayHasKey( 'items', $category );
			$this->assertIsArray( $category['items'] );
			foreach ( $category['items'] as $item ) {
				$this->assertIsString( $item['title'] );
			}
		}
	}

	public function testCreateSupportTicketIsDisabledAndHasExtraLinkByDefault() {
		$categories = $this->provider->get();

		$create_ticket_item = $this->get_item_by_text( $categories, __( 'Create a support ticket', 'sensei-lms' ) );
		$this->assertNotNull( $create_ticket_item, 'Create support ticket item could not be found!' );

		// Create ticket item is disabled.
		$this->assertNull( $create_ticket_item['url'] );
		// Create ticket item contains extra link.
		$this->assertIsArray( $create_ticket_item['extra_link'] );
		// Create ticket item has the 'lock' icon.
		$this->assertEquals( 'lock', $create_ticket_item['icon'] );
	}

	public function testCreateSupportTicketIsEnabledAndWithoutExtralinkWhenFilterIsOverrided() {
		add_filter( 'sensei_home_support_ticket_creation_upsell_show', '__return_false' );

		$categories = $this->provider->get();

		$create_ticket_item = $this->get_item_by_text( $categories, __( 'Create a support ticket', 'sensei-lms' ) );
		$this->assertNotNull( $create_ticket_item, 'Create support ticket item could not be found!' );
		// Create ticket item has a string as url.
		$this->assertIsString( $create_ticket_item['url'], 'URL must be valid when upsell is disabled.' );
		// Create ticket item does not contain extra link.
		$this->assertNull( $create_ticket_item['extra_link'], 'Extra link must be null since we expect the upsell to be disabled.' );
		// Create ticket item does not have any special icon.
		$this->assertNull( $create_ticket_item['icon'], 'Icon must be null since we expect to use default icon when upsell is disabled.' );

	}

	/**
	 * Given a list of categories retrieve an item matching by item's title.
	 *
	 * @param array[] $categories
	 * @param string $text
	 * @return array
	 */
	private function get_item_by_text( $categories, $text ) {
		foreach ( $categories as $category ) {
			foreach ( $category['items'] as $item ) {
				if ( $item['title'] === $text ) {
					return $item;
				}
			}
		}
		return null;
	}
}
