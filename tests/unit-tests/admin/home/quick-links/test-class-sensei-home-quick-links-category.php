<?php
/**
 * This file contains the Sensei_Home_Quick_Links_Category_Test class.
 *
 * @package sensei
 */


/**
 * Tests for Sensei_Home_Quick_Links_Category class.
 *
 * @covers Sensei_Home_Quick_Links_Category
 */
class Sensei_Home_Quick_Links_Category_Test extends WP_UnitTestCase {

	/**
	 * Test category properties are correctly set.
	 */
	public function testCategoryKeepsCorrectTitleAndItems() {
		$the_title = 'Some random title';
		$items     = [ new Sensei_Home_Quick_Links_Item( 'category title 1', 'category url 1' ) ];

		$category = new Sensei_Home_Quick_Links_Category( $the_title, $items );

		$this->assertEquals( $the_title, $category->title );
		$this->assertSame( $items, $category->items );
	}

	/**
	 * Test category properties are correctly set.
	 */
	public function testCategorySetsEmptyArrayForItemsByDefault() {
		$the_title = 'Some random title';

		$category = new Sensei_Home_Quick_Links_Category( $the_title );

		$this->assertEquals( $the_title, $category->title );
		$this->assertIsArray( $category->items );
		$this->assertEmpty( $category->items );
	}

}
