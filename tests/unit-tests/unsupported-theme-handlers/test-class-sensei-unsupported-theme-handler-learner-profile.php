<?php

require_once 'test-class-sensei-unsupported-theme-handler-page-imitator.php';

class Sensei_Unsupported_Theme_Handler_Learner_Profile_Test extends WP_UnitTestCase {

	/**
	 * @var Sensei_Unsupported_Theme_Handler_Learner_Profile_Test The request handler to test.
	 */
	private $handler;

	/**
	 * @var WP_User
	 */
	private $learner_user;

	public function setUp() {
		parent::setUp();
		$this->factory = new Sensei_Factory();

		$this->setupLearnerProfilePage();

		Sensei_Unsupported_Theme_Handler_Page_Imitator_Test::create_page_template();

		$this->handler = new Sensei_Unsupported_Theme_Handler_Learner_Profile();
	}

	public function tearDown() {
		$this->handler = null;

		Sensei_Unsupported_Theme_Handler_Page_Imitator_Test::delete_page_template();

		parent::tearDown();
	}

	/**
	 * Ensure Sensei_Unsupported_Theme_Handler_Learner_Profile handles the learner_user results page.
	 *
	 * @since 1.12.0
	 */
	public function testShouldHandleLearnerProfilePage() {
		$this->assertTrue( $this->handler->can_handle_request() );
	}

	/**
	 * Ensure Sensei_Unsupported_Theme_Handler_Learner_Profile does not handle a
	 * non-learner_user results page.
	 *
	 * @since 1.12.0
	 */
	public function testShouldNotHandleNonLearnerProfilePage() {
		// Set up the query to be for the Courses page.
		global $wp_query;
		$wp_query = new WP_Query(
			array(
				'post_type' => 'learner_user',
			)
		);

		$this->assertFalse( $this->handler->can_handle_request() );
	}

	/**
	 * Ensure Sensei_Unsupported_Theme_Handler_Learner_Profile disables the header and
	 * footer when rendering the learner_user results page.
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
	 * Ensure Sensei_Unsupported_Theme_Handler_Learner_Profile renders the learner_user results page using
	 * the learner_user-results.php template.
	 *
	 * @since 1.12.0
	 */
	public function testShouldUseLearnerProfileTemplate() {
		// We'll test to ensure it uses the template by checking if the
		// sensei_learner_profile_content_before action was run.
		$this->assertEquals(
			0,
			did_action( 'sensei_learner_profile_content_before' ),
			'Should not have already done action sensei_learner_profile_content_before'
		);

		$this->handler->handle_request();

		$this->assertEquals(
			1,
			did_action( 'sensei_learner_profile_content_before' ),
			'Should have done action sensei_learner_profile_content_before'
		);
	}

	/**
	 * Ensure Sensei_Unsupported_Theme_Handler_Learner_Profile sets up a dummy post for
	 * the final render of the learner_user results content.
	 *
	 * @since 1.12.0
	 */
	public function testShouldSetupDummyPost() {
		global $post;

		$this->handler->handle_request();

		$this->assertNotEquals( 0, $post->ID, 'The dummy post ID should be non-zero' );
		$this->assertNull( get_post( $post->ID ), 'The dummy post ID should be unused' );
		$this->assertEquals( 'page', $post->post_type, 'The dummy post type should be "page"' );

		$copied_from_learner_user = array(
			'post_author'       => 'ID',
			'post_date'         => 'user_registered',
			'post_date_gmt'     => 'user_registered',
			'post_modified'     => 'user_registered',
			'post_modified_gmt' => 'user_registered',
		);
		foreach ( $copied_from_learner_user as $post_field => $user_field ) {
			$this->assertEquals(
				$this->learner_user->{$user_field},
				$post->{$post_field},
				"Dummy post field $post_field should be copied from the Course"
			);
		}
	}

	/**
	 * Helper to set up the current request to be a learner_user results page. This request
	 * will be handled by the unsupported theme handler if the theme is not
	 * supported.
	 *
	 * @since 1.12.0
	 */
	private function setupLearnerProfilePage() {
		global $post, $wp_query, $wp_the_query;

		$this->learner_user = $this->factory->user->create_and_get();

		$posts = $this->factory->post->create_many( 2 );

		// Setup $wp_query to be simple post list with the learner_profile query arg.
		$args         = array(
			'post_type'       => 'post',
			'learner_profile' => $this->learner_user->user_nicename,
		);
		$wp_query     = new WP_Query( $args );
		$wp_the_query = $wp_query;

		// Setup $post to be the first lesson.
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
