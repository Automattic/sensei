<?php

namespace SenseiTest\Internal\Emails;

use Sensei\Internal\Emails\Email_Patterns;
use Sensei\Internal\Emails\Email_Repository;
use Sensei\Internal\Emails\Email_Seeder;
use Sensei\Internal\Emails\Email_Seeder_Data;
use Sensei\Internal\Emails\Email_Sender;
use Sensei_Factory;
use Sensei_Settings;
use WP_Post;

/**
 * Tests for Sensei\Internal\Emails\Email_Sender class.
 *
 * @covers \Sensei\Internal\Emails\Email_Sender
 */
class Email_Sender_Test extends \WP_UnitTestCase {

	/**
	 * Factory for creating test data.
	 *
	 * @var Sensei_Factory
	 */
	protected $factory;

	/**
	 * The mail data.
	 *
	 * @var array
	 */
	protected $email_data;


	/**
	 * The sensei settings.
	 *
	 * @var Sensei_Settings
	 */
	protected $settings;

	/**
	 * The email sender.
	 *
	 * @var Email_Sender
	 */
	protected $email_sender;

	/**
	 * Skip did_filter check.
	 *
	 * @var bool
	 */
	protected $skip_did_filter = false;

	public function setUp(): void {
		parent::setUp();
		reset_phpmailer_instance();

		$this->settings     = new Sensei_Settings();
		$this->factory      = new Sensei_Factory();
		$this->email_sender = new Email_Sender( new Email_Repository(), $this->settings, new Email_Patterns() );
		$this->email_sender->init();

		$this->create_test_email_template();

		$this->skip_did_filter = ! version_compare( get_bloginfo( 'version' ), '6.1.0', '>=' );

		add_action( 'wp_mail_succeeded', [ $this, 'wp_mail_succeeded' ] );
		add_action( 'get_header', [ $this, 'get_header' ] );
	}

	public function wp_mail_succeeded( $result ) {
		$this->email_data = $result;
	}

	public function get_header() {
		echo 'Header';
	}

	public function testInit_WhenCalled_AddsFilter() {
		/* Assert. */
		$priority = has_action( 'sensei_email_send', [ $this->email_sender, 'send_email' ] );
		self::assertSame( 10, $priority );
	}

	public function testSendEmail_WhenCalledWithoutExistingTemplate_DoesNotProceed() {
		if ( $this->skip_did_filter ) {
			$this->markTestSkipped( 'Requires `did_filter()` which was introduced in WordPress 6.1.0.' );
		}

		/* Act. */
		$this->email_sender->send_email( 'non-existing-template', [] );

		/* Assert. */
		self::assertEquals( 0, did_filter( 'sensei_email_replacements' ) );
	}

	public function testSendEmail_WhenCalledWithExistingTemplate_ProceedsFarther() {
		if ( $this->skip_did_filter ) {
			$this->markTestSkipped( 'Requires `did_filter()` which was introduced in WordPress 6.1.0.' );
		}

		/* Act. */
		$this->email_sender->send_email( 'student_starts_course', [] );

		/* Assert. */
		self::assertEquals( 1, did_filter( 'sensei_email_replacements' ) );
	}

	public function testSendEmail_WhenCalledWithReplacements_ReplacesPlaceholders() {
		/* Act. */
		$this->email_sender->send_email(
			'student_starts_course',
			[
				'a@a.test' => [
					'student:displayname' => 'Test Student',
				],
			]
		);

		/* Assert. */
		self::assertStringContainsString( 'Test Student', $this->email_data['message'] );
	}

	public function testSendEmail_WhenCalled_ReplacesPlaceholdersAddedByHook() {
		/* Arrange. */
		add_filter(
			'sensei_email_replacements',
			function () {
				return [
					'a@a.test' => [
						'student:displayname' => 'Test Student',
					],
				];
			}
		);

		/* Act. */
		$this->email_sender->send_email(
			'student_starts_course',
			[]
		);

		/* Assert. */
		self::assertStringContainsString( 'Test Student', $this->email_data['message'] );
	}

	public function testSendEmail_WhenCssInPresent_MovesCssToInline() {
		/* Arrange. */
		add_filter(
			'sensei_email_styles',
			function ( $styles ) {
				return $styles . 'p { background-color: yellow; }';
			}
		);

		/* Act. */
		$this->email_sender->send_email(
			'student_starts_course',
			[
				'a@a.test' => [
					'student:displayname' => 'Test Student',
				],
			]
		);

		/* Assert. */
		self::assertStringContainsString( 'background-color: yellow;', $this->email_data['message'] );
	}

	public function testSendEmail_WhenSubjectHasPlaceholders_ReplacesThePlaceholder() {
		/* Act. */
		$this->email_sender->send_email(
			'student_starts_course',
			[
				'a@a.test' => [
					'student:displayname' => 'Test Student',
					'course:name'         => 'Test Course',
				],
			]
		);

		/* Assert. */
		self::assertStringContainsString( 'Test Course', $this->email_data['subject'] );
	}

	public function testGetEmailSubject_WhenCalled_ReturnsTheEmailSubjectWithReplacedPlaceholders() {
		/* Arrange. */
		$post = new WP_Post( (object) [ 'post_title' => 'Welcome - [name]' ] );

		/* Act. */
		$email_body = $this->email_sender->get_email_subject( $post, [ 'name' => 'John' ] );

		/* Assert. */
		self::assertStringContainsString( 'Welcome - John', $email_body );
	}

	public function testGetEmailBody_WhenCalled_ReturnsTheEmailBodyWithReplacedPlaceholders() {
		/* Arrange. */
		$post = new WP_Post( (object) [ 'post_content' => 'Welcome - [name]' ] );

		/* Act. */
		$email_body = $this->email_sender->get_email_body( $post, [ 'name' => 'John' ] );

		/* Assert. */
		self::assertStringContainsString( 'Welcome - John', $email_body );
	}

	public function testSendEmail_WhenTheReplyToIsSet_SetReplyTo() {
		/* Arrange. */
		$this->settings->set( 'email_reply_to_address', 'address_to_be_replied@gmail.com' );
		$this->settings->set( 'email_reply_to_name', 'John Reply' );
		$mailer = tests_retrieve_phpmailer_instance();

		/* Act */
		$this->email_sender->send_email(
			'student_starts_course',
			[
				'a@a.test' => [
					'student:displayname' => 'Test Student',
				],
			]
		);

		/* Assert. */
		$last_email = $mailer->get_sent( 0 );
		self::assertStringContainsString( 'Reply-To: John Reply <address_to_be_replied@gmail.com>', $last_email->header );
	}

	public function testSendEmail_WhenTheReplyToIsNotSet_SetReplyTo() {
		/* Arrange. */
		$this->settings->set( 'email_reply_to_address', null );

		/* Act */
		$this->email_sender->send_email(
			'student_starts_course',
			[
				'a@a.test' => [
					'student:displayname' => 'Test Student',
				],
			]
		);

		/* Assert. */
		$email_sent = tests_retrieve_phpmailer_instance()->get_sent( 0 );
		self::assertStringNotContainsString( 'Reply-To', $email_sent->header );
	}

	public function testSendEmail_WhenTheReplyToNameIsNotSet_SetReplyTo() {
		/* Arrange. */
		$this->settings->set( 'email_reply_to_address', 'address_to_be_replied@gmail.com' );
		$this->settings->set( 'email_reply_to_name', null );
		$mailer = tests_retrieve_phpmailer_instance();

		/* Act */
		$this->email_sender->send_email(
			'student_starts_course',
			[
				'a@a.test' => [
					'student:displayname' => 'Test Student',
				],
			]
		);

		/* Assert. */
		$last_email = $mailer->get_sent( 0 );
		self::assertStringContainsString( 'Reply-To: address_to_be_replied@gmail.com', $last_email->header );
	}

	private function create_test_email_template() {
		$repository = new Email_Repository();

		$seeder = new Email_Seeder( new Email_Seeder_Data(), $repository );
		$seeder->init();
		$seeder->create_email( 'student_starts_course' );

		return $repository->get( 'student_starts_course' );
	}
}
