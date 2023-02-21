<?php

namespace SenseiTest\Internal\Emails;

use Sensei\Internal\Emails\Email_List_Table_Actions;
use Sensei_Factory;
use Sensei_Test_Login_Helpers;
use Sensei_Test_Redirect_Helpers;
use Sensei_WP_Redirect_Exception;
use WPDieException;

/**
 * Tests for Sensei\Internal\Emails\Email_List_Table_Actions.
 *
 * @covers \Sensei\Internal\Emails\Email_List_Table_Actions
 */
class Email_List_Table_Actions_Test extends \WP_UnitTestCase {
	use Sensei_Test_Login_Helpers;
	use Sensei_Test_Redirect_Helpers;

	public function setUp(): void {
		parent::setUp();
		$this->factory = new Sensei_Factory();
	}

	public function tearDown(): void {
		parent::tearDown();
		$this->factory->tearDown();
	}

	public function testInit_WhenCalled_AddsEnableEmailHook() {
		/* Arrange. */
		$list_table_actions = new Email_List_Table_Actions();

		/* Act. */
		$list_table_actions->init();

		/* Assert. */
		$priority = has_action( 'post_action_enable-email', [ $list_table_actions, 'enable_email' ] );
		$this->assertSame( 10, $priority );
	}

	public function testInit_WhenCalled_AddsDisableEmailHook() {
		/* Arrange. */
		$list_table_actions = new Email_List_Table_Actions();

		/* Act. */
		$list_table_actions->init();

		/* Assert. */
		$priority = has_action( 'post_action_disable-email', [ $list_table_actions, 'disable_email' ] );
		$this->assertSame( 10, $priority );
	}

	public function testEnableEmail_WhenIncorrectAdminReferrer_ThrowsError() {
		/* Arrange. */
		$list_table_actions = new Email_List_Table_Actions();

		/* Assert. */
		$this->expectException( WPDieException::class );
		$this->expectExceptionMessage( 'The link you followed has expired.' );

		/* Act. */
		$list_table_actions->enable_email( 1 );
	}

	public function testEnableEmail_WhenUserHasInsufficientPermissions_ThrowsError() {
		/* Arrange. */
		$this->login_as_teacher();

		$post_id              = 1;
		$list_table_actions   = new Email_List_Table_Actions();
		$_REQUEST['_wpnonce'] = wp_create_nonce( 'enable-email-post_' . $post_id );

		/* Assert. */
		$this->expectException( WPDieException::class );
		$this->expectExceptionMessage( 'Insufficient permissions' );

		/* Act. */
		$list_table_actions->enable_email( $post_id );
	}

	public function testEnableEmail_WhenWrongPostType_ThrowsError() {
		/* Arrange. */
		$this->login_as_admin();

		$post_id              = $this->factory->post->create();
		$list_table_actions   = new Email_List_Table_Actions();
		$_REQUEST['_wpnonce'] = wp_create_nonce( 'enable-email-post_' . $post_id );

		/* Assert. */
		$this->expectException( WPDieException::class );
		$this->expectExceptionMessage( 'Invalid request' );

		/* Act. */
		$list_table_actions->enable_email( $post_id );
	}

	public function testEnableEmail_WhenRequestIsValid_ChangesTheEmailStatusToPublish() {
		/* Arrange. */
		$this->login_as_admin();
		$this->prevent_wp_redirect();

		$post_id              = $this->factory->email->create( [ 'post_status' => 'draft' ] );
		$list_table_actions   = new Email_List_Table_Actions();
		$_REQUEST['_wpnonce'] = wp_create_nonce( 'enable-email-post_' . $post_id );

		/* Act. */
		try {
			$list_table_actions->enable_email( $post_id );
		} catch ( Sensei_WP_Redirect_Exception $e ) {
			$redirect_status = $e->getCode();
		}

		/* Assert. */
		$this->assertSame( 'publish', get_post( $post_id )->post_status );
	}

	public function testEnableEmail_WhenRequestIsValid_Redirects() {
		/* Arrange. */
		$this->login_as_admin();
		$this->prevent_wp_redirect();

		$post_id              = $this->factory->email->create( [ 'post_status' => 'draft' ] );
		$list_table_actions   = new Email_List_Table_Actions();
		$_REQUEST['_wpnonce'] = wp_create_nonce( 'enable-email-post_' . $post_id );

		/* Assert. */
		$this->expectException( Sensei_WP_Redirect_Exception::class );

		/* Act. */
		$list_table_actions->enable_email( $post_id );
	}

	public function testEnableEmail_WhenNoReferer_RedirectsToTheEmailSettingsURL() {
		/* Arrange. */
		$this->login_as_admin();
		$this->prevent_wp_redirect();

		$post_id              = $this->factory->email->create( [ 'post_status' => 'draft' ] );
		$list_table_actions   = new Email_List_Table_Actions();
		$_REQUEST['_wpnonce'] = wp_create_nonce( 'enable-email-post_' . $post_id );

		/* Act. */
		try {
			$list_table_actions->enable_email( $post_id );
		} catch ( Sensei_WP_Redirect_Exception $e ) {
			$redirect_location = $e->getMessage();
		}

		/* Assert. */
		$expected = admin_url( 'admin.php?page=sensei-settings&tab=email-notification-settings' );
		$this->assertSame( $expected, $redirect_location );
	}

	public function testEnableEmail_WhenHasReferer_RedirectsToRefererURL() {
		/* Arrange. */
		$this->login_as_admin();
		$this->prevent_wp_redirect();

		$post_id              = $this->factory->email->create( [ 'post_status' => 'draft' ] );
		$list_table_actions   = new Email_List_Table_Actions();
		$_REQUEST['_wpnonce'] = wp_create_nonce( 'enable-email-post_' . $post_id );

		$referer                      = admin_url( 'wat.php' );
		$_REQUEST['_wp_http_referer'] = $referer;

		/* Act. */
		try {
			$list_table_actions->enable_email( $post_id );
		} catch ( Sensei_WP_Redirect_Exception $e ) {
			$redirect_location = $e->getMessage();
		}

		/* Assert. */
		$this->assertSame( $referer, $redirect_location );
	}

	public function testDisableEmail_WhenIncorrectAdminReferrer_ThrowsError() {
		/* Arrange. */
		$list_table_actions = new Email_List_Table_Actions();

		/* Assert. */
		$this->expectException( WPDieException::class );
		$this->expectExceptionMessage( 'The link you followed has expired.' );

		/* Act. */
		$list_table_actions->disable_email( 1 );
	}

	public function testDisableEmail_WhenUserHasInsufficientPermissions_ThrowsError() {
		/* Arrange. */
		$this->login_as_teacher();

		$post_id              = 1;
		$list_table_actions   = new Email_List_Table_Actions();
		$_REQUEST['_wpnonce'] = wp_create_nonce( 'disable-email-post_' . $post_id );

		/* Assert. */
		$this->expectException( WPDieException::class );
		$this->expectExceptionMessage( 'Insufficient permissions' );

		/* Act. */
		$list_table_actions->disable_email( $post_id );
	}

	public function testDisableEmail_WhenWrongPostType_ThrowsError() {
		/* Arrange. */
		$this->login_as_admin();

		$post_id              = $this->factory->post->create();
		$list_table_actions   = new Email_List_Table_Actions();
		$_REQUEST['_wpnonce'] = wp_create_nonce( 'disable-email-post_' . $post_id );

		/* Assert. */
		$this->expectException( WPDieException::class );
		$this->expectExceptionMessage( 'Invalid request' );

		/* Act. */
		$list_table_actions->disable_email( $post_id );
	}

	public function testDisableEmail_WhenRequestIsValid_ChangesTheEmailStatusToDraft() {
		/* Arrange. */
		$this->login_as_admin();
		$this->prevent_wp_redirect();

		$post_id              = $this->factory->email->create();
		$list_table_actions   = new Email_List_Table_Actions();
		$_REQUEST['_wpnonce'] = wp_create_nonce( 'disable-email-post_' . $post_id );

		/* Act. */
		try {
			$list_table_actions->disable_email( $post_id );
		} catch ( Sensei_WP_Redirect_Exception $e ) {
			$redirect_status = $e->getCode();
		}

		/* Assert. */
		$this->assertSame( 'draft', get_post( $post_id )->post_status );
	}

	public function testDisableEmail_WhenNoReferer_RedirectsToTheEmailSettingsURL() {
		/* Arrange. */
		$this->login_as_admin();
		$this->prevent_wp_redirect();

		$post_id              = $this->factory->email->create();
		$list_table_actions   = new Email_List_Table_Actions();
		$_REQUEST['_wpnonce'] = wp_create_nonce( 'disable-email-post_' . $post_id );

		/* Act. */
		try {
			$list_table_actions->disable_email( $post_id );
		} catch ( Sensei_WP_Redirect_Exception $e ) {
			$redirect_location = $e->getMessage();
		}

		/* Assert. */
		$expected = admin_url( 'admin.php?page=sensei-settings&tab=email-notification-settings' );
		$this->assertSame( $expected, $redirect_location );
	}

	public function testDisableEmail_WhenHasReferer_RedirectsToRefererURL() {
		/* Arrange. */
		$this->login_as_admin();
		$this->prevent_wp_redirect();

		$post_id              = $this->factory->email->create();
		$list_table_actions   = new Email_List_Table_Actions();
		$_REQUEST['_wpnonce'] = wp_create_nonce( 'disable-email-post_' . $post_id );

		$referer                      = admin_url( 'wat.php' );
		$_REQUEST['_wp_http_referer'] = $referer;

		/* Act. */
		try {
			$list_table_actions->disable_email( $post_id );
		} catch ( Sensei_WP_Redirect_Exception $e ) {
			$redirect_location = $e->getMessage();
		}

		/* Assert. */
		$this->assertSame( $referer, $redirect_location );
	}
}