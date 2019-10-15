<?php

require_once 'test-class-sensei-unsupported-theme-handler-page-imitator.php';

class Sensei_Unsupported_Theme_Handler_Message_Archive_Test extends WP_UnitTestCase {

	/**
	 * @var Sensei_Unsupported_Theme_Handler_Message_Archive_Test The request handler to test.
	 */
	private $handler;

	public function setUp() {
		parent::setUp();

		$this->factory = new Sensei_Factory();
		$this->setupMessageArchiveRequest();

		Sensei_Unsupported_Theme_Handler_Page_Imitator_Test::create_page_template();

		$this->handler = new Sensei_Unsupported_Theme_Handler_Message_Archive();
	}

	public function tearDown() {
		$this->handler = null;

		Sensei_Unsupported_Theme_Handler_Page_Imitator_Test::delete_page_template();

		parent::tearDown();
	}

	/**
	 * Ensure Sensei_Unsupported_Theme_Handler_Message_Archive handles the message archive page.
	 *
	 * @since 1.12.0
	 */
	public function testShouldHandleMessageArchivePage() {
		$this->assertTrue( $this->handler->can_handle_request() );
	}

	/**
	 * Ensure Sensei_Unsupported_Theme_Handler_Message_Archive does not handle a
	 * non-message archive page.
	 *
	 * @since 1.12.0
	 */
	public function testShouldNotHandleNonMessageArchivePage() {
		// Set up the query to be for the Courses page.
		global $wp_query;
		$wp_query = new WP_Query(
			array(
				'post_type' => 'post',
			)
		);

		$this->assertFalse( $this->handler->can_handle_request() );
	}

	/**
	 * Ensure Sensei_Unsupported_Theme_Handler_Message_Archive disables the header and
	 * footer when rendering the message archive page.
	 *
	 * @since 1.12.0
	 */
	public function testShouldDisableHeaderAndFooter() {
		$this->assertFalse( has_filter( 'sensei_show_main_header', '__return_false' ), 'Header should initially be enabled' );
		$this->assertFalse( has_filter( 'sensei_show_main_footer', '__return_false' ), 'Footer should initially be enabled' );

		$this->handler->handle_request();

		$this->assertNotFalse( has_filter( 'sensei_show_main_header', '__return_false' ), 'Header should be disabled by handler' );
		$this->assertNotFalse( has_filter( 'sensei_show_main_footer', '__return_false' ), 'Footer should be disabled by handler' );
	}

	/**
	 * Ensure Sensei_Unsupported_Theme_Handler_Message_Archive renders the message archive page using
	 * the archive-message.php template.
	 *
	 * @since 1.12.0
	 */
	public function testShouldUseMessageArchiveTemplate() {
		// We'll test to ensure it uses the template by checking if the
		// sensei_archive_before_message_loop action was run.
		$this->assertEquals(
			0,
			did_action( 'sensei_archive_before_message_loop' ),
			'Should not have already done action sensei_archive_before_message_loop'
		);

		$this->handler->handle_request();

		$this->assertEquals(
			1,
			did_action( 'sensei_archive_before_message_loop' ),
			'Should have done action sensei_archive_before_message_loop'
		);
	}

	/**
	 * Ensure Sensei_Unsupported_Theme_Handler_Message_Archive sets up a dummy post for
	 * the final render of the message archive content.
	 *
	 * @since 1.12.0
	 */
	public function testShouldSetupDummyPost() {
		global $post;

		$this->handler->handle_request();

		$this->assertNotEquals( 0, $post->ID, 'The dummy post ID should be non-zero' );
		$this->assertNull( get_post( $post->ID ), 'The dummy post ID should be unused' );
		$this->assertEquals( 'page', $post->post_type, 'The dummy post type should be "page"' );

		$post_type = get_post_type_object( 'sensei_message' );

		$copied_from_post_type = array(
			'post_title' => 'label',
		);

		foreach ( $copied_from_post_type as $post_field => $post_type_field ) {
			$this->assertEquals(
				$post_type->{$post_type_field},
				$post->{$post_field},
				"Dummy post field $post_field should be copied from the term"
			);
		}
	}

	/**
	 * Helper to set up the current request to be a message archive page. This request
	 * will be handled by the unsupported theme handler if the theme is not
	 * supported.
	 *
	 * @since 1.12.0
	 */
	private function setupMessageArchiveRequest() {
		global $wp_query, $wp_the_query;

		$this->factory->message->create_many( 2 );

		// Setup $wp_query to be simple post list.
		$args = array(
			'post_type' => 'sensei_message',
		);

		$wp_query     = new WP_Query( $args );
		$wp_the_query = $wp_query;

		$wp_query->is_post_type_archive    = 'sensei_message';
		$wp_query->queried_object          = get_post_type_object( 'sensei_message' );
		$wp_query->queried_object_id       = '';
		$wp_query->query_vars['post_type'] = 'sensei_message';
	}
}
