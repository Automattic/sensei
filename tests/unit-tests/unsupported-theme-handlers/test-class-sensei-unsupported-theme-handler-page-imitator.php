<?php

$test_dir = Sensei_Unit_Tests_Bootstrap::instance()->tests_dir;
require_once $test_dir . '/framework/class-sensei-unsupported-theme-handler-faux-page-imitator.php';

class Sensei_Unsupported_Theme_Handler_Page_Imitator_Test extends WP_UnitTestCase {

	/**
	 * @var Sensei_Unsupported_Theme_Handler_Faux_Page_Imitator The request handler to test.
	 */
	private $handler;

	/**
	 * @var WP_Post A course post.
	 */
	private $course;

	public function setUp() {
		parent::setUp();
		$this->factory = new Sensei_Factory();

		$this->setupUnsupportedPage();
		self::create_page_template();

		$this->handler = new Sensei_Unsupported_Theme_Handler_Faux_Page_Imitator( $this->course );
	}

	public function tearDown() {
		$this->handler = null;

		self::delete_page_template();

		parent::tearDown();
	}

	/**
	 * Create the blank page template used in testing.
	 */
	public static function create_page_template() {
		// Set up the page.php template.
		$filename = get_template_directory() . '/page.php';
		$handle   = fopen( $filename, 'w' );
		fwrite( $handle, "<?php\n// Silence is golden\n" );
		fclose( $handle );
	}

	/**
	 * Delete the page template added for testing.
	 */
	public static function delete_page_template() {
		// Remove the page.php template.
		$filename = get_template_directory() . '/page.php';
		unlink( $filename );
	}

	/**
	 * Ensure Sensei_Unsupported_Theme_Handler_Faux_Page_Imitator render runs by checking action.
	 *
	 * @since 1.12.0
	 */
	public function testShouldRunTestActionInRender() {
		$test_action = Sensei_Unsupported_Theme_Handler_Faux_Page_Imitator::TEST_ACTION;

		// We'll test to ensure it uses the template by checking if the
		// test action action was run.
		$this->assertEquals(
			0,
			did_action( $test_action ),
			'Should not have already done action ' . $test_action
		);

		$this->handler->handle_request();

		$this->assertEquals(
			1,
			did_action( $test_action ),
			'Should have done action ' . $test_action
		);
	}

	/**
	 * Ensure Sensei_Unsupported_Theme_Handler_Faux_Page_Imitator sets up a dummy post for
	 * the final render of the faux content.
	 *
	 * @since 1.12.0
	 */
	public function testShouldSetupDummyPost() {
		global $post;

		$this->handler->handle_request();

		$this->assertNotEquals( 0, $post->ID, 'The dummy post ID should be non-zero' );
		$this->assertNull( get_post( $post->ID ), 'The dummy post ID should be unused' );
		$this->assertEquals( 'page', $post->post_type, 'The dummy post type should be "page"' );

		$copied_from_course = array(
			'post_author',
			'post_date',
			'post_date_gmt',
			'post_modified',
			'post_modified_gmt',
		);
		foreach ( $copied_from_course as $field ) {
			$this->assertEquals(
				$this->course->{$field},
				$post->{$field},
				"Dummy post field $field should be copied from the Course"
			);
		}
	}

	/**
	 * Ensure Sensei_Unsupported_Theme_Handler_Faux_Page_Imitator renders the dummy post
	 * with the 'page' template if it's available.
	 *
	 * @since 1.12.0
	 */
	public function testShouldRenderDummyPostWithPageTemplate() {
		$this->handler->handle_request();
		$template_file = apply_filters( 'template_include', 'a-random-template.php' );
		$this->assertEquals( get_template_directory() . '/page.php', $template_file );
	}

	/**
	 * Ensure Sensei_Unsupported_Theme_Handler_Faux_Page_Imitator hides the dummy post
	 * title when it is rendered.
	 *
	 * @since 1.12.0
	 */
	public function testShouldHideDummyPostTitle() {
		global $post;

		$this->handler->handle_request();

		$this->assertEquals(
			'',
			apply_filters( 'the_title', 'Dummy Post Title', $post->ID ),
			'Title of the dummy post should be blank'
		);
		$this->assertEquals(
			'Course Title',
			apply_filters( 'the_title', 'Course Title', $this->course ),
			'Title of the course post should not be blank'
		);
	}

	/**
	 * Helper to set up the current request to be an unsupported page (using course object).
	 * This request will be handled by the unsupported theme handler if the theme is not
	 * supported.
	 *
	 * @since 1.12.0
	 */
	private function setupUnsupportedPage() {
		global $post, $wp_query, $wp_the_query;

		$this->course = $this->factory->course->create_and_get();
		$this->factory->post->create_many( 2 );

		$args         = array(
			'post_type' => 'course',
		);
		$wp_query     = new WP_Query( $args );
		$wp_the_query = $wp_query;

		// Setup $post to be the first post.
		$posts = get_posts(
			array_merge(
				$args,
				array(
					'posts_per_page' => 1,
				)
			)
		);
		$post  = $posts[0];
	}
}
