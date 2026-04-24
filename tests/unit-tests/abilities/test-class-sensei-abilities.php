<?php
/**
 * Tests for Sensei_Abilities.
 *
 * @covers Sensei_Abilities
 */
class Sensei_Abilities_Test extends WP_UnitTestCase {
	use Sensei_Test_Login_Helpers;

	public function setUp(): void {
		parent::setUp();

		if ( ! function_exists( 'wp_get_ability' ) ) {
			$this->markTestSkipped( 'The WordPress Abilities API (WP 6.9+) is not available.' );
		}
	}

	public function testInit_Always_RegistersCategoryHook() {
		Sensei_Abilities::init();

		$this->assertNotFalse(
			has_action( 'wp_abilities_api_categories_init', array( Sensei_Abilities::class, 'register_category' ) )
		);
	}

	public function testInit_Always_RegistersAbilitiesHook() {
		Sensei_Abilities::init();

		$this->assertNotFalse(
			has_action( 'wp_abilities_api_init', array( Sensei_Abilities::class, 'register_abilities' ) )
		);
	}

	public function testGetCourses_WhenCalled_ReturnsCourses() {
		$factory   = new Sensei_Factory();
		$course_id = $factory->course->create( array( 'post_title' => 'Alpha' ) );

		$this->login_as_admin();
		$ability = wp_get_ability( 'sensei/get-courses' );
		$this->assertNotNull( $ability, 'The sensei/get-courses ability should be registered.' );

		$result = $ability->execute( array() );

		$this->assertIsArray( $result, 'The ability should return an array result.' );
		$course_ids = wp_list_pluck( $result['items'], 'id' );
		$this->assertContains( $course_id, $course_ids, 'The created course should appear in the items list.' );

		$factory->tearDown();
	}

	public function testGetCourses_WhenUserLacksCapability_ReturnsFalseFromPermission() {
		wp_set_current_user( 0 );
		$ability = wp_get_ability( 'sensei/get-courses' );

		$this->assertFalse( $ability->check_permissions( array() ) );
	}

	public function testGetCourses_WhenCallerIsTeacher_ReturnsOnlyOwnCourses() {
		$factory      = new Sensei_Factory();
		$teacher_a    = $this->factory->user->create( array( 'role' => 'teacher' ) );
		$teacher_b    = $this->factory->user->create( array( 'role' => 'teacher' ) );
		$own_course   = $factory->course->create( array( 'post_author' => $teacher_a ) );
		$other_course = $factory->course->create( array( 'post_author' => $teacher_b ) );

		wp_set_current_user( $teacher_a );
		$ability = wp_get_ability( 'sensei/get-courses' );

		$result     = $ability->execute( array() );
		$course_ids = wp_list_pluck( $result['items'], 'id' );

		$this->assertContains( $own_course, $course_ids, "The teacher's own course should be returned." );
		$this->assertNotContains( $other_course, $course_ids, "Another teacher's course should not be returned." );

		$factory->tearDown();
	}

	public function testGetLessons_WhenCalled_ReturnsLessons() {
		$factory   = new Sensei_Factory();
		$lesson_id = $factory->lesson->create( array( 'post_title' => 'Alpha' ) );

		$this->login_as_admin();
		$ability = wp_get_ability( 'sensei/get-lessons' );
		$this->assertNotNull( $ability, 'The sensei/get-lessons ability should be registered.' );

		$result = $ability->execute( array() );

		$this->assertIsArray( $result, 'The ability should return an array result.' );
		$lesson_ids = wp_list_pluck( $result['items'], 'id' );
		$this->assertContains( $lesson_id, $lesson_ids, 'The created lesson should appear in the items list.' );

		$factory->tearDown();
	}

	public function testGetLessons_WhenUserLacksCapability_ReturnsFalseFromPermission() {
		wp_set_current_user( 0 );
		$ability = wp_get_ability( 'sensei/get-lessons' );

		$this->assertFalse( $ability->check_permissions( array() ) );
	}

	public function testGetLessons_WhenCallerIsTeacher_ReturnsOnlyOwnLessons() {
		$factory      = new Sensei_Factory();
		$teacher_a    = $this->factory->user->create( array( 'role' => 'teacher' ) );
		$teacher_b    = $this->factory->user->create( array( 'role' => 'teacher' ) );
		$own_lesson   = $factory->lesson->create( array( 'post_author' => $teacher_a ) );
		$other_lesson = $factory->lesson->create( array( 'post_author' => $teacher_b ) );

		wp_set_current_user( $teacher_a );
		$ability = wp_get_ability( 'sensei/get-lessons' );

		$result     = $ability->execute( array() );
		$lesson_ids = wp_list_pluck( $result['items'], 'id' );

		$this->assertContains( $own_lesson, $lesson_ids, "The teacher's own lesson should be returned." );
		$this->assertNotContains( $other_lesson, $lesson_ids, "Another teacher's lesson should not be returned." );

		$factory->tearDown();
	}

	public function testGetLessons_WithCourseFilter_ReturnsLessonsInCourse() {
		$factory     = new Sensei_Factory();
		$course_x    = $factory->course->create();
		$course_y    = $factory->course->create();
		$lesson_in_x = $factory->lesson->create( array( 'meta_input' => array( '_lesson_course' => $course_x ) ) );
		$lesson_in_y = $factory->lesson->create( array( 'meta_input' => array( '_lesson_course' => $course_y ) ) );

		$this->login_as_admin();
		$ability = wp_get_ability( 'sensei/get-lessons' );

		$result     = $ability->execute( array( 'course' => $course_x ) );
		$lesson_ids = wp_list_pluck( $result['items'], 'id' );

		$this->assertContains( $lesson_in_x, $lesson_ids, 'The lesson assigned to the filtered course should be returned.' );
		$this->assertNotContains( $lesson_in_y, $lesson_ids, 'A lesson in a different course should not be returned.' );

		$factory->tearDown();
	}

	public function testGetQuiz_WhenCalled_ReturnsQuizSettings() {
		$factory   = new Sensei_Factory();
		$lesson_id = $factory->lesson->create( array( 'post_title' => 'Stretching' ) );
		$quiz_id   = $factory->maybe_create_quiz_for_lesson( $lesson_id );

		update_post_meta( $quiz_id, '_pass_required', 'on' );
		update_post_meta( $quiz_id, '_quiz_passmark', 70 );
		update_post_meta( $quiz_id, '_quiz_grade_type', 'auto' );

		$this->login_as_admin();
		$ability = wp_get_ability( 'sensei/get-quiz' );
		$this->assertNotNull( $ability, 'The sensei/get-quiz ability should be registered.' );

		$result = $ability->execute( array( 'lesson' => $lesson_id ) );

		$this->assertIsArray( $result, 'The ability should return an array result.' );
		$this->assertSame( $quiz_id, $result['id'], 'The returned quiz ID should match the lesson\'s quiz.' );
		$this->assertTrue( $result['pass_required'], 'pass_required should be true when _pass_required is "on".' );
		$this->assertSame( 70, $result['quiz_passmark'], 'quiz_passmark should be sourced from _quiz_passmark.' );
		$this->assertTrue( $result['auto_grade'], 'auto_grade should be true when _quiz_grade_type is "auto".' );
		$this->assertSame( $lesson_id, $result['lesson']['id'], 'The lesson nesting should carry the lesson ID.' );

		$factory->tearDown();
	}

	public function testGetQuiz_WhenLessonHasNoQuiz_ReturnsError() {
		$factory   = new Sensei_Factory();
		$lesson_id = $factory->lesson->create();

		$this->login_as_admin();
		$ability = wp_get_ability( 'sensei/get-quiz' );

		$result = $ability->execute( array( 'lesson' => $lesson_id ) );

		$this->assertInstanceOf( WP_Error::class, $result );

		$factory->tearDown();
	}

	public function testGetQuiz_WhenUserCannotEditLesson_ReturnsFalseFromPermission() {
		$factory   = new Sensei_Factory();
		$teacher_a = $this->factory->user->create( array( 'role' => 'teacher' ) );
		$teacher_b = $this->factory->user->create( array( 'role' => 'teacher' ) );
		$lesson_id = $factory->lesson->create( array( 'post_author' => $teacher_b ) );

		wp_set_current_user( $teacher_a );
		$ability = wp_get_ability( 'sensei/get-quiz' );

		$this->assertFalse( $ability->check_permissions( array( 'lesson' => $lesson_id ) ) );

		$factory->tearDown();
	}

	public function testGetQuiz_WhenCallerIsTeacherOfOwnLesson_ReturnsTrueFromPermission() {
		$factory   = new Sensei_Factory();
		$teacher   = $this->factory->user->create( array( 'role' => 'teacher' ) );
		$lesson_id = $factory->lesson->create( array( 'post_author' => $teacher ) );

		wp_set_current_user( $teacher );
		$ability = wp_get_ability( 'sensei/get-quiz' );

		$this->assertTrue( $ability->check_permissions( array( 'lesson' => $lesson_id ) ) );

		$factory->tearDown();
	}

	public function testGetQuiz_WhenLessonIdIsNotALesson_ReturnsFalseFromPermission() {
		$page_id = $this->factory->post->create( array( 'post_type' => 'page' ) );

		$this->login_as_admin();
		$ability = wp_get_ability( 'sensei/get-quiz' );

		$this->assertFalse( $ability->check_permissions( array( 'lesson' => $page_id ) ) );
	}

	public function testGetQuestions_WhenCalled_ReturnsQuestionsInOrder() {
		$factory   = new Sensei_Factory();
		$lesson_id = $factory->lesson->create();
		$quiz_id   = $factory->maybe_create_quiz_for_lesson( $lesson_id );
		$factory->question->create_many(
			3,
			array(
				'quiz_id'       => $quiz_id,
				'question_type' => 'multiple-choice',
			)
		);

		$this->login_as_admin();
		$ability = wp_get_ability( 'sensei/get-questions' );
		$this->assertNotNull( $ability, 'The sensei/get-questions ability should be registered.' );

		$result = $ability->execute( array( 'quiz' => $quiz_id ) );

		$this->assertIsArray( $result, 'The ability should return an array result.' );
		$this->assertCount( 3, $result['items'], 'All three questions should be returned.' );
		$this->assertSame( 3, $result['total'] );

		$factory->tearDown();
	}

	public function testGetQuestions_WhenCalled_IncludesQuestionTypeAndGrade() {
		$factory   = new Sensei_Factory();
		$lesson_id = $factory->lesson->create();
		$quiz_id   = $factory->maybe_create_quiz_for_lesson( $lesson_id );
		$factory->question->create(
			array(
				'quiz_id'        => $quiz_id,
				'question_type'  => 'boolean',
				'question_grade' => 5,
			)
		);

		$this->login_as_admin();
		$ability = wp_get_ability( 'sensei/get-questions' );
		$result  = $ability->execute( array( 'quiz' => $quiz_id ) );

		$this->assertNotEmpty( $result['items'], 'The quiz should have at least one question.' );
		$first = $result['items'][0];
		$this->assertSame( 'boolean', $first['type'], 'The question type slug should be emitted.' );
		$this->assertSame( 5, $first['grade'], 'The question grade should be emitted as an integer.' );

		$factory->tearDown();
	}

	public function testGetQuestions_WhenUserCannotEditLesson_ReturnsFalseFromPermission() {
		$factory   = new Sensei_Factory();
		$teacher_a = $this->factory->user->create( array( 'role' => 'teacher' ) );
		$teacher_b = $this->factory->user->create( array( 'role' => 'teacher' ) );
		$lesson_id = $factory->lesson->create( array( 'post_author' => $teacher_b ) );
		$quiz_id   = $factory->maybe_create_quiz_for_lesson( $lesson_id );

		wp_set_current_user( $teacher_a );
		$ability = wp_get_ability( 'sensei/get-questions' );

		$this->assertFalse( $ability->check_permissions( array( 'quiz' => $quiz_id ) ) );

		$factory->tearDown();
	}

	public function testGetQuestions_WhenQuizHasNoLesson_ReturnsFalseFromPermission() {
		$orphan_quiz = $this->factory->post->create( array( 'post_type' => 'quiz' ) );

		$this->login_as_admin();
		$ability = wp_get_ability( 'sensei/get-questions' );

		$this->assertFalse( $ability->check_permissions( array( 'quiz' => $orphan_quiz ) ) );
	}

	public function testGetQuestions_WithPagination_ReturnsCorrectSlice() {
		$factory   = new Sensei_Factory();
		$lesson_id = $factory->lesson->create();
		$quiz_id   = $factory->maybe_create_quiz_for_lesson( $lesson_id );
		$factory->question->create_many(
			5,
			array(
				'quiz_id'       => $quiz_id,
				'question_type' => 'multiple-choice',
			)
		);

		$this->login_as_admin();
		$ability = wp_get_ability( 'sensei/get-questions' );

		$result = $ability->execute(
			array(
				'quiz'     => $quiz_id,
				'per_page' => 2,
				'page'     => 2,
			)
		);

		$this->assertSame( 5, $result['total'], 'total should reflect the full question set.' );
		$this->assertSame( 3, $result['total_pages'], 'total_pages should reflect ceil(5/2).' );
		$this->assertCount( 2, $result['items'], 'page 2 with per_page=2 should return two items.' );

		$factory->tearDown();
	}

	public function testGetQuestions_WhenCallerIsTeacherOfOwnLesson_ReturnsTrueFromPermission() {
		$factory   = new Sensei_Factory();
		$teacher   = $this->factory->user->create( array( 'role' => 'teacher' ) );
		$lesson_id = $factory->lesson->create( array( 'post_author' => $teacher ) );
		$quiz_id   = $factory->maybe_create_quiz_for_lesson( $lesson_id );

		wp_set_current_user( $teacher );
		$ability = wp_get_ability( 'sensei/get-questions' );

		$this->assertTrue( $ability->check_permissions( array( 'quiz' => $quiz_id ) ) );

		$factory->tearDown();
	}

	public function testGetQuestions_WithCategoryQuestion_EmitsPoolPlaceholderShape() {
		$factory     = new Sensei_Factory();
		$lesson_id   = $factory->lesson->create();
		$quiz_id     = $factory->maybe_create_quiz_for_lesson( $lesson_id );
		$category_id = $this->factory->term->create(
			array(
				'taxonomy' => 'question-category',
				'name'     => 'Geography',
			)
		);
		$pool_id     = $this->factory->post->create(
			array(
				'post_type'  => 'multiple_question',
				'post_title' => 'Pool',
				'meta_input' => array(
					'category'                        => $category_id,
					'number'                          => 3,
					'_quiz_id'                        => $quiz_id,
					'_quiz_question_order' . $quiz_id => $quiz_id . '0001',
				),
			)
		);

		$this->login_as_admin();
		$ability = wp_get_ability( 'sensei/get-questions' );

		$result = $ability->execute( array( 'quiz' => $quiz_id ) );

		$match = null;
		foreach ( $result['items'] as $item ) {
			if ( (int) $item['id'] === (int) $pool_id ) {
				$match = $item;
				break;
			}
		}
		$this->assertNotNull( $match, 'The category-question placeholder should be present.' );
		$this->assertSame( 'category-question', $match['type'], 'Placeholders should use the synthetic type slug.' );
		$this->assertStringContainsString( 'Geography', $match['title'], 'Synthesized title should name the pool category.' );
		$this->assertStringContainsString( '3', $match['title'], 'Synthesized title should name the pool size.' );

		$factory->tearDown();
	}

	public function testGetQuestions_Always_EchoesQuizInEnvelope() {
		$factory   = new Sensei_Factory();
		$lesson_id = $factory->lesson->create( array( 'post_title' => 'Stretching' ) );
		$quiz_id   = $factory->maybe_create_quiz_for_lesson( $lesson_id );

		$this->login_as_admin();
		$ability = wp_get_ability( 'sensei/get-questions' );
		$result  = $ability->execute( array( 'quiz' => $quiz_id ) );

		$this->assertSame( $quiz_id, $result['quiz']['id'], 'The quiz id should be echoed.' );
		$this->assertSame( $lesson_id, $result['quiz']['lesson']['id'], 'The parent lesson id should be echoed.' );
		$this->assertSame( 'Stretching', $result['quiz']['lesson']['title'], 'The parent lesson title should be echoed.' );

		$factory->tearDown();
	}

	public function testGetStudents_WhenCalled_ReturnsEnrolledUsers() {
		$factory   = new Sensei_Factory();
		$course_id = $factory->course->create();
		$user_id   = $this->factory->user->create( array( 'role' => 'subscriber' ) );

		Sensei_Course_Manual_Enrolment_Provider::instance()->enrol_learner( $user_id, $course_id );

		$this->login_as_admin();
		$ability = wp_get_ability( 'sensei/get-students' );
		$this->assertNotNull( $ability, 'The sensei/get-students ability should be registered.' );

		$result = $ability->execute( array( 'course' => $course_id ) );

		$this->assertIsArray( $result, 'The ability should return an array result.' );
		$ids = wp_list_pluck( $result['items'], 'id' );
		$this->assertContains( $user_id, $ids, 'The enrolled user should appear in the items list.' );

		$factory->tearDown();
	}

	public function testGetStudents_WithCourseFilter_ReturnsEnrolledUsersOnly() {
		$factory   = new Sensei_Factory();
		$course_id = $factory->course->create();
		$enrolled  = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		$not_enr   = $this->factory->user->create( array( 'role' => 'subscriber' ) );

		Sensei_Course_Manual_Enrolment_Provider::instance()->enrol_learner( $enrolled, $course_id );

		$this->login_as_admin();
		$ability = wp_get_ability( 'sensei/get-students' );

		$result = $ability->execute( array( 'course' => $course_id ) );
		$ids    = wp_list_pluck( $result['items'], 'id' );

		$this->assertContains( $enrolled, $ids, 'The enrolled user should be in the result.' );
		$this->assertNotContains( $not_enr, $ids, 'A user not enrolled in the course should not be in the result.' );

		$factory->tearDown();
	}

	public function testGetStudents_WithSearch_MatchesByEmail() {
		$factory   = new Sensei_Factory();
		$course_id = $factory->course->create();
		$user_id   = $this->factory->user->create(
			array(
				'role'       => 'subscriber',
				'user_email' => 'alice@example.com',
			)
		);

		Sensei_Course_Manual_Enrolment_Provider::instance()->enrol_learner( $user_id, $course_id );

		$this->login_as_admin();
		$ability = wp_get_ability( 'sensei/get-students' );

		$result = $ability->execute(
			array(
				'course' => $course_id,
				'search' => 'alice@example.com',
			)
		);
		$ids    = wp_list_pluck( $result['items'], 'id' );

		$this->assertContains( $user_id, $ids );

		$factory->tearDown();
	}

	public function testGetStudents_Always_IncludesUserContactDetails() {
		$factory   = new Sensei_Factory();
		$course_id = $factory->course->create();
		$user_id   = $this->factory->user->create(
			array(
				'role'         => 'subscriber',
				'display_name' => 'Alice',
				'user_login'   => 'alice',
				'user_email'   => 'alice@example.com',
			)
		);

		Sensei_Course_Manual_Enrolment_Provider::instance()->enrol_learner( $user_id, $course_id );

		$this->login_as_admin();
		$ability = wp_get_ability( 'sensei/get-students' );
		$result  = $ability->execute(
			array(
				'course' => $course_id,
				'search' => 'alice@example.com',
			)
		);

		$this->assertNotEmpty( $result['items'], 'Searching by email should return at least one item.' );
		$match = null;
		foreach ( $result['items'] as $item ) {
			if ( (int) $item['id'] === (int) $user_id ) {
				$match = $item;
				break;
			}
		}
		$this->assertNotNull( $match, 'The created user should be present in the search results.' );
		$this->assertSame( 'Alice', $match['display_name'], 'The item should include the display name.' );
		$this->assertSame( 'alice@example.com', $match['user_email'], 'The item should include the email.' );

		$factory->tearDown();
	}

	public function testGetStudents_WhenCallerIsTeacherOfOtherCourse_ReturnsFalseFromPermission() {
		$factory      = new Sensei_Factory();
		$teacher_a    = $this->factory->user->create( array( 'role' => 'teacher' ) );
		$teacher_b    = $this->factory->user->create( array( 'role' => 'teacher' ) );
		$other_course = $factory->course->create( array( 'post_author' => $teacher_b ) );

		wp_set_current_user( $teacher_a );
		$ability = wp_get_ability( 'sensei/get-students' );

		$this->assertFalse( $ability->check_permissions( array( 'course' => $other_course ) ) );

		$factory->tearDown();
	}

	public function testGetStudents_WhenCallerIsTeacherOfOwnCourse_ReturnsTrueFromPermission() {
		$factory    = new Sensei_Factory();
		$teacher    = $this->factory->user->create( array( 'role' => 'teacher' ) );
		$own_course = $factory->course->create( array( 'post_author' => $teacher ) );

		wp_set_current_user( $teacher );
		$ability = wp_get_ability( 'sensei/get-students' );

		$this->assertTrue( $ability->check_permissions( array( 'course' => $own_course ) ) );

		$factory->tearDown();
	}

	public function testGetStudents_WithProgressStatusFilter_ReportsTotalFromFilteredSet() {
		$factory      = new Sensei_Factory();
		$course_id    = $factory->course->create();
		$completed_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		$in_progress  = $this->factory->user->create( array( 'role' => 'subscriber' ) );

		Sensei_Course_Manual_Enrolment_Provider::instance()->enrol_learner( $completed_id, $course_id );
		Sensei_Course_Manual_Enrolment_Provider::instance()->enrol_learner( $in_progress, $course_id );

		Sensei_Utils::user_start_course( $completed_id, $course_id );
		Sensei_Utils::update_course_status( $completed_id, $course_id, \Sensei\Internal\Student_Progress\Course_Progress\Models\Course_Progress_Interface::STATUS_COMPLETE );
		Sensei_Utils::user_start_course( $in_progress, $course_id );

		$this->login_as_admin();
		$ability = wp_get_ability( 'sensei/get-students' );

		$result = $ability->execute(
			array(
				'course'          => $course_id,
				'progress_status' => \Sensei\Internal\Student_Progress\Course_Progress\Models\Course_Progress_Interface::STATUS_COMPLETE,
			)
		);

		$this->assertSame( 1, $result['total'], 'total should reflect only the filtered cohort.' );
		$this->assertCount( 1, $result['items'], 'items should match the filtered total.' );
		$this->assertSame( $completed_id, (int) $result['items'][0]['id'] );

		$factory->tearDown();
	}

	public function testGetStudents_WhenCourseDoesNotExist_ReturnsFalseFromPermission() {
		$this->login_as_admin();
		$ability = wp_get_ability( 'sensei/get-students' );

		$this->assertFalse( $ability->check_permissions( array( 'course' => 999999 ) ) );
	}

	public function testGetStudents_WhenCourseIdIsNotACourse_ReturnsErrorWhenExecutedDirectly() {
		$page_id = $this->factory->post->create( array( 'post_type' => 'page' ) );

		$result = Sensei_Abilities::execute_get_students( array( 'course' => $page_id ) );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'sensei_course_not_found', $result->get_error_code() );
	}

	public function testGetStudents_WhenNoOneIsEnrolled_ReturnsEmptyEnvelope() {
		$factory   = new Sensei_Factory();
		$course_id = $factory->course->create();

		$this->login_as_admin();
		$ability = wp_get_ability( 'sensei/get-students' );

		$result = $ability->execute( array( 'course' => $course_id ) );

		$this->assertSame( 0, $result['total'], 'Empty course should report zero students.' );
		$this->assertSame( 0, $result['total_pages'] );
		$this->assertSame( array(), $result['items'], 'Items should be empty, not the full user list.' );

		$factory->tearDown();
	}

	public function testGetStudents_WithProgressFilterAndPagination_KeepsTotalAlignedWithItems() {
		$factory   = new Sensei_Factory();
		$course_id = $factory->course->create();

		$completed_ids = array();
		for ( $i = 0; $i < 3; $i++ ) {
			$user_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );
			Sensei_Course_Manual_Enrolment_Provider::instance()->enrol_learner( $user_id, $course_id );
			Sensei_Utils::user_start_course( $user_id, $course_id );
			Sensei_Utils::update_course_status( $user_id, $course_id, \Sensei\Internal\Student_Progress\Course_Progress\Models\Course_Progress_Interface::STATUS_COMPLETE );
			$completed_ids[] = $user_id;
		}
		for ( $i = 0; $i < 2; $i++ ) {
			$user_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );
			Sensei_Course_Manual_Enrolment_Provider::instance()->enrol_learner( $user_id, $course_id );
			Sensei_Utils::user_start_course( $user_id, $course_id );
		}

		$this->login_as_admin();
		$ability = wp_get_ability( 'sensei/get-students' );

		$result = $ability->execute(
			array(
				'course'          => $course_id,
				'progress_status' => \Sensei\Internal\Student_Progress\Course_Progress\Models\Course_Progress_Interface::STATUS_COMPLETE,
				'per_page'        => 2,
				'page'            => 2,
			)
		);

		$this->assertSame( 3, $result['total'], 'Total should reflect the filtered cohort.' );
		$this->assertSame( 2, $result['total_pages'], 'Total pages should reflect ceil(3/2).' );
		$this->assertCount( 1, $result['items'], 'Page 2 of the filtered cohort should have one item.' );
		$this->assertContains( (int) $result['items'][0]['id'], $completed_ids, 'The returned student should be one of the completed cohort.' );

		$factory->tearDown();
	}

	public function testGetStudents_Always_EchoesCourseInEnvelope() {
		$factory   = new Sensei_Factory();
		$course_id = $factory->course->create( array( 'post_title' => 'Brewing 101' ) );

		$this->login_as_admin();
		$ability = wp_get_ability( 'sensei/get-students' );
		$result  = $ability->execute( array( 'course' => $course_id ) );

		$this->assertArrayHasKey( 'course', $result, 'The response should carry a top-level course echo.' );
		$this->assertSame( $course_id, $result['course']['id'], 'The echoed course id should match the input.' );
		$this->assertSame( 'Brewing 101', $result['course']['title'], 'The echoed course title should match the course post.' );

		$factory->tearDown();
	}
}
