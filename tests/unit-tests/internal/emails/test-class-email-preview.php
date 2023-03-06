<?php

namespace SenseiTest\Internal\Emails;

use Sensei\Internal\Emails\Email_Preview;
use Sensei\Internal\Emails\Email_Sender;
use Sensei_Assets;
use Sensei_Factory;
use Sensei_Test_Login_Helpers;
use WPDieException;

/**
 * Tests for Sensei\Internal\Emails\Email_Preview.
 *
 * @covers \Sensei\Internal\Emails\Email_Preview
 */
class Email_Preview_Test extends \WP_UnitTestCase {
	use Sensei_Test_Login_Helpers;

	public function setUp(): void {
		parent::setUp();
		$this->factory      = new Sensei_Factory();
		$this->email_sender = $this->createMock( Email_Sender::class );
		$this->assets       = $this->createMock( Sensei_Assets::class );
	}

	public function tearDown(): void {
		parent::tearDown();
		$this->factory->tearDown();
	}

	public function testInit_WhenCalled_AddsRenderPreviewHook() {
		/* Arrange. */
		$email_preview = new Email_Preview( $this->email_sender, $this->assets );

		/* Act. */
		$email_preview->init();

		/* Assert. */
		$priority = has_action( 'template_redirect', [ $email_preview, 'render_preview' ] );
		$this->assertSame( 10, $priority );
	}

	public function testInit_WhenCalled_AddsRegisterAdminScriptsHook() {
		/* Arrange. */
		$email_preview = new Email_Preview( $this->email_sender, $this->assets );

		/* Act. */
		$email_preview->init();

		/* Assert. */
		$priority = has_action( 'admin_enqueue_scripts', [ $email_preview, 'register_admin_scripts' ] );
		$this->assertSame( 10, $priority );
	}

	public function testRenderPreview_WhenNoPreviewID_DoesNothing() {
		/* Arrange. */
		$email_preview = new Email_Preview( $this->email_sender, $this->assets );

		/* Act. */
		$result = $email_preview->render_preview();

		/* Assert. */
		$this->assertNull( $result );
	}

	public function testRenderPreview_WhenPostDoesNotExist_ThrowsError() {
		/* Arrange. */
		$email_preview = new Email_Preview( $this->email_sender, $this->assets );

		$_GET['sensei_email_preview_id'] = 42;

		/* Assert. */
		$this->expectException( WPDieException::class );
		$this->expectExceptionMessage( 'Invalid request' );

		/* Act. */
		$email_preview->render_preview();
	}

	public function testRenderPreview_WhenIncorrectPostType_ThrowsError() {
		/* Arrange. */
		$post_id       = $this->factory->post->create();
		$email_preview = new Email_Preview( $this->email_sender, $this->assets );

		$_GET['sensei_email_preview_id'] = $post_id;

		/* Assert. */
		$this->expectException( WPDieException::class );
		$this->expectExceptionMessage( 'Invalid request' );

		/* Act. */
		$email_preview->render_preview();
	}

	public function testRenderPreview_WhenInsufficientPermissions_ThrowsError() {
		/* Arrange. */
		$this->login_as_teacher();

		$post_id       = $this->factory->email->create();
		$email_preview = new Email_Preview( $this->email_sender, $this->assets );

		$_GET['sensei_email_preview_id'] = $post_id;

		/* Assert. */
		$this->expectException( WPDieException::class );
		$this->expectExceptionMessage( 'Insufficient permissions' );

		/* Act. */
		$email_preview->render_preview();
	}

	public function testRenderPreview_WhenIncorrectAdminReferrer_ThrowsError() {
		/* Arrange. */
		$this->login_as_admin();

		$post_id       = $this->factory->email->create();
		$email_preview = new Email_Preview( $this->email_sender, $this->assets );

		$_GET['sensei_email_preview_id'] = $post_id;

		/* Assert. */
		$this->expectException( WPDieException::class );
		$this->expectExceptionMessage( 'The link you followed has expired.' );

		/* Act. */
		$email_preview->render_preview();
	}

	public function testRenderPreview_WhenRenderingPage_RendersEmailSubject() {
		/* Arrange. */
		$this->login_as_admin();

		$post          = $this->factory->email->create_and_get( [ 'post_title' => 'Welcome' ] );
		$email_preview = new Email_Preview( $this->email_sender, $this->assets );

		$this->email_sender
			->expects( $this->once() )
			->method( 'get_email_subject' )
			->with( $post )
			->willReturn( 'Welcome' );

		$_GET['sensei_email_preview_id'] = $post->ID;
		$_REQUEST['_wpnonce']            = wp_create_nonce( 'preview-email-post_' . $post->ID );

		/* Act. */
		ob_start();
		$email_preview->render_preview();
		$content = ob_get_clean();

		/* Assert. */
		$this->assertStringContainsString( 'Welcome', $content );
	}

	public function testRenderPreview_WhenRenderingPage_RendersFromAddress() {
		/* Arrange. */
		$this->login_as_admin();

		$post_id       = $this->factory->email->create();
		$email_preview = new Email_Preview( $this->email_sender, $this->assets );

		$_GET['sensei_email_preview_id'] = $post_id;
		$_REQUEST['_wpnonce']            = wp_create_nonce( 'preview-email-post_' . $post_id );

		/* Act. */
		ob_start();
		$email_preview->render_preview();
		$content = ob_get_clean();

		/* Assert. */
		$this->assertStringContainsString( 'admin@example.org', $content );
	}

	public function testRenderPreview_WhenRenderingPage_RendersFromName() {
		/* Arrange. */
		$this->login_as_admin();

		$post_id       = $this->factory->email->create();
		$email_preview = new Email_Preview( $this->email_sender, $this->assets );

		$_GET['sensei_email_preview_id'] = $post_id;
		$_REQUEST['_wpnonce']            = wp_create_nonce( 'preview-email-post_' . $post_id );

		/* Act. */
		ob_start();
		$email_preview->render_preview();
		$content = ob_get_clean();

		/* Assert. */
		$this->assertStringContainsString( 'Test Blog', $content );
	}

	public function testRenderPreview_WhenRenderingPage_RendersAvatar() {
		/* Arrange. */
		$this->login_as_admin();

		$post_id       = $this->factory->email->create();
		$email_preview = new Email_Preview( $this->email_sender, $this->assets );

		$_GET['sensei_email_preview_id'] = $post_id;
		$_REQUEST['_wpnonce']            = wp_create_nonce( 'preview-email-post_' . $post_id );

		/* Act. */
		ob_start();
		$email_preview->render_preview();
		$content = ob_get_clean();

		/* Assert. */
		$this->assertStringContainsString( 'gravatar', $content );
	}

	public function testRenderPreview_WhenRenderingEmail_RendersEmailBody() {
		/* Arrange. */
		$this->login_as_admin();

		$post          = $this->factory->email->create_and_get( [ 'post_content' => 'content' ] );
		$email_preview = new Email_Preview( $this->email_sender, $this->assets );

		$this->email_sender
			->expects( $this->once() )
			->method( 'get_email_body' )
			->with( $post )
			->willReturn( 'content' );

		$_GET['sensei_email_preview_id'] = $post->ID;
		$_GET['render_email']            = 1;
		$_REQUEST['_wpnonce']            = wp_create_nonce( 'preview-email-post_' . $post->ID );

		/* Act. */
		ob_start();
		$email_preview->render_preview();
		$content = ob_get_clean();

		/* Assert. */
		$this->assertSame( 'content', $content );
	}

	public function testRegisterAdminScripts_WhenNoScreen_DoesNothing() {
		/* Arrange. */
		$email_preview = new Email_Preview( $this->email_sender, $this->assets );

		/* Assert. */
		$this->assets
			->expects( $this->never() )
			->method( 'enqueue' );

		/* Act. */
		$email_preview->register_admin_scripts();
	}

	public function testRegisterAdminScripts_WhenNotOnTheEmailEditScreen_DoesNothing() {
		/* Arrange. */
		$email_preview = new Email_Preview( $this->email_sender, $this->assets );

		set_current_screen( 'test' );

		/* Assert. */
		$this->assets
			->expects( $this->never() )
			->method( 'enqueue' );

		/* Act. */
		$email_preview->register_admin_scripts();
	}

	public function testRegisterAdminScripts_WhenOnTheEmailEditScreen_EnqueuesTheScript() {
		/* Arrange. */
		$email_preview = new Email_Preview( $this->email_sender, $this->assets );

		set_current_screen( 'sensei_email' );

		/* Assert. */
		$this->assets
			->expects( $this->exactly( 2 ) )
			->method( 'enqueue' )
			->withConsecutive(
				[ 'sensei-email-preview-button', 'admin/emails/email-preview-button/index.js', [], true ],
				[ 'sensei-email-preview-button', 'admin/emails/email-preview-button/email-preview-button.css' ]
			);

		/* Act. */
		$email_preview->register_admin_scripts();
	}

	public function testGetPreviewLink_WhenCalled_ReturnsThePreviewLink() {
		/* Arrange. */
		$post_id = 1;

		/* Act. */
		$link = Email_Preview::get_preview_link( $post_id );

		/* Assert. */
		$this->assertSame(
			wp_nonce_url( get_home_url() . "?sensei_email_preview_id=$post_id", 'preview-email-post_' . $post_id ),
			$link
		);
	}
}
