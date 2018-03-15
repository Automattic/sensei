<?php

require 'includes/class-sensei-data-cleaner.php';

class Sensei_Data_Cleaner_Test extends WP_UnitTestCase {
	// Posts.
	private $post_ids;
	private $biography_ids;
	private $course_ids;
	private $lesson_ids;

	// Pages.
	private $regular_page_ids;
	private $course_archive_page_id;
	private $my_courses_page_id;

	/**
	 * Add some posts to run tests against. Any that are associated with Sensei
	 * should be trashed on cleanup. The other should not be trashed.
	 */
	private function setupPosts() {
		// Create some regular posts.
		$this->post_ids = $this->factory->post->create_many( 2, array(
			'post_status' => 'publish',
			'post_type'   => 'post',
		) );

		// Create an unrelated CPT to ensure its posts do not get deleted.
		register_post_type( 'biography', array(
			'label'       => 'Biographies',
			'description' => 'A biography of a famous person (for testing)',
			'public'      => true,
		) );
		$this->biography_ids = $this->factory->post->create_many( 4, array(
			'post_status' => 'publish',
			'post_type'   => 'biography',
		) );

		// Create some Sensei posts.
		$this->course_ids = $this->factory->post->create_many( 8, array(
			'post_status' => 'publish',
			'post_type'   => 'course',
		) );

		$this->lesson_ids = $this->factory->post->create_many( 16, array(
			'post_status' => 'publish',
			'post_type'   => 'lesson',
		) );
	}

	/**
	 * Add some pages to run tests against. Any that are associated with Sensei
	 * should be trashed on cleanup. The others should not be trashed.
	 */
	private function setupPages() {
		// Create some regular pages.
		$this->regular_page_ids = $this->factory->post->create_many( 2, array(
			'post_type'  => 'page',
			'post_title' => 'Normal page',
		) );

		// Create the Course Archive page.
		$this->course_archive_page_id = $this->factory->post->create( array(
			'post_type'  => 'page',
			'post_title' => 'Course Archive Page',
		) );
		Sensei()->settings->set( 'course_page', $this->course_archive_page_id );

		// Create the My Courses page.
		$this->my_courses_page_id = $this->factory->post->create( array(
			'post_type'  => 'page',
			'post_title' => 'My Courses',
		) );
		Sensei()->settings->set( 'my_course_page', $this->my_courses_page_id );

		// Refresh the Sensei settings in memory.
		Sensei()->settings->get_settings();
	}

	/**
	 * Set up for tests.
	 */
	public function setUp() {
		parent::setUp();

		$this->setupPosts();
		$this->setupPages();
	}

	/**
	 * Ensure the Sensei posts are moved to trash.
	 *
	 * @covers Sensei_Data_Cleaner::cleanup_all
	 * @covers Sensei_Data_Cleaner::cleanup_custom_post_types
	 */
	public function testSenseiPostsTrashed() {
		Sensei_Data_Cleaner::cleanup_all();

		$ids = array_merge( $this->course_ids, $this->lesson_ids );
		foreach ( $ids as $id ) {
			$post = get_post( $id );
			$this->assertEquals( 'trash', $post->post_status, 'Sensei post should be trashed' );
		}
	}

	/**
	 * Ensure the non-Sensei posts are not moved to trash.
	 *
	 * @covers Sensei_Data_Cleaner::cleanup_all
	 * @covers Sensei_Data_Cleaner::cleanup_custom_post_types
	 */
	public function testOtherPostsUntouched() {
		Sensei_Data_Cleaner::cleanup_all();

		$ids = array_merge( $this->post_ids, $this->biography_ids );
		foreach ( $ids as $id ) {
			$post = get_post( $id );
			$this->assertNotEquals( 'trash', $post->post_status, 'Non-Sensei post should not be trashed' );
		}
	}

	/**
	 * Ensure the Sensei options are deleted and the others aren't.
	 *
	 * @covers Sensei_Data_Cleaner::cleanup_all
	 * @covers Sensei_Data_Cleaner::cleanup_options
	 */
	public function testSenseiOptionsDeleted() {
		// Set a couple Sensei options.
		update_option( 'sensei_usage_tracking_opt_in_hide', '1' );
		update_option( 'woothemes-sensei-version', '1.10.0' );

		// Set a couple other options.
		update_option( 'my_option_1', 'Value 1' );
		update_option( 'my_option_2', 'Value 2' );

		Sensei_Data_Cleaner::cleanup_all();

		// Ensure the Sensei options are deleted.
		$this->assertFalse( get_option( 'sensei_usage_tracking_opt_in_hide' ) );
		$this->assertFalse( get_option( 'woothemes-sensei-version' ) );

		// Ensure the non-Sensei options are intact.
		$this->assertEquals( 'Value 1', get_option( 'my_option_1' ) );
		$this->assertEquals( 'Value 2', get_option( 'my_option_2' ) );
	}

	/**
	 * Ensure the Sensei pages are trashed, and the other pages are not.
	 *
	 * @covers Sensei_Data_Cleaner::cleanup_all
	 * @covers Sensei_Data_Cleaner::cleanup_pages
	 */
	public function testSenseiPagesTrashed() {
		Sensei_Data_Cleaner::cleanup_all();

		$this->assertEquals( 'trash', get_post_status( $this->course_archive_page_id ), 'Course Archive page should be trashed' );
		$this->assertEquals( 'trash', get_post_status( $this->my_courses_page_id ), 'My Courses page should be trashed' );

		foreach ( $this->regular_page_ids as $page_id ) {
			$this->assertNotEquals( 'trash', get_post_status( $page_id ), 'Regular page should not be trashed' );
		}
	}
}
