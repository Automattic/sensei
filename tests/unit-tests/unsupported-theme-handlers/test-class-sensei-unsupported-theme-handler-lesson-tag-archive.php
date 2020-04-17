<?php

require_once 'test-class-sensei-unsupported-theme-handler-page-imitator.php';

class Sensei_Unsupported_Theme_Handler_Lesson_Tag_Archive_Test extends WP_UnitTestCase {

	/**
	 * @var Sensei_Unsupported_Theme_Handler_Lesson_Tag_Archive_Test The request handler to test.
	 */
	private $handler;

	/**
	 * @var WP_Term $term
	 */
	private $term;

	public function setUp() {
		parent::setUp();

		$this->factory = new Sensei_Factory();
		$this->setupLessonTagArchiveRequest();

		Sensei_Unsupported_Theme_Handler_Page_Imitator_Test::create_page_template();

		$this->handler = new Sensei_Unsupported_Theme_Handler_Lesson_Tag_Archive();
	}

	public function tearDown() {
		$this->handler = null;

		Sensei_Unsupported_Theme_Handler_Page_Imitator_Test::delete_page_template();

		parent::tearDown();
	}

	/**
	 * Ensure Sensei_Unsupported_Theme_Handler_Lesson_Tag_Archive handles the lesson tag archive page.
	 *
	 * @since 1.12.0
	 */
	public function testShouldHandleLessonTagArchivePage() {
		$this->assertTrue( $this->handler->can_handle_request() );
	}

	/**
	 * Ensure Sensei_Unsupported_Theme_Handler_Lesson_Tag_Archive does not handle a
	 * non-lesson tag archive page.
	 *
	 * @since 1.12.0
	 */
	public function testShouldNotHandleNonLessonTagArchivePage() {
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
	 * Ensure Sensei_Unsupported_Theme_Handler_Lesson_Tag_Archive disables the header and
	 * footer when rendering the lesson tag archive page.
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
	 * Ensure Sensei_Unsupported_Theme_Handler_Lesson_Tag_Archive renders the lesson tag archive page using
	 * the lesson-archive.php template.
	 *
	 * @since 1.12.0
	 */
	public function testShouldUseLessonArchiveTemplate() {
		// We'll test to ensure it uses the template by checking if the
		// sensei_archive_before_lesson_loop action was run.
		$this->assertEquals(
			0,
			did_action( 'sensei_archive_before_lesson_loop' ),
			'Should not have already done action sensei_archive_before_lesson_loop'
		);

		$this->handler->handle_request();

		$this->assertEquals(
			1,
			did_action( 'sensei_archive_before_lesson_loop' ),
			'Should have done action sensei_archive_before_lesson_loop'
		);
	}

	/**
	 * Ensure Sensei_Unsupported_Theme_Handler_Lesson_Tag_Archive sets up a dummy post for
	 * the final render of the lesson tag archive content.
	 *
	 * @since 1.12.0
	 */
	public function testShouldSetupDummyPost() {
		global $post;

		$this->handler->handle_request();

		$this->assertNotEquals( 0, $post->ID, 'The dummy post ID should be non-zero' );
		$this->assertNull( get_post( $post->ID ), 'The dummy post ID should be unused' );
		$this->assertEquals( 'page', $post->post_type, 'The dummy post type should be "page"' );

		$copied_from_term = array(
			'post_title' => 'name',
		);
		foreach ( $copied_from_term as $post_field => $term_field ) {
			$this->assertEquals(
				$this->term->{$term_field},
				$post->{$post_field},
				"Dummy post field $post_field should be copied from the term"
			);
		}
	}

	/**
	 * Helper to set up the current request to be a lesson tag archive page. This request
	 * will be handled by the unsupported theme handler if the theme is not
	 * supported.
	 *
	 * @since 1.12.0
	 */
	private function setupLessonTagArchiveRequest() {
		global $wp_query, $wp_the_query;

		wp_insert_term( 'test1', 'lesson-tag' );
		$this->term = get_term_by( 'slug', 'test1', 'lesson-tag' );

		$course_lessons = $this->factory->get_course_with_lessons();
		wp_set_object_terms( $course_lessons['lesson_ids'][0], $this->term->term_id, 'lesson-tag' );

		// Setup $wp_query to be simple post list.
		$args = array(
			'post_type' => 'post',
		);

		$wp_query     = new WP_Query( $args );
		$wp_the_query = $wp_query;

		$wp_query->is_tax            = 'lesson-tag';
		$wp_query->queried_object    = $this->term;
		$wp_query->queried_object_id = $this->term->term_id;
	}
}
