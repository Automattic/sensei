<?php

/**
 * Tests for Sensei_Block_Contact_Teacher class.
 *
 * @group course-structure
 */
class Sensei_Block_Contact_Teacher_Test extends WP_UnitTestCase {

	use Sensei_Course_Enrolment_Manual_Test_Helpers;
	use Sensei_Test_Login_Helpers;

	/**
	 * Contact Teacher Block.
	 *
	 * @var Sensei_Block_Contact_Teacher
	 */
	private $block;

	public function setUp() {
		parent::setUp();

		$GLOBALS['post']        = (object) [
			'ID'        => 0,
			'post_type' => 'course',
		];
		$_SERVER['REQUEST_URI'] = '/course/test/';
		$this->block            = new Sensei_Block_Contact_Teacher();

		$this->login_as_student();
	}

	/**
	 * Test the saved block content is used for the button, and it is wrapped in a form
	 */
	public function testFormWithContactIsAdded() {

		$content = '<div><a class="wp-block-button__link">Contact teacher</a></div>';
		$output  = $this->block->render_contact_teacher_block( [], $content );

		$this->assertContains( '<form action="#private_message" method="get">', $output );
		$this->assertContains( $content, $output );
	}

	/**
	 * Test success message is displayed after submitting.
	 */
	public function testSuccessMessageDisplayed() {
		$_GET['send'] = 'complete';

		$output = $this->block->render_contact_teacher_block( [], '<div><a class="wp-block-button__link">Contact teacher</a></div>' );

		$this->assertContains( 'Your private message has been sent.', $output );
	}

	/**
	 * Test a private message form is present.
	 */
	public function testMessageForm() {

		$output = $this->block->render_contact_teacher_block( [], '<div><a class="wp-block-button__link">Contact teacher</a></div>' );

		$this->assertRegExp( '|<form.*<input.* name="sensei_message_teacher_nonce" .*</form>|ms', $output );
		$this->assertRegExp( '|<form.*<textarea.* name="contact_message" .*</form>|ms', $output );
	}

}
