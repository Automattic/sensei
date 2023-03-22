<?php

namespace SenseiTest\Internal\Emails;

use Sensei\Internal\Emails\Email_Page_Template;
use Sensei\Internal\Emails\Email_Page_Template_Repository;

/**
 * Tests for Sensei\Internal\Emails\Email_Page_Template_Repository class.
 *
 * @covers \Sensei\Internal\Emails\Email_Page_Template_Repository
 */
class Email_Page_Template_Repository_Test extends \WP_UnitTestCase {

	/**
	 * Helper class to create testing data.
	 *
	 * @var Sensei_Factory
	 */
	protected $factory;

	/**
	 * Set up the test.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->factory = new \Sensei_Factory();
	}

	public function testGet_FoundInDatabase_ReturnsTemplate() {
		/* Arrange. */
		$repository = new Email_Page_Template_Repository();
		$this->factory->post->create(
			[
				'post_type'  => 'wp_template',
				'post_title' => 'My template',
				'post_name'  => 'some-template-name',
			]
		);

		/* Act. */
		$result = $repository->get( 'theme//some-template-name' );

		/* Assert. */
		$this->assertNotNull( $result->wp_id );
	}

	public function testGet_WhenThereIsNoTemplates_ReturnsNull() {
		/* Arrange. */
		$repository = new Email_Page_Template_Repository();

		/* Act. */
		$result = $repository->get( 'theme//some-no-existent-template' );

		/* Assert. */
		$this->assertNull( $result );
	}

	public function testGetFromFile_TemplateFound_ReturnsTemplate() {
		/* Arrange. */
		$repository = new Email_Page_Template_Repository();

		/* Act. */
		$result = $repository->get_from_file( Email_Page_Template::TEMPLATE_PATH, 'theme//some-identifer' );

		/* Assert. */
		$this->assertNotNull( $result->content );
	}

	public function testGetFromFile_WhenThereIsNoTemplate_ReturnsNull() {
		/* Arrange. */
		$repository = new Email_Page_Template_Repository();

		/* Act. */
		$result = $repository->get_from_file( 'some-random_path', 'theme//some-identifer' );

		/* Assert. */
		$this->assertNull( $result );
	}
}
