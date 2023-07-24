<?php

/**
 * Tests for Sensei_Block_Contact_Teacher class.
 *
 * @group course-structure
 */
class Sensei_Block_Contact_Teacher_Test extends WP_UnitTestCase {

	use Sensei_Course_Enrolment_Manual_Test_Helpers;
	use Sensei_Test_Login_Helpers;

	public function setUp(): void {
		parent::setUp();

		$GLOBALS['post']        = (object) [
			'ID'        => 0,
			'post_type' => 'course',
		];
		$_SERVER['REQUEST_URI'] = '/course/test/';

		$this->login_as_student();
	}

	public function tearDown(): void {
		parent::tearDown();
		WP_Block_Type_Registry::get_instance()->unregister( 'sensei-lms/button-contact-teacher' );
	}

	/**
	 * Test the saved block content is used for the button, with link added to open the form.
	 */
	public function testHrefAttributeAdded() {
		$block  = new Sensei_Block_Contact_Teacher();
		$output = $block->render_contact_teacher_block( [], '<div><a class="wp-block-button__link">Contact teacher</a></div>' );

		$this->assertMatchesRegularExpression( '|<a href="/course/test/\?contact=course#private_message".*>Contact teacher</a>|', $output );
	}

	/**
	 * Test success message is displayed after submitting.
	 */
	public function testSuccessMessageDisplayed() {
		$property = new ReflectionProperty( 'Sensei_Notices', 'has_printed' );
		$property->setAccessible( true );
		$property->setValue( Sensei()->notices, false );

		$_GET['send'] = 'complete';

		new Sensei_Block_Contact_Teacher();

		ob_start();
		Sensei()->notices->maybe_print_notices();
		$notices = ob_get_clean();

		$this->assertStringContainsString( 'Your private message has been sent.', $notices );
	}

	/**
	 * Test a private message form is present.
	 */
	public function testMessageForm() {
		$block  = new Sensei_Block_Contact_Teacher();
		$output = $block->render_contact_teacher_block( [], '<div><a class="wp-block-button__link">Contact teacher</a></div>' );

		$this->assertMatchesRegularExpression( '|<form.*<input.* name="' . \Sensei_Messages::NONCE_FIELD_NAME . '" .*</form>|ms', $output );
		$this->assertMatchesRegularExpression( '|<form.*<textarea.* name="contact_message" .*</form>|ms', $output );
	}

}
