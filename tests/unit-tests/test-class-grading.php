<?php

class Sensei_Class_Grading_Test extends WP_UnitTestCase {
	use Sensei_Test_Login_Helpers;

	/**
	 * Setup function
	 *
	 * This function sets up the lessons, quizes and their questions. This function runs before
	 * every single test in this class
	 */
	public function setUp(): void {
		parent::setUp();

		Sensei()->grading = new WooThemes_Sensei_Grading( '' );
		$this->factory    = new Sensei_Factory();
	}

	public function tearDown(): void {
		parent::tearDown();
		$this->factory->tearDown();
	}

	/**
	 * Testing the quiz class to make sure it is loaded
	 */
	public function testClassInstance() {
		// setup the test
		// test if the global sensei quiz class is loaded
		$this->assertTrue( isset( Sensei()->grading ), 'Sensei Grading class is not loaded' );
	}

	/**
	 * Tests that the ungraded quiz count is not displayed in the Grading menu.
	 *
	 * @covers Sensei_Grading::grading_admin_menu
	 */
	public function testGradingAdminMenuTitleWithoutIndicator() {
		$user_id    = $this->factory->user->create();
		$course_id  = $this->factory->course->create();
		$lesson_ids = $this->factory->lesson->create_many( 5 );

		foreach ( $lesson_ids as $lesson_id ) {
			add_post_meta( $lesson_id, '_lesson_course', $course_id );
		}

		Sensei_Utils::update_lesson_status( $user_id, $lesson_ids[0], 'passed' );
		Sensei_Utils::update_lesson_status( $user_id, $lesson_ids[1], 'in-progress' );
		Sensei_Utils::update_lesson_status( $user_id, $lesson_ids[2], 'failed' );
		Sensei_Utils::update_lesson_status( $user_id, $lesson_ids[3], 'complete' );
		Sensei_Utils::update_lesson_status( $user_id, $lesson_ids[4], 'graded' );

		$this->login_as_admin();
		Sensei()->grading->grading_admin_menu();

		global $submenu;

		$this->assertEquals( 'Grading', end( $submenu['sensei'] )[0], 'Should not have indicator when there are no ungraded quizzes' );

		// Clean up the submenu.
		unset( $submenu['sensei'] );
	}

	/**
	 * Tests that the ungraded quiz count is displayed in the Grading menu.
	 *
	 * @covers Sensei_Grading::grading_admin_menu
	 */
	public function testGradingAdminMenuTitleWithIndicator() {
		$user_id    = $this->factory->user->create();
		$course_id  = $this->factory->course->create();
		$lesson_ids = $this->factory->lesson->create_many( 5 );

		foreach ( $lesson_ids as $lesson_id ) {
			add_post_meta( $lesson_id, '_lesson_course', $course_id, true );
		}

		Sensei_Utils::update_lesson_status( $user_id, $lesson_ids[0], 'passed' );
		Sensei_Utils::update_lesson_status( $user_id, $lesson_ids[1], 'ungraded' );
		Sensei_Utils::update_lesson_status( $user_id, $lesson_ids[2], 'failed' );
		Sensei_Utils::update_lesson_status( $user_id, $lesson_ids[3], 'ungraded' );
		Sensei_Utils::update_lesson_status( $user_id, $lesson_ids[4], 'graded' );

		$this->login_as_admin();
		Sensei()->grading->grading_admin_menu();

		global $submenu;

		$this->assertEquals( 'Grading <span class="awaiting-mod">2</span>', end( $submenu['sensei'] )[0], 'Should display 2 ungraded quizzes' );

		// Clean up the submenu.
		unset( $submenu['sensei'] );
	}

	/**
	 * Data source for ::testGradeGapFillQuestionRegEx
	 *
	 * @return array
	 */
	public function gradeGapFillQuestions() {
		return array(
			'simple-partial-word-case-insensitive' => array(
				'correct|Answer|simple',
				array( 'correct|Answer|simple', 'Correct', 'answer', 'correct', 'answer|simple' ),
				array( 'r|s', '|', 'bad' ),
				false,
			),
			'complete-word-only'                   => array(
				'^correct|Answer|simple$',
				array( 'Correct', 'answer', 'correct' ),
				array( 'incorrect' ),
				false,
			),
			'simple-case-sensitive'                => array(
				'correct|Answer|simple',
				array( 'simple', 'Answer', 'correct' ),
				array( 'r|s', '|', 'Correct', 'answer' ),
				true,
			),
			// See: https://github.com/Automattic/sensei/issues/1721
			'with-forward-slash'                   => array(
				'some|text|1.4|13/4',
				array( 'some|text|1.4|13/4', 'some', 'text', '1.4', '13/4' ),
				array( 'Some', 'Text', '4|13', '4' ),
				true,
			),
			'with-several-forward-slash'           => array(
				'some|text|1.4|13/4|13//3',
				array( 'some', 'text', '1.4', '13/4', '13//3' ),
				array( 'Some', 'Text', '4|13', '4', '13' ),
				true,
			),
			'all-valid'                            => array(
				'.+',
				array( 'some', 'text', 'dinosaur', '1', '0' ),
				array(),
				false,
			),
			'all-words-ending-in-s'                => array(
				'^[a-z]+s$',
				array( 'chickens', 'precious', 'dinosaurs' ),
				array( 'pie', 'beer', 'bread', 'spacepeople', '20' ),
				false,
			),
			'all-basic-integers'                   => array(
				'^[0-9]+$',
				array( '1', 1, '200', '34' ),
				array( '2e10', '2.2', 4.4, 'monkey', '' ),
				false,
			),
			'invalid-regex'                        => array(
				'[some|text|1.4|13/4',
				array( '[some|text|1.4|13/4' ),
				array( 'Some', 'Text', '4|13', '4', 'some', 'text', '1.4', '13/4' ),
				false,
			),
		);
	}

	/**
	 * @dataProvider gradeGapFillQuestions
	 * @covers Sensei_Grading::grade_gap_fill_question
	 * @since 1.9.18
	 */
	public function testGradeGapFillQuestionRegEx( $answer, $found, $not_found, $case_sensitive ) {
		// Set up question
		$question_id = $this->getTestQuestion( 'gap-fill' );
		$this->assertNotFalse( $question_id );
		update_post_meta( $question_id, '_question_right_answer', 'pre||' . $answer . '||post' );
		if ( $case_sensitive ) {
			remove_filter( 'sensei_gap_fill_case_sensitive_grading', '__return_false' );
			add_filter( 'sensei_gap_fill_case_sensitive_grading', '__return_true' );
		} else {
			remove_filter( 'sensei_gap_fill_case_sensitive_grading', '__return_true' );
			add_filter( 'sensei_gap_fill_case_sensitive_grading', '__return_false' );
		}
		foreach ( $found as $found_item ) {
			$response = Sensei_Grading::grade_gap_fill_question( $question_id, $found_item );
			$this->assertEquals( 1, $response, "Expecting {$found_item} to match {$answer}" );
		}
		foreach ( $not_found as $not_found_item ) {
			$response = Sensei_Grading::grade_gap_fill_question( $question_id, $not_found_item );
			$this->assertFalse( $response, "Expecting {$not_found_item} to not match {$answer}" );
		}
	}

	/**
	 * Test that courses average grade is calculated correctly when there are no grades.
	 *
	 * @covers Sensei_Grading::get_courses_average_grade
	 */
	public function testGetGradedLessonsAverageGradeNoGrades() {
		$this->assertEquals( 0, Sensei()->grading->get_graded_lessons_average_grade() );
	}

	/**
	 *
	 * This tests generated graded lessons and makes sure that the function
	 * get graded lessons average is returning expected value for average lesson.
	 *
	 * @covers Sensei_Grading::get_graded_lessons_average_grade
	 * @since 4.2.0
	 */
	public function testGetGradedLessonsAverage() {

		$grades = [ 10, 20, 30, 40, 50 ];
		$this->factory->generate_graded_lessons( $grades );
		$graded_lessons_average_grade = Sensei()->grading->get_graded_lessons_average_grade();

		$this->assertEquals( 30, $graded_lessons_average_grade );
	}

	/**
	 * Test that courses average grade is calculated correctly when some lessons are ungraded or in-progress.
	 *
	 * @covers Sensei_Grading::get_courses_average_grade
	 */
	public function testGetCoursesAverageGradeLessonStatus() {
		$course_id  = $this->factory->course->create();
		$lesson_ids = $this->factory->lesson->create_many(
			3,
			[
				'meta_input' => [
					'_lesson_course'      => $course_id,
					'_quiz_has_questions' => 1,
				],
			]
		);
		$user_ids   = $this->factory->user->create_many( 2 );

		// Start each student in each lesson.
		$comment_ids[] = $this->startStudentInLesson( $lesson_ids[0], $user_ids[0], 'failed' );
		$comment_ids[] = $this->startStudentInLesson( $lesson_ids[0], $user_ids[1], 'graded' );
		$comment_ids[] = $this->startStudentInLesson( $lesson_ids[1], $user_ids[0], 'graded' );
		$comment_ids[] = $this->startStudentInLesson( $lesson_ids[1], $user_ids[1], 'passed' );
		$comment_ids[] = $this->startStudentInLesson( $lesson_ids[2], $user_ids[0], 'ungraded' );
		$comment_ids[] = $this->startStudentInLesson( $lesson_ids[2], $user_ids[1], 'in-progress' );

		// Assign a grade to each lesson for each student.
		$this->assignGrade( $comment_ids[0], '10' );
		$this->assignGrade( $comment_ids[1], '50' );
		$this->assignGrade( $comment_ids[2], '100' );
		$this->assignGrade( $comment_ids[3], '95' );
		$this->assignGrade( $comment_ids[4], '40' );
		$this->assignGrade( $comment_ids[5], '' );

		$this->assertEquals( ( 10 + 50 + 100 + 95 ) / 4, Sensei()->grading->get_courses_average_grade() );
	}

	/**
	 * Test that courses average grade is calculated correctly when there are no grades.
	 *
	 * @covers Sensei_Grading::get_courses_average_grade
	 */
	public function testGetCoursesAverageGradeNoGrades() {
		$course_id  = $this->factory->course->create();
		$lesson_ids = $this->factory->lesson->create_many(
			3,
			[
				'meta_input' => [
					'_lesson_course'      => $course_id,
					'_quiz_has_questions' => 1,
				],
			]
		);
		$user_ids   = $this->factory->user->create_many( 2 );

		// Start each student in each lesson.
		$comment_ids[] = $this->startStudentInLesson( $lesson_ids[0], $user_ids[0], 'failed' );
		$comment_ids[] = $this->startStudentInLesson( $lesson_ids[0], $user_ids[1], 'graded' );
		$comment_ids[] = $this->startStudentInLesson( $lesson_ids[1], $user_ids[0], 'passed' );
		$comment_ids[] = $this->startStudentInLesson( $lesson_ids[1], $user_ids[1], 'failed' );
		$comment_ids[] = $this->startStudentInLesson( $lesson_ids[2], $user_ids[0], 'graded' );
		$comment_ids[] = $this->startStudentInLesson( $lesson_ids[2], $user_ids[1], 'passed' );

		$this->assertEquals( 0, Sensei()->grading->get_courses_average_grade() );
	}

	/**
	 * Test that courses average grade is calculated correctly when some lessons have no course.
	 *
	 * @covers Sensei_Grading::get_courses_average_grade
	 */
	public function testGetCoursesAverageGradeNoCourse() {
		$course_id  = $this->factory->course->create();
		$lesson_ids = $this->factory->lesson->create_many(
			3,
			[
				'meta_input' => [
					'_quiz_has_questions' => 1,
				],
			]
		);
		$user_ids   = $this->factory->user->create_many( 2 );

		// Assign course to some lessons.
		add_post_meta( $lesson_ids[0], '_lesson_course', '' );
		add_post_meta( $lesson_ids[2], '_lesson_course', $course_id );

		// Start each student in each lesson.
		$comment_ids[] = $this->startStudentInLesson( $lesson_ids[0], $user_ids[0], 'failed' );
		$comment_ids[] = $this->startStudentInLesson( $lesson_ids[0], $user_ids[1], 'graded' );
		$comment_ids[] = $this->startStudentInLesson( $lesson_ids[1], $user_ids[0], 'passed' );
		$comment_ids[] = $this->startStudentInLesson( $lesson_ids[1], $user_ids[1], 'failed' );
		$comment_ids[] = $this->startStudentInLesson( $lesson_ids[2], $user_ids[0], 'graded' );
		$comment_ids[] = $this->startStudentInLesson( $lesson_ids[2], $user_ids[1], 'passed' );

		// Assign a grade to each lesson for each student.
		$this->assignGrade( $comment_ids[0], '10' );
		$this->assignGrade( $comment_ids[1], '50' );
		$this->assignGrade( $comment_ids[2], '100' );
		$this->assignGrade( $comment_ids[3], '35' );
		$this->assignGrade( $comment_ids[4], '70' );
		$this->assignGrade( $comment_ids[5], '85' );

		$this->assertEquals( ( 70 + 85 ) / 2, Sensei()->grading->get_courses_average_grade() );
	}

	/**
	 * Test that courses average grade is calculated correctly when some lessons have no quiz.
	 *
	 * @covers Sensei_Grading::get_courses_average_grade
	 */
	public function testGetCoursesAverageGradeNoQuiz() {
		$course_id  = $this->factory->course->create();
		$lesson_ids = $this->factory->lesson->create_many(
			3,
			[
				'meta_input' => [
					'_lesson_course' => $course_id,
				],
			]
		);
		$user_ids   = $this->factory->user->create_many( 2 );

		// Set some lessons to have a quiz.
		add_post_meta( $lesson_ids[1], '_quiz_has_questions', 1 );
		add_post_meta( $lesson_ids[2], '_quiz_has_questions', 1 );

		// Start each student in each lesson.
		$comment_ids[] = $this->startStudentInLesson( $lesson_ids[0], $user_ids[0], 'failed' );
		$comment_ids[] = $this->startStudentInLesson( $lesson_ids[0], $user_ids[1], 'graded' );
		$comment_ids[] = $this->startStudentInLesson( $lesson_ids[1], $user_ids[0], 'passed' );
		$comment_ids[] = $this->startStudentInLesson( $lesson_ids[1], $user_ids[1], 'failed' );
		$comment_ids[] = $this->startStudentInLesson( $lesson_ids[2], $user_ids[0], 'graded' );
		$comment_ids[] = $this->startStudentInLesson( $lesson_ids[2], $user_ids[1], 'passed' );

		// Assign a grade to each lesson for each student.
		$this->assignGrade( $comment_ids[0], '10' );
		$this->assignGrade( $comment_ids[1], '50' );
		$this->assignGrade( $comment_ids[2], '100' );
		$this->assignGrade( $comment_ids[3], '35' );
		$this->assignGrade( $comment_ids[4], '70' );
		$this->assignGrade( $comment_ids[5], '85' );

		$this->assertEquals( ( 100 + 35 + 70 + 85 ) / 4, Sensei()->grading->get_courses_average_grade() );
	}

	/**
	 * Test that courses average grade is calculated correctly when there are multiple courses.
	 *
	 * @covers Sensei_Grading::get_courses_average_grade
	 */
	public function testGetCoursesAverageGradeMultipleCourses() {
		$course_ids = $this->factory->course->create_many( 2 );
		$lesson_ids = $this->factory->lesson->create_many(
			3,
			[
				'meta_input' => [
					'_quiz_has_questions' => 1,
				],
			]
		);
		$user_ids   = $this->factory->user->create_many( 2 );

		// Assign different courses to lessons.
		add_post_meta( $lesson_ids[0], '_lesson_course', $course_ids[0] );
		add_post_meta( $lesson_ids[1], '_lesson_course', $course_ids[0] );
		add_post_meta( $lesson_ids[2], '_lesson_course', $course_ids[1] );

		// Start each student in each lesson.
		$comment_ids[] = $this->startStudentInLesson( $lesson_ids[0], $user_ids[0], 'failed' );
		$comment_ids[] = $this->startStudentInLesson( $lesson_ids[0], $user_ids[1], 'graded' );
		$comment_ids[] = $this->startStudentInLesson( $lesson_ids[1], $user_ids[0], 'passed' );
		$comment_ids[] = $this->startStudentInLesson( $lesson_ids[1], $user_ids[1], 'failed' );
		$comment_ids[] = $this->startStudentInLesson( $lesson_ids[2], $user_ids[0], 'graded' );
		$comment_ids[] = $this->startStudentInLesson( $lesson_ids[2], $user_ids[1], 'passed' );

		// Assign a grade to each lesson for each student.
		$this->assignGrade( $comment_ids[0], '10' );
		$this->assignGrade( $comment_ids[1], '50' );
		$this->assignGrade( $comment_ids[2], '100' );
		$this->assignGrade( $comment_ids[3], '35' );
		$this->assignGrade( $comment_ids[4], '70' );
		$this->assignGrade( $comment_ids[5], '85' );

		$first_course_average  = ( 10 + 50 + 100 + 35 ) / 4;
		$second_course_average = ( 70 + 85 ) / 2;

		$this->assertEquals( ( $first_course_average + $second_course_average ) / count( $course_ids ), Sensei()->grading->get_courses_average_grade() );
	}

	/**
	 * Test that courses average grade is calculated correctly when all conditions are met.
	 *
	 * @covers Sensei_Grading::get_courses_average_grade
	 */
	public function testGetCoursesAverageGrade() {
		$course_id  = $this->factory->course->create();
		$lesson_ids = $this->factory->lesson->create_many(
			3,
			[
				'meta_input' => [
					'_lesson_course'      => $course_id,
					'_quiz_has_questions' => 1,
				],
			]
		);
		$user_ids   = $this->factory->user->create_many( 2 );

		// Start each student in each lesson.
		$comment_ids[] = $this->startStudentInLesson( $lesson_ids[0], $user_ids[0], 'failed' );
		$comment_ids[] = $this->startStudentInLesson( $lesson_ids[0], $user_ids[1], 'graded' );
		$comment_ids[] = $this->startStudentInLesson( $lesson_ids[1], $user_ids[0], 'passed' );
		$comment_ids[] = $this->startStudentInLesson( $lesson_ids[1], $user_ids[1], 'failed' );
		$comment_ids[] = $this->startStudentInLesson( $lesson_ids[2], $user_ids[0], 'graded' );
		$comment_ids[] = $this->startStudentInLesson( $lesson_ids[2], $user_ids[1], 'passed' );

		// Assign a grade to each lesson for each student.
		$this->assignGrade( $comment_ids[0], '10' );
		$this->assignGrade( $comment_ids[1], '50' );
		$this->assignGrade( $comment_ids[2], '100' );
		$this->assignGrade( $comment_ids[3], '35' );
		$this->assignGrade( $comment_ids[4], '70' );
		$this->assignGrade( $comment_ids[5], '85' );

		$this->assertEquals( ( 10 + 50 + 100 + 35 + 70 + 85 ) / 6, Sensei()->grading->get_courses_average_grade() );
	}

	public function testGetGradedLessonsCount_WhenCalled_ReturnsCorrectCount() {
		/* Arrange. */
		$course_id  = $this->factory->course->create();
		$lesson_ids = $this->factory->lesson->create_many(
			3,
			[
				'meta_input' => [
					'_lesson_course'      => $course_id,
					'_quiz_has_questions' => 1,
				],
			]
		);
		$user_ids   = $this->factory->user->create_many( 2 );

		// Start students in each lesson.
		$comment_ids[] = $this->startStudentInLesson( $lesson_ids[0], $user_ids[0], 'failed' );
		$comment_ids[] = $this->startStudentInLesson( $lesson_ids[1], $user_ids[1], 'graded' );
		$comment_ids[] = $this->startStudentInLesson( $lesson_ids[2], $user_ids[1], 'passed' );

		// Assign grades.
		$this->assignGrade( $comment_ids[0], '10' );
		$this->assignGrade( $comment_ids[1], '50' );
		$this->assignGrade( $comment_ids[2], '100' );

		/* Act. */
		$count = Sensei()->grading::get_graded_lessons_count();

		/* Assert. */
		$this->assertEquals( 3, $count );
	}

	public function testGetGradedLessonsSum_WhenCalled_ReturnsCorrectSum() {
		/* Arrange. */
		$course_id  = $this->factory->course->create();
		$lesson_ids = $this->factory->lesson->create_many(
			3,
			[
				'meta_input' => [
					'_lesson_course'      => $course_id,
					'_quiz_has_questions' => 1,
				],
			]
		);
		$user_ids   = $this->factory->user->create_many( 2 );

		// Start students in each lesson.
		$comment_ids[] = $this->startStudentInLesson( $lesson_ids[0], $user_ids[0], 'failed' );
		$comment_ids[] = $this->startStudentInLesson( $lesson_ids[1], $user_ids[1], 'graded' );
		$comment_ids[] = $this->startStudentInLesson( $lesson_ids[2], $user_ids[1], 'passed' );

		// Assign grades.
		$this->assignGrade( $comment_ids[0], '10' );
		$this->assignGrade( $comment_ids[1], '50' );
		$this->assignGrade( $comment_ids[2], '100' );

		/* Act. */
		$sum = Sensei()->grading::get_graded_lessons_sum();

		/* Assert. */
		$this->assertEquals( 160, $sum );
	}

	public function testGetUserGradedLessonsSum_WhenCalled_ReturnsCorrectSum() {
		/* Arrange. */
		$course_id  = $this->factory->course->create();
		$lesson_ids = $this->factory->lesson->create_many(
			3,
			[
				'meta_input' => [
					'_lesson_course'      => $course_id,
					'_quiz_has_questions' => 1,
				],
			]
		);
		$user_ids   = $this->factory->user->create_many( 2 );

		// Start students in each lesson.
		$comment_ids[] = $this->startStudentInLesson( $lesson_ids[0], $user_ids[0], 'failed' );
		$comment_ids[] = $this->startStudentInLesson( $lesson_ids[1], $user_ids[0], 'graded' );
		$comment_ids[] = $this->startStudentInLesson( $lesson_ids[2], $user_ids[1], 'passed' );

		// Assign grades.
		$this->assignGrade( $comment_ids[0], '10' );
		$this->assignGrade( $comment_ids[1], '50' );
		$this->assignGrade( $comment_ids[2], '100' );

		/* Act. */
		$sum = Sensei()->grading::get_user_graded_lessons_sum( $user_ids[0] );

		/* Assert. */
		$this->assertEquals( 60, $sum );
	}

	public function testGetLessonsUsersGradesSum_WhenCalled_ReturnsCorrectSum() {
		/* Arrange. */
		$course_id  = $this->factory->course->create();
		$lesson_ids = $this->factory->lesson->create_many(
			2,
			[
				'meta_input' => [
					'_lesson_course'      => $course_id,
					'_quiz_has_questions' => 1,
				],
			]
		);
		$user_ids   = $this->factory->user->create_many( 2 );

		// Start students in each lesson.
		$comment_ids[] = $this->startStudentInLesson( $lesson_ids[0], $user_ids[0], 'failed' );
		$comment_ids[] = $this->startStudentInLesson( $lesson_ids[0], $user_ids[1], 'graded' );
		$comment_ids[] = $this->startStudentInLesson( $lesson_ids[1], $user_ids[1], 'passed' );

		// Assign grades.
		$this->assignGrade( $comment_ids[0], '10' );
		$this->assignGrade( $comment_ids[1], '50' );
		$this->assignGrade( $comment_ids[2], '100' );

		/* Act. */
		$sum = Sensei()->grading::get_lessons_users_grades_sum( $lesson_ids[0] );

		/* Assert. */
		$this->assertEquals( 60, $sum );
	}

	public function testGetCourseUsersGradesSum_WhenCalled_ReturnsCorrectSum() {
		/* Arrange. */
		$course_1            = $this->factory->course->create();
		$course_2            = $this->factory->course->create();
		$course_1_lesson_ids = $this->factory->lesson->create_many(
			3,
			[
				'meta_input' => [
					'_lesson_course'      => $course_1,
					'_quiz_has_questions' => 1,
				],
			]
		);
		$course_2_lesson_ids = $this->factory->lesson->create_many(
			3,
			[
				'meta_input' => [
					'_lesson_course'      => $course_2,
					'_quiz_has_questions' => 1,
				],
			]
		);
		$user_ids            = $this->factory->user->create_many( 2 );

		// Start students in each lesson.
		$comment_ids[] = $this->startStudentInLesson( $course_1_lesson_ids[0], $user_ids[0], 'failed' );
		$comment_ids[] = $this->startStudentInLesson( $course_1_lesson_ids[1], $user_ids[1], 'graded' );
		$comment_ids[] = $this->startStudentInLesson( $course_1_lesson_ids[2], $user_ids[0], 'passed' );
		$comment_ids[] = $this->startStudentInLesson( $course_2_lesson_ids[0], $user_ids[1], 'failed' );
		$comment_ids[] = $this->startStudentInLesson( $course_2_lesson_ids[1], $user_ids[0], 'graded' );
		$comment_ids[] = $this->startStudentInLesson( $course_2_lesson_ids[2], $user_ids[1], 'passed' );

		// Assign grades.
		$this->assignGrade( $comment_ids[0], '10' );
		$this->assignGrade( $comment_ids[1], '50' );
		$this->assignGrade( $comment_ids[2], '100' );
		$this->assignGrade( $comment_ids[3], '10' );
		$this->assignGrade( $comment_ids[4], '50' );
		$this->assignGrade( $comment_ids[5], '100' );

		/* Act. */
		$sum = Sensei()->grading::get_course_users_grades_sum( $course_1 );

		/* Assert. */
		$this->assertEquals( 160, $sum );
	}

	/**
	 * Get a test question.
	 *
	 * @param string $question_type
	 * @return bool|int
	 */
	private function getTestQuestion( $question_type ) {
		$lesson_id = $this->factory->get_random_lesson_id();
		$quiz_id   = Sensei()->lesson->lesson_quizzes( $lesson_id );

		$question                = $this->factory->question->get_sample_question_data( $question_type );
		$question['quiz_id']     = $quiz_id;
		$question['post_author'] = get_post( $quiz_id )->post_author;
		return Sensei()->lesson->lesson_save_question( $question );
	}

	/**
	 * Add lesson status for a given student.
	 *
	 * @param int    $lesson_id Lesson ID.
	 * @param int    $user_id   User ID.
	 * @param string $status    Lesson status.
	 * @return int Comment ID.
	 */
	private function startStudentInLesson( $lesson_id, $user_id, $status ) {
		return $this->factory->comment->create(
			[
				'comment_type'     => 'sensei_lesson_status',
				'comment_approved' => $status,
				'comment_post_ID'  => $lesson_id,
				'user_id'          => $user_id,
			]
		);
	}

	/**
	 * Assign a grade.
	 *
	 * @param int    $comment_id Comment ID.
	 * @param string $grade      Grade.
	 */
	private function assignGrade( $comment_id, $grade ) {
		add_comment_meta( $comment_id, 'grade', $grade );
	}
}
