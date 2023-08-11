<?php

namespace SenseiTest\Internal\Emails;

use Sensei\Internal\Emails\Email_Patterns;
use Sensei\Internal\Emails\Email_Post_Type;
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
	 * Identifier used in usage tracking.
	 *
	 * @var string
	 */
	private const USAGE_TRACKING_TYPE = 'teacher-started-course';

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

		$this->settings = new Sensei_Settings();
		$this->factory  = new Sensei_Factory();

		$this->create_test_email_template();

		$this->skip_did_filter = ! version_compare( get_bloginfo( 'version' ), '6.1.0', '>=' );

		add_action( 'wp_mail_succeeded', [ $this, 'wp_mail_succeeded' ] );
		add_action( 'get_header', [ $this, 'get_header' ] );
		add_filter( 'pre_get_block_template', [ $this, 'get_fake_template' ], 10, 3 );

		$this->email_sender = new Email_Sender( new Email_Repository(), $this->settings, new Email_Patterns() );
		$this->email_sender->init();

	}

	public function get_fake_template( $block_template, $id, $template_type ) {

		$template          = new \WP_Block_Template();
		$template->content = '
			Some Content from page template
			<!-- wp:post-content /-->
		';

		return $template;
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

	private function create_test_email_template() {
		$repository = new Email_Repository();

		$seeder = new Email_Seeder( new Email_Seeder_Data(), $repository );
		$seeder->init();
		$seeder->create_email( 'student_starts_course' );

		return $repository->get( 'student_starts_course' );
	}

	public function testSendEmail_WhenCalledWithoutExistingTemplate_DoesNotProceed() {
		if ( $this->skip_did_filter ) {
			$this->markTestSkipped( 'Requires `did_filter()` which was introduced in WordPress 6.1.0.' );
		}

		/* Act. */
		$this->email_sender->send_email( 'non-existing-template', [], '' );

		/* Assert. */
		self::assertEquals( 0, did_filter( 'sensei_email_replacements' ) );
	}

	public function testSendEmail_WhenCalledWithExistingTemplate_ProceedsFarther() {
		if ( $this->skip_did_filter ) {
			$this->markTestSkipped( 'Requires `did_filter()` which was introduced in WordPress 6.1.0.' );
		}

		/* Act. */
		$this->email_sender->send_email( 'student_starts_course', [], self::USAGE_TRACKING_TYPE );

		/* Assert. */
		self::assertEquals( 1, did_filter( 'sensei_email_replacements' ) );
	}

	public function testSendEmail_WhenCalled_RendersMessageWithTemplate() {
		/* Act. */
		$this->email_sender->send_email(
			'student_starts_course',
			[
				'a@a.test' => [
					'student:displayname' => 'Test Student',
				],
			],
			self::USAGE_TRACKING_TYPE
		);

		/* Assert. */
		self::assertStringContainsString( 'Some Content from page template', $this->email_data['message'] );
	}


	public function testSendEmail_WhenCalledWithReplacements_ReplacesPlaceholders() {
		/* Act. */
		$this->email_sender->send_email(
			'student_starts_course',
			[
				'a@a.test' => [
					'student:displayname' => 'Test Student',
				],
			],
			self::USAGE_TRACKING_TYPE
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
			[],
			self::USAGE_TRACKING_TYPE
		);

		/* Assert. */
		self::assertStringContainsString( 'Test Student', $this->email_data['message'] );
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
			],
			self::USAGE_TRACKING_TYPE
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
		$post = $this->factory->post->create_and_get(
			[
				'post_type'    => Email_Post_Type::POST_TYPE,
				'post_title'   => 'My template',
				'post_name'    => 'Welcome - [name]',
				'post_content' => 'Welcome - [name]',
			]
		);

		/* Act. */
		$email_body = $this->email_sender->get_email_body( $post, [ 'name' => 'John' ] );

		/* Assert. */
		self::assertStringContainsString( 'Welcome – John', $email_body );
	}

	public function testGetEmailBody_WhenCalled_ResetsTheGlobalWpQuery() {
		$post = $this->factory->post->create_and_get(
			[
				'post_type'    => Email_Post_Type::POST_TYPE,
				'post_title'   => 'My template',
				'post_name'    => 'Welcome - [name]',
				'post_content' => 'Welcome - [name]',
			]
		);

		/* Act. */
		$this->email_sender->get_email_body( $post, [ 'name' => 'John' ] );
		global $wp_query, $wp_the_query;

		/* Assert. */
		self::assertEquals( $wp_query, $wp_the_query );
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
			],
			self::USAGE_TRACKING_TYPE
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
			],
			self::USAGE_TRACKING_TYPE
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
			],
			self::USAGE_TRACKING_TYPE
		);

		/* Assert. */
		$last_email = $mailer->get_sent( 0 );
		self::assertStringContainsString( 'Reply-To: address_to_be_replied@gmail.com', $last_email->header );
	}

	public function testSendEmail_WhenEmailCcWasSet_SetsEmailCcHeader() {
		/* Arrange. */
		$this->settings->set( 'email_cc', 'cc@example.com' );
		$mailer = tests_retrieve_phpmailer_instance();

		/* Act */
		$this->email_sender->send_email(
			'student_starts_course',
			[
				'a@a.test' => [
					'student:displayname' => 'Test Student',
				],
			],
			self::USAGE_TRACKING_TYPE
		);

		/* Assert. */
		$last_email = $mailer->get_sent( 0 );
		self::assertStringContainsString( 'Cc: cc@example.com', $last_email->header );
	}

	public function testSendEmail_WhenEmailCcWasNotSet_DoesntSetEmailCcHeader() {
		/* Arrange. */
		$this->settings->set( 'email_cc', '' );
		$mailer = tests_retrieve_phpmailer_instance();

		/* Act */
		$this->email_sender->send_email(
			'student_starts_course',
			[
				'a@a.test' => [
					'student:displayname' => 'Test Student',
				],
			],
			self::USAGE_TRACKING_TYPE
		);

		/* Assert. */
		$last_email = $mailer->get_sent( 0 );
		self::assertStringNotContainsString( 'Cc: cc@example.com', $last_email->header );
	}

	public function testSendEmail_WhenEmailBccWasSet_SetsEmailBccHeader() {
		/* Arrange. */
		$this->settings->set( 'email_bcc', 'bcc@example.com' );
		$mailer = tests_retrieve_phpmailer_instance();

		/* Act */
		$this->email_sender->send_email(
			'student_starts_course',
			[
				'a@a.test' => [
					'student:displayname' => 'Test Student',
				],
			],
			self::USAGE_TRACKING_TYPE
		);

		/* Assert. */
		$last_email = $mailer->get_sent( 0 );
		self::assertStringContainsString( 'Bcc: bcc@example.com', $last_email->header );
	}

	public function testSendEmail_WhenEmailBccWasNotSet_DoesntSetEmailBccHeader() {
		/* Arrange. */
		$this->settings->set( 'email_cc', '' );
		$mailer = tests_retrieve_phpmailer_instance();

		/* Act */
		$this->email_sender->send_email(
			'student_starts_course',
			[
				'a@a.test' => [
					'student:displayname' => 'Test Student',
				],
			],
			self::USAGE_TRACKING_TYPE
		);

		/* Assert. */
		$last_email = $mailer->get_sent( 0 );
		self::assertStringNotContainsString( 'Bcc: bcc@example.com', $last_email->header );
	}

	public function testSendEmail_SetFromEmailName_RendersFromNameInHeader() {
		$this->settings->set( 'email_from_name', 'Sensei From Name' );
		$mailer = tests_retrieve_phpmailer_instance();

		/* Act */
		$this->email_sender->send_email(
			'student_starts_course',
			[
				'a@a.test' => [
					'student:displayname' => 'Test Student',
				],
			],
			self::USAGE_TRACKING_TYPE
		);

		/* Assert. */
		$last_email = $mailer->get_sent( 0 );
		self::assertStringContainsString( 'From: Sensei From Name <admin@example.org>', $last_email->header );
	}

	public function testSendEmail_SetFromEmailAddress_RendersFromEmailInHeader() {
		$this->settings->set( 'email_from_name', 'Sensei From Name' );
		$this->settings->set( 'email_from_address', 'from_email@example.com' );
		$mailer = tests_retrieve_phpmailer_instance();

		/* Act */
		$this->email_sender->send_email(
			'student_starts_course',
			[
				'a@a.test' => [
					'student:displayname' => 'Test Student',
				],
			],
			self::USAGE_TRACKING_TYPE
		);

		/* Assert. */
		$last_email = $mailer->get_sent( 0 );
		self::assertStringContainsString( 'From: Sensei From Name <from_email@example.com>', $last_email->header );
	}


	public function testSendEmail_WhenThereIsNoFromInfo_SetFromWithDefaultValues() {
		$this->settings->set( 'email_from_name', '' );
		$this->settings->set( 'email_from_address', '' );

		$mailer = tests_retrieve_phpmailer_instance();

		/* Act */
		$this->email_sender->send_email(
			'student_starts_course',
			[
				'a@a.test' => [
					'student:displayname' => 'Test Student',
				],
			],
			self::USAGE_TRACKING_TYPE
		);

		/* Assert. */
		$last_email = $mailer->get_sent( 0 );
		self::assertStringContainsString( 'From: Test Blog <admin@example.org>', $last_email->header );
	}

}
