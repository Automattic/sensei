<?php

class Sensei_Class_Modules_Test extends WP_UnitTestCase {
	use Sensei_Test_Login_Helpers;

	/**
	 * Constructor function
	 */
	public function __construct() {
		parent::__construct();
	}


	/**
	 * setup function
	 * This function sets up the lessons, quizes and their questions. This function runs before
	 * every single test in this class
	 */
	public function setUp(): void {
		parent::setUp();

		$this->factory = new Sensei_Factory();
	}

	/**
	 * tearDown function
	 */
	public function tearDown(): void {
		parent::tearDown();
		$this->factory->tearDown();
	}

	/**
	 * Testing the quiz class to make sure it is loaded
	 */
	public function testClassInstance() {

		// test if the global sensei quiz class is loaded
		$this->assertTrue( isset( Sensei()->modules ), 'Sensei Modules class is not loaded' );

	}

	/**
	 * @covers Sensei_Core_Modules::do_link_to_module
	 */
	public function testDoLinkToModuleEmptyDescription() {
		$course_id   = $this->factory->get_course_with_modules();
		$modules     = wp_get_post_terms( $course_id, 'module' );
		$test_module = $modules[0];

		wp_update_term( $test_module->term_id, $test_module->taxonomy, array( 'description' => '' ) );
		$test_module = get_term( $test_module->term_id, 'module' );

		// Module doesn't have description.
		$this->assertFalse( Sensei()->modules->do_link_to_module( $test_module ) );
	}

	/**
	 * @covers Sensei_Core_Modules::do_link_to_module
	 */
	public function testDoLinkToModuleWithDescription() {
		$course_id   = $this->factory->get_course_with_modules();
		$modules     = wp_get_post_terms( $course_id, 'module' );
		$test_module = $modules[0];

		// Module now has description.
		$this->assertTrue( Sensei()->modules->do_link_to_module( $test_module ) );
	}


	/**
	 * @covers Sensei_Core_Modules::do_link_to_module
	 */
	public function testDoLinkToModuleCurrentTax() {
		global $wp_query;

		$course_id   = $this->factory->get_course_with_modules();
		$modules     = wp_get_post_terms( $course_id, 'module' );
		$test_module = $modules[0];

		wp_update_term( $test_module->term_id, $test_module->taxonomy, array( 'description' => 'A test description' ) );
		$wp_query->is_tax         = true;
		$wp_query->queried_object = $test_module;

		$test_module = get_term( $test_module->term_id, 'module' );

		$this->assertTrue( Sensei()->modules->do_link_to_module( $test_module, true ) );
		$this->assertFalse( Sensei()->modules->do_link_to_module( $test_module, false ) );
	}

	public function testGetTermAuthor_WhenNoAuthorAndSiteAdminEmailDoesNotMatchAnyUser_AddsTheFirstAdminUserInFallback() {
		/* Arrange */
		wp_insert_term( 'Get Started', 'module' );

		$term = wp_insert_term(
			'A test term',
			'module',
			array(
				'description' => 'A yummy apple.',
				'slug'        => 'a-test-term',
			)
		);
		update_site_option( 'admin_email', 'non-existant-user-mail@abc.com' );

		$admins       = get_super_admins();
		$admin        = get_user_by( 'login', $admins[0] );
		$test_user_id = $this->factory->user->create(
			[
				'display_name' => 'Test User',
				'user_email'   => 'test@a.com',
			]
		);

		/* Act */
		$term_author_admin = Sensei_Core_Modules::get_term_author( 'a-test-term' );
		update_term_meta( $term['term_id'], 'module_author', $test_user_id );
		$term_author_teacher = Sensei_Core_Modules::get_term_author( 'a-test-term' );

		/* Assert */
		$this->assertTrue( $admin->ID === $term_author_admin->ID, 'The function should return the first admin user in fallback.' );
		$this->assertFalse( 'non-existant-user-mail@abc.com' === $admin->user_email );
		$this->assertTrue( $test_user_id === $term_author_teacher->ID, 'The function should return the teacher user if exists using term meta.' );
	}

	public function testGetTermAuthor_WhenAuthorDoesNotExists_ReturnsFirstAdminUserAsFallback() {
		/* Arrange */
		wp_insert_term( 'Get Started', 'module' );

		$term = wp_insert_term(
			'A test term',
			'module',
			array(
				'description' => 'A yummy apple.',
				'slug'        => 'a-test-term',
			)
		);

		$admin                = get_user_by( 'email', get_bloginfo( 'admin_email' ) );
		$not_existing_user_id = 2000;

		update_term_meta( $term['term_id'], 'module_author', $not_existing_user_id );

		/* Act */
		$term_author_admin = Sensei_Core_Modules::get_term_author( 'a-test-term' );

		/* Assert */
		$this->assertSame( $admin->ID, $term_author_admin->ID );
	}

	/**
	 * Ensure the course modules column "more" link is shown
	 * only if the course has more than 3 modules.
	 *
	 * @covers Sensei_Core_Modules::course_column_content
	 * @covers Sensei_Core_Modules::output_course_modules_column
	 */
	public function testCourseModulesColumnShouldShowMoreLinkIfMoreThan3Modules() {
		$course_id = $this->factory->course->create();
		$modules   = [
			$this->factory->module->create_and_get(),
			$this->factory->module->create_and_get(),
			$this->factory->module->create_and_get(),
			$this->factory->module->create_and_get(),
		];

		wp_set_object_terms( $course_id, wp_list_pluck( $modules, 'term_id' ), Sensei()->modules->taxonomy );

		ob_start();
		Sensei()->modules->course_column_content( 'modules', $course_id );
		$column_output = ob_get_clean();

		foreach ( $modules as $module ) {
			$this->assertStringContainsString( $module->name, $column_output, 'The module link should be present.' );
		}

		$this->assertStringContainsString( '+1 more', $column_output, 'The "+1 more" link should be present.' );
	}

	/**
	 * Ensure the course modules column "more" link is not shown
	 * if the course has less than 4 modules.
	 *
	 * @covers Sensei_Core_Modules::course_column_content
	 * @covers Sensei_Core_Modules::output_course_modules_column
	 */
	public function testCourseModulesColumnShouldNotShowMoreLinkIfLessThan4Modules() {
		$course_id = $this->factory->course->create();
		$modules   = [
			$this->factory->module->create_and_get(),
			$this->factory->module->create_and_get(),
			$this->factory->module->create_and_get(),
		];

		wp_set_object_terms( $course_id, wp_list_pluck( $modules, 'term_id' ), Sensei()->modules->taxonomy );

		ob_start();
		Sensei()->modules->course_column_content( 'modules', $course_id );
		$column_output = ob_get_clean();

		foreach ( $modules as $module ) {
			$this->assertStringContainsString( $module->name, $column_output, 'The module link should be present.' );
		}

		$this->assertStringNotContainsString( 'more', $column_output, 'The "more" link shouldn\'t be present.' );
	}

	public function testModuleTeacherMeta_WhenAddedToACourse_TeacherIdGetsAddedToMeta() {
		/* Arrange */
		$this->login_as_teacher();

		$course = $this->factory->get_course_with_lessons(
			[
				'module_count'   => 0,
				'lesson_count'   => 1,
				'question_count' => 0,
			]
		);

		$module = wp_insert_term(
			'Get Started',
			'module',
			array(
				'description' => 'A yummy apple.',
				'slug'        => 'get-started',
			)
		);

		/* Act */
		wp_set_object_terms( $course['course_id'], [ $module['term_id'] ], 'module' );

		/* Assert */
		$this->assertSame( absint( get_term_meta( $module['term_id'], 'module_author', true ) ), wp_get_current_user()->ID );
	}

	public function testModuleTeacherMeta_WhenRemovedFromACourse_TeacherIdGetsRemovedFromMeta() {
		/* Arrange */
		$this->login_as_teacher();

		$course = $this->factory->get_course_with_lessons(
			[
				'module_count'   => 0,
				'lesson_count'   => 1,
				'question_count' => 0,
			]
		);

		$module = wp_insert_term(
			'Get Started',
			'module',
			array(
				'description' => 'A yummy apple.',
				'slug'        => 'get-started',
			)
		);

		wp_set_object_terms( $course['course_id'], [ $module['term_id'] ], 'module' );

		/* Act */
		wp_remove_object_terms( $course['course_id'], $module['term_id'], 'module' );

		/* Assert */
		$this->assertSame( '', get_term_meta( $module['term_id'], 'module_author', true ) );
	}

	public function testModuleTeacherMeta_WhenCourseTeacherChanged_TeacherIdMetaChangesAccordingly() {
		/* Arrange */
		$this->login_as_teacher();

		$course = $this->factory->get_course_with_lessons(
			[
				'module_count'   => 0,
				'lesson_count'   => 1,
				'question_count' => 0,
			]
		);

		$module = wp_insert_term(
			'Get Started',
			'module',
			array(
				'description' => 'A yummy apple.',
				'slug'        => 'get-started',
			)
		);

		wp_set_object_terms( $course['course_id'], [ $module['term_id'] ], 'module' );

		$this->login_as_teacher_b();

		/* Act */
		$args = [
			'ID'          => $course['course_id'],
			'post_author' => wp_get_current_user()->ID,
		];
		wp_update_post( $args );

		/* Assert */
		$this->assertSame( absint( get_term_meta( $module['term_id'], 'module_author', true ) ), wp_get_current_user()->ID, 'Module teacher ID meta not set to the updated Author ID' );
	}

	public function testFilterModuleTerms_WhenViewedByTeacher_ExcludesOtherUsersModules() {
		/* Arrange */
		set_current_screen( 'edit-module' );

		$teacher_a  = $this->get_user_by_role( 'teacher' );
		$admin_id   = $this->get_user_by_role( 'administrator' );
		$own_module = wp_insert_term( 'Teacher A Module', 'module', array( 'slug' => 'teacher-a-module' ) );
		update_term_meta( $own_module['term_id'], 'module_author', $teacher_a );
		$admin_module = wp_insert_term( 'Admin Module', 'module', array( 'slug' => 'admin-module' ) );
		update_term_meta( $admin_module['term_id'], 'module_author', $admin_id );

		$this->login_as( $teacher_a );

		/* Act */
		$names = wp_list_pluck(
			get_terms(
				array(
					'taxonomy'   => 'module',
					'hide_empty' => false,
				)
			),
			'name'
		);

		/* Assert */
		self::assertContains( 'Teacher A Module', $names, 'The teacher should see their own module.' );
		self::assertNotContains( 'Admin Module', $names, 'The teacher should not see another user\'s module in the admin list.' );

		set_current_screen( 'front' );
	}

	public function testRestrictModuleTermManagement_WhenTeacherDoesNotOwnModule_DeniesEditAndDelete() {
		/* Arrange */
		$teacher_a = $this->get_user_by_role( 'teacher' );
		$module    = wp_insert_term( 'Owned by A', 'module', array( 'slug' => 'owned-by-a' ) );
		update_term_meta( $module['term_id'], 'module_author', $teacher_a );

		$this->login_as_teacher_b();

		/* Act & Assert */
		self::assertFalse(
			current_user_can( 'edit_term', $module['term_id'] ),
			'A teacher should not be able to edit a module owned by another user.'
		);
		self::assertFalse(
			current_user_can( 'delete_term', $module['term_id'] ),
			'A teacher should not be able to delete a module owned by another user.'
		);
	}

	public function testRestrictModuleTermManagement_WhenTeacherOwnsModule_AllowsEditAndDelete() {
		/* Arrange */
		$teacher_a = $this->get_user_by_role( 'teacher' );
		$module    = wp_insert_term( 'Owned by A', 'module', array( 'slug' => 'owned-by-a' ) );
		update_term_meta( $module['term_id'], 'module_author', $teacher_a );

		$this->login_as_teacher();

		/* Act & Assert */
		self::assertTrue(
			current_user_can( 'edit_term', $module['term_id'] ),
			'A teacher should be able to edit a module they own.'
		);
		self::assertTrue(
			current_user_can( 'delete_term', $module['term_id'] ),
			'A teacher should be able to delete a module they own.'
		);
	}

	public function testRestrictModuleTermManagement_WhenAdminDoesNotOwnModule_AllowsEditAndDelete() {
		/* Arrange */
		$teacher_a = $this->get_user_by_role( 'teacher' );
		$module    = wp_insert_term( 'Owned by A', 'module', array( 'slug' => 'owned-by-a' ) );
		update_term_meta( $module['term_id'], 'module_author', $teacher_a );

		$this->login_as_admin();

		/* Act & Assert */
		self::assertTrue(
			current_user_can( 'edit_term', $module['term_id'] ),
			'An administrator should be able to edit any module.'
		);
		self::assertTrue(
			current_user_can( 'delete_term', $module['term_id'] ),
			'An administrator should be able to delete any module.'
		);
	}

	public function testRestrictModuleTermManagement_WhenEditorDoesNotOwnModule_AllowsEditAndDelete() {
		/* Arrange */
		$teacher_a = $this->get_user_by_role( 'teacher' );
		$module    = wp_insert_term( 'Owned by A', 'module', array( 'slug' => 'owned-by-a' ) );
		update_term_meta( $module['term_id'], 'module_author', $teacher_a );

		$this->login_as_editor();

		/* Act & Assert */
		self::assertTrue(
			current_user_can( 'edit_term', $module['term_id'] ),
			'An editor should be able to edit any module.'
		);
		self::assertTrue(
			current_user_can( 'delete_term', $module['term_id'] ),
			'An editor should be able to delete any module.'
		);
	}

	public function testSaveModuleCourse_WhenTeacherAssignsModuleToCourseTheyCannotEdit_DoesNotAttach() {
		/* Arrange */
		$admin_id     = $this->get_user_by_role( 'administrator' );
		$admin_course = $this->factory->course->create( array( 'post_author' => $admin_id ) );

		$this->login_as_teacher();
		$module = wp_insert_term( 'Teacher Module', 'module', array( 'slug' => 'teacher-module' ) );

		/* Act */
		$_POST['module_courses'] = array( $admin_course );
		Sensei()->modules->save_module_course( $module['term_id'] );
		unset( $_POST['module_courses'] );

		/* Assert */
		$module_terms = wp_get_object_terms( $admin_course, 'module', array( 'fields' => 'ids' ) );
		self::assertNotContains(
			$module['term_id'],
			$module_terms,
			'A teacher should not be able to attach a module to a course they cannot edit.'
		);
	}

	public function testSaveModuleCourse_WhenTeacherRemovesModuleFromCourseTheyCannotEdit_DoesNotDetach() {
		/* Arrange */
		$admin_id     = $this->get_user_by_role( 'administrator' );
		$admin_course = $this->factory->course->create( array( 'post_author' => $admin_id ) );

		$this->login_as_teacher();
		$module = wp_insert_term( 'Teacher Module', 'module', array( 'slug' => 'teacher-module' ) );

		$this->login_as_admin();
		wp_set_object_terms( $admin_course, array( $module['term_id'] ), 'module' );

		/* Act */
		$this->login_as_teacher();
		$_POST['module_courses'] = array();
		Sensei()->modules->save_module_course( $module['term_id'] );
		unset( $_POST['module_courses'] );

		/* Assert */
		$module_terms = wp_get_object_terms( $admin_course, 'module', array( 'fields' => 'ids' ) );
		self::assertContains(
			$module['term_id'],
			$module_terms,
			'A teacher should not be able to detach a module from a course they cannot edit.'
		);
	}

	public function testSaveModuleCourse_WhenTeacherAssignsModuleToCourseTheyOwn_Attaches() {
		/* Arrange */
		$this->login_as_teacher();
		$own_course = $this->factory->course->create( array( 'post_author' => get_current_user_id() ) );
		$module     = wp_insert_term( 'Teacher Module', 'module', array( 'slug' => 'teacher-module' ) );

		/* Act */
		$_POST['module_courses'] = array( $own_course );
		Sensei()->modules->save_module_course( $module['term_id'] );
		unset( $_POST['module_courses'] );

		/* Assert */
		$module_terms = wp_get_object_terms( $own_course, 'module', array( 'fields' => 'ids' ) );
		self::assertContains(
			$module['term_id'],
			$module_terms,
			'A teacher should be able to attach a module to a course they own.'
		);
	}

	public function testSaveModuleCourse_WhenTeacherRemovesModuleFromCourseTheyOwn_Detaches() {
		/* Arrange */
		$this->login_as_teacher();
		$own_course = $this->factory->course->create( array( 'post_author' => get_current_user_id() ) );
		$module     = wp_insert_term( 'Teacher Module', 'module', array( 'slug' => 'teacher-module' ) );
		wp_set_object_terms( $own_course, array( $module['term_id'] ), 'module' );

		/* Act */
		$_POST['module_courses'] = array();
		Sensei()->modules->save_module_course( $module['term_id'] );
		unset( $_POST['module_courses'] );

		/* Assert */
		$module_terms = wp_get_object_terms( $own_course, 'module', array( 'fields' => 'ids' ) );
		self::assertNotContains(
			$module['term_id'],
			$module_terms,
			'A teacher should be able to detach a module from a course they own.'
		);
	}
}
