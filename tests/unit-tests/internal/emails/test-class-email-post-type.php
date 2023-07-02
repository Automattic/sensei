<?php

namespace SenseiTest\Internal\Emails;

use Sensei\Internal\Emails\Email_Post_Type;
use WP_Post_Type;

/**
 * Tests for the Email_Post_Type class.
 *
 * @covers \Sensei\Internal\Emails\Email_Post_Type
 */
class Email_Post_Type_Test extends \WP_UnitTestCase {

	use \Sensei_Test_Redirect_Helpers;
	use \Sensei_Test_Login_Helpers;

	public function testRegisterPostType_WhenCalled_RegistersEmailPostType() {
		/* Arrange. */
		$email_post_type = Email_Post_Type::instance();

		/* Act. */
		$email_post_type->register_post_type();

		/* Assert. */
		$this->assertTrue( post_type_exists( 'sensei_email' ) );
	}

	public function testInit_WhenCalled_AddsInitAction() {
		/* Arrange. */
		$email_post_type = Email_Post_Type::instance();

		/* Act. */
		$email_post_type->init();

		/* Assert. */
		$priority = has_action( 'init', [ $email_post_type, 'register_post_type' ] );
		$this->assertSame( 10, $priority );
	}

	public function testInit_WhenCalled_AddsLoadEditAction() {
		/* Arrange. */
		$email_post_type = Email_Post_Type::instance();

		/* Act. */
		$email_post_type->init();

		/* Assert. */
		$priority = has_action( 'load-edit.php', [ $email_post_type, 'maybe_redirect_to_listing' ] );
		$this->assertSame( 10, $priority );
	}


	public function testEnableEmailTemplateEditor_WhenIsAdminAndPostTypeIsEmail_ReturnsTrue() {
		/* Arrange. */
		$original = false;
		set_current_screen( 'edit-post' );
		$current_post_type = new WP_Post_Type( Email_Post_Type::POST_TYPE );

		$email_post_type = Email_Post_Type::instance();

		/* Act. */
		$result = $email_post_type->enable_email_template_editor( $original, $current_post_type );

		/* Assert. */
		$this->assertSame( true, $result );
	}


	public function testEnableEmailTemplateEditorWhenIsNotTheEmailPostType_ReturnsOriginal() {
		/* Arrange. */
		$original          = false;
		$current_post_type = new WP_Post_Type( 'other-post-type' );
		$email_post_type   = Email_Post_Type::instance();
		set_current_screen( 'edit-post' );

		/* Act. */
		$result = $email_post_type->enable_email_template_editor( $original, $current_post_type );

		/* Assert. */
		$this->assertSame( $original, $result );
	}

	public function testEnableEmailTemplateEditorWhenIsNotTheAdminInterface_ReturnsOriginal() {
		/* Arrange. */
		$original          = false;
		$current_post_type = new WP_Post_Type( Email_Post_Type::POST_TYPE );
		$email_post_type   = Email_Post_Type::instance();

		/* Act. */
		$result = $email_post_type->enable_email_template_editor( $original, $current_post_type );

		/* Assert. */
		$this->assertSame( $original, $result );
	}

	public function testInit_WhenCalled_AddsHookForRemovingEmailDeletingCap() {
		/* Arrange. */
		$email_post_type = Email_Post_Type::instance();

		/* Act. */
		$email_post_type->init();

		/* Assert. */
		$this->assertEquals( 10, has_action( 'map_meta_cap', [ $email_post_type, 'remove_cap_of_deleting_email' ] ) );
	}

	public function testUserCap_WhenCalledForAdminUserForEmailPost_DoesNotAllowToDelete() {
		/* Arrange. */
		$this->login_as_admin();
		$email_post_type = Email_Post_Type::instance();
		$email_post_type->register_post_type();
		$email_id = $this->factory->post->create(
			[
				'post_type'   => 'sensei_email',
				'post_status' => 'publish',
			]
		);

		/* Act. */
		$email_post_type->init();

		/* Assert. */
		$this->assertFalse( current_user_can( 'delete_post', $email_id ) );
	}

	public function testUserCap_WhenCalledForEditorUser_DoesNotAllowToDelete() {
		/* Arrange. */
		$this->login_as_editor();
		$email_post_type = Email_Post_Type::instance();
		$email_post_type->register_post_type();
		$email_id = $this->factory->post->create(
			[
				'post_type'   => 'sensei_email',
				'post_status' => 'publish',
			]
		);

		/* Act. */
		$email_post_type->init();

		/* Assert. */
		$this->assertFalse( current_user_can( 'delete_post', $email_id ) );
	}

	/**
	 * Test that the `remove_cap_of_deleting_email` method returns the expected caps.
	 *
	 * @dataProvider providerRemoveCapOfDeletingEmail_ArgumentsGiven_ReturnsMatchingCaps
	 */
	public function testRemoveCapOfDeletingEmail_ArgumentsGiven_ReturnsMatchingCaps( $caps, $cap, $post_type, $expected_caps ) {
		/* Arrange. */
		$this->login_as_admin();
		$user_id = $this->get_user_by_role( 'admin' );

		$email_post_type = Email_Post_Type::instance();
		$email_post_type->init();

		$email_id = $this->factory->post->create(
			[
				'post_type'   => $post_type,
				'post_status' => 'publish',
			]
		);

		/* Act. */
		$actual_caps = $email_post_type->remove_cap_of_deleting_email( $caps, $cap, $user_id, array( $email_id ) );

		/* Assert. */
		self::assertSame( $expected_caps, $actual_caps );
	}

	public function providerRemoveCapOfDeletingEmail_ArgumentsGiven_ReturnsMatchingCaps(): array {
		return array(
			'delete_post cap for email post type' => array(
				array( 'delete_published_posts' ),
				'delete_post',
				'sensei_email',
				array(
					'delete_published_posts',
					'do_not_allow',
				),
			),
			'other cap'                           => array(
				array( 'delete_published_posts' ),
				'edit_post',
				'sensei_email',
				array( 'delete_published_posts' ),
			),
			'delete_post cap for other post type' => array(
				array( 'delete_published_posts' ),
				'delete_post',
				'post',
				array( 'delete_published_posts' ),
			),
		);
	}

	public function testCurrentUserCan_HookNotAttachedAndEmailProvided_AllowsDeleting() {
		/* Arrange. */
		$this->login_as_admin();

		$email_post_type = Email_Post_Type::instance();
		$email_post_type->init();

		$email_id = $this->factory->post->create(
			[
				'post_type'   => 'sensei_email',
				'post_status' => 'publish',
			]
		);

		remove_action( 'map_meta_cap', [ $email_post_type, 'remove_cap_of_deleting_email' ], 10 );

		/* Act. */
		$actual = current_user_can( 'delete_post', $email_id );

		/* Assert. */
		$this->assertTrue( $actual );
	}

	public function testEmailDeleteCapHook_WhenCalled_DoesNotAffectOtherPostTypes() {
		/* Arrange. */
		$this->login_as_admin();
		$email_post_type = Email_Post_Type::instance();
		$email_post_type->register_post_type();
		$post_id = $this->factory->post->create(
			[
				'post_status' => 'publish',
			]
		);

		/* Act. */
		$email_post_type->init();

		/* Assert. */
		$this->assertTrue( current_user_can( 'delete_post', $post_id ) );
	}

	public function testMaybeRedirectToListing_WhenCalledWithEmailPostType_RedirectsToEmailsPage() {
		/* Arrange. */
		$email_post_type   = Email_Post_Type::instance();
		$_GET['post_type'] = 'sensei_email';
		$this->prevent_wp_redirect();

		/* Expect & Act. */
		self::expectException( \Sensei_WP_Redirect_Exception::class );
		self::expectExceptionCode( 301 );
		self::getExpectedExceptionMessage( admin_url( 'admin.php?page=sensei-settings&tab=email-notification-settings' ) );
		$email_post_type->maybe_redirect_to_listing();
	}

	public function testMaybeRedirectToListing_WhenCalledWithNonEmailPostType_DoesNotRedirectToEmailsPage() {
		/* Arrange. */
		$email_post_type   = Email_Post_Type::instance();
		$_GET['post_type'] = 'non_sensei_email';
		$this->prevent_wp_redirect();

		/* Act. */
		$redirect_happened = false;
		try {
			$email_post_type->maybe_redirect_to_listing();
		} catch ( \Sensei_WP_Redirect_Exception $e ) {
			$redirect_happened = true;
		}

		/* Assert. */
		$this->assertFalse( $redirect_happened );
	}

	public function testMaybeRedirectToListing_WhenCalledWithEmailPostTypeAndInvalidBulkAction_RedirectsToEmailsPage() {
		/* Arrange. */
		$email_post_type   = Email_Post_Type::instance();
		$_GET['post_type'] = 'sensei_email';
		$_GET['action']    = '-1';
		$this->prevent_wp_redirect();

		/* Expect & Act. */
		self::expectException( \Sensei_WP_Redirect_Exception::class );
		self::expectExceptionCode( 301 );
		self::getExpectedExceptionMessage( admin_url( 'admin.php?page=sensei-settings&tab=email-notification-settings' ) );
		$email_post_type->maybe_redirect_to_listing();
	}

	public function testMaybeRedirectToListing_WhenCalledWithEmailPostTypeAndValidBulkAction_DoesNotRedirectToEmailsPage() {
		/* Arrange. */
		$email_post_type   = Email_Post_Type::instance();
		$_GET['post_type'] = 'sensei_email';
		$_GET['action']    = 'disable-bulk-action';
		$this->prevent_wp_redirect();

		/* Act. */
		$redirect_happened = false;
		try {
			$email_post_type->maybe_redirect_to_listing();
		} catch ( \Sensei_WP_Redirect_Exception $e ) {
			$redirect_happened = true;
		}

		/* Assert. */
		$this->assertFalse( $redirect_happened );
	}
}
