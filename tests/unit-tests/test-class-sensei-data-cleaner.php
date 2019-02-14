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

	// Taxonomies.
	private $modules;
	private $categories;
	private $ages;

	// Users.
	private $regular_user_id;
	private $teacher_user_id;

	/**
	 * Add some posts to run tests against. Any that are associated with Sensei
	 * should be trashed on cleanup. The others should not be trashed.
	 */
	private function setupPosts() {
		// Create some regular posts.
		$this->post_ids = $this->factory->post->create_many(
			2,
			array(
				'post_status' => 'publish',
				'post_type'   => 'post',
			)
		);

		// Create an unrelated CPT to ensure its posts do not get deleted.
		register_post_type(
			'biography',
			array(
				'label'       => 'Biographies',
				'description' => 'A biography of a famous person (for testing)',
				'public'      => true,
			)
		);
		$this->biography_ids = $this->factory->post->create_many(
			4,
			array(
				'post_status' => 'publish',
				'post_type'   => 'biography',
			)
		);

		// Create some Sensei posts.
		$this->course_ids = $this->factory->post->create_many(
			8,
			array(
				'post_status' => 'publish',
				'post_type'   => 'course',
			)
		);

		$this->lesson_ids = $this->factory->post->create_many(
			16,
			array(
				'post_status' => 'publish',
				'post_type'   => 'lesson',
			)
		);
	}

	/**
	 * Add some taxonomies to run tests against. Any that are associated with
	 * Sensei should be deleted on cleanup. The others should not be deleted.
	 */
	private function setupTaxonomyTerms() {
		// Setup some modules.
		$this->modules = array();

		for ( $i = 1; $i <= 3; $i++ ) {
			$this->modules[] = wp_insert_term( 'Module ' . $i, 'module' );
		}

		wp_set_object_terms(
			$this->course_ids[0],
			array(
				$this->modules[0]['term_id'],
				$this->modules[1]['term_id'],
			),
			'module'
		);
		wp_set_object_terms(
			$this->course_ids[1],
			array(
				$this->modules[1]['term_id'],
				$this->modules[2]['term_id'],
			),
			'module'
		);
		wp_set_object_terms(
			$this->course_ids[2],
			array(
				$this->modules[0]['term_id'],
				$this->modules[1]['term_id'],
				$this->modules[2]['term_id'],
			),
			'module'
		);

		// Setup some categories.
		$this->categories = array();

		for ( $i = 1; $i <= 3; $i++ ) {
			$this->categories[] = wp_insert_term( 'Category ' . $i, 'category' );
		}

		wp_set_object_terms(
			$this->course_ids[0],
			array(
				$this->categories[0]['term_id'],
				$this->categories[1]['term_id'],
			),
			'category'
		);
		wp_set_object_terms(
			$this->post_ids[0],
			array(
				$this->categories[1]['term_id'],
				$this->categories[2]['term_id'],
			),
			'category'
		);
		wp_set_object_terms(
			$this->biography_ids[2],
			array(
				$this->categories[0]['term_id'],
				$this->categories[1]['term_id'],
				$this->categories[2]['term_id'],
			),
			'category'
		);

		// Setup a custom taxonomy.
		register_taxonomy( 'age', 'biography' );

		$this->ages = array(
			wp_insert_term( 'Old', 'age' ),
			wp_insert_term( 'New', 'age' ),
		);

		wp_set_object_terms( $this->biography_ids[0], $this->ages[0]['term_id'], 'age' );
		wp_set_object_terms( $this->biography_ids[1], $this->ages[1]['term_id'], 'age' );

		// Add a piece of termmeta for every term.
		$terms = array_merge( $this->modules, $this->categories, $this->ages );
		foreach ( $terms as $term ) {
			$key   = 'the_term_id';
			$value = 'The ID is ' . $term['term_id'];
			update_term_meta( $term['term_id'], $key, $value );
		}
	}

	/**
	 * Add some pages to run tests against. Any that are associated with Sensei
	 * should be trashed on cleanup. The others should not be trashed.
	 */
	private function setupPages() {
		// Create some regular pages.
		$this->regular_page_ids = $this->factory->post->create_many(
			2,
			array(
				'post_type'  => 'page',
				'post_title' => 'Normal page',
			)
		);

		// Create the Course Archive page.
		$this->course_archive_page_id = $this->factory->post->create(
			array(
				'post_type'  => 'page',
				'post_title' => 'Course Archive Page',
			)
		);
		Sensei()->settings->set( 'course_page', $this->course_archive_page_id );

		// Create the My Courses page.
		$this->my_courses_page_id = $this->factory->post->create(
			array(
				'post_type'  => 'page',
				'post_title' => 'My Courses',
			)
		);
		Sensei()->settings->set( 'my_course_page', $this->my_courses_page_id );

		// Refresh the Sensei settings in memory.
		Sensei()->settings->get_settings();
	}

	/**
	 * Add some users to run tests against. The roles and capabilities
	 * associated with Sensei should be deleted on cleanup. The others should
	 * not be deleted.
	 */
	private function setupUsers() {
		// Ensure the role is created.
		Sensei()->teacher->create_role();

		// Create a regular user and assign some caps.
		$this->regular_user_id = $this->factory->user->create( array( 'role' => 'author' ) );
		$regular_user          = get_user_by( 'id', $this->regular_user_id );
		$regular_user->add_cap( 'edit_others_posts' );
		$regular_user->add_cap( 'manage_sensei' );

		// Create a teacher user and assign some caps.
		$this->teacher_user_id = $this->factory->user->create( array( 'role' => 'teacher' ) );
		$teacher_user          = get_user_by( 'id', $this->teacher_user_id );
		$teacher_user->add_cap( 'edit_others_posts' );
		$teacher_user->add_cap( 'manage_sensei' );

		// Add a Sensei cap to an existing role.
		$role = get_role( 'editor' );
		$role->add_cap( 'manage_sensei_grades' );
	}

	/**
	 * Set up for tests.
	 */
	public function setUp() {
		parent::setUp();

		$this->setupPosts();
		$this->setupPages();
		$this->setupTaxonomyTerms();
		$this->setupUsers();
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
		update_option( 'sensei-version', '1.10.0' );

		// Set a couple other options.
		update_option( 'my_option_1', 'Value 1' );
		update_option( 'my_option_2', 'Value 2' );

		Sensei_Data_Cleaner::cleanup_all();

		// Ensure the Sensei options are deleted.
		$this->assertFalse( get_option( 'sensei_usage_tracking_opt_in_hide' ) );
		$this->assertFalse( get_option( 'sensei-version' ) );

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

	/**
	 * Ensure the data for Sensei taxonomies and terms are deleted.
	 *
	 * @covers Sensei_Data_Cleaner::cleanup_all
	 * @covers Sensei_Data_Cleaner::cleanup_taxonomies
	 */
	public function testSenseiTaxonomiesDeleted() {
		global $wpdb;

		Sensei_Data_Cleaner::cleanup_all();

		foreach ( $this->modules as $module ) {
			$term_id          = $module['term_id'];
			$term_taxonomy_id = $module['term_taxonomy_id'];

			// Ensure the data is deleted from all the relevant DB tables.
			$this->assertEquals(
				array(),
				$wpdb->get_results(
					$wpdb->prepare(
						"SELECT * from $wpdb->termmeta WHERE term_id = %s",
						$term_id
					)
				),
				'Sensei term meta should be deleted'
			);

			$this->assertEquals(
				array(),
				$wpdb->get_results(
					$wpdb->prepare(
						"SELECT * from $wpdb->terms WHERE term_id = %s",
						$term_id
					)
				),
				'Sensei term should be deleted'
			);

			$this->assertEquals(
				array(),
				$wpdb->get_results(
					$wpdb->prepare(
						"SELECT * from $wpdb->term_taxonomy WHERE term_taxonomy_id = %s",
						$term_taxonomy_id
					)
				),
				'Sensei term taxonomy should be deleted'
			);

			$this->assertEquals(
				array(),
				$wpdb->get_results(
					$wpdb->prepare(
						"SELECT * from $wpdb->term_relationships WHERE term_taxonomy_id = %s",
						$term_taxonomy_id
					)
				),
				'Sensei term relationships should be deleted'
			);
		}
	}

	/**
	 * Ensure the data for non-Sensei taxonomies and terms are not deleted.
	 *
	 * @covers Sensei_Data_Cleaner::cleanup_all
	 * @covers Sensei_Data_Cleaner::cleanup_taxonomies
	 */
	public function testOtherTaxonomiesUntouched() {
		global $wpdb;

		Sensei_Data_Cleaner::cleanup_all();

		// Check "Category 1".
		$this->assertEquals(
			array( $this->biography_ids[2] ),
			$this->getPostIdsWithTerm( $this->categories[0]['term_id'], 'category' ),
			'Category 1 should not be deleted'
		);

		// Check "Category 2". Sort the arrays because the ordering doesn't
		// matter.
		$expected = array( $this->post_ids[0], $this->biography_ids[2] );
		$actual   = $this->getPostIdsWithTerm( $this->categories[1]['term_id'], 'category' );
		sort( $expected );
		sort( $actual );
		$this->assertEquals(
			$expected,
			$actual,
			'Category 2 should not be deleted'
		);

		// Check "Category 3". Sort the arrays because the ordering doesn't
		// matter.
		$expected = array( $this->post_ids[0], $this->biography_ids[2] );
		$actual   = $this->getPostIdsWithTerm( $this->categories[2]['term_id'], 'category' );
		sort( $expected );
		sort( $actual );
		$this->assertEquals(
			$expected,
			$actual,
			'Category 3 should not be deleted'
		);

		// Check "Old" biographies.
		$this->assertEquals(
			array( $this->biography_ids[0] ),
			$this->getPostIdsWithTerm( $this->ages[0]['term_id'], 'age' ),
			'"Old" should not be deleted'
		);

		// Check "New" biographies.
		$this->assertEquals(
			array( $this->biography_ids[1] ),
			$this->getPostIdsWithTerm( $this->ages[1]['term_id'], 'age' ),
			'"New" should not be deleted'
		);
	}

	/**
	 * Ensure the Sensei roles and caps are deleted.
	 *
	 * @covers Sensei_Data_Cleaner::cleanup_all
	 * @covers Sensei_Data_Cleaner::cleanup_roles_and_caps
	 */
	public function testSenseiRolesAndCapsDeleted() {
		Sensei_Data_Cleaner::cleanup_all();

		// Refresh user info.
		wp_cache_flush();

		$regular_user = get_user_by( 'id', $this->regular_user_id );
		$this->assertTrue( in_array( 'author', $regular_user->roles, true ), 'Author role should not be removed' );
		$this->assertTrue( $regular_user->has_cap( 'edit_others_posts' ), 'Non-Sensei cap should not be removed from user' );
		$this->assertFalse( $regular_user->has_cap( 'manage_sensei' ), 'Sensei cap should be removed from user' );

		$teacher_user = get_user_by( 'id', $this->teacher_user_id );
		$this->assertFalse( in_array( 'teacher', $teacher_user->roles, true ), 'Teacher role should be removed from user' );
		$this->assertFalse( array_key_exists( 'teacher', $teacher_user->caps ), 'Teacher role should be removed from user caps' );
		$this->assertTrue( $teacher_user->has_cap( 'edit_others_posts' ), 'Non-Sensei cap should not be removed from teacher' );
		$this->assertFalse( $teacher_user->has_cap( 'manage_sensei' ), 'Sensei cap should be removed from teacher' );

		$role = get_role( 'editor' );
		$this->assertFalse( $role->has_cap( 'manage_sensei_grades' ), 'Sensei cap should be removed from role' );

		$role = get_role( 'teacher' );
		$this->assertNull( $role, 'Teacher role should be removed overall' );
	}

	/**
	 * Ensure the Sensei transients are deleted from the DB.
	 *
	 * @covers Sensei_Data_Cleaner::cleanup_all
	 * @covers Sensei_Data_Cleaner::cleanup_transients
	 */
	public function testSenseiTransientsDeleted() {
		set_transient( 'sensei_123_none_module_lessons', 'value', 3600 );
		set_transient( 'sensei_answers_123_456', 'value', 3600 );
		set_transient( 'sensei_answers_feedback_123_456', 'value', 3600 );
		set_transient( 'quiz_grades_123_456', 'value', 3600 );
		set_transient( 'other_transient', 'value', 3600 );

		Sensei_Data_Cleaner::cleanup_all();

		// Flush transients from cache.
		wp_cache_flush();

		$prefix         = '_transient_';
		$timeout_prefix = '_transient_timeout_';

		// Ensure the transients and their timeouts were deleted.
		$this->assertFalse( get_option( "{$prefix}sensei_123_none_module_lessons" ), 'Sensei none_module_lessons transient' );
		$this->assertFalse( get_option( "{$timeout_prefix}sensei_123_none_module_lessons" ), 'Sensei none_module_lessons transient timeout' );
		$this->assertFalse( get_option( "{$prefix}sensei_answers_123_456" ), 'Sensei sensei_answers transient' );
		$this->assertFalse( get_option( "{$timeout_prefix}sensei_answers_123_456" ), 'Sensei sensei_answers transient timeout' );
		$this->assertFalse( get_option( "{$prefix}sensei_answers_feedback_123_456" ), 'Sensei sensei_answers_feedback transient' );
		$this->assertFalse( get_option( "{$timeout_prefix}sensei_answers_feedback_123_456" ), 'Sensei sensei_answers_feedback transient timeout' );
		$this->assertFalse( get_option( "{$prefix}quiz_grades_123_456" ), 'Sensei quiz_grades transient' );

		// Ensure the other transient and its timeout was not deleted.
		$this->assertNotFalse( get_option( "{$prefix}other_transient" ), 'Non-Sensei transient' );
		$this->assertNotFalse( get_option( "{$timeout_prefix}other_transient" ), 'Non-Sensei transient' );
	}

	/**
	 * Ensure the Sensei user meta are deleted from the DB.
	 *
	 * @covers Sensei_Data_Cleaner::cleanup_all
	 * @covers Sensei_Data_Cleaner::cleanup_user_meta
	 */
	public function testCleanupUserMeta() {
		$student_id = $this->factory->user->create( array( 'role' => 'author' ) );

		$keep_meta_keys = array(
			'module_progress_1_1',
			'_module_progress__1',
			'_module_progress',
			'_sensei_hide_menu_settings_notice',
			'test_sensei_hide_menu_settings_notice',
			'^sensei_hide_menu_settings_notice$',
		);

		$remove_meta_keys = array(
			'_module_progress_1_1',
			'_module_progress_10000_10',
			'_module_progress_8_1',
			'sensei_hide_menu_settings_notice',
		);

		foreach ( array_merge( $keep_meta_keys, $remove_meta_keys ) as $meta_key ) {
			update_user_meta( $student_id, $meta_key, 'test_value' );
			$this->assertTrue( 'test_value' === get_user_meta( $student_id, $meta_key, true ) );
		}

		Sensei_Data_Cleaner::cleanup_all();
		wp_cache_flush();

		foreach ( $keep_meta_keys as $meta_key ) {
			$this->assertTrue( 'test_value' === get_user_meta( $student_id, $meta_key, true ), sprintf( 'The user meta key "%s" was supposed to be preserved.', $meta_key ) );
		}

		foreach ( $remove_meta_keys as $meta_key ) {
			$this->assertTrue( '' === get_user_meta( $student_id, $meta_key, true ), sprintf( 'The user meta key "%s" was supposed to be removed.', $meta_key ) );
		}
	}

	/**
	 * Ensure the Sensei roles and caps are deleted.
	 *
	 * @covers Sensei_Data_Cleaner::cleanup_all
	 * @covers Sensei_Data_Cleaner::cleanup_roles_and_caps
	 */
	public function testSenseiPostMetaDeleted() {
		// Sensei post meta.
		update_post_meta( $this->course_ids[0], '_course_prerequisite', $this->course_ids[1] );
		update_post_meta( $this->lesson_ids[0], '_lesson_course', $this->course_ids[0] );

		// Non-Sensei post meta.
		update_post_meta( $this->post_ids[0], 'meta1', 'value1' );
		update_post_meta( $this->post_ids[1], 'meta2', 'value2' );

		// Sensei meta that needs to be deleted directly.
		update_post_meta( $this->post_ids[0], 'sensei_payment_complete', true );
		update_post_meta( $this->post_ids[1], 'sensei_products_processed', 3 );

		Sensei_Data_Cleaner::cleanup_all();

		$this->emptyTrash();

		// Ensure Sensei post meta is deleted.
		$this->assertEmpty( get_post_meta( $this->course_ids[0], '_course_prerequisite', true ), '_course_prerequisite should be deleted' );
		$this->assertEmpty( get_post_meta( $this->lesson_ids[0], '_lesson_course', true ), '_lesson_course should be deleted' );

		// Ensure non-Sensei post meta is not deleted.
		$this->assertEquals( 'value1', get_post_meta( $this->post_ids[0], 'meta1', true ), 'meta1 should not be deleted' );
		$this->assertEquals( 'value2', get_post_meta( $this->post_ids[1], 'meta2', true ), 'meta2 should not be deleted' );

		// Ensure other Sensei meta is deleted.
		$this->assertEmpty( get_post_meta( $this->post_ids[0], 'sensei_payment_complete', true ), 'sensei_payment_complete should be deleted' );
		$this->assertEmpty( get_post_meta( $this->post_ids[1], 'sensei_products_processed', true ), 'sensei_products_processed should not be deleted' );
	}

	/* Helper functions. */

	private function getPostIdsWithTerm( $term_id, $taxonomy ) {
		return get_posts(
			array(
				'fields'    => 'ids',
				'post_type' => 'any',
				'tax_query' => array(
					array(
						'field'    => 'term_id',
						'terms'    => $term_id,
						'taxonomy' => $taxonomy,
					),
				),
			)
		);
	}

	private function emptyTrash() {
		$trashed_posts = get_posts(
			array(
				'post_type'   => 'any',
				'post_status' => 'trash',
				'numberposts' => -1,
			)
		);

		// Set the trash time to be a very long time ago.
		foreach ( $trashed_posts as $post ) {
			update_post_meta( $post->ID, '_wp_trash_meta_time', 0 );
		}

		// Run a scheduled trash cleanup.
		wp_scheduled_delete();
	}
}
