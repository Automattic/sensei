<?php

namespace SenseiTest\Internal\Emails;

use Sensei\Internal\Emails\Email_Page_Template;
use Sensei\Internal\Emails\Email_Page_Template_Repository;
use Sensei\Internal\Emails\Email_Post_Type;
use WP_Post;

/**
 * Tests for Sensei\Internal\Emails\Email_Page_Template class.
 *
 * @covers \Sensei\Internal\Emails\Email_Page_Template
 */
class Email_Page_Template_Test extends \WP_UnitTestCase {
	/**
	 * The URI which was given in order to access this page.
	 *
	 * @var string
	 */
	private static $initial_request_uri;

	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		self::$initial_request_uri = wp_unslash( $_SERVER['REQUEST_URI'] );
	}

	public function setUp(): void {
		parent::setUp();

		add_filter( 'pre_get_block_file_template', [ $this, 'pre_get_block_file_template' ], 10, 3 );
		add_filter( 'get_block_templates', [ $this, 'get_block_templates' ], 10, 3 );
	}

	public function tearDown(): void {
		parent::tearDown();

		$_SERVER['REQUEST_URI'] = self::$initial_request_uri;
	}

	public function testHasInit_Always_RegistersFilters() {

		/* Arrange. */
		$repository    = $this->createMock( Email_Page_Template_Repository::class );
		$page_template = new Email_Page_Template( $repository );

		/* Act. */
		$page_template->init();

		/* Assert. */
		$get_file_block_priorty       = has_filter( 'pre_get_block_file_template', [ $page_template, 'get_from_file' ] );
		$get_block_templates_priority = has_filter( 'get_block_templates', [ $page_template, 'add_email_template' ] );

		self::assertSame( 10, $get_file_block_priorty );
		self::assertSame( 10, $get_block_templates_priority );
	}

	public function testGetFromFile_FoundTemplateInRepository_ReturnsTemplateFromRepository() {

		/* Arrange. */
		$repository        = $this->createMock( Email_Page_Template_Repository::class );
		$default_template  = $this->createMock( \WP_Block_Template::class );
		$expected_template = $this->createMock( \WP_Block_Template::class );

		$repository
			->method( 'get_from_file' )
			->with( Email_Page_Template::TEMPLATE_PATH, Email_Page_Template::ID )
			->willReturn( $expected_template );

		$page_template = new Email_Page_Template( $repository );

		/* Act. */
		$result = $page_template->get_from_file( $default_template, Email_Page_Template::ID, 'wp_template' );

		/* Assert. */
		self::assertSame( $result, $expected_template );
	}


	public function testGetFromFile_WhenTheIdentifierIsIncorrect_ReturnsDefaultTemplate() {

		/* Arrange. */
		$repository       = $this->createMock( Email_Page_Template_Repository::class );
		$page_template    = new Email_Page_Template( $repository );
		$default_template = null;

		/* Act. */
		$result = $page_template->get_from_file( $default_template, 'some-id', 'wp_template' );

		/* Assert. */
		self::assertSame( $result, $default_template );
	}

	public function testGetFromFile_WhenThePostTypeIsIncorrect_ReturnsDefaultTemplate() {

		/* Arrange. */
		$repository       = $this->createMock( Email_Page_Template_Repository::class );
		$page_template    = new Email_Page_Template( $repository );
		$default_template = $this->createMock( \WP_Block_Template::class );

		/* Act. */
		$result = $page_template->get_from_file( $default_template, Email_Page_Template::ID, 'some-wrong-template-part' );

		/* Assert. */
		self::assertSame( $result, $default_template );
	}


	public function testAddEmailTemplate_TemplateGiven_ReturnsUpdatedList() {

		/* Arrange. */
		$_SERVER['REQUEST_URI'] = '/wp-admin/site-editor.php';
		$repository             = $this->createMock( Email_Page_Template_Repository::class );
		$page_template          = new Email_Page_Template( $repository );
		$default_list           = [ $this->createMock( \WP_Block_Template::class ) ];
		$template_to_be_added   = $this->createMock( \WP_Block_Template::class );

		$repository
			->method( 'get' )
			->willReturn( $template_to_be_added );

		/* Act. */
		$result = $page_template->add_email_template( $default_list, array( 'post_type' => 'sensei_email' ), 'wp_template' );

		/* Assert. */
		self::assertSame( $template_to_be_added, $result[1] );
	}

	public function testAddEmailTemplate_WhenThePostTypeIsIncorrect_ReturnsDefaultTemplate() {

		/* Arrange. */
		$repository           = $this->createMock( Email_Page_Template_Repository::class );
		$page_template        = new Email_Page_Template( $repository );
		$default_list         = [ $this->createMock( \WP_Block_Template::class ) ];
		$template_to_be_added = $this->createMock( \WP_Block_Template::class );
		$post                 = new \stdClass();
		$post->post_type      = 'page';
		$GLOBALS['post']      = $post;

		$repository
			->method( 'get' )
			->willReturn( $template_to_be_added );

		/* Act. */
		$result = $page_template->add_email_template( $default_list, [], 'wp_template' );

		/* Assert. */
		self::assertSame( $default_list, $result );
	}

	public function testAddEmailTemplate_GivenQuerySpecificPostType_ReturnsDefaultTemplate() {

		/* Arrange. */
		$repository    = $this->createMock( Email_Page_Template_Repository::class );
		$page_template = new Email_Page_Template( $repository );
		$default_list  = [ $this->createMock( \WP_Block_Template::class ) ];

		/* Act. */
		$result = $page_template->add_email_template( $default_list, [ 'post_type' => 'page' ], 'wp_template' );

		/* Assert. */
		self::assertSame( $default_list, $result );
	}

	public function testAddEmailTemplate_GivenQueryWithoutPostType_ReturnsUpdatedList() {
		/* Arrange. */
		$_SERVER['REQUEST_URI'] = '/wp-admin/site-editor.php';
		$repository             = $this->createMock( Email_Page_Template_Repository::class );
		$page_template          = new Email_Page_Template( $repository );
		$default_list           = [ $this->createMock( \WP_Block_Template::class ) ];
		$template_to_be_added   = $this->createMock( \WP_Block_Template::class );

		$repository
			->method( 'get' )
			->willReturn( $template_to_be_added );

		/* Act. */
		$result = $page_template->add_email_template( $default_list, [], 'wp_template' );

		/* Assert. */
		self::assertSame( $template_to_be_added, $result[1] );
	}

	public function testAddEmailTemplate_WhenPostTypeIsIncorrect_ReturnsDefaultList() {

		/* Arrange. */
		$repository           = $this->createMock( Email_Page_Template_Repository::class );
		$page_template        = new Email_Page_Template( $repository );
		$default_list         = [ $this->createMock( \WP_Block_Template::class ) ];
		$template_to_be_added = $this->createMock( \WP_Block_Template::class );

		$repository
			->method( 'get' )
			->willReturn( $template_to_be_added );

		/* Act. */
		$result = $page_template->add_email_template( $default_list, [], 'some-wrong-post-type' );

		/* Assert. */
		self::assertSame( $default_list, $result );
	}

	public function testAddEmailTemplate_WhenThemeIsSet_ReturnsDefaultList() {

		/* Arrange. */
		$repository           = $this->createMock( Email_Page_Template_Repository::class );
		$page_template        = new Email_Page_Template( $repository );
		$default_list         = [ $this->createMock( \WP_Block_Template::class ) ];
		$template_to_be_added = $this->createMock( \WP_Block_Template::class );

		$repository
			->method( 'get' )
			->willReturn( $template_to_be_added );

		/* Act. */
		$result = $page_template->add_email_template( $default_list, [ 'theme' => 'some-theme' ], 'wp-template' );

		/* Assert. */
		self::assertSame( $default_list, $result );
	}

	public function testAddEmailTemplate_WhenThereIsNoTemplateStoredOnDB_ReturnsFromFile() {

		/* Arrange. */
		$_SERVER['REQUEST_URI'] = '/wp-admin/site-editor.php';
		$repository             = $this->createMock( Email_Page_Template_Repository::class );
		$page_template          = new Email_Page_Template( $repository );
		$default_list           = [ $this->createMock( \WP_Block_Template::class ) ];
		$template_to_be_added   = $this->createMock( \WP_Block_Template::class );

		$repository
			->method( 'get' )
			->willReturn( null );

		$repository
			->method( 'get_from_file' )
			->willReturn( $template_to_be_added );

		/* Act. */
		$result = $page_template->add_email_template( $default_list, array( 'post_type' => 'sensei_email' ), 'wp_template' );

		/* Assert. */
		self::assertSame( $template_to_be_added, $result[1] );
	}

	public function testAddEmailTemplate_WhenAjaxLookupRequest_ReturnsListWithoutUpdating() {
		/* Arrange. */
		$_SERVER['REQUEST_URI'] = '/wp-json/wp/v2/templates/lookup?slug=single-post-hello-world&_locale=user';
		$repository             = $this->createMock( Email_Page_Template_Repository::class );
		$page_template          = new Email_Page_Template( $repository );
		$default_list           = [ $this->createMock( \WP_Block_Template::class ) ];
		$template_to_be_added   = $this->createMock( \WP_Block_Template::class );

		$repository
			->method( 'get' )
			->willReturn( $template_to_be_added );

		/* Act. */
		$result = $page_template->add_email_template( $default_list, [], 'wp_template' );

		/* Assert. */
		self::assertSame( $default_list, $result );
	}
}
