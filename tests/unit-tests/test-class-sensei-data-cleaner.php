<?php

require 'includes/class-sensei-data-cleaner.php';

class Sensei_Data_Cleaner_Test extends WP_UnitTestCase {
	// Posts.
	private $post_ids;
	private $biography_ids;
	private $course_ids;
	private $lesson_ids;

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
	 * Set up for tests.
	 */
	public function setUp() {
		parent::setUp();

		$this->setupPosts();
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
}
