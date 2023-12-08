<?php

class Sensei_Class_Teacher_Test extends WP_UnitTestCase {
	use Sensei_Test_Login_Helpers, Sensei_HPPS_Helpers;

	/**
	 * Factory object.
	 *
	 * @var Sensei_Factory
	 */
	protected $factory;

	/**
	 * Constructor function
	 */
	public function __construct() {
		parent::__construct();
	}

	/**
	 * setup function
	 *
	 * This function sets up the lessons, quizes and their questions. This function runs before
	 * every single test in this class
	 */
	public function setUp(): void {
		parent::setUp();

		$this->factory = new Sensei_Factory();
	}

	/**
	 *
	 */
	public function tearDown(): void {
		parent::tearDown();
		$this->factory->tearDown();

		// remove all courses
		$lessons = get_posts( 'post_type=course' );
		foreach ( $lessons as $index => $lesson ) {
			wp_delete_post( $lesson->ID, true );
		}

		// remove all lessons
		$lessons = get_posts( 'post_type=lesson' );
		foreach ( $lessons as $index => $lesson ) {
			wp_delete_post( $lesson->ID, true );
		}

		// remove all quizzes
		$quizzes = get_posts( 'post_type=quiz' );
		foreach ( $quizzes as $index => $quiz ) {
			wp_delete_post( $quiz->ID, true );
		}

	}

	/**
	 * Testing the quiz class to make sure it is loaded
	 */
	public function testClassInstance() {

		// test if the global sensei quiz class is loaded
		$this->assertTrue( isset( Sensei()->teacher ), 'Sensei Modules class is not loaded' );

	}

	/**
	 * Test if the module order is updated after the teacher has changed.
	 */
	public function testUpdateCourseModules_TeacherUpdated_UpdatesTheModuleOrder() {
		/* Arrange. */
		$user_id   = $this->factory->user->create();
		$course_id = $this->factory->course->create();

		$structure_source = [
			[
				'type'    => 'module',
				'title'   => 'Module A',
				'lessons' => [
					[
						'type'  => 'lesson',
						'title' => 'Lesson A',
					],
				],
			],
			[
				'type'    => 'module',
				'title'   => 'Module B',
				'lessons' => [
					[
						'type'  => 'lesson',
						'title' => 'Lesson B',
					],
				],
			],
		];

		$course_structure = Sensei_Course_Structure::instance( $course_id );
		$course_structure->save( $structure_source );

		/* Act. */
		Sensei_Teacher::update_course_modules_author( $course_id, $user_id );

		/* Assert. */
		$new_module_order = Sensei()->modules->get_course_module_order( $course_id );
		$new_structure    = $course_structure->get( 'edit' );

		$expected_module_order = [];
		foreach ( $new_structure as $item ) {
			if ( 'module' === $item['type'] ) {
				$expected_module_order[] = $item['id'];
			}
		}

		$this->assertEquals( $expected_module_order, $new_module_order );
	}

	/**
	 * Testing Sensei_Teacher::update_course_modules_author
	 * This test focus on changing module author
	 *
	 * @since 1.8.0
	 */
	public function testUpdateCourseModulesAuthorChange() {

		// setup assertions
		$test_teacher_id = wp_create_user( 'teacherCourseModulesAuthor', 'teacherCourseModulesAuthor', 'teacherCourseModulesAuthor@test.com' );

		// create test course with current admin as owner
		$test_course_id = $this->factory->get_random_course_id();
		$administrator  = get_user_by( 'email', get_bloginfo( 'admin_email' ) );
		wp_update_post(
			array(
				'ID'          => $test_course_id,
				'post_author' => $administrator->ID,
			)
		);

		// insert sample module terms
		$term_start = wp_insert_term( 'Sample Test Start', 'module' );
		$term_end   = wp_insert_term( 'Sample Test End', 'module' );

		// assign sample terms to course
		wp_set_object_terms( $test_course_id, array( $term_start['term_id'], $term_end['term_id'] ), 'module', true );

		// run the function passing in new teacher
		Sensei_Teacher::update_course_modules_author( $test_course_id, $test_teacher_id );

		// set the current active user to be the teacher so that get object terms
		// only return the teachers terms
		$current_user = get_current_user_id();
		wp_set_current_user( $test_teacher_id );

		// check the if the object terms have change to the new new user within the slug
		$updated_module_terms = wp_get_object_terms( $test_course_id, 'module' );
		$assert_message       = 'Course module term authors not updated.';
		foreach ( $updated_module_terms as $term ) {

			// skip $term_start and $term_end
			if ( $term_start['term_id'] == $term->term_id || $term_end['term_id'] == $term->term_id ) {
				continue;
			}

			$updated_author = Sensei_Core_Modules::get_term_author( $term->slug );
			$this->assertEquals( $test_teacher_id, $updated_author->ID, $assert_message );

		}

		// modules should be removed from the course
		foreach ( $updated_module_terms as $term ) {

			// skip $term_start and $term_end
			$this->assertFalse(
				$term_start['term_id'] == $term->term_id || $term_end['term_id'] == $term->term_id,
				'The old modules should no longer be on the course'
			);
		}

		// reset current user for other tests
		wp_set_current_user( $current_user );

		// when the lessons are moved back to admin they should be duplciated
		// first clear all the object term on the test course.
		$terms = wp_get_object_terms( $test_course_id, 'module' );
		foreach ( $terms as $term ) {
			wp_remove_object_terms( $test_course_id, array( $term->term_id ), 'module' );
		}
		$admin_module = wp_insert_term( 'Admin Test Module', 'module' );
		wp_set_object_terms( $test_course_id, array( $admin_module['term_id'] ), 'module', true );
		Sensei_Teacher::update_course_modules_author( $test_course_id, $administrator->ID );

		// move to teacher and then back to admin
		Sensei_Teacher::update_course_modules_author( $test_course_id, $test_teacher_id );
		Sensei_Teacher::update_course_modules_author( $test_course_id, $administrator->ID );

		// after the update this course should still only have one module as course should not be duplicated for admin
		$admin_term_after_multiple_updates = wp_get_object_terms( $test_course_id, 'module' );
		$message                           = 'A new admin term with slug {adminID}-slug should not have been created. The admin term should not be duplicated when passed back to admin';
		$this->assertFalse( strpos( $admin_term_after_multiple_updates[0]->slug, (string) $administrator->ID ), $message );

	}

	/**
	 * Testing Sensei_Teacher::update_course_modules_author
	 * Test to see if the lessons in the course was assigned to
	 * a new author.
	 *
	 * @since 1.8.0
	 */
	public function testUpdateCourseModulesAuthorChangeLessons() {

		// setup assertions
		$test_teacher_id = wp_create_user( 'teacherCourseModulesAuthorLessons', 'teacherCourseModulesAuthorLessons', 'teacherCourseModulesAuthorLessons@test.com' );

		// create test course with current admin as owner
		$test_course_id = $this->factory->get_random_course_id();
		$administrator  = get_user_by( 'email', get_bloginfo( 'admin_email' ) );
		wp_update_post(
			array(
				'ID'          => $test_course_id,
				'post_author' => $administrator->ID,
			)
		);

		// insert sample module terms
		$test_module_1 = wp_insert_term( 'Lesson Test Module', 'module' );
		$test_module_2 = wp_insert_term( 'Lesson Test Module 2', 'module' );

		// assign sample terms to course
		wp_set_object_terms( $test_course_id, array( $test_module_1['term_id'], $test_module_2['term_id'] ), 'module', true );

		// add sample lessons to course and assign them to modules
		$test_lessons = $this->factory->get_lessons();
		foreach ( $test_lessons as $lesson_id ) {
			update_post_meta( $lesson_id, '_lesson_course', intval( $test_course_id ) );
		}

		// split array in 2 and assign each group of lessons to one of the modules
		$array_middle       = round( ( count( $test_lessons ) + 1 ) / 2 );
		$lesson_in_module_1 = array_slice( $test_lessons, 0, $array_middle );
		$lesson_in_module_2 = array_slice( $test_lessons, $array_middle );

		// assign lessons to module 1
		foreach ( $lesson_in_module_1 as $lesson_id ) {
			wp_set_object_terms( $lesson_id, $test_module_1['term_id'], 'module', false );
		}

		// assign lessons to module 2
		foreach ( $lesson_in_module_2 as $lesson_id ) {
			wp_set_object_terms( $lesson_id, $test_module_2['term_id'], 'module', false );
		}

		// Do the update changing the author
		Sensei_Teacher::update_course_modules_author( $test_course_id, $test_teacher_id );

		// check each lesson
		// do the lessons for module 1 group now belong to ta new module term with the new teacher as owner?
		$expected_module_1_slug = $test_teacher_id . '-' . str_ireplace( ' ', '-', strtolower( ( 'Lesson Test Module' ) ) );
		foreach ( $lesson_in_module_1 as $lesson_id ) {

			$term_after_update = wp_get_object_terms( $lesson_id, 'module' );
			$this->assertEquals( $expected_module_1_slug, $term_after_update[0]->slug, 'Lesson module was not updated, ID: ' . $lesson_id );

		}

		// do the lessons for module 2 group now belong to ta new module term with the new teacher as owner?
		$expected_module_2_slug = $test_teacher_id . '-' . str_ireplace( ' ', '-', strtolower( trim( 'Lesson Test Module 2' ) ) );
		foreach ( $lesson_in_module_2 as $lesson_id ) {
			$term_after_update = wp_get_object_terms( $lesson_id, 'module' );
			$this->assertEquals( $expected_module_2_slug, $term_after_update[0]->slug, 'Lesson module was not updated, ID: ' . $lesson_id );
		}

	}

	public function testUpdateLessonTeacher() {
		// setup assertions
		$test_teacher_id     = wp_create_user( 'teacherUpdateLessonTeacher', 'teacherUpdateLessonTeacher', 'teacherUpdateLessonTeacher@test.com' );
		$test_teacher_id_two = wp_create_user( 'teacherTwoUpdateLessonTeacher', 'teacherTwoUpdateLessonTeacher', 'teacherTwoUpdateLessonTeacher@test.com' );

		// create test course with current admin as owner
		$test_course_id = $this->factory->get_random_course_id();
		// set course teacher to $test_teacher_id
		wp_update_post(
			array(
				'ID'          => $test_course_id,
				'post_author' => $test_teacher_id,
			)
		);

		// add sample lessons to course
		$test_lessons = $this->factory->get_lessons();
		foreach ( $test_lessons as $lesson_id ) {
			update_post_meta( $lesson_id, '_lesson_course', intval( $test_course_id ) );

			$lesson = get_post( $lesson_id, ARRAY_A );
			$id     = wp_insert_post( array_merge( $lesson, array( 'post_title' => 'A Lesson with ID ' . $lesson['ID'] ) ) );

			$lesson = get_post( $id, ARRAY_A );
			$this->assertEquals( $test_teacher_id, intval( $lesson['post_author'] ) );
		}

		// change course teacher
		wp_update_post(
			array(
				'ID'          => $test_course_id,
				'post_author' => $test_teacher_id_two,
			)
		);

		foreach ( $test_lessons as $lesson_id ) {

			$lesson    = get_post( $lesson_id, ARRAY_A );
			$lesson_id = wp_insert_post( array_merge( $lesson, array( 'post_title' => 'An Updated Lesson with ID ' . $lesson['ID'] ) ) );

			$lesson = get_post( $lesson_id, ARRAY_A );
			$this->assertEquals( $test_teacher_id_two, intval( $lesson['post_author'] ) );
		}
	}

	/**
	 * Test to make sure questions change ownership when they can.
	 */
	public function testUpdateQuestionTeacher() {
		$this->login_as_teacher_c();
		$teacher_id_c            = get_current_user_id();
		$quiz_id_c               = $this->factory->quiz->create();
		$shared_questions_c      = $this->factory->question->create_many( 1, [ 'quiz_id' => $quiz_id_c ] );
		$free_questions_c        = $this->factory->question->create_many( 1 );
		$free_questions_c_remain = $this->factory->question->create_many( 1 );

		$this->login_as_teacher_b();
		$teacher_id_b       = get_current_user_id();
		$quiz_id_b          = $this->factory->quiz->create();
		$shared_questions_b = $this->factory->question->create_many( 1, [ 'quiz_id' => $quiz_id_b ] );
		$free_questions_b   = $this->factory->question->create_many( 1 );

		$this->login_as_teacher();
		$teacher_id_a       = get_current_user_id();
		$quiz_id_a          = $this->factory->quiz->create();
		$shared_questions_a = $this->factory->question->create_many( 1, [ 'quiz_id' => $quiz_id_a ] );
		$free_questions_a   = $this->factory->question->create_many( 1 );

		$shared_questions_distribute = array_merge( $free_questions_c, $shared_questions_b, $free_questions_b, $shared_questions_c, $shared_questions_a, $free_questions_a );

		$basic_course = $this->factory->get_course_with_lessons(
			[
				'reuse_questions'         => function () use ( &$shared_questions_distribute ) {
					if ( empty( $shared_questions_distribute ) ) {
						return [ 'post__in' => [ -1 ] ];
					}

					return [
						'post__in' => array_filter(
							[
								array_pop( $shared_questions_distribute ),
								array_pop( $shared_questions_distribute ),
							]
						),
					];
				},
				'multiple_question_count' => 1,
				'lesson_count'            => 3,
			]
		);

		$multi_question_ids = get_posts(
			[
				'post_type' => 'multiple_question',
				'fields'    => 'ids',
			]
		);

		$this->login_as_admin();
		Sensei()->teacher->update_course_lessons_author( $basic_course['course_id'], $teacher_id_b );

		$this->assertPostAuthor( $teacher_id_c, $shared_questions_c, 'Shared questions from teacher C should still be authored by teacher C' );
		$this->assertPostAuthor( $teacher_id_b, $shared_questions_b, 'Shared questions from teacher B should still be authored by teacher B' );
		$this->assertPostAuthor( $teacher_id_a, $shared_questions_a, 'Shared questions from teacher A should still be authored by teacher A' );
		$this->assertPostAuthor( $teacher_id_b, $free_questions_a, 'Free questions from teacher A should now be owned by teacher B' );
		$this->assertPostAuthor( $teacher_id_b, $free_questions_b, 'Free questions from teacher B should still be owned by teacher B' );
		$this->assertPostAuthor( $teacher_id_b, $free_questions_c, 'Free used questions from teacher C should now be owned by teacher B' );
		$this->assertPostAuthor( $teacher_id_c, $free_questions_c_remain, 'Free unused questions from teacher C should still be owned by teacher C' );
		$this->assertPostAuthor( $teacher_id_b, $multi_question_ids, 'All multi-questions should be transferred to teacher B' );
	}

	/**
	 * Asset a list of posts have a certain post_author.
	 *
	 * @param int    $user_id  User ID to check.
	 * @param array  $post_ids Post IDs to check.
	 * @param string $message  Message to show..
	 */
	private function assertPostAuthor( int $user_id, array $post_ids, string $message = '' ) {
		foreach ( $post_ids as $post_id ) {
			$check_post = get_post( $post_id );
			$this->assertEquals( $user_id, (int) $check_post->post_author, $message );
		}
	}

	/**
	 * Test Sensei()->Teacher->add_courses_to_author_archive
	 * Test for the normal case on
	 *
	 * @since 1.8.4
	 */
	public function testAddCoursesToAuthorArchive() {

		// create WP_Query object with the right conditions
		$query            = new WP_Query();
		$query->is_author = true;
		Sensei()->teacher->create_role();

		// test author for which the archive is running
		$teacher_id = wp_create_user( 'teacher_archive_post_type', 'teacher_archive_post_type', 'teacher_archive_post_type@tt.com' );
		$teacher    = get_userdata( $teacher_id );

		$teacher->add_role( 'teacher' );
		$query->set( 'author_name', 'teacher_archive_post_type' );
		wp_set_current_user( $teacher_id );

		// Test the query with no post_type set
		$changed_query = Sensei()->teacher->add_courses_to_author_archive( $query );
		$this->assertEquals( array( 'post', 'course' ), $changed_query->get( 'post_type' ), 'The new WP_Query object post type should have been changed' );

		// test the existing post types passed in
		$query->set( 'post_type', array( 'custom_pt', 'books', 'records' ) );
		$changed_query = Sensei()->teacher->add_courses_to_author_archive( $query );
		$this->assertEquals( array( 'custom_pt', 'books', 'records', 'course' ), $changed_query->get( 'post_type' ), 'The new WP_Query object post type should have been merged with existing post types' );

		// test if the post type is set to a string
		$query->set( 'post_type', 'simple_post_type' );
		$changed_query = Sensei()->teacher->add_courses_to_author_archive( $query );
		$this->assertEquals( array( 'simple_post_type', 'course' ), $changed_query->get( 'post_type' ), 'The new WP_Query object post type should be an array of two items' );

	}

	/**
	 *
	 * Asserts that only users with edit course capabilities are returned
	 *
	 * @covers Sensei_Teacher::get_teachers_and_authors
	 */
	public function testGetTeachersAndAuthors() {
		$subscribers    = $this->factory->user->create_many( 2, [ 'role' => 'subscriber' ] );
		$teachers       = $this->factory->user->create_many( 3, [ 'role' => 'teacher' ] );
		$editors        = $this->factory->user->create_many( 1, [ 'role' => 'editor' ] );
		$administrators = $this->factory->user->create_many( 1, [ 'role' => 'administrator' ] );

		$users_with_edit_courses_rights_ids = Sensei()->teacher->get_teachers_and_authors();

		foreach ( array_merge( $administrators, $editors, $teachers ) as $user_id ) {
			$this->assertContainsEquals( $user_id, $users_with_edit_courses_rights_ids, 'Should include users which have the `edit_courses` capability.' );
		}
		foreach ( $subscribers as $subscriber_id ) {
			$this->assertNotContainsEquals( $subscriber_id, $users_with_edit_courses_rights_ids, 'Should not include users that don\'t have the `edit_courses` capability.' );
		}
	}

	public function testModuleSaving_ifCustomSlugAdded_savesExistingTeacherIdFromSlugToMeta() {
		// Arrange.
		$this->login_as_teacher();
		$current_user_id = wp_get_current_user()->ID;

		$new_term = wp_insert_term(
			'Test module',
			'module',
			array(
				'description' => 'A yummy apple.',
				'slug'        => $current_user_id . '-test-module',
			)
		);

		// Act.
		wp_update_term( $new_term['term_id'], 'module', [ 'slug' => 'custom-slug' ] );

		// Assert.
		$term_meta = get_term_meta( $new_term['term_id'], 'module_author', true );
		$this->assertEquals( $current_user_id, $term_meta );
	}

	/**
	 * Test to make sure that author for all lessons is updated including drafts.
	 */
	public function testUpdateCourseLessonsAuthor_WhenLessonsAreInDraftStatus_GetAuthorUpdatedToTheNewValue() {
		$this->login_as_teacher_b();
		$new_teacher_id = get_current_user_id();
		$this->login_as_teacher();

		$course = $this->factory->get_course_with_lessons(
			[
				'multiple_question_count' => 1,
				'lesson_count'            => 3,
				'lesson_args'             => [ 'post_status' => 'draft' ],
			]
		);

		$this->login_as_admin();
		Sensei()->teacher->update_course_lessons_author( $course['course_id'], $new_teacher_id );

		$this->assertPostAuthor( $new_teacher_id, $course['lesson_ids'], 'All lessons must be from teacher B now' );
	}

	public function testFilterLearnersQuery_WhenUserIsNoATeacher_ReturnsSameInput() {
		// Arrange.
		set_current_screen( 'sensei-lms_page_sensei_learners' ); // Pretend we're on the students admin screen.

		// Act.
		$sql = Sensei()->teacher->filter_learners_query( 'WHERE 1=1' );

		// Assert.
		$this->assertSame( 'WHERE 1=1', $sql );
	}

	public function testFilterLearnersQuery_WhenHPPSIsEnabledAndTeacherHasCourses_ReturnsCorrectCustomTablesQuery() {
		$nonteacher_course_id = $this->factory->course->create();

		$this->login_as_teacher();
		$teacher_course_id_1 = $this->factory->course->create();
		$teacher_course_id_2 = $this->factory->course->create();

		$this->enable_hpps_tables_repository();
		set_current_screen( 'sensei-lms_page_sensei_learners' ); // Pretend we're on the students admin screen.

		// Act.
		$sql = Sensei()->teacher->filter_learners_query( 'WHERE 1=1' );

		// Assert.
		$expected = "
INNER JOIN wptests_sensei_lms_progress AS progress ON u.ID = progress.user_id
WHERE 1=1
AND progress.post_id IN ($teacher_course_id_1,$teacher_course_id_2)";
		$this->assertSame( $expected, $sql );

		// Reset.
		$this->reset_hpps_repository();
	}

	public function testFilterLearnersQuery_WhenHPPSIsDisabledAndTeacherHasCourses_ReturnsCorrectCommentsQuery() {
		$nonteacher_course_id = $this->factory->course->create();

		$this->login_as_teacher();
		$teacher_course_id_1 = $this->factory->course->create();
		$teacher_course_id_2 = $this->factory->course->create();

		set_current_screen( 'sensei-lms_page_sensei_learners' ); // Pretend we're on the students admin screen.

		// Act.
		$sql = Sensei()->teacher->filter_learners_query( 'WHERE 1=1' );

		// Assert.
		$expected = "
INNER JOIN wptests_comments AS comments ON u.ID = comments.user_id
WHERE 1=1
AND comments.comment_post_ID IN ($teacher_course_id_1,$teacher_course_id_2)
AND comments.comment_type = 'sensei_course_status'";
		$this->assertSame( $expected, $sql );
	}

	public function testFilterLearnersQuery_WhenTheTeacherHasNoCourses_ReturnsCourseIdOfZero() {
		// Arrange.
		$nonteacher_course_id  = $this->factory->course->create();

		$this->login_as_teacher();

		set_current_screen( 'sensei-lms_page_sensei_learners' ); // Pretend we're on the students admin screen.

		// Act.
		$sql = Sensei()->teacher->filter_learners_query( 'WHERE 1=1' );

		// Assert.
		$expected = "
INNER JOIN wptests_comments AS comments ON u.ID = comments.user_id
WHERE 1=1
AND comments.comment_post_ID IN (0)
AND comments.comment_type = 'sensei_course_status'";
		$this->assertSame( $expected, $sql );
	}
}
