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

	public function testGet_FoundInDatabase_ReturnsPluginTemplate() {
		/* Arrange. */
		$repository = new Email_Page_Template_Repository();
		$post_id    = $this->factory->post->create(
			array(
				'post_type'  => 'wp_template',
				'post_title' => 'My template',
				'post_name'  => 'some-template-name',
			)
		);

		/* Act. */
		$result = $repository->get( 'theme//some-template-name' );

		/* Assert. */
		$this->assertSame(
			array(
				'wp_id'     => $post_id,
				'is_custom' => false,
				'origin'    => 'plugin',
				'plugin'    => basename( dirname( SENSEI_LMS_PLUGIN_FILE ) ),
			),
			array(
				'wp_id'     => $result->wp_id,
				'is_custom' => $result->is_custom,
				'origin'    => $result->origin,
				'plugin'    => $result->plugin,
			)
		);
	}

	public function testGet_WhenThereIsNoTemplates_ReturnsNull() {
		/* Arrange. */
		$repository = new Email_Page_Template_Repository();

		/* Act. */
		$result = $repository->get( 'theme//some-no-existent-template' );

		/* Assert. */
		$this->assertNull( $result );
	}

	public function testGetFromFile_TemplateFound_ReturnsPluginTemplate() {
		/* Arrange. */
		$repository = new Email_Page_Template_Repository();

		/* Act. */
		$result = $repository->get_from_file( Email_Page_Template::TEMPLATE_PATH, 'theme//some-identifer' );

		/* Assert. */
		$this->assertSame(
			array(
				'has_content' => true,
				'is_custom'   => false,
				'origin'      => 'plugin',
				'plugin'      => basename( dirname( SENSEI_LMS_PLUGIN_FILE ) ),
			),
			array(
				'has_content' => ! empty( $result->content ),
				'is_custom'   => $result->is_custom,
				'origin'      => $result->origin,
				'plugin'      => $result->plugin,
			)
		);
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
