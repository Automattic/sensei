<?php

namespace SenseiTest\Internal\Emails;

use Sensei\Internal\Emails\Sensei_Email_Post_Type;

/**
 * Tests for the Sensei_Email_Post_Type class.
 *
 * @covers \Sensei\Internal\Emails\Sensei_Email_Post_Type
 */
class Sensei_Email_Post_Type_Test extends \WP_UnitTestCase {
	public function testRegisterPostType_WhenCalled_RegistersEmailPostType() {
		/* Arrange. */
		$email_post_type = new Sensei_Email_Post_Type();

		/* Act. */
		$email_post_type->register_post_type();

		/* Assert. */
		$this->assertTrue( post_type_exists( 'sensei_email' ) );
	}

	public function testInit_WhenCalled_AddsInitAction() {
		/* Arrange. */
		$email_post_type = new Sensei_Email_Post_Type();

		/* Act. */
		$email_post_type->init();

		/* Assert. */
		$priority = has_action( 'init', [ $email_post_type, 'register_post_type' ] );
		$this->assertSame( 10, $priority );
	}
}
