<?php

/**
 * Tests for Sensei_Contact_Teacher_Block class.
 *
 * @group course-structure
 */
class Sensei_Contact_Teacher_Block_Test extends WP_UnitTestCase {


	/**
	 * Test the course structure is used for rendering.
	 */
	public function testHrefAttributeAdded() {
		$GLOBALS['post']        = (object) [
			'ID'        => 0,
			'post_type' => 'course',
		];
		$_SERVER['REQUEST_URI'] = '/course/test/';
		$block                  = new Sensei_Contact_Teacher_Block();

		$output = $block->render_contact_teacher_block( [], '<div><a class="wp-block-button__link">Contact teacher</a></div>' );

		$this->assertDiscardWhitespace( '<div><a href="/course/test/?contact=course" class="wp-block-button__link">Contact teacher</a></div>', $output );
	}

}
