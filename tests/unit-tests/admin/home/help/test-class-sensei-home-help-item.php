<?php
/**
 * This file contains the Sensei_Home_Help_Item_Test class.
 *
 * @package sensei
 */


/**
 * Tests for Sensei_Home_Help_Item class.
 *
 * @covers Sensei_Home_Help_Item
 */
class Sensei_Home_Help_Item_Test extends WP_UnitTestCase {

	/**
	 * Test item properties are correctly set.
	 */
	public function testItemKeepsCorrectAttributes() {
		$the_title      = 'Some random title';
		$the_url        = 'Some random url';
		$the_icon       = 'Some random icon';
		$the_extra_link = new Sensei_Home_Help_Extra_Link( 'random label', 'random url' );

		$item = new Sensei_Home_Help_Item( $the_title, $the_url, $the_icon, $the_extra_link );

		$this->assertEquals( $the_title, $item->get_title() );
		$this->assertEquals( $the_url, $item->get_url() );
		$this->assertEquals( $the_icon, $item->get_icon() );
		$this->assertEquals( $the_extra_link, $item->get_extra_link() );
	}

	/**
	 * Test item properties are correctly set.
	 */
	public function testItemSetsCorrectDefaultValuesForOptionalAttributes() {
		$the_title = 'Some random title';
		$the_url   = 'Some random url';

		$item = new Sensei_Home_Help_Item( $the_title, $the_url );

		$this->assertNull( $item->get_icon() );
		$this->assertNull( $item->get_extra_link() );
	}

	/**
	 * Test item accepts url as null (to disable the item).
	 */
	public function testItemAcceptsNullAsUrl() {
		$the_title = 'Some random title';
		$the_url   = null;

		$item = new Sensei_Home_Help_Item( $the_title, $the_url );

		$this->assertNull( $item->get_url() );
	}
}
