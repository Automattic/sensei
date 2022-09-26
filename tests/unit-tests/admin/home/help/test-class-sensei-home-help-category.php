<?php
/**
 * This file contains the Sensei_Home_Help_Category_Test class.
 *
 * @package sensei
 */


/**
 * Tests for Sensei_Home_Help_Category class.
 *
 * @covers Sensei_Home_Help_Category
 */
class Sensei_Home_Help_Category_Test extends WP_UnitTestCase {

	/**
	 * Test category properties are correctly set.
	 */
	public function testCategoryKeepsCorrectTitleAndItems() {
		$the_title = 'Some random title';
		$the_items = [ new Sensei_Home_Help_Item( 'item 1 title', 'item 2 url' ) ];

		$category = new Sensei_Home_Help_Category( $the_title, $the_items );

		$this->assertEquals( $the_title, $category->get_title() );
		$this->assertSame( $the_items, $category->get_items() );
	}

	/**
	 * Test category has empty items by default.
	 */
	public function testCategorySetsEmptyArrayForItemsByDefault() {
		$the_title = 'Some random title';

		$category = new Sensei_Home_Help_Category( $the_title );

		$this->assertEquals( $the_title, $category->get_title() );
		$this->assertIsArray( $category->get_items() );
		$this->assertEmpty( $category->get_items() );
	}

}
