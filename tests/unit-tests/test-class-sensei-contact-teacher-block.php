<?php

/**
 * Tests for Sensei_Block_Contact_Teacher class.
 *
 * @group course-structure
 */
class Sensei_Block_Contact_Teacher_Test extends WP_UnitTestCase {


	/**
	 * Test the course structure is used for rendering.
	 */
	public function testHrefAttributeAdded() {
		$GLOBALS['post']        = (object) [
			'ID'        => 0,
			'post_type' => 'course',
		];
		$_SERVER['REQUEST_URI'] = '/course/test/';
		$block                  = new Sensei_Block_Contact_Teacher();

		$output = $block->render_contact_teacher_block( [], '<div><a class="wp-block-button__link">Contact teacher</a></div>' );

		$this->assertDiscardWhitespace( '<div><a href="/course/test/?contact=course" class="wp-block-button__link">Contact teacher</a></div>', $output );
	}

}
