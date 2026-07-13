<?php

class Sensei_Class_Grading_Test extends WP_UnitTestCase {
	use Sensei_Test_Login_Helpers;
	use Sensei_Test_Redirect_Helpers;
	use Sensei_HPPS_Helpers;

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
	 * Tests that prepare_items() applies sensei_count_statuses_args
	 * restrictions to listing rows for tables-based storage.
	 *
	 * @covers Sensei_Grading_Main::prepare_items
	 */
	public function testPrepareItems_TablesBasedWithCountStatusesArgsRestriction_RestrictsListingRows(): void {
		/* Arrange. */
		global $wpdb;
		$user_id    = $this->factory->user->create();
		$course_id  = $this->factory->course->create();
		$lesson_ids = $this->factory->lesson->create_many(
			2,
			[ 'meta_input' => [ '_lesson_course' => $course_id ] ]
		);

		$table = $wpdb->prefix . 'sensei_lms_progress';
		$now   = current_time( 'mysql' );
		foreach ( $lesson_ids as $lid ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Test helper.
			$wpdb->insert(
				$table,
				[
					'post_id'    => $lid,
					'user_id'    => $user_id,
					'type'       => 'lesson',
					'status'     => 'in-progress',
					'started_at' => $now,
					'created_at' => $now,
					'updated_at' => $now,
				],
				[ '%d', '%d', '%s', '%s', '%s', '%s', '%s' ]
			);
		}

		// Simulate teacher restriction: only allow the first lesson.
		$restrict_filter = function ( $args ) use ( $lesson_ids ) {
			$args['post__in'] = [ $lesson_ids[0] ];
			return $args;
		};
		add_filter( 'sensei_count_statuses_args', $restrict_filter );

		try {
			$this->login_as_admin();
			$service      = new \Sensei\Internal\Services\Tables_Based_Grading_Listing_Service( $wpdb );
			$grading_main = new Sensei_Grading_Main( [ 'view' => 'all' ], $service );

			/* Act. */
			$grading_main->prepare_items();

			/* Assert. */
			$this->assertCount( 1, $grading_main->items, 'Listing should only show items for the allowed lesson.' );
			$this->assertSame( $lesson_ids[0], $grading_main->items[0]->lesson_id, 'Listing item should be for the restricted lesson.' );
		} finally {
			remove_filter( 'sensei_count_statuses_args', $restrict_filter );
		}
	}

	/**
	 * Tests that the ungraded quiz count is not displayed in the Grading menu.
	 *
	 * @covers Sensei_Grading::grading_admin_menu
	 */
	public function testGradingAdminMenuTitleWithoutIndicator() {
		if ( self::is_hpps_tables_mode() ) {
			$this->enable_hpps_tables_repository();
		}

		$user_id    = $this->factory->user->create();
		$course_id  = $this->factory->course->create();
		$lesson_ids = $this->factory->lesson->create_many( 5 );

		foreach ( $lesson_ids as $lesson_id ) {
			add_post_meta( $lesson_id, '_lesson_course', $course_id );
		}

		// Lessons with quizzes: passed, failed, graded (none ungraded).
		$lessons_with_quizzes = [
			$lesson_ids[0] => 'pass',
			$lesson_ids[2] => 'fail',
			$lesson_ids[4] => 'grade',
		];
		foreach ( $lessons_with_quizzes as $lesson_id => $status ) {
			$quiz_id = $this->factory->quiz->create( [ 'post_parent' => $lesson_id ] );
			update_post_meta( $lesson_id, '_lesson_quiz', $quiz_id );
			Sensei()->lesson_progress_repository->create( $lesson_id, $user_id );
			Sensei()->quiz_submission_repository->get_or_create( $quiz_id, $user_id );
			$qp = Sensei()->quiz_progress_repository->create( $quiz_id, $user_id );
			$qp->{$status}();
			Sensei()->quiz_progress_repository->save( $qp );
		}

		// Lessons without quizzes: in-progress, complete.
		Sensei()->lesson_progress_repository->create( $lesson_ids[1], $user_id );
		$lp = Sensei()->lesson_progress_repository->create( $lesson_ids[3], $user_id );
		$lp->complete();
		Sensei()->lesson_progress_repository->save( $lp );

		$this->login_as_admin();
		Sensei()->grading->grading_admin_menu();

		global $submenu;

		$this->assertEquals( 'Grading', end( $submenu['sensei'] )[0], 'Should not have indicator when there are no ungraded quizzes' );

		// Clean up the submenu.
		unset( $submenu['sensei'] );

		if ( self::is_hpps_tables_mode() ) {
			$this->reset_hpps_repository();
		}
	}

	/**
	 * Tests that the ungraded quiz count is displayed in the Grading menu.
	 *
	 * @covers Sensei_Grading::grading_admin_menu
	 */
	public function testGradingAdminMenuTitleWithIndicator() {
		if ( self::is_hpps_tables_mode() ) {
			$this->enable_hpps_tables_repository();
		}

		$user_id    = $this->factory->user->create();
		$course_id  = $this->factory->course->create();
		$lesson_ids = $this->factory->lesson->create_many( 5 );

		foreach ( $lesson_ids as $lesson_id ) {
			add_post_meta( $lesson_id, '_lesson_course', $course_id, true );
		}

		$statuses = [ 'pass', 'ungrade', 'fail', 'ungrade', 'grade' ];
		foreach ( $lesson_ids as $index => $lesson_id ) {
			$quiz_id = $this->factory->quiz->create( [ 'post_parent' => $lesson_id ] );
			update_post_meta( $lesson_id, '_lesson_quiz', $quiz_id );
			Sensei()->lesson_progress_repository->create( $lesson_id, $user_id );
			Sensei()->quiz_submission_repository->get_or_create( $quiz_id, $user_id );
			$qp = Sensei()->quiz_progress_repository->create( $quiz_id, $user_id );
			$qp->{$statuses[ $index ]}();
			Sensei()->quiz_progress_repository->save( $qp );
		}

		$this->login_as_admin();
		Sensei()->grading->grading_admin_menu();

		global $submenu;

		$this->assertEquals( 'Grading <span class="awaiting-mod">2</span>', end( $submenu['sensei'] )[0], 'Should display 2 ungraded quizzes' );

		// Clean up the submenu.
		unset( $submenu['sensei'] );

		if ( self::is_hpps_tables_mode() ) {
			$this->reset_hpps_repository();
		}
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
	 * Test grade_gap_fill_question with regex patterns.
	 *
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
	 * Test that courses average grade returns zero when there are no courses with graded quizzes.
	 *
	 * @covers Sensei_Grading::get_courses_average_grade
	 */
	public function testGetCoursesAverageGrade_WhenNoCourses_ReturnsZero() {
		$this->assertSame( 0.0, Sensei()->grading->get_courses_average_grade(), 'Average grade should be zero when there are no courses with graded quizzes.' );
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
		// Lesson 0 has no quiz, so no quiz_answers.
		$this->assignGrade( $comment_ids[0], '10', false );
		$this->assignGrade( $comment_ids[1], '50', false );
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
	 * A teacher who cannot edit a lesson should not be able to grade its quiz; the
	 * submission handler should bail out and leave the student's existing quiz
	 * status untouched.
	 *
	 * @covers Sensei_Grading::admin_process_grading_submission
	 */
	public function testAdminProcessGradingSubmission_WhenTeacherLacksLessonAccess_DoesNotGrade(): void {
		$owner         = $this->get_user_by_role( 'teacher' );
		$other_teacher = $this->get_user_by_role( 'teacher', '_b' );
		$student       = $this->factory->user->create();

		list( $quiz_id, $lesson_id ) = $this->createOwnedPassRequiredQuiz( $owner );

		// Student has passed the owner's quiz.
		Sensei_Utils::sensei_start_lesson( $lesson_id, $student );
		Sensei_Utils::update_lesson_status( $student, $lesson_id, 'passed' );

		// A different teacher submits a grade for the quiz.
		$this->login_as( $other_teacher );
		$this->setGradingSubmissionGlobals( $quiz_id, $student );

		// A blocked submission returns false without redirecting; a processed one
		// redirects. Intercept redirects so a processed submission surfaces as a
		// clean assertion failure.
		$this->prevent_wp_redirect();
		$result     = null;
		$redirected = false;
		try {
			$result = Sensei()->grading->admin_process_grading_submission();
		} catch ( Sensei_WP_Redirect_Exception $e ) {
			$redirected = true;
		}

		$this->assertFalse( $redirected, 'A teacher without lesson access should not reach the redirect.' );
		$this->assertFalse( $result, 'A teacher without access to the lesson should not be able to grade it.' );
		$this->assertEquals(
			'passed',
			Sensei()->quiz_progress_repository->get( $quiz_id, $student )->get_status(),
			"The student's quiz status should be unchanged."
		);
	}

	/**
	 * The teacher who owns the course must still be able to grade the quiz.
	 *
	 * @covers Sensei_Grading::admin_process_grading_submission
	 */
	public function testAdminProcessGradingSubmission_WhenOwningTeacher_GradesQuiz(): void {
		$owner   = $this->get_user_by_role( 'teacher' );
		$student = $this->factory->user->create();

		// The quiz has a pass mark of 50 and requires a passing grade.
		list( $quiz_id, $lesson_id ) = $this->createOwnedPassRequiredQuiz( $owner );

		// The student starts out passing.
		Sensei_Utils::sensei_start_lesson( $lesson_id, $student );
		Sensei_Utils::update_lesson_status( $student, $lesson_id, 'passed' );

		$this->login_as( $owner );

		// Submit the grading with no per-question grades, so the quiz is graded 0
		// against the pass mark of 50 and the student should end up failed.
		$this->setGradingSubmissionGlobals( $quiz_id, $student );

		// The handler redirects on success; intercept it so we can assert on the result.
		$this->prevent_wp_redirect();
		$redirected = false;
		try {
			Sensei()->grading->admin_process_grading_submission();
		} catch ( Sensei_WP_Redirect_Exception $e ) {
			$redirected = true;
		}

		$this->assertTrue( $redirected, 'A successful grading submission should redirect.' );
		$this->assertEquals(
			'failed',
			Sensei()->quiz_progress_repository->get( $quiz_id, $student )->get_status(),
			'The owning teacher should be able to change the quiz status from passed to failed.'
		);
	}

	/**
	 * Creates a manual, pass-required quiz authored by (and whose lesson is authored by)
	 * the given teacher.
	 *
	 * @param int $teacher_id Teacher user ID who owns the lesson and quiz.
	 * @return array{0:int,1:int} [ $quiz_id, $lesson_id ].
	 */
	private function createOwnedPassRequiredQuiz( $teacher_id ): array {
		$lesson_id = $this->factory->lesson->create( array( 'post_author' => $teacher_id ) );
		$quiz_id   = $this->factory->quiz->create(
			array(
				'post_parent' => $lesson_id,
				'post_author' => $teacher_id,
				'meta_input'  => array(
					'_quiz_grade_type' => 'manual',
					'_pass_required'   => 'on',
					'_quiz_passmark'   => 50,
				),
			)
		);

		return array( $quiz_id, $lesson_id );
	}

	/**
	 * Populates the request globals the grading submission handler reads from.
	 *
	 * @param int $quiz_id Quiz being graded.
	 * @param int $user_id Student whose submission is graded.
	 */
	private function setGradingSubmissionGlobals( $quiz_id, $user_id ): void {
		$_GET['quiz_id']  = (string) $quiz_id;
		$_GET['user']     = (string) $user_id;
		$_REQUEST['user'] = (string) $user_id;

		$_POST['sensei_manual_grade']             = (string) $quiz_id;
		$_POST['_wp_sensei_manual_grading_nonce'] = wp_create_nonce( 'sensei_manual_grading' );
		$_POST['quiz_grade_total']                = '100';
		$_POST['all_questions_graded']            = 'yes';
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
	 * Tests that grade_quiz_auto() ignores question IDs whose post type is not
	 * 'question' (e.g. deleted posts or multiple_question containers) instead of
	 * silently treating them as zero-grade via PHP's loose 0 == false comparison.
	 *
	 * Before the fix, get_question_grade() returns false for a non-'question' post type,
	 * but `0 == false` evaluated to true in PHP, so the ID fell through to the zero-grade
	 * branch and was stored with grade false via set_user_grades(). After the fix the
	 * strict false === $achievable_grade guard skips the ID entirely (continue), so it
	 * does not appear in the stored per-question grades and the percentage reflects only
	 * valid questions.
	 *
	 * @covers Sensei_Grading::grade_quiz_auto
	 */
	public function testGradeQuizAuto_InvalidQuestionIdInSubmitted_IsSkippedAndNotStoredInGrades(): void {
		/* Arrange. */
		$user_id   = wp_create_user( 'grading_invalid_q', 'pass', 'grading_invalid_q@example.com' );
		$lesson_id = $this->factory->lesson->create();
		$quiz_id   = $this->factory->quiz->create( array( 'post_parent' => $lesson_id ) );
		update_post_meta( $lesson_id, '_lesson_quiz', $quiz_id );
		update_post_meta( $quiz_id, '_quiz_grade_type', 'auto' );

		// One valid boolean question where 'true' is the correct answer (grade = 1 by default).
		$question_args                                  = $this->factory->question->get_sample_question_data( 'boolean' );
		$question_args['quiz_id']                       = $quiz_id;
		$question_args['post_author']                   = get_post( $quiz_id )->post_author;
		$question_args['question_right_answer_boolean'] = 'true';
		$valid_question_id                              = Sensei()->lesson->lesson_save_question( $question_args );

		// Precondition: the valid question must exist as a 'question' post type.
		$this->assertSame( 'question', get_post_type( $valid_question_id ), 'Precondition: valid question must be of post type "question".' );

		// A regular post (not a 'question' post type): get_question_grade() returns false for it.
		$invalid_question_id = $this->factory->post->create();
		$this->assertNotSame( 'question', get_post_type( $invalid_question_id ), 'Precondition: invalid question must NOT be of post type "question".' );
		$this->assertFalse( Sensei()->question->get_question_grade( $invalid_question_id ), 'Precondition: get_question_grade() must return false for the invalid ID.' );

		wp_set_current_user( $user_id );
		Sensei_Utils::user_start_lesson( $user_id, $lesson_id );

		// Both IDs are submitted — the valid one answered correctly, the invalid one with anything.
		$submitted = array(
			$valid_question_id   => 'true',
			$invalid_question_id => 'true',
		);
		Sensei_Quiz::save_user_answers( $submitted, array(), $lesson_id, $user_id );

		/* Act. */
		$grade = Sensei_Grading::grade_quiz_auto( $quiz_id, $submitted, 0, 'auto' );

		/* Assert. */

		// The returned grade must be 100 — the valid question was answered correctly and the
		// invalid ID must not inflate the denominator or corrupt the numerator.
		$this->assertSame( 100, (int) $grade, 'grade_quiz_auto() should return 100 when the only valid question is answered correctly.' );

		// The valid question must appear in stored grades with a passing mark.
		$stored_grades = Sensei()->quiz->get_user_grades( $lesson_id, $user_id );
		$this->assertIsArray( $stored_grades, 'get_user_grades() should return an array.' );
		$this->assertArrayHasKey(
			$valid_question_id,
			$stored_grades,
			'The valid question ID must be stored in per-question grades.'
		);

		// The invalid ID must not appear in stored grades at all.
		$this->assertArrayNotHasKey(
			$invalid_question_id,
			$stored_grades,
			'An invalid question ID (non-\'question\' post type) must not be stored in per-question grades.'
		);
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
	private function assignGrade( $comment_id, $grade, $has_quiz_answers = true ) {
		add_comment_meta( $comment_id, 'grade', $grade );
		if ( '' !== $grade && $has_quiz_answers ) {
			add_comment_meta( $comment_id, 'quiz_answers', 'a:1:{i:0;s:1:"1";}' );
		}
	}
}
