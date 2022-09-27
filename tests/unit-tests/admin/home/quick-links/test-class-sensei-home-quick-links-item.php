<?php
/**
 * This file contains the Sensei_Home_Quick_Links_Item_Test class.
 *
 * @package sensei
 */


/**
 * Tests for Sensei_Home_Quick_Links_Item class.
 *
 * @covers Sensei_Home_Quick_Links_Item
 */
class Sensei_Home_Quick_Links_Item_Test extends WP_UnitTestCase {

	/**
	 * Test item properties are correctly set.
	 */
	public function testItemKeepsCorrectTitleAndUrl() {
		$the_title = 'Some random title';
		$the_url   = 'Some random url';

		$item = new Sensei_Home_Quick_Links_Item( $the_title, $the_url );

		$this->assertEquals( $the_title, $item->get_title() );
		$this->assertEquals( $the_url, $item->get_url() );
	}

}
