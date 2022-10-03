<?php
/**
 * This file contains the Sensei_Home_Help_Extra_Link_Test class.
 *
 * @package sensei
 */


/**
 * Tests for Sensei_Home_Help_Extra_Link class.
 *
 * @covers Sensei_Home_Help_Extra_Link
 */
class Sensei_Home_Help_Extra_Link_Test extends WP_UnitTestCase {

	/**
	 * Test item properties are correctly set.
	 */
	public function testItemKeepsCorrectAttributes() {
		$the_label = 'Some random label';
		$the_url   = 'Some random url';

		$link = new Sensei_Home_Help_Extra_Link( $the_label, $the_url );

		$this->assertEquals( $the_label, $link->get_label() );
		$this->assertEquals( $the_url, $link->get_url() );
	}
}
