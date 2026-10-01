<?php
/**
 * This file contains the Sensei_Course_Theme_Editor_Test class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tests for Sensei_Course_Theme_Editor.
 */
class Sensei_Course_Theme_Editor_Test extends WP_UnitTestCase {

	/**
	 * Initial request URI.
	 *
	 * @var string
	 */
	private $initial_request_uri;

	/**
	 * Set up the test.
	 */
	public function setUp(): void {
		parent::setUp();

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$this->initial_request_uri = wp_unslash( $_SERVER['REQUEST_URI'] );
	}

	/**
	 * Tear down the test.
	 */
	public function tearDown(): void {
		$_SERVER['REQUEST_URI'] = $this->initial_request_uri;

		parent::tearDown();
	}

	public function testIsSiteEditorRequest_TemplatesEndpointRequested_ReturnsTrue() {
		/* Arrange. */
		$_SERVER['REQUEST_URI'] = '/wp-json/wp/v2/templates?context=edit';

		/* Act. */
		$result = Sensei_Course_Theme_Editor::is_site_editor_request();

		/* Assert. */
		self::assertTrue( $result );
	}

	public function testIsSiteEditorRequest_RegisteredTemplatesEndpointRequested_ReturnsTrue() {
		/* Arrange. */
		$_SERVER['REQUEST_URI'] = '/wp-json/wp/v2/registered-templates?context=edit';

		/* Act. */
		$result = Sensei_Course_Theme_Editor::is_site_editor_request();

		/* Assert. */
		self::assertTrue( $result );
	}
}
