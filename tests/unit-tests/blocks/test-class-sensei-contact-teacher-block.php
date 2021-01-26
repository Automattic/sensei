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

	public function tearDown() {
		parent::tearDown();
		WP_Block_Type_Registry::get_instance()->unregister( 'sensei-lms/button-contact-teacher' );
	}

	/**
	 * Test the saved block content is used for the button, with link added to open the form.
	 */
	public function testHrefAttributeAdded() {

		$output = $this->block->render_contact_teacher_block( [], '<div><a class="wp-block-button__link">Contact teacher</a></div>' );

		$this->assertRegExp( '|<a href="/course/test/\?contact=course#private_message".*>Contact teacher</a>|', $output );
	}

	/**
	 * Test success message is displayed after submitting.
	 */
	public function testSuccessMessageDisplayed() {
		$property = new ReflectionProperty( 'Sensei_Notices', 'has_printed' );
		$property->setAccessible( true );
		$property->setValue( Sensei()->notices, false );

		$_GET['send'] = 'complete';

		$this->block->render_contact_teacher_block( [], '<div><a class="wp-block-button__link">Contact teacher</a></div>' );

		ob_start();
		Sensei()->notices->maybe_print_notices();
		$notices = ob_get_clean();

		$this->assertContains( 'Your private message has been sent.', $notices );
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
