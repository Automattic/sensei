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
		$ability = wp_get_ability( 'sensei/courses-list' );
		$this->assertNotNull( $ability, 'The sensei/courses-list ability should be registered.' );

		$result = $ability->execute( array() );

		$this->assertIsArray( $result, 'The ability should return an array result.' );
		$course_ids = wp_list_pluck( $result['items'], 'id' );
		$this->assertContains( $course_id, $course_ids, 'The created course should appear in the items list.' );

		$factory->tearDown();
	}

	public function testGetCourses_WhenUserLacksCapability_ReturnsFalseFromPermission() {
		wp_set_current_user( 0 );
		$ability = wp_get_ability( 'sensei/courses-list' );

		$this->assertFalse( $ability->check_permissions( array() ) );
	}

	public function testGetCourses_WhenCallerIsTeacher_ReturnsOnlyOwnCourses() {
		$factory      = new Sensei_Factory();
		$teacher_a    = $this->factory->user->create( array( 'role' => 'teacher' ) );
		$teacher_b    = $this->factory->user->create( array( 'role' => 'teacher' ) );
		$own_course   = $factory->course->create( array( 'post_author' => $teacher_a ) );
		$other_course = $factory->course->create( array( 'post_author' => $teacher_b ) );

		wp_set_current_user( $teacher_a );
		$ability = wp_get_ability( 'sensei/courses-list' );

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
		$ability = wp_get_ability( 'sensei/lessons-list' );
		$this->assertNotNull( $ability, 'The sensei/lessons-list ability should be registered.' );

		$result = $ability->execute( array() );

		$this->assertIsArray( $result, 'The ability should return an array result.' );
		$lesson_ids = wp_list_pluck( $result['items'], 'id' );
		$this->assertContains( $lesson_id, $lesson_ids, 'The created lesson should appear in the items list.' );

		$factory->tearDown();
	}

	public function testGetLessons_WhenUserLacksCapability_ReturnsFalseFromPermission() {
		wp_set_current_user( 0 );
		$ability = wp_get_ability( 'sensei/lessons-list' );

		$this->assertFalse( $ability->check_permissions( array() ) );
	}

	public function testGetLessons_WhenCallerIsTeacher_ReturnsOnlyOwnLessons() {
		$factory      = new Sensei_Factory();
		$teacher_a    = $this->factory->user->create( array( 'role' => 'teacher' ) );
		$teacher_b    = $this->factory->user->create( array( 'role' => 'teacher' ) );
		$own_lesson   = $factory->lesson->create( array( 'post_author' => $teacher_a ) );
		$other_lesson = $factory->lesson->create( array( 'post_author' => $teacher_b ) );

		wp_set_current_user( $teacher_a );
		$ability = wp_get_ability( 'sensei/lessons-list' );

		$result     = $ability->execute( array() );
		$lesson_ids = wp_list_pluck( $result['items'], 'id' );

		$this->assertContains( $own_lesson, $lesson_ids, "The teacher's own lesson should be returned." );
		$this->assertNotContains( $other_lesson, $lesson_ids, "Another teacher's lesson should not be returned." );

		$factory->tearDown();
	}

	public function testGetLessons_WhenCallerIsTeacherOfOtherCourse_ReturnsFalseFromPermission() {
		$factory      = new Sensei_Factory();
		$teacher_a    = $this->factory->user->create( array( 'role' => 'teacher' ) );
		$teacher_b    = $this->factory->user->create( array( 'role' => 'teacher' ) );
		$other_course = $factory->course->create( array( 'post_author' => $teacher_b ) );

		wp_set_current_user( $teacher_a );
		$ability = wp_get_ability( 'sensei/lessons-list' );

		$this->assertFalse( $ability->check_permissions( array( 'course' => $other_course ) ) );

		$factory->tearDown();
	}

	public function testGetLessons_WithCourseFilter_ReturnsLessonsInCourse() {
		$factory     = new Sensei_Factory();
		$course_x    = $factory->course->create();
		$course_y    = $factory->course->create();
		$lesson_in_x = $factory->lesson->create( array( 'meta_input' => array( '_lesson_course' => $course_x ) ) );
		$lesson_in_y = $factory->lesson->create( array( 'meta_input' => array( '_lesson_course' => $course_y ) ) );

		$this->login_as_admin();
		$ability = wp_get_ability( 'sensei/lessons-list' );

		$result     = $ability->execute( array( 'course' => $course_x ) );
		$lesson_ids = wp_list_pluck( $result['items'], 'id' );

		$this->assertContains( $lesson_in_x, $lesson_ids, 'The lesson assigned to the filtered course should be returned.' );
		$this->assertNotContains( $lesson_in_y, $lesson_ids, 'A lesson in a different course should not be returned.' );

		$factory->tearDown();
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
		$ability = wp_get_ability( 'sensei/questions-list' );
		$this->assertNotNull( $ability, 'The sensei/questions-list ability should be registered.' );

		$result = $ability->execute( array( 'lesson' => $lesson_id ) );

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
		$ability = wp_get_ability( 'sensei/questions-list' );
		$result  = $ability->execute( array( 'lesson' => $lesson_id ) );

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

		wp_set_current_user( $teacher_a );
		$ability = wp_get_ability( 'sensei/questions-list' );

		$this->assertFalse( $ability->check_permissions( array( 'lesson' => $lesson_id ) ) );

		$factory->tearDown();
	}

	public function testGetQuestions_WhenLessonHasNoQuiz_ReturnsError() {
		$factory   = new Sensei_Factory();
		$lesson_id = $factory->lesson->create();

		$this->login_as_admin();
		$ability = wp_get_ability( 'sensei/questions-list' );

		$result = $ability->execute( array( 'lesson' => $lesson_id ) );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'sensei_quiz_not_found', $result->get_error_code() );

		$factory->tearDown();
	}

	public function testGetQuestions_WhenLessonIdIsNotALesson_ReturnsErrorWhenExecutedDirectly() {
		$page_id = $this->factory->post->create( array( 'post_type' => 'page' ) );

		$result = Sensei_Abilities::execute_questions_list( array( 'lesson' => $page_id ) );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'sensei_lesson_not_found', $result->get_error_code() );
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
		$ability = wp_get_ability( 'sensei/questions-list' );

		$expected_ids = wp_list_pluck( Sensei()->quiz->get_questions( $quiz_id ), 'ID' );

		$result = $ability->execute(
			array(
				'lesson'   => $lesson_id,
				'per_page' => 2,
				'page'     => 2,
			)
		);

		$this->assertSame( 5, $result['total'], 'total should reflect the full question set.' );
		$this->assertSame( 3, $result['total_pages'], 'total_pages should reflect ceil(5/2).' );
		$this->assertSame(
			array_slice( $expected_ids, 2, 2 ),
			wp_list_pluck( $result['items'], 'id' ),
			'Page 2 should contain the 3rd and 4th questions in ability order.'
		);

		$factory->tearDown();
	}

	public function testGetQuestions_WhenCallerIsTeacherOfOwnLesson_ReturnsTrueFromPermission() {
		$factory   = new Sensei_Factory();
		$teacher   = $this->factory->user->create( array( 'role' => 'teacher' ) );
		$lesson_id = $factory->lesson->create( array( 'post_author' => $teacher ) );

		wp_set_current_user( $teacher );
		$ability = wp_get_ability( 'sensei/questions-list' );

		$this->assertTrue( $ability->check_permissions( array( 'lesson' => $lesson_id ) ) );

		$factory->tearDown();
	}

	public function testGetQuestions_WhenLessonIdIsNotALesson_ReturnsFalseFromPermission() {
		$page_id = $this->factory->post->create( array( 'post_type' => 'page' ) );

		$this->login_as_admin();
		$ability = wp_get_ability( 'sensei/questions-list' );

		$this->assertFalse( $ability->check_permissions( array( 'lesson' => $page_id ) ) );
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
		$ability = wp_get_ability( 'sensei/questions-list' );

		$result = $ability->execute( array( 'lesson' => $lesson_id ) );

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
		$this->assertArrayNotHasKey( 'grade', $match, 'Placeholders should omit the grade field.' );
		$this->assertArrayNotHasKey( 'description', $match, 'Placeholders should omit the description field.' );

		$factory->tearDown();
	}

	public function testGetQuestions_Always_EchoesLessonInEnvelope() {
		$factory   = new Sensei_Factory();
		$lesson_id = $factory->lesson->create( array( 'post_title' => 'Stretching' ) );
		$factory->maybe_create_quiz_for_lesson( $lesson_id );

		$this->login_as_admin();
		$ability = wp_get_ability( 'sensei/questions-list' );
		$result  = $ability->execute( array( 'lesson' => $lesson_id ) );

		$this->assertSame( $lesson_id, $result['lesson']['id'], 'The lesson id should be echoed.' );
		$this->assertSame( 'Stretching', $result['lesson']['title'], 'The lesson title should be echoed.' );

		$factory->tearDown();
	}

	public function testGetStudents_WhenCalled_ReturnsEnrolledUsers() {
		$factory   = new Sensei_Factory();
		$course_id = $factory->course->create();
		$user_id   = $this->factory->user->create( array( 'role' => 'subscriber' ) );

		Sensei_Course_Manual_Enrolment_Provider::instance()->enrol_learner( $user_id, $course_id );

		$this->login_as_admin();
		$ability = wp_get_ability( 'sensei/students-list' );
		$this->assertNotNull( $ability, 'The sensei/students-list ability should be registered.' );

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
		$ability = wp_get_ability( 'sensei/students-list' );

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
		$ability = wp_get_ability( 'sensei/students-list' );

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
		$ability = wp_get_ability( 'sensei/students-list' );
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
		$ability = wp_get_ability( 'sensei/students-list' );

		$this->assertFalse( $ability->check_permissions( array( 'course' => $other_course ) ) );

		$factory->tearDown();
	}

	public function testGetStudents_WhenCallerIsTeacherOfOwnCourse_ReturnsTrueFromPermission() {
		$factory    = new Sensei_Factory();
		$teacher    = $this->factory->user->create( array( 'role' => 'teacher' ) );
		$own_course = $factory->course->create( array( 'post_author' => $teacher ) );

		wp_set_current_user( $teacher );
		$ability = wp_get_ability( 'sensei/students-list' );

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
		$ability = wp_get_ability( 'sensei/students-list' );

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
		$ability = wp_get_ability( 'sensei/students-list' );

		$this->assertFalse( $ability->check_permissions( array( 'course' => 999999 ) ) );
	}

	public function testGetStudents_WhenCallerLacksManageGradesCap_ReturnsFalseFromPermission() {
		$factory    = new Sensei_Factory();
		$course_id  = $factory->course->create();
		$subscriber = $this->factory->user->create( array( 'role' => 'subscriber' ) );

		wp_set_current_user( $subscriber );
		$ability = wp_get_ability( 'sensei/students-list' );

		$this->assertFalse( $ability->check_permissions( array( 'course' => $course_id ) ) );

		$factory->tearDown();
	}

	public function testGetStudents_WhenCourseInputMissing_ReturnsFalseFromPermission() {
		$this->login_as_admin();
		$ability = wp_get_ability( 'sensei/students-list' );

		$this->assertFalse( $ability->check_permissions( array() ) );
	}

	public function testGetStudents_WhenCourseIdIsNotACourse_ReturnsErrorWhenExecutedDirectly() {
		$page_id = $this->factory->post->create( array( 'post_type' => 'page' ) );

		$result = Sensei_Abilities::execute_students_list( array( 'course' => $page_id ) );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'sensei_course_not_found', $result->get_error_code() );
	}

	public function testGetStudents_WhenNoOneIsEnrolled_ReturnsEmptyEnvelope() {
		$factory   = new Sensei_Factory();
		$course_id = $factory->course->create();

		$this->login_as_admin();
		$ability = wp_get_ability( 'sensei/students-list' );

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
		$ability = wp_get_ability( 'sensei/students-list' );

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
		$ability = wp_get_ability( 'sensei/students-list' );
		$result  = $ability->execute( array( 'course' => $course_id ) );

		$this->assertArrayHasKey( 'course', $result, 'The response should carry a top-level course echo.' );
		$this->assertSame( $course_id, $result['course']['id'], 'The echoed course id should match the input.' );
		$this->assertSame( 'Brewing 101', $result['course']['title'], 'The echoed course title should match the course post.' );

		$factory->tearDown();
	}

	/**
	 * A teacher creating a course gets a draft they own, with the default course layout and the description filled in.
	 *
	 * @covers Sensei_Abilities::execute_course_create
	 */
	public function testExecuteCourseCreate_TeacherGiven_CreatesDraftOwnedByTeacher() {
		$teacher = $this->factory->user->create( array( 'role' => 'teacher' ) );
		wp_set_current_user( $teacher );
		$ability = wp_get_ability( 'sensei/course-create' );

		$result = $ability->execute(
			array(
				'title'       => 'Power chords',
				'description' => 'Root and fifth.',
			)
		);

		$this->assertIsArray( $result, 'The ability should return the created course.' );
		$course = get_post( $result['id'] );
		$this->assertSame( 'course', $course->post_type, 'A course post should be created.' );
		$this->assertSame( 'draft', $course->post_status, 'The course should be a draft.' );
		$this->assertSame( $teacher, (int) $course->post_author, 'The teacher should own the course.' );
		$this->assertSame( 'Power chords', $course->post_title, 'The title should be stored.' );
		$this->assertSame( 'Root and fifth.', $course->post_excerpt, 'The description should be stored as the excerpt.' );
		$this->assertStringContainsString( '<!-- wp:sensei-lms/course-outline /-->', $course->post_content, 'The default course pattern should be applied.' );
		$this->assertStringContainsString( '<p class="sensei-content-description">Root and fifth.</p>', $course->post_content, 'The description placeholder should be filled.' );
		$this->assertSame( '', get_post_meta( $course->ID, '_new_post', true ), 'The editor wizard flag should not be set.' );
		$this->assertSame( 'draft', $result['status'], 'The result should report the draft status.' );
		$this->assertStringContainsString( 'post=' . $course->ID, $result['edit_link'], 'The result should include the edit link.' );
	}

	/**
	 * Without a description the pattern's empty placeholder paragraph is kept, as the editor wizard does.
	 *
	 * @covers Sensei_Abilities::execute_course_create
	 */
	public function testExecuteCourseCreate_DescriptionOmitted_LeavesPlaceholderEmpty() {
		$this->login_as_admin();
		$ability = wp_get_ability( 'sensei/course-create' );

		$result = $ability->execute( array( 'title' => 'Power chords' ) );

		$course = get_post( $result['id'] );
		$this->assertSame( '', $course->post_excerpt, 'The excerpt should be empty.' );
		$this->assertStringContainsString( '<p class="sensei-content-description"></p>', $course->post_content, 'The placeholder paragraph should stay empty.' );
	}

	/**
	 * Backslashes in the input survive wp_insert_post(), which unslashes its data.
	 *
	 * @covers Sensei_Abilities::execute_course_create
	 */
	public function testExecuteCourseCreate_BackslashesGiven_KeepsBackslashes() {
		$this->login_as_admin();
		$ability = wp_get_ability( 'sensei/course-create' );

		$result = $ability->execute(
			array(
				'title'       => 'Regex \\d+ basics',
				'description' => 'Match C:\\temp with \\\\ escapes.',
			)
		);

		$course = get_post( $result['id'] );
		$this->assertSame( 'Regex \\d+ basics', $course->post_title, 'The title should keep its backslash.' );
		$this->assertSame( 'Match C:\\temp with \\\\ escapes.', $course->post_excerpt, 'The excerpt should keep its backslashes.' );
		$this->assertStringContainsString( 'Match C:\\temp with \\\\ escapes.', $course->post_content, 'The description paragraph should keep its backslashes.' );
	}

	/**
	 * Even an administrator only gets a draft: publishing is left to a person.
	 *
	 * @covers Sensei_Abilities::execute_course_create
	 */
	public function testExecuteCourseCreate_AdminGiven_CreatesDraft() {
		$this->login_as_admin();
		$ability = wp_get_ability( 'sensei/course-create' );

		$result = $ability->execute( array( 'title' => 'Power chords' ) );

		$this->assertSame( 'draft', get_post_status( $result['id'] ), 'The course should be a draft.' );
	}

	/**
	 * A blank title is rejected without creating a course.
	 *
	 * @covers Sensei_Abilities::execute_course_create
	 */
	public function testExecuteCourseCreate_BlankTitleGiven_ReturnsError() {
		$this->login_as_admin();
		$courses_before = count(
			get_posts(
				array(
					'post_type'   => 'course',
					'post_status' => 'any',
					'fields'      => 'ids',
				)
			)
		);

		$result = Sensei_Abilities::execute_course_create( array( 'title' => '   ' ) );

		$this->assertInstanceOf( WP_Error::class, $result, 'A blank title should return an error.' );
		$this->assertSame( 'sensei_course_missing_title', $result->get_error_code(), 'The error code should identify the missing title.' );
		$courses_after = count(
			get_posts(
				array(
					'post_type'   => 'course',
					'post_status' => 'any',
					'fields'      => 'ids',
				)
			)
		);
		$this->assertSame( $courses_before, $courses_after, 'No course should be created.' );
	}

	/**
	 * Creating courses requires the same capability as the Add New Course screen.
	 *
	 * @covers Sensei_Abilities::can_create_courses
	 * @dataProvider providerCanCreateCourses
	 *
	 * @param string|null $role     Role of the caller, or null when logged out.
	 * @param bool        $expected Whether the caller may create courses.
	 */
	public function testCanCreateCourses_RoleGiven_MatchesEditCoursesCapability( ?string $role, bool $expected ) {
		wp_set_current_user( $role ? $this->factory->user->create( array( 'role' => $role ) ) : 0 );
		$ability = wp_get_ability( 'sensei/course-create' );

		$actual = $ability->check_permissions( array( 'title' => 'Power chords' ) );

		$this->assertSame( $expected, $actual );
	}

	/**
	 * Roles and whether they may create courses.
	 *
	 * @return array[]
	 */
	public function providerCanCreateCourses(): array {
		return array(
			'logged out'    => array( null, false ),
			'subscriber'    => array( 'subscriber', false ),
			'teacher'       => array( 'teacher', true ),
			'administrator' => array( 'administrator', true ),
		);
	}

	/**
	 * Build a teacher-owned course and the structure input used by the structure tests.
	 *
	 * @return array{0:int,1:int,2:array} Teacher id, course id and a structure with one module and one top-level lesson.
	 */
	private function createTeacherCourseWithStructureInput(): array {
		$factory   = new Sensei_Factory();
		$teacher   = $this->factory->user->create( array( 'role' => 'teacher' ) );
		$course_id = $factory->course->create( array( 'post_author' => $teacher ) );
		$structure = array(
			array(
				'type'        => 'module',
				'title'       => 'Basics',
				'description' => 'Start here.',
				'lessons'     => array(
					array(
						'type'  => 'lesson',
						'title' => 'Welcome',
					),
					array(
						'type'  => 'lesson',
						'title' => 'Tuning',
					),
				),
			),
			array(
				'type'  => 'lesson',
				'title' => 'Wrap up',
			),
		);

		return array( $teacher, $course_id, $structure );
	}

	/**
	 * Count the lessons attached to a course.
	 *
	 * @param int $course_id Course ID.
	 */
	private function countCourseLessons( int $course_id ): int {
		return count(
			get_posts(
				array(
					'post_type'   => 'lesson',
					'post_status' => 'any',
					'numberposts' => -1,
					'fields'      => 'ids',
					'meta_key'    => '_lesson_course', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
					'meta_value'  => $course_id, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				)
			)
		);
	}

	/**
	 * New modules and lessons are created as drafts owned by the teacher, each lesson with an empty quiz.
	 *
	 * @covers Sensei_Abilities::execute_course_structure_set
	 */
	public function testExecuteCourseStructureSet_NewItemsGiven_CreatesDraftLessonsWithQuizzesAndModule() {
		list( $teacher, $course_id, $structure ) = $this->createTeacherCourseWithStructureInput();
		wp_set_current_user( $teacher );
		$ability = wp_get_ability( 'sensei/course-structure-set' );

		$result = $ability->execute(
			array(
				'course'    => $course_id,
				'structure' => $structure,
			)
		);

		$this->assertIsArray( $result, 'The ability should return the saved structure.' );
		$this->assertSame( $course_id, $result['course']['id'], 'The course should be echoed.' );
		$this->assertCount( 2, $result['structure'], 'The module and the top-level lesson should be returned.' );

		$module = $result['structure'][0];
		$this->assertSame( 'module', $module['type'] );
		$this->assertGreaterThan( 0, $module['id'], 'The module should have an id.' );
		$this->assertSame( 'Basics', $module['title'] );
		$this->assertSame( 'Start here.', $module['description'] );
		$this->assertSame( array( 'Welcome', 'Tuning' ), wp_list_pluck( $module['lessons'], 'title' ), 'The module lessons should keep their order.' );
		$this->assertTrue( has_term( $module['id'], 'module', $course_id ), 'The module should be attached to the course.' );
		$this->assertSame( 'Wrap up', $result['structure'][1]['title'] );
		$this->assertTrue( $result['structure'][1]['draft'], 'New lessons should be drafts.' );

		$lesson = get_post( $module['lessons'][0]['id'] );
		$this->assertSame( 'draft', $lesson->post_status, 'The lesson should be a draft.' );
		$this->assertSame( $teacher, (int) $lesson->post_author, 'The teacher should own the lesson.' );
		$this->assertSame( $course_id, (int) get_post_meta( $lesson->ID, '_lesson_course', true ), 'The lesson should belong to the course.' );
		$this->assertSame( 'quiz', get_post_type( (int) get_post_meta( $lesson->ID, '_lesson_quiz', true ) ), 'The lesson should have a quiz.' );
	}

	/**
	 * Sending the returned ids back updates items in place instead of creating duplicates.
	 *
	 * @covers Sensei_Abilities::execute_course_structure_set
	 */
	public function testExecuteCourseStructureSet_ReturnedIdsResent_UpdatesInPlace() {
		list( $teacher, $course_id, $structure ) = $this->createTeacherCourseWithStructureInput();
		wp_set_current_user( $teacher );
		$ability = wp_get_ability( 'sensei/course-structure-set' );
		$first   = $ability->execute(
			array(
				'course'    => $course_id,
				'structure' => $structure,
			)
		);

		$resend = array(
			array(
				'type'    => 'module',
				'id'      => $first['structure'][0]['id'],
				'title'   => 'Basics',
				'lessons' => array(
					array(
						'type'  => 'lesson',
						'id'    => $first['structure'][0]['lessons'][0]['id'],
						'title' => 'Welcome back',
					),
					array(
						'type'  => 'lesson',
						'id'    => $first['structure'][0]['lessons'][1]['id'],
						'title' => 'Tuning',
					),
				),
			),
			array(
				'type'  => 'lesson',
				'id'    => $first['structure'][1]['id'],
				'title' => 'Wrap up',
			),
		);
		$second = $ability->execute(
			array(
				'course'    => $course_id,
				'structure' => $resend,
			)
		);

		$this->assertSame( $first['structure'][0]['id'], $second['structure'][0]['id'], 'The module id should be unchanged.' );
		$this->assertSame( wp_list_pluck( $first['structure'][0]['lessons'], 'id' ), wp_list_pluck( $second['structure'][0]['lessons'], 'id' ), 'The lesson ids should be unchanged.' );
		$this->assertSame( 'Welcome back', $second['structure'][0]['lessons'][0]['title'], 'The lesson title should be updated.' );
		$this->assertSame( 3, $this->countCourseLessons( $course_id ), 'No lessons should be duplicated.' );
	}

	/**
	 * A lesson left out of the structure is detached from the course but not deleted.
	 *
	 * @covers Sensei_Abilities::execute_course_structure_set
	 */
	public function testExecuteCourseStructureSet_LessonOmitted_DetachesWithoutDeleting() {
		list( $teacher, $course_id, $structure ) = $this->createTeacherCourseWithStructureInput();
		wp_set_current_user( $teacher );
		$ability = wp_get_ability( 'sensei/course-structure-set' );
		$first   = $ability->execute(
			array(
				'course'    => $course_id,
				'structure' => $structure,
			)
		);
		$omitted = $first['structure'][1]['id'];

		$second = $ability->execute(
			array(
				'course'    => $course_id,
				'structure' => array(
					array(
						'type'    => 'module',
						'id'      => $first['structure'][0]['id'],
						'title'   => 'Basics',
						'lessons' => array(
							array(
								'type'  => 'lesson',
								'id'    => $first['structure'][0]['lessons'][0]['id'],
								'title' => 'Welcome',
							),
						),
					),
				),
			)
		);

		$this->assertCount( 1, $second['structure'], 'Only the module should remain.' );
		$this->assertSame( 'draft', get_post_status( $omitted ), 'The omitted lesson should still exist.' );
		$this->assertSame( '', get_post_meta( $omitted, '_lesson_course', true ), 'The omitted lesson should no longer belong to the course.' );
		$this->assertSame( 1, $this->countCourseLessons( $course_id ), 'Only the kept lesson should belong to the course.' );
	}

	/**
	 * Validation failures write nothing.
	 *
	 * @covers Sensei_Abilities::execute_course_structure_set
	 */
	public function testExecuteCourseStructureSet_DuplicateModuleTitlesGiven_ReturnsErrorWithoutWriting() {
		list( $teacher, $course_id ) = $this->createTeacherCourseWithStructureInput();
		wp_set_current_user( $teacher );
		$ability = wp_get_ability( 'sensei/course-structure-set' );
		$module  = array(
			'type'    => 'module',
			'title'   => 'Twice',
			'lessons' => array(
				array(
					'type'  => 'lesson',
					'title' => 'Lesson',
				),
			),
		);

		$result = $ability->execute(
			array(
				'course'    => $course_id,
				'structure' => array( $module, $module ),
			)
		);

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'sensei_course_structure_duplicate_module_title', $result->get_error_code() );
		$this->assertSame( 0, $this->countCourseLessons( $course_id ), 'No lesson should be created.' );
		$this->assertEmpty(
			get_terms(
				array(
					'taxonomy'   => 'module',
					'name'       => 'Twice',
					'hide_empty' => false,
				)
			),
			'No module should be created.'
		);
	}

	/**
	 * The execute callback re-checks the post type.
	 *
	 * @covers Sensei_Abilities::execute_course_structure_set
	 * @covers Sensei_Abilities::execute_course_structure_get
	 */
	public function testExecuteCourseStructure_PageIdGiven_ReturnsCourseNotFound() {
		$this->login_as_admin();
		$page_id = $this->factory->post->create( array( 'post_type' => 'page' ) );

		$set = Sensei_Abilities::execute_course_structure_set(
			array(
				'course'    => $page_id,
				'structure' => array(),
			)
		);
		$get = Sensei_Abilities::execute_course_structure_get( array( 'course' => $page_id ) );

		$this->assertSame( 'sensei_course_not_found', $set->get_error_code() );
		$this->assertSame( 'sensei_course_not_found', $get->get_error_code() );
	}

	/**
	 * The structure can be read back with the ids a later set call needs.
	 *
	 * @covers Sensei_Abilities::execute_course_structure_get
	 */
	public function testExecuteCourseStructureGet_CourseGiven_ReturnsStructureWithIds() {
		list( $teacher, $course_id, $structure ) = $this->createTeacherCourseWithStructureInput();
		wp_set_current_user( $teacher );
		$saved = wp_get_ability( 'sensei/course-structure-set' )->execute(
			array(
				'course'    => $course_id,
				'structure' => $structure,
			)
		);

		$result = wp_get_ability( 'sensei/course-structure-get' )->execute( array( 'course' => $course_id ) );

		$this->assertSame( $course_id, $result['course']['id'] );
		$this->assertSame( get_post_status( $course_id ), $result['course']['status'] );
		$this->assertSame( wp_list_pluck( $saved['structure'], 'id' ), wp_list_pluck( $result['structure'], 'id' ), 'The same items should come back in order.' );
		$this->assertSame( wp_list_pluck( $saved['structure'][0]['lessons'], 'id' ), wp_list_pluck( $result['structure'][0]['lessons'], 'id' ), 'The module lessons should come back in order.' );
	}

	/**
	 * A teacher can set and get their own course's structure.
	 *
	 * @covers Sensei_Abilities::can_set_course_structure
	 * @covers Sensei_Abilities::can_get_course_structure
	 */
	public function testCanCourseStructure_OwnCourseGiven_ReturnsTrue() {
		list( $teacher, $course_id, $structure ) = $this->createTeacherCourseWithStructureInput();
		wp_set_current_user( $teacher );

		$this->assertTrue(
			wp_get_ability( 'sensei/course-structure-set' )->check_permissions(
				array(
					'course'    => $course_id,
					'structure' => $structure,
				)
			)
		);
		$this->assertTrue( wp_get_ability( 'sensei/course-structure-get' )->check_permissions( array( 'course' => $course_id ) ) );
	}

	/**
	 * Another teacher's course, or a lesson of theirs inside the caller's own course, is refused.
	 *
	 * @covers Sensei_Abilities::can_set_course_structure
	 * @covers Sensei_Abilities::can_get_course_structure
	 */
	public function testCanCourseStructure_AnotherTeachersContentGiven_ReturnsFalse() {
		list( $teacher_a, $course_a ) = $this->createTeacherCourseWithStructureInput();
		$factory                      = new Sensei_Factory();
		$lesson_a                     = $factory->lesson->create( array( 'post_author' => $teacher_a ) );
		$teacher_b                    = $this->factory->user->create( array( 'role' => 'teacher' ) );
		$course_b                     = $factory->course->create( array( 'post_author' => $teacher_b ) );
		wp_set_current_user( $teacher_b );
		$set = wp_get_ability( 'sensei/course-structure-set' );

		$this->assertFalse(
			$set->check_permissions(
				array(
					'course'    => $course_a,
					'structure' => array(),
				)
			),
			"Another teacher's course should be refused."
		);
		$this->assertFalse(
			$set->check_permissions(
				array(
					'course'    => $course_b,
					'structure' => array(
						array(
							'type'  => 'lesson',
							'id'    => $lesson_a,
							'title' => 'Stolen',
						),
					),
				)
			),
			"Another teacher's lesson should be refused."
		);
		$this->assertFalse( wp_get_ability( 'sensei/course-structure-get' )->check_permissions( array( 'course' => $course_a ) ), "Another teacher's course should not be readable." );
	}

	/**
	 * Logged-out users are refused.
	 *
	 * @covers Sensei_Abilities::can_set_course_structure
	 * @covers Sensei_Abilities::can_get_course_structure
	 */
	public function testCanCourseStructure_LoggedOut_ReturnsFalse() {
		list( , $course_id ) = $this->createTeacherCourseWithStructureInput();
		wp_set_current_user( 0 );

		$this->assertFalse(
			wp_get_ability( 'sensei/course-structure-set' )->check_permissions(
				array(
					'course'    => $course_id,
					'structure' => array(),
				)
			)
		);
		$this->assertFalse( wp_get_ability( 'sensei/course-structure-get' )->check_permissions( array( 'course' => $course_id ) ) );
	}

	/**
	 * Create a teacher and a lesson they own.
	 *
	 * @return array{0:int,1:int} Teacher id and lesson id.
	 */
	private function createTeacherLesson(): array {
		$factory   = new Sensei_Factory();
		$teacher   = $this->factory->user->create( array( 'role' => 'teacher' ) );
		$lesson_id = $factory->lesson->create( array( 'post_author' => $teacher ) );

		return array( $teacher, $lesson_id );
	}

	/**
	 * The questions used by the quiz ability tests, one per editor question type.
	 *
	 * @return array[]
	 */
	private function getQuizQuestionsInput(): array {
		return array(
			array(
				'type'    => 'multiple-choice',
				'title'   => 'What are power chords?',
				'answer'  => array(
					'answers' => array(
						array(
							'label'   => 'Root and fifth',
							'correct' => true,
						),
						array(
							'label'   => 'Any chord',
							'correct' => false,
						),
					),
				),
				'options' => array(
					'grade'          => 2,
					'answerFeedback' => 'Root and fifth.',
				),
			),
			array(
				'type'   => 'boolean',
				'title'  => 'A power chord has a third.',
				'answer' => array( 'correct' => false ),
			),
			array(
				'type'   => 'gap-fill',
				'title'  => 'Fill the gap',
				'answer' => array(
					'before' => 'The root and the',
					'gap'    => array( 'fifth', '5th' ),
					'after'  => '.',
				),
			),
			array(
				'type'    => 'single-line',
				'title'   => 'Paste the link to your recording.',
				'options' => array( 'teacherNotes' => 'Check the link opens.' ),
			),
			array(
				'type'  => 'multi-line',
				'title' => 'Describe your project.',
			),
			array(
				'type'    => 'file-upload',
				'title'   => 'Upload your tab.',
				'options' => array( 'studentHelp' => 'PDF only.' ),
			),
		);
	}

	/**
	 * Every editor question type is created, in order, and comes back with an id.
	 *
	 * @covers Sensei_Abilities::execute_quiz_questions_set
	 */
	public function testExecuteQuizQuestionsSet_NewQuizGiven_CreatesQuestionsInOrderAndReturnsIds() {
		list( $teacher, $lesson_id ) = $this->createTeacherLesson();
		wp_set_current_user( $teacher );
		$input = $this->getQuizQuestionsInput();

		$result = wp_get_ability( 'sensei/quiz-questions-set' )->execute(
			array(
				'lesson'    => $lesson_id,
				'options'   => array(
					'pass_required' => true,
					'quiz_passmark' => 70,
				),
				'questions' => $input,
			)
		);

		$this->assertIsArray( $result, 'The ability should return the saved quiz.' );
		$this->assertSame( $lesson_id, $result['lesson']['id'] );
		$this->assertSame( 'quiz', get_post_type( $result['quiz_id'] ), 'The quiz should be created.' );
		$this->assertTrue( $result['options']['pass_required'] );
		$this->assertSame( 70, $result['options']['quiz_passmark'] );
		$this->assertSame( wp_list_pluck( $input, 'title' ), wp_list_pluck( $result['questions'], 'title' ), 'The questions should come back in order.' );
		$this->assertSame( wp_list_pluck( $input, 'type' ), wp_list_pluck( $result['questions'], 'type' ), 'Every type should be stored.' );
		$this->assertCount( 6, array_filter( wp_list_pluck( $result['questions'], 'id' ) ), 'Every question should have an id.' );
		$this->assertSame( 2, $result['questions'][0]['options']['grade'], 'Question options should be stored.' );
		$this->assertFalse( $result['questions'][1]['answer']['correct'], 'The boolean answer should be stored.' );
		$this->assertSame( array( 'fifth', '5th' ), $result['questions'][2]['answer']['gap'], 'The gap-fill answer should be stored.' );
		$this->assertSame( $teacher, (int) get_post( $result['questions'][0]['id'] )->post_author, 'The teacher should own the questions.' );
	}

	/**
	 * Settings left out keep the values the quiz already has.
	 *
	 * @covers Sensei_Abilities::execute_quiz_questions_set
	 */
	public function testExecuteQuizQuestionsSet_OptionsOmittedOnExistingQuiz_KeepsCurrentValues() {
		list( $teacher, $lesson_id ) = $this->createTeacherLesson();
		wp_set_current_user( $teacher );
		$ability = wp_get_ability( 'sensei/quiz-questions-set' );
		$ability->execute(
			array(
				'lesson'    => $lesson_id,
				'options'   => array(
					'pass_required' => true,
					'quiz_passmark' => 70,
					'allow_retakes' => false,
				),
				'questions' => array(),
			)
		);

		$result = $ability->execute(
			array(
				'lesson'    => $lesson_id,
				'questions' => array(
					array(
						'type'  => 'multi-line',
						'title' => 'Describe your project.',
					),
				),
			)
		);

		$this->assertTrue( $result['options']['pass_required'], 'The pass requirement should be kept.' );
		$this->assertSame( 70, $result['options']['quiz_passmark'], 'The passmark should be kept.' );
		$this->assertFalse( $result['options']['allow_retakes'], 'The retake setting should be kept.' );
		$this->assertCount( 1, $result['questions'] );
	}

	/**
	 * The output can be sent back as input: readonly keys are tolerated and ids update in place.
	 *
	 * @covers Sensei_Abilities::execute_quiz_questions_set
	 */
	public function testExecuteQuizQuestionsSet_ReturnedQuestionsResent_UpdatesInPlace() {
		list( $teacher, $lesson_id ) = $this->createTeacherLesson();
		wp_set_current_user( $teacher );
		$ability = wp_get_ability( 'sensei/quiz-questions-set' );
		$first   = $ability->execute(
			array(
				'lesson'    => $lesson_id,
				'questions' => $this->getQuizQuestionsInput(),
			)
		);

		$questions             = $first['questions'];
		$questions[0]['title'] = 'What are power chords, really?';
		$second                = $ability->execute(
			array(
				'lesson'    => $lesson_id,
				'questions' => $questions,
			)
		);

		$this->assertIsArray( $second, 'Resending the output should be accepted.' );
		$this->assertSame( wp_list_pluck( $first['questions'], 'id' ), wp_list_pluck( $second['questions'], 'id' ), 'The question ids should be unchanged.' );
		$this->assertSame( 'What are power chords, really?', $second['questions'][0]['title'] );
		$this->assertCount(
			6,
			get_posts(
				array(
					'post_type'   => 'question',
					'post_status' => 'any',
					'numberposts' => -1,
					'fields'      => 'ids',
				)
			),
			'No question should be duplicated.'
		);
	}

	/**
	 * A question left out is removed from the quiz but stays in the question bank.
	 *
	 * @covers Sensei_Abilities::execute_quiz_questions_set
	 */
	public function testExecuteQuizQuestionsSet_QuestionOmitted_RemovesFromQuizButKeepsQuestion() {
		list( $teacher, $lesson_id ) = $this->createTeacherLesson();
		wp_set_current_user( $teacher );
		$ability = wp_get_ability( 'sensei/quiz-questions-set' );
		$first   = $ability->execute(
			array(
				'lesson'    => $lesson_id,
				'questions' => array_slice( $this->getQuizQuestionsInput(), 0, 2 ),
			)
		);
		$omitted = $first['questions'][1]['id'];

		$second = $ability->execute(
			array(
				'lesson'    => $lesson_id,
				'questions' => array( $first['questions'][0] ),
			)
		);

		$this->assertSame( array( $first['questions'][0]['id'] ), wp_list_pluck( $second['questions'], 'id' ), 'Only the kept question should remain in the quiz.' );
		$this->assertSame( 'publish', get_post_status( $omitted ), 'The omitted question should still exist.' );
	}

	/**
	 * The execute callback re-checks the post type.
	 *
	 * @covers Sensei_Abilities::execute_quiz_questions_set
	 */
	public function testExecuteQuizQuestionsSet_PageIdGiven_ReturnsLessonNotFound() {
		$this->login_as_admin();
		$page_id = $this->factory->post->create( array( 'post_type' => 'page' ) );

		$result = Sensei_Abilities::execute_quiz_questions_set(
			array(
				'lesson'    => $page_id,
				'questions' => array(),
			)
		);

		$this->assertSame( 'sensei_lesson_not_found', $result->get_error_code() );
	}

	/**
	 * Questions are validated against the schema of their type.
	 *
	 * @covers Sensei_Abilities::execute_quiz_questions_set
	 */
	public function testExecuteQuizQuestionsSet_InvalidAnswerGiven_ReturnsError() {
		list( $teacher, $lesson_id ) = $this->createTeacherLesson();
		wp_set_current_user( $teacher );

		$result = wp_get_ability( 'sensei/quiz-questions-set' )->execute(
			array(
				'lesson'    => $lesson_id,
				'questions' => array(
					array(
						'type'   => 'boolean',
						'title'  => 'Will it blend?',
						'answer' => 'A string',
					),
				),
			)
		);

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertNull( Sensei()->lesson->lesson_quizzes( $lesson_id ), 'No quiz should be created.' );
	}

	/**
	 * A teacher can set their own lesson's quiz; another teacher's lesson and guests are refused.
	 *
	 * @covers Sensei_Abilities::can_set_quiz_questions
	 */
	public function testCanSetQuizQuestions_LessonOwnershipGiven_MatchesEditLesson() {
		list( $teacher, $lesson_id ) = $this->createTeacherLesson();
		$ability                     = wp_get_ability( 'sensei/quiz-questions-set' );
		$input                       = array(
			'lesson'    => $lesson_id,
			'questions' => array(),
		);

		wp_set_current_user( $teacher );
		$this->assertTrue( $ability->check_permissions( $input ), 'The owner should be allowed.' );

		wp_set_current_user( $this->factory->user->create( array( 'role' => 'teacher' ) ) );
		$this->assertFalse( $ability->check_permissions( $input ), 'Another teacher should be refused.' );

		$this->login_as_admin();
		$this->assertTrue( $ability->check_permissions( $input ), 'An administrator should be allowed.' );

		wp_set_current_user( 0 );
		$this->assertFalse( $ability->check_permissions( $input ), 'A guest should be refused.' );
	}
}
