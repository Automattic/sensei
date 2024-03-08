<?php

class Sensei_Class_Lesson_Test extends WP_UnitTestCase {
	use Sensei_Course_Enrolment_Manual_Test_Helpers;

	/**
	 * @var Sensei_Factory
	 */
	protected $factory;

	/**
	 * Keep initial state of Sensei()->course.
	 *
	 * @var Sensei_Course|null
	 */
	private $initial_course;

	/**
	 * Keep initial state of Sensei()->lesson.
	 *
	 * @var Sensei_Lesson|null
	 */
	private $initial_lesson;

	/**
	 * Keep initial state of Sensei()->quiz.
	 *
	 * @var Sensei_Quiz|null
	 */
	private $initail_quiz;

	/**
	 * Keep initial state of Sensei()->question.
	 *
	 * @var Sensei_Question|null
	 */
	private $initial_question;

	/**
	 * Keep initial state of Sensei()->notices.
	 *
	 * @var Sensei_Notices|null
	 */
	private $initial_notices;

	/**
	 * Keep initial state of global $current_screen.
	 *
	 * @var WP_Screen|null
	 */
	private $initial_screen;

	/**
	 * Keep initial state of global $taxnow.
	 *
	 * @var mixed|string
	 */
	private $initial_taxnow;

	/**
	 * Keep initial state of global $typenow.
	 *
	 * @var mixed|string
	 */
	private $initial_typenow;

	/**
	 * setup function
	 *
	 * This function sets up the lessons, quizes and their questions. This function runs before
	 * every single test in this class
	 */
	public function setUp(): void {
		parent::setUp();

		$this->factory = new Sensei_Factory();
		Sensei_Test_Events::reset();

		global $current_screen, $taxnow, $typenow;
		$this->initial_screen  = $current_screen;
		$this->initial_taxnow  = $taxnow;
		$this->initial_typenow = $typenow;

		$this->initial_course   = Sensei()->course;
		$this->initial_lesson   = Sensei()->lesson;
		$this->initail_quiz     = Sensei()->quiz;
		$this->initial_question = Sensei()->question;
		$this->initial_notices  = Sensei()->notices;
	}

	public function tearDown(): void {
		parent::tearDown();
		$this->factory->tearDown();

		global $current_screen, $taxnow, $typenow;
		$current_screen = $this->initial_screen;
		$taxnow         = $this->initial_taxnow;
		$typenow        = $this->initial_typenow;

		Sensei()->course   = $this->initial_course;
		Sensei()->lesson   = $this->initial_lesson;
		Sensei()->quiz     = $this->initail_quiz;
		Sensei()->question = $this->initial_question;
		Sensei()->notices  = $this->initial_notices;
	}

	/**
	 * Testing the quiz class to make sure it is loaded
	 *
	 * @since 1.8.0
	 */
	public function testClassInstance() {

		// test if the class exists
		$this->assertTrue( class_exists( 'WooThemes_Sensei_Lesson' ), 'Sensei Lesson class does not exist' );

		// test if the global sensei lesson class is loaded
		$this->assertTrue( isset( Sensei()->lesson ), 'Sensei lesson class is not loaded on the global sensei Object' );
	}


	/**
	 * Testing the is lesson pre-requisite completed function.
	 *
	 * @since 1.9.0
	 */
	public function testIsPreRequisiteComplete() {

		$this->assertTrue(
			method_exists( 'WooThemes_Sensei_Lesson', 'is_prerequisite_complete' ),
			'The lesson class method `is_prerequisite_complete` does not exist '
		);

		// falsy state
		$user_id   = 0;
		$lesson_id = 0;
		$this->assertFalse(
			WooThemes_Sensei_Lesson::is_prerequisite_complete( $lesson_id, $user_id ),
			'None existing lesson or user should return false'
		);

		$test_user_id = wp_create_user( 'studentPrerequisite', 'studentPrerequisite', 'studentPrerequisite@test.com' );

		$test_lesson    = $this->factory->get_lessons();
		$test_lesson_id = $test_lesson[0];

		// truthy state
		$course_id                   = $this->factory->get_random_course_id();
		$lessons                     = $this->factory->get_lessons();
		$test_lesson_prerequisite_id = $lessons[1];

		// add lesson to random course
		update_post_meta( $test_lesson_prerequisite_id, '_lesson_course', $course_id );
		update_post_meta( $test_lesson_id, '_lesson_course', $course_id );

		// setup prerequisite
		update_post_meta( $test_lesson_id, '_lesson_prerequisite', $test_lesson_prerequisite_id );

		Sensei_Utils::user_start_lesson( $test_user_id, $test_lesson_prerequisite_id );
		$this->assertFalse(
			WooThemes_Sensei_Lesson::is_prerequisite_complete( $test_lesson_id, $test_user_id ),
			'Users that has NOT completed prerequisite should return false.'
		);

		Sensei_Utils::user_start_lesson( $test_user_id, $test_lesson_prerequisite_id, true );
		$this->assertTrue(
			Sensei_Lesson::is_prerequisite_complete( $test_lesson_id, $test_user_id, true ),
			'Users that has completed prerequisite should return true.'
		);
	}

	/**
	 * Verify if the method get_course_id returns the expected course ID.
	 *
	 * @covers Sensei_Lesson::get_course_id
	 */
	public function testGetCourseId() {
		$this->assertTrue(
			method_exists( 'WooThemes_Sensei_Lesson', 'get_course_id' ),
			'The lesson class method `get_course_id` does not exist '
		);
		$expected_course_id = $this->factory->course->create();
		$lesson_id          = $this->factory->lesson->create();
		update_post_meta( $lesson_id, '_lesson_course', $expected_course_id );
		$course_id = Sensei()->lesson->get_course_id( $lesson_id );
		$this->assertEquals(
			$expected_course_id,
			$course_id,
			"Lesson {$lesson_id} has course ID {$course_id}, expected {$expected_course_id}"
		);
	}

	/**
	 * Verify if the method get_course_ids returns the same result as get_course_id, while also verifying
	 * if it is being cached properly.
	 *
	 * @covers Sensei_Lesson::get_course_ids
	 */
	public function testGetCourseIds() {
		$this->assertTrue(
			method_exists( 'WooThemes_Sensei_Lesson', 'get_course_ids' ),
			'The lesson class method `get_course_ids` does not exist '
		);
		$course_ids = $this->factory->course->create_many( 3 );
		$lesson_ids = $this->factory->lesson->create_many( 9 );
		foreach ( $lesson_ids as $lesson_id_index => $lesson_id ) {
			$course_index = $lesson_id_index % count( $course_ids );
			$course_id    = $course_ids[ $course_index ];
			update_post_meta( $lesson_id, '_lesson_course', $course_id );
		}
		$result_courses_id = Sensei()->lesson->get_course_ids( $lesson_ids );
		foreach ( $lesson_ids as $lesson_id_index => $lesson_id ) {
			$expected_course_index = $lesson_id_index % count( $course_ids );
			$expected_course_id    = $course_ids[ $expected_course_index ];
			$course_id             = $result_courses_id[ $lesson_id ];
			$get_course_id_result  = Sensei()->lesson->get_course_id( $lesson_id );
			$this->assertEquals(
				$expected_course_id,
				$course_id,
				"Lesson with ID {$lesson_id} has course ID {$course_id}, expected {$expected_course_id}"
			);
			$this->assertEquals(
				$get_course_id_result,
				$course_id,
				"get_course_ids returned ID {$course_id} for lesson {$lesson_id}, but get_course_id returned {$get_course_id_result}"
			);
		}
		$shuffled_lesson_ids = $lesson_ids;
		shuffle( $shuffled_lesson_ids );
		$cached_courses_id = Sensei()->lesson->get_course_ids( $shuffled_lesson_ids );
		$this->assertEquals( $result_courses_id, $cached_courses_id );
		foreach ( $lesson_ids as $lesson_id_index => $lesson_id ) {
			$expected_course_index = $lesson_id_index % count( $course_ids );
			$expected_course_id    = $course_ids[ $expected_course_index ];
			$course_id             = $cached_courses_id[ $lesson_id ];
			$this->assertEquals(
				$expected_course_id,
				$course_id,
				"Lesson with ID {$lesson_id} has course ID {$course_id}, expected {$expected_course_id}"
			);
		}
	}

	public function testAddLessonToCourseOrderHook() {
		if ( ! isset( Sensei()->admin ) ) {
			Sensei()->admin = new WooThemes_Sensei_Admin();
		}
		$this->assertTrue(
			method_exists( 'WooThemes_Sensei_Lesson', 'add_lesson_to_course_order' ),
			'The lesson class method `add_lesson_to_course_order` does not exist '
		);

		$course_id = $this->factory->course->create();
		$lessons   = $this->factory->lesson->create_many( 7 );

		$not_a_lesson_post_type              = get_post( $lessons[0], ARRAY_A );
		$not_a_lesson_post_type['post_type'] = 'post';
		wp_insert_post( $not_a_lesson_post_type );

		$unpublished_lesson                = get_post( $lessons[1], ARRAY_A );
		$unpublished_lesson['post_status'] = 'draft';
		wp_insert_post( $unpublished_lesson );

		$lesson_one_id      = $lessons[2];
		$lesson_two_id      = $lessons[3];
		$lesson_three_id    = $lessons[4];
		$ordered_lesson_ids = array( $lesson_one_id, $lesson_two_id, $lesson_three_id );

		$last_lesson_id                            = $lessons[5];
		$a_lesson_assigned_to_an_invalid_course_id = $lessons[6];

		foreach ( $ordered_lesson_ids as $lesson_id ) {
			update_post_meta( $lesson_id, '_lesson_course', $course_id );
		}

		$orderer_lesson_string = implode( ',', $ordered_lesson_ids );
		Sensei()->admin->save_lesson_order( $orderer_lesson_string, $course_id );

		$course_lesson_order = self::get_course_lesson_order( $course_id );

		foreach ( $ordered_lesson_ids as $lesson_id ) {
			$this->assertTrue(
				in_array( $lesson_id, $course_lesson_order ),
				'Lesson with ID ' . $lesson_id . ' is part of course lesson order meta entry'
			);
		}

		update_post_meta( $not_a_lesson_post_type['ID'], '_lesson_course', $course_id );
		update_post_meta( $unpublished_lesson['ID'], '_lesson_course', $course_id );
		update_post_meta( $last_lesson_id, '_lesson_course', $course_id );
		update_post_meta( $a_lesson_assigned_to_an_invalid_course_id, '_lesson_course', - 123 );

		Sensei()->lesson->add_lesson_to_course_order( null );
		$this->assertEquals(
			3,
			count( self::get_course_lesson_order( $course_id ) ),
			'Null does nothing'
		);

		Sensei()->lesson->add_lesson_to_course_order( '' );
		$this->assertEquals(
			3,
			count( self::get_course_lesson_order( $course_id ) ),
			'Empty string does nothing'
		);

		Sensei()->lesson->add_lesson_to_course_order( 0 );
		$this->assertEquals(
			3,
			count( self::get_course_lesson_order( $course_id ) ),
			'Empty string does nothing'
		);

		Sensei()->lesson->add_lesson_to_course_order( - 12 );
		$this->assertEquals(
			3,
			count( self::get_course_lesson_order( $course_id ) ),
			'Invalid post does nothing'
		);

		// test that this lesson will not be added to the course order because it is not
		Sensei()->lesson->add_lesson_to_course_order( $not_a_lesson_post_type['ID'] );
		$this->assertFalse(
			in_array( $not_a_lesson_post_type['ID'], self::get_course_lesson_order( $course_id ) ),
			'Only lesson post types are added course order meta'
		);

		Sensei()->lesson->add_lesson_to_course_order( $last_lesson_id );
		$last_order = self::get_course_lesson_order( $course_id );
		$this->assertTrue(
			in_array( $last_lesson_id, self::get_course_lesson_order( $course_id ) ),
			'All course lessons should be added to the course order meta'
		);
		$this->assertEquals( 5, count( $last_order ) );

		$this->assertTrue(
			in_array( $unpublished_lesson['ID'], self::get_course_lesson_order( $course_id ) ),
			'Unpublished lessons are also added to course order meta'
		);

		$last_id = array_pop( $last_order );
		$this->assertEquals( $last_lesson_id, $last_id, 'by default new lessons are added last' );

		Sensei()->lesson->add_lesson_to_course_order( $a_lesson_assigned_to_an_invalid_course_id );
		$this->assertEquals( 5, count( self::get_course_lesson_order( $course_id ) ), 'do nothing on lessons where no order meta is found' );
	}

	/**
	 * @covers Sensei_Lesson::lesson_has_quiz_with_graded_questions()
	 */
	public function testLessonHasQuizWithGradedQuestionsLessonWithNoQuiz() {
		$lesson_id = $this->factory->get_lesson_no_quiz();
		$this->assertFalse( Sensei()->lesson->lesson_has_quiz_with_graded_questions( $lesson_id ) );
	}

	/**
	 * @covers Sensei_Lesson::lesson_has_quiz_with_graded_questions()
	 */
	public function testLessonHasQuizWithGradedQuestionsQuizWithNoQuestions() {
		$lesson_id = $this->factory->get_lesson_empty_quiz();
		$this->assertFalse( Sensei()->lesson->lesson_has_quiz_with_graded_questions( $lesson_id ) );
	}

	/**
	 * @covers Sensei_Lesson::lesson_has_quiz_with_graded_questions()
	 */
	public function testLessonHasQuizWithGradedQuestionsQuizWithNoGradedQuestions() {
		$lesson_id = $this->factory->get_lesson_no_graded_quiz();
		$this->assertFalse( Sensei()->lesson->lesson_has_quiz_with_graded_questions( $lesson_id ) );
	}

	/**
	 * @covers Sensei_Lesson::lesson_has_quiz_with_graded_questions()
	 */
	public function testLessonHasQuizWithGradedQuestionsQuizWithGradedQuestions() {
		$lesson_id = $this->factory->get_lesson_graded_quiz();
		$this->assertTrue( Sensei()->lesson->lesson_has_quiz_with_graded_questions( $lesson_id ) );
	}

	/**
	 * @covers Sensei_Lesson::maybe_start_lesson
	 */
	public function testMaybeStartLessonNotInLoop() {
		$user_id = wp_create_user( 'getlearnertestuser', 'password', 'getlearnertestuser@sensei-test.com' );
		wp_set_current_user( $user_id );

		$lesson_id = '';
		Sensei_Lesson::maybe_start_lesson( $lesson_id, $user_id );
		$this->assertFalse( Sensei_Utils::user_started_lesson( $lesson_id, $user_id ) );
	}

	/**
	 * @covers Sensei_Lesson::maybe_start_lesson
	 */
	public function testMaybeStartLessonNotActuallyLesson() {
		$user_id = wp_create_user( 'getlearnertestuser', 'password', 'getlearnertestuser@sensei-test.com' );
		wp_set_current_user( $user_id );

		$lesson_id = $this->factory->quiz->create();
		Sensei_Lesson::maybe_start_lesson( $lesson_id, $user_id );
		$this->assertFalse( Sensei_Utils::user_started_lesson( $lesson_id, $user_id ) );
	}

	/**
	 * @covers Sensei_Lesson::maybe_start_lesson
	 */
	public function testMaybeStartLessonNotEnrolled() {
		$user_id = wp_create_user( 'getlearnertestuser', 'password', 'getlearnertestuser@sensei-test.com' );
		wp_set_current_user( $user_id );

		$course_lessons = $this->factory->get_course_with_lessons(
			array(
				'lesson_count'   => 1,
				'question_count' => 1,
			)
		);
		$lesson_id      = array_pop( $course_lessons['lesson_ids'] );

		Sensei_Lesson::maybe_start_lesson( $lesson_id, $user_id );
		$this->assertFalse( Sensei_Utils::user_started_lesson( $lesson_id, $user_id ) );
	}

	/**
	 * @group course-enrolment
	 * @covers Sensei_Lesson::maybe_start_lesson
	 */
	public function testMaybeStartLessonEnrolled() {
		$this->resetCourseEnrolmentManager();

		$user_id = wp_create_user( 'getlearnertestuser', 'password', 'getlearnertestuser@sensei-test.com' );
		wp_set_current_user( $user_id );

		$course_lessons = $this->factory->get_course_with_lessons(
			array(
				'lesson_count'   => 1,
				'question_count' => 1,
			)
		);

		$lesson_id = array_pop( $course_lessons['lesson_ids'] );
		$this->manuallyEnrolStudentInCourse( $user_id, $course_lessons['course_id'] );

		Sensei_Lesson::maybe_start_lesson( $lesson_id, $user_id );
		$this->assertTrue( false !== Sensei_Utils::user_started_lesson( $lesson_id, $user_id ) );
	}

	/**
	 * @covers Sensei_Lesson::maybe_start_lesson
	 */
	public function testMaybeStartLessonPreviewLesson() {
		$user_id = wp_create_user( 'getlearnertestuser', 'password', 'getlearnertestuser@sensei-test.com' );
		wp_set_current_user( $user_id );

		$course_lessons = $this->factory->get_course_with_lessons(
			array(
				'lesson_count'   => 1,
				'question_count' => 1,
				'lesson_args'    => array(
					'meta_input' => array(
						'_lesson_preview' => true,
					),
				),
			)
		);
		$lesson_id      = array_pop( $course_lessons['lesson_ids'] );
		$this->assertEquals( '1', get_post_meta( $lesson_id, '_lesson_preview', true ) );

		Sensei_Lesson::maybe_start_lesson( $lesson_id, $user_id );
		$this->assertFalse( Sensei_Utils::user_started_lesson( $lesson_id, $user_id ) );
	}

	/**
	 * Test initial publish logging course ID.
	 *
	 * @covers Sensei_Lesson::log_initial_publish_event
	 */
	public function testLogInitialPublishCourseID() {
		$lesson_id = $this->factory->lesson->create(
			[
				'post_status' => 'draft',
			]
		);
		$course_id = $this->factory->course->create();
		Sensei_Test_Events::reset();

		// Set the course ID on the lesson.
		add_post_meta( $lesson_id, '_lesson_course', $course_id, true );

		// Publish lesson.
		wp_update_post(
			[
				'ID'          => $lesson_id,
				'post_status' => 'publish',
			]
		);

		Sensei()->post_types->fire_scheduled_initial_publish_actions();
		$events = Sensei_Test_Events::get_logged_events( 'sensei_lesson_publish' );
		$this->assertCount( 1, $events );

		// Ensure course ID is correct.
		$event = $events[0];
		$this->assertEquals( $course_id, $event['url_args']['course_id'] );
	}

	/**
	 * Test initial publish logging no course ID.
	 *
	 * @covers Sensei_Lesson::log_initial_publish_event
	 */
	public function testLogInitialPublishNoCourseID() {
		// Create lesson with no course ID.
		$lesson_id = $this->factory->lesson->create();

		Sensei()->post_types->fire_scheduled_initial_publish_actions();
		$events = Sensei_Test_Events::get_logged_events( 'sensei_lesson_publish' );
		$this->assertCount( 1, $events );

		// Ensure course ID is -1.
		$event = $events[0];
		$this->assertEquals( - 1, $event['url_args']['course_id'] );
	}

	/**
	 * Test initial publish logging module ID.
	 *
	 * @covers Sensei_Lesson::log_initial_publish_event
	 */
	public function testLogInitialPublishModuleID() {
		$lesson_id = $this->factory->lesson->create(
			[
				'post_status' => 'draft',
			]
		);

		// Add a module.
		$module_ids = wp_set_object_terms( $lesson_id, [ 'test-module' ], 'module' );
		$module_id  = $module_ids[0];

		// Publish lesson.
		wp_update_post(
			[
				'ID'          => $lesson_id,
				'post_status' => 'publish',
			]
		);

		Sensei()->post_types->fire_scheduled_initial_publish_actions();
		$events = Sensei_Test_Events::get_logged_events( 'sensei_lesson_publish' );
		$this->assertCount( 1, $events );

		// Ensure course ID is correct.
		$event = $events[0];
		$this->assertEquals( $module_id, $event['url_args']['module_id'] );
	}

	/**
	 * Test initial publish logging no module ID.
	 *
	 * @covers Sensei_Lesson::log_initial_publish_event
	 */
	public function testLogInitialPublishNoModuleID() {
		// Create lesson with no module ID.
		$lesson_id = $this->factory->lesson->create();

		Sensei()->post_types->fire_scheduled_initial_publish_actions();
		$events = Sensei_Test_Events::get_logged_events( 'sensei_lesson_publish' );
		$this->assertCount( 1, $events );

		// Ensure course ID is -1.
		$event = $events[0];
		$this->assertEquals( - 1, $event['url_args']['module_id'] );
	}

	private static function get_course_lesson_order( $course_id ) {
		$order_string_array = explode( ',', get_post_meta( intval( $course_id ), '_lesson_order', true ) );

		return array_map( 'intval', $order_string_array );
	}

	/**
	 * Tests that Sensei_Lesson::find_first_prerequisite_lesson returns the first lesson that needs to get completed in
	 * a prerequisite chain.
	 *
	 * @covers Sensei_Lesson::find_first_prerequisite_lesson
	 */
	public function testFirstPrerequisiteIsCorrect() {
		$user_id             = $this->factory->user->create();
		$course_with_lessons = $this->factory->get_course_with_lessons(
			[
				'module_count'   => 2,
				'lesson_count'   => 5,
				'question_count' => 0,
			]
		);

		$first_lesson = Sensei()->lesson::find_first_prerequisite_lesson( $course_with_lessons['lesson_ids'][4], $user_id );
		$this->assertEquals( 0, $first_lesson, 'Result should be 0 when there are not prerequisites to the lesson.' );

		update_post_meta( $course_with_lessons['lesson_ids'][4], '_lesson_prerequisite', $course_with_lessons['lesson_ids'][3] );
		update_post_meta( $course_with_lessons['lesson_ids'][3], '_lesson_prerequisite', $course_with_lessons['lesson_ids'][2] );
		update_post_meta( $course_with_lessons['lesson_ids'][2], '_lesson_prerequisite', $course_with_lessons['lesson_ids'][1] );
		update_post_meta( $course_with_lessons['lesson_ids'][1], '_lesson_prerequisite', $course_with_lessons['lesson_ids'][0] );

		$first_lesson = Sensei()->lesson::find_first_prerequisite_lesson( $course_with_lessons['lesson_ids'][4], $user_id );
		$this->assertEquals( $course_with_lessons['lesson_ids'][0], $first_lesson, 'Result not equal with the first lesson in the prerequisite chain.' );

		$first_lesson = Sensei()->lesson::find_first_prerequisite_lesson( $course_with_lessons['lesson_ids'][2], $user_id );
		$this->assertEquals( $course_with_lessons['lesson_ids'][0], $first_lesson, 'Result not equal with the first lesson in the prerequisite chain.' );

		// Complete a lesson in the prerequisite chain and observe that the next one is returned.
		Sensei_Utils::user_start_lesson( $user_id, $course_with_lessons['lesson_ids'][1], true );
		$first_lesson = Sensei()->lesson::find_first_prerequisite_lesson( $course_with_lessons['lesson_ids'][4], $user_id );
		$this->assertEquals( $course_with_lessons['lesson_ids'][2], $first_lesson, 'Result not equal with the third lesson when user has completed the second.' );

		// Ensure that there is no infinite loop when there is a cycle of prerequisites.
		$user_id = $this->factory->user->create();
		update_post_meta( $course_with_lessons['lesson_ids'][0], '_lesson_prerequisite', $course_with_lessons['lesson_ids'][4] );
		$first_lesson = Sensei()->lesson::find_first_prerequisite_lesson( $course_with_lessons['lesson_ids'][3], $user_id );
		$this->assertEquals( $course_with_lessons['lesson_ids'][1], $first_lesson );
	}

	/**
	 * Test get lesson quiz permalink.
	 *
	 * @covers Sensei_Lesson::get_quiz_permalink()
	 */
	public function testGetLessonQuizPermalink() {
		$lesson_id_empty_quiz = $this->factory->get_lesson_empty_quiz();
		$this->assertNull( Sensei()->lesson->get_quiz_permalink( $lesson_id_empty_quiz ) );

		$lesson_id_with_quiz = $this->factory->get_lesson_with_quiz_and_questions();
		$this->assertNotEmpty( Sensei()->lesson->get_quiz_permalink( $lesson_id_with_quiz ) );
	}

	/**
	 * Test quiz submitted.
	 *
	 * @covers Sensei_Lesson::is_quiz_submitted()
	 */
	public function testQuizSubmitted() {
		$lesson_id = $this->factory->get_random_lesson_id();
		$quiz_id   = Sensei()->lesson->lesson_quizzes( $lesson_id );
		$user_id   = $this->factory->user->create();

		$this->assertFalse( Sensei()->lesson->is_quiz_submitted( $lesson_id, $user_id ) );

		Sensei_Quiz::submit_answers_for_grading( [], [], $lesson_id, $user_id );
		$this->assertTrue( Sensei()->lesson->is_quiz_submitted( $lesson_id, $user_id ) );
	}

	/**
	 * Ensure that when getting the lesson prerequisites, they are filtered based on the lesson's course.
	 * The prerequisites should be lessons linked to that course.
	 *
	 * @covers Sensei_Lesson::get_prerequisites
	 */
	public function testLessonsAssignedToACourseShouldHavePrerequisitesFromThatCourse() {
		/* Arrange */
		$course_with_lessons = $this->factory->get_course_with_lessons(
			[
				'lesson_count'   => 3,
				'question_count' => 0,
			]
		);

		// Populate the database with an additional course and lessons.
		$this->factory->get_course_with_lessons(
			[
				'lesson_count'   => 3,
				'question_count' => 0,
			]
		);

		$lesson_id       = $course_with_lessons['lesson_ids'][0];
		$lesson_instance = new Sensei_Lesson();
		$method          = new ReflectionMethod( $lesson_instance, 'get_prerequisites' );
		$method->setAccessible( true );

		/* Act */
		$prerequisites = $method->invoke( $lesson_instance, $lesson_id, $course_with_lessons['course_id'] );

		/* Assert */
		$this->assertCount(
			2, // Excluding the original lesson from the count.
			$prerequisites
		);
	}

	public function testConstruct_Always_SetsTokenToLesson(): void {
		/* Act */
		$lesson = new Sensei_Lesson();

		/* Assert */
		self::assertSame( 'lesson', $lesson->token );
	}

	public function testConstruct_Always_SetsMetaFields(): void {
		/* Act */
		$lesson = new Sensei_Lesson();

		/* Assert */
		$expected = [
			'lesson_prerequisite',
			'lesson_course',
			'lesson_preview',
			'lesson_length',
			'lesson_complexity',
			'lesson_video_embed',
		];
		self::assertSame( $expected, $lesson->meta_fields );
	}

	public function testConstruct_Always_SetsAllowedHtmlToDefaultKsesAllowedHtml(): void {
		/* Act */
		$lesson = new Sensei_Lesson();

		/* Assert */
		$expected = [
			'embed'  => [],
			'iframe' => [
				'width'           => [],
				'height'          => [],
				'src'             => [],
				'frameborder'     => [],
				'allowfullscreen' => [],
			],
			'video'  => [
				'source'   => [],
				'autoplay' => [],
				'controls' => [],
				'height'   => [],
				'loop'     => [],
				'muted'    => [],
				'poster'   => [],
				'preload'  => [],
				'src'      => [],
				'width'    => [],
			],
			'a'      => [
				'class' => [],
				'href'  => [],
				'rel'   => [],
			],
			'span'   => [
				'class' => [],
			],
			'source' => [
				'src'    => [],
				'type'   => [],
				'srcset' => [],
				'sizes'  => [],
				'media'  => [],
			],
		];
		self::assertSame( $expected, $lesson->allowed_html );
	}

	/**
	 * Test that add_custom_navigation doesn't have output when there is a wrong scree.
	 *
	 * @dataProvider providerAddCustomNavigation_WhenWrongScreen_DoesntHaveOutput
	 */
	public function testAddCustomNavigation_WhenWrongScreen_DoesntHaveOutput( string $screen_id, string $screen_base ): void {
		/* Arrange */
		$screen       = WP_Screen::get( $screen_id );
		$screen->base = $screen_base;
		$screen->set_current_screen();

		$lesson = new Sensei_Lesson();

		/* Act */
		ob_start();
		$lesson->add_custom_navigation();
		$output = ob_get_clean();

		/* Assert */
		self::assertEmpty( $output );
	}

	public function providerAddCustomNavigation_WhenWrongScreen_DoesntHaveOutput(): array {
		return [
			'wrong id'     => [
				'a',
				'b',
			],
			'base is term' => [
				'edit-lesson',
				'term',
			],
		];
	}

	/**
	 * Test that add_custom_navigation outputs expected HTML.
	 *
	 * @dataProvider providerAddCustomNavigation_WhenCorrectScreen_HasOutput
	 */
	public function testAddCustomNavigation_WhenCorrectScreen_HasOutput( string $screen_id ): void {
		/* Arrange */
		$screen           = WP_Screen::get( $screen_id );
		$screen->taxonomy = 'a';

		global $current_screen, $taxnow;
		$current_screen = $screen;
		$taxnow         = 'a';

		$lesson = new Sensei_Lesson();

		/* Act */
		ob_start();
		$lesson->add_custom_navigation();
		$output = ob_get_clean();

		/* Assert */
		$expected = '		<div id="sensei-custom-navigation" class="sensei-custom-navigation">
			<div class="sensei-custom-navigation__heading">
				<div class="sensei-custom-navigation__title">
					<h1>Lessons</h1>
				</div>
				<div class="sensei-custom-navigation__links">
					<a class="page-title-action" href="http://example.org/wp-admin/post-new.php?post_type=lesson">New Lesson</a>
					<a href="http://example.org/wp-admin/admin.php?page=lesson-order">Order Lessons</a>
					<a href="http://example.org/wp-admin/admin.php?page=sensei-settings&#038;tab=lesson-settings">Lesson Settings</a>
				</div>
			</div>
			<div class="sensei-custom-navigation__tabbar">
				<a class="sensei-custom-navigation__tab " href="http://example.org/wp-admin/edit.php?post_type=lesson">All Lessons</a>
				<a class="sensei-custom-navigation__tab " href="http://example.org/wp-admin/edit-tags.php?taxonomy=lesson-tag&#038;post_type=course">Lesson Tags</a>
			</div>
		</div>
		';
		self::assertSame( $expected, $output );
	}

	public function providerAddCustomNavigation_WhenCorrectScreen_HasOutput(): array {
		return [
			'edit lesson'     => [
				'edit-lesson',
			],
			'edit lesson tag' => [
				'edit-lesson-tag',
			],
		];
	}

	public function testAddCustomLinkToCourse_WhenNoPost_DoesntHaveOutput(): void {
		/* Arrange */
		global $post;
		$post = null;

		$lesson = new Sensei_Lesson();

		/* Act */
		ob_start();
		$lesson->add_custom_link_to_course();
		$output = ob_get_clean();

		/* Assert */
		self::assertEmpty( $output );
	}

	public function testAddCustomLinkToCourse_WhenScreenBaseWasNotPost_DoesntHaveOutput(): void {
		/* Arrange */
		global $post;
		$post = new WP_Post( (object) [ 'ID' => 1 ] );

		$screen       = WP_Screen::get( 'a' );
		$screen->base = 'b';
		$screen->set_current_screen();

		$lesson = new Sensei_Lesson();

		/* Act */
		ob_start();
		$lesson->add_custom_link_to_course();
		$output = ob_get_clean();

		/* Assert */
		self::assertEmpty( $output );
	}

	public function testAddCustomLinkToCourse_WhenNoCourseIdInPostMeta_DoesntHaveOutput(): void {
		/* Arrange */
		global $post;
		$post = new WP_Post( (object) [ 'ID' => 1 ] );

		update_post_meta( $post->ID, '_course_id', '', true );

		$screen       = WP_Screen::get( 'a' );
		$screen->base = 'post';
		$screen->set_current_screen();

		$lesson = new Sensei_Lesson();

		/* Act */
		ob_start();
		$lesson->add_custom_link_to_course();
		$output = ob_get_clean();

		/* Assert */
		self::assertEmpty( $output );
	}

	public function testAddCustomLinkToCourse_WhenCourseInTrash_DoesntHaveOutput(): void {
		/* Arrange */
		global $post;
		$post = new WP_Post( (object) [ 'ID' => 1 ] );

		$course_id = $this->factory->course->create( [ 'post_status' => 'trash' ] );
		update_post_meta( $post->ID, '_course_id', $course_id, true );

		$screen       = WP_Screen::get( 'a' );
		$screen->base = 'post';
		$screen->set_current_screen();

		$lesson = new Sensei_Lesson();

		/* Act */
		ob_start();
		$lesson->add_custom_link_to_course();
		$output = ob_get_clean();

		/* Assert */
		self::assertEmpty( $output );
	}

	public function testAddCustomLinkToCourse_WhenPostWasntLesson_DoesntHaveOutput(): void {
		/* Arrange */
		global $post;
		$post = $this->factory->post->create_and_get();

		$course_id = $this->factory->course->create();
		update_post_meta( $post->ID, '_course_id', $course_id, true );

		$screen       = WP_Screen::get( 'a' );
		$screen->base = 'post';
		$screen->set_current_screen();

		$lesson = new Sensei_Lesson();

		/* Act */
		ob_start();
		$lesson->add_custom_link_to_course();
		$output = ob_get_clean();

		/* Assert */
		self::assertEmpty( $output );
	}

	public function testAddCustomLinkToCourse_WhenPostWasLesson_HasOutput(): void {
		/* Arrange */
		global $post;
		$post      = $this->factory->lesson->create_and_get();
		$course_id = $this->factory->course->create();
		update_post_meta( $post->ID, '_lesson_course', $course_id, true );

		$screen       = WP_Screen::get( 'a' );
		$screen->base = 'post';
		$screen->set_current_screen();

		$lesson = new Sensei_Lesson();

		/* Act */
		ob_start();
		$lesson->add_custom_link_to_course();
		$output = ob_get_clean();

		/* Assert */
		$expected = '
		<script>
			jQuery(function () {
				jQuery("body.post-type-lesson .wrap a.page-title-action")
					.last()
					.after(\'<a href="http://example.org/wp-admin/post.php?post=' . $course_id . '&amp;action=edit" class="page-title-action" data-sensei-log-event="lesson_edit_course_click">Edit Course</a>\');
			});
		</script>
		';
		self::assertSame( $expected, $output );
	}

	public function testAddVideoMetaBox_WhenNoCourseInMeta_DoesntAddMetaBox(): void {
		/* Arrange */
		$post = $this->factory->lesson->create_and_get();
		update_post_meta( $post->ID, '_lesson_course', '', true );

		$lesson = new Sensei_Lesson();

		/* Act */
		$lesson->add_video_meta_box( $post );

		/* Assert */
		global $wp_meta_boxes;
		$existing_meta_box = $wp_meta_boxes['lesson']['side']['low']['lesson-video'] ?? null;
		self::assertNull( $existing_meta_box );
	}


	public function testAddVideoMetaBox_WhenCourseInMeta_AddsMetaBox(): void {
		/* Arrange */
		$course = $this->factory->course->create_and_get();
		$post   = $this->factory->lesson->create_and_get();
		update_post_meta( $post->ID, '_lesson_course', $course->ID, true );

		$lesson = new Sensei_Lesson();

		/* Act */
		$lesson->add_video_meta_box( $post );

		/* Assert */
		global $wp_meta_boxes;
		$meta_box_title = $wp_meta_boxes['lesson']['side']['low']['lesson-video']['title'] ?? null;
		self::assertSame( 'Video', $meta_box_title );
	}

	public function testLessonComplexities_Always_ReturnsMatchingArray(): void {
		/* Arrange */
		$lesson = new Sensei_Lesson();

		/* Act */
		$result = $lesson->lesson_complexities();

		/* Assert */
		$expected = [
			'easy' => 'Easy',
			'std'  => 'Standard',
			'hard' => 'Hard',
		];
		self::assertSame( $expected, $result );
	}

	/**
	 * Test that lesson_count returns the correct number of lessons.
	 *
	 * @dataProvider providerLessonCount_ParamsGiven_ReturnsMatchingValue
	 */
	public function testLessonCount_ParamsGiven_ReturnsMatchingValue( $status, $with_course, $expected ): void {
		$course_id = $this->factory->course->create();
		$this->factory->lesson->create_many(
			2,
			[
				'post_status' => 'publish',
				'meta_input'  => [ '_lesson_course' => 0 ],
			]
		);
		$this->factory->lesson->create_many(
			3,
			[
				'post_status' => 'draft',
				'meta_input'  => [ '_lesson_course' => 0 ],
			]
		);
		$this->factory->lesson->create_many(
			4,
			[
				'post_status' => 'trash',
				'meta_input'  => [ '_lesson_course' => 0 ],
			]
		);
		$this->factory->lesson->create_many(
			5,
			[
				'post_status' => 'publish',
				'meta_input'  => [ '_lesson_course' => $course_id ],
			]
		);
		$this->factory->lesson->create_many(
			6,
			[
				'post_status' => 'draft',
				'meta_input'  => [ '_lesson_course' => $course_id ],
			]
		);
		$this->factory->lesson->create_many(
			7,
			[
				'post_status' => 'trash',
				'meta_input'  => [ '_lesson_course' => $course_id ],
			]
		);

		$lesson = new Sensei_Lesson();

		/* Act */
		$result = $lesson->lesson_count( $status, $with_course ? $course_id : false );

		/* Assert */
		self::assertSame( $expected, $result );
	}

	public function providerLessonCount_ParamsGiven_ReturnsMatchingValue(): array {
		return [
			'publish w/o course' => [ 'publish', false, 7 ],
			'draft w/o course'   => [ 'draft', false, 9 ],
			'trash w/o course'   => [ 'trash', false, 11 ],
			'publish w/ course'  => [ 'publish', true, 5 ],
			'draft w/ course'    => [ 'draft', true, 6 ],
			'trash w/ course'    => [ 'trash', true, 7 ],
		];
	}

	public function testSingleCourseLessonsClasses_WhenUserNotLoggedInAndNoPreview_ReturnsMatchingClasses(): void {
		/* Arrange */
		global $post;
		$post   = $this->factory->course->create_and_get();
		$lesson = new Sensei_Lesson();

		/* Act */
		$result = $lesson->single_course_lessons_classes( [ 'a' ] );

		/* Assert */
		$expected = [
			'a',
			'course',
			'post',
		];
		self::assertSame( $expected, $result );
	}

	public function testSingleCourseLessonsClasses_WhenUserLoggedInButDidntCompleteLessonAndNoPreview_ReturnsMatchingClasses(): void {
		/* Arrange */
		global $post;
		$post = $this->factory->course->create_and_get();

		global $current_user;
		$current_user = $this->factory->user->create_and_get();

		$lesson = new Sensei_Lesson();

		/* Act */
		$result = $lesson->single_course_lessons_classes( [ 'a' ] );

		/* Assert */
		$expected = [
			'a',
			'course',
			'post',
		];
		self::assertSame( $post->ID, get_the_ID() );
	}

	public function testCourseSignupLink_NoCourse_ReturnsEmptyString(): void {
		/* Arrange */
		$lesson = new Sensei_Lesson();

		/* Act */
		$result = $lesson->course_signup_link();

		/* Assert */
		self::assertNull( $result );
	}

	public function testCourseSignupLink_WhenSignupNoticeNeededAndCourseAllowsSelfEnrollment_AddsNotice(): void {
		/* Arrange */
		global $post;
		$lesson_id        = $this->factory->lesson->create();
		$post             = get_post( $lesson_id );
		$notices          = $this->createMock( Sensei_Notices::class );
		Sensei()->notices = $notices;

		$course = $this->factory->course->create_and_get();

		$lesson = $this->createMock( Sensei_Lesson::class );
		$lesson->method( 'get_course_id' )->willReturn( $course->ID );
		Sensei()->lesson = $lesson;

		/* Expect & Act */
		$notices->expects( self::once() )
			->method( 'add_notice' )
			->with( $this->stringContains( 'Please sign up for' ) );
		$result = Sensei_Lesson::course_signup_link();
	}

	public function testCourseSignupLink_WhenSignupNoticeNeededAndCourseDoesntAllowSelfEnrollment_AddsNotice(): void {
		/* Arrange */
		global $post;
		$lesson_id        = $this->factory->lesson->create();
		$post             = get_post( $lesson_id );
		$notices          = $this->createMock( Sensei_Notices::class );
		Sensei()->notices = $notices;

		$course = $this->factory->course->create_and_get();
		update_post_meta( $course->ID, '_sensei_self_enrollment_not_allowed', true );

		$lesson = $this->createMock( Sensei_Lesson::class );
		$lesson->method( 'get_course_id' )->willReturn( $course->ID );
		Sensei()->lesson = $lesson;

		/* Expect & Act */
		$notices->expects( self::once() )
			->method( 'add_notice' )
			->with( 'Please contact the course administrator to access the course content.' );
		$result = Sensei_Lesson::course_signup_link();
	}

	public function testPrerequisiteCompleteMessage_PrerequisiteFound_AddsNotice(): void {
		/* Arrange */
		$user_id             = $this->factory->user->create();
		$course_with_lessons = $this->factory->get_course_with_lessons(
			[
				'module_count'   => 0,
				'lesson_count'   => 2,
				'question_count' => 0,
			]
		);

		update_post_meta( $course_with_lessons['lesson_ids'][1], '_lesson_prerequisite', $course_with_lessons['lesson_ids'][0] );

		global $post;
		$post = get_post( $course_with_lessons['lesson_ids'][1] );

		$notices          = $this->createMock( Sensei_Notices::class );
		Sensei()->notices = $notices;

		$course = $this->factory->course->create_and_get();

		$lesson = $this->createMock( Sensei_Lesson::class );
		$lesson->method( 'get_course_id' )->willReturn( $course->ID );
		Sensei()->lesson = $lesson;

		/* Expect & Act */
		$notices->expects( self::once() )->method( 'add_notice' );
		$result = Sensei_Lesson::prerequisite_complete_message();
	}

	public function testHasSenseiBlocks_WhenNoBlocks_ReturnsFalse(): void {
		/* Arrange */
		$lesson_id = $this->factory->lesson->create();
		$lesson    = new Sensei_Lesson();

		/* Act */
		$result = $lesson->has_sensei_blocks( $lesson_id );

		/* Assert */
		self::assertFalse( $result );
	}

	public function testHasSenseiBlocks_WhenHasBlocks_ReturnsTrue(): void {
		/* Arrange */
		$lesson_id = $this->factory->lesson->create(
			[
				'post_content' => '<!-- wp:sensei-lms/lesson-actions --><!-- /wp:sensei-lms/lesson-actions -->',
			]
		);
		$lesson    = new Sensei_Lesson();

		/* Act */
		$result = $lesson->has_sensei_blocks( $lesson_id );

		/* Assert */
		self::assertTrue( $result );
	}

	public function testLogLessonUpdate_WhenCalled_LogsLessonUpdate(): void {
		/* Arrange */
		$course_id = $this->factory->course->create();
		$post      = $this->factory->lesson->create_and_get(
			[
				'post_status' => 'publish',
				'meta_input'  => [ '_lesson_course' => $course_id ],
			]
		);
		$lesson    = new Sensei_Lesson();

		$events     = [];
		$log_events = function ( $log_event, $event_name ) use ( &$events ) {
			$events[] = $event_name;
		};

		$lesson->mark_updating_lesson_id( $post->ID, $post );

		/* Act */
		add_filter( 'sensei_log_event', $log_events, 10, 2 );
		$lesson->log_lesson_update();
		remove_filter( 'sensei_log_event', $log_events, 10, 2 );

		/* Assert */
		$expected = [ 'lesson_update' ];
		self::assertSame( $expected, $events );
	}

	public function testGetAllLessonIds_WhenCalled_ReturnsMatchingValue(): void {
		/* Arrange */
		$lesson_ids = $this->factory->lesson->create_many( 3, [ 'post_status' => 'publish' ] );

		$lesson = new Sensei_Lesson();

		/* Act */
		$result = $lesson->get_all_lesson_ids();

		/* Assert */
		sort( $lesson_ids );
		sort( $result );
		self::assertSame( $lesson_ids, $result );
	}

	public function testUserLessonQuizStatusMessage_QuizFound_OutputsStatus(): void {
		/* Arrange */
		global $current_user;
		$current_user = $this->factory->user->create_and_get();
		$course       = $this->factory->get_course_with_lessons();

		$lesson = $this->createMock( Sensei_Lesson::class );
		$lesson->method( 'lesson_quizzes' )->willReturn( $course['quiz_ids'][0] );
		Sensei()->lesson = $lesson;

		add_filter( 'sensei_is_enrolled', '__return_true' );

		/* Act */
		ob_start();
		Sensei_Lesson::user_lesson_quiz_status_message( $course['lesson_ids'][0], $current_user->ID );
		$result = ob_get_clean();
		remove_filter( 'sensei_is_enrolled', '__return_true' );

		/* Assert */
		self::assertStringContainsString( '<div class="sensei-message info">', $result );
	}

	public function testShouldShowLessonActions_WhenNoPrerequisite_ReturnsTrue(): void {
		/* Arrange */
		$user_id   = $this->factory->user->create();
		$lesson_id = $this->factory->lesson->create();
		update_post_meta( $lesson_id, '_lesson_prerequisite', 0 );

		/* Act */
		$result = Sensei_Lesson::should_show_lesson_actions( $lesson_id, $user_id );

		/* Assert */
		self::assertTrue( $result );
	}

	public function testShouldShowLessonActions_WhenPrerequisiteFoundAndWasLessonAuthor_ReturnsTrue(): void {
		/* Arrange */
		$user_id         = $this->factory->user->create();
		$prerequisite_id = $this->factory->lesson->create();
		$lesson_id       = $this->factory->lesson->create( [ 'post_author' => $user_id ] );
		update_post_meta( $lesson_id, '_lesson_prerequisite', $prerequisite_id );

		/* Act */
		$result = Sensei_Lesson::should_show_lesson_actions( $lesson_id, $user_id );

		/* Assert */
		self::assertTrue( $result );
	}

	public function testShouldShowLessonActions_WhenPrerequisiteFoundAndWasNotLessonAuthor_ReturnsFalse(): void {
		/* Arrange */
		$user_id         = $this->factory->user->create();
		$author_id       = $this->factory->user->create();
		$prerequisite_id = $this->factory->lesson->create();
		$lesson_id       = $this->factory->lesson->create( [ 'post_author' => $author_id ] );
		update_post_meta( $lesson_id, '_lesson_prerequisite', $prerequisite_id );

		/* Act */
		$result = Sensei_Lesson::should_show_lesson_actions( $lesson_id, $user_id );

		/* Assert */
		self::assertFalse( $result );
	}

	public function testFooterQuizCallToAction_WhenCalled_OutputsButton(): void {
		/* Arrange */
		$course = $this->factory->get_course_with_lessons();

		global $post;
		$post = get_post( $course['lesson_ids'][0] );

		global $current_user;
		$current_user = $this->factory->user->create_and_get();

		update_post_meta( $post->ID, '_quiz_has_questions', 1 );

		$lesson = $this->createMock( Sensei_Lesson::class );
		$lesson->method( 'lesson_quizzes' )->willReturn( $course['quiz_ids'][0] );
		Sensei()->lesson = $lesson;

		/* Act */
		add_filter( 'sensei_can_user_view_lesson', '__return_true' );
		add_filter( 'sensei_user_all_access', '__return_true' );

		ob_start();
		Sensei_Lesson::footer_quiz_call_to_action( $course['lesson_ids'][0], $current_user->ID );
		$result = ob_get_clean();

		remove_filter( 'sensei_can_user_view_lesson', '__return_true' );
		remove_filter( 'sensei_user_all_access', '__return_true' );

		/* Assert */
		self::assertStringContainsString( 'View the Lesson Quiz', $result );
	}

	public function testTheTitle_WhenCalled_OutputsTitle(): void {
		/* Arrange */
		global $post;
		$post      = $this->factory->lesson->create_and_get( [ 'post_title' => 'Test Lesson' ] );
		$course_id = $this->factory->course->create();
		update_post_meta( $post->ID, '_lesson_course', $course_id );

		/* Act */
		ob_start();
		Sensei_Lesson::the_title();
		$result = ob_get_clean();

		/* Assert */
		self::assertStringContainsString( 'Test Lesson', $result );
	}

	public function testTheLessonThumbnail_WhenCalled_OutputsThumbnail(): void {
		/* Arrange */
		$lesson_id = $this->factory->lesson->create();

		$lesson = $this->createMock( Sensei_Lesson::class );
		$lesson->method( 'lesson_image' )->willReturn( '<img src="test.jpg" />' );
		Sensei()->lesson = $lesson;

		/* Act */
		ob_start();
		Sensei_Lesson::the_lesson_thumbnail( $lesson_id );
		$result = ob_get_clean();

		/* Assert */
		self::assertStringContainsString( '<img src="test.jpg" />', $result );
	}

	public function testGetSubmittedSettingValue_FieldGiven_ReturnsMatchingValue(): void {
		/* Arrange */
		$_POST['test_field'] = 'test_value';

		$field = [
			'id' => 'test_field',
		];

		$lesson = new Sensei_Lesson();

		/* Act */
		$result = $lesson->get_submitted_setting_value( $field );

		/* Assert */
		self::assertSame( 'test_value', $result );
	}

	public function testGetSubmittedSettingValue_NoValueInPostAndContainsFieldIdInPostWasSet_ReturnsEmptyString(): void {
		/* Arrange */
		$_POST['contains_test_field'] = true;

		$field = [
			'id' => 'test_field',
		];

		$lesson = new Sensei_Lesson();

		/* Act */
		$result = $lesson->get_submitted_setting_value( $field );

		/* Assert */
		self::assertSame( '', $result );
	}

	/**
	 * Test that get_submitted_setting_value returns matching value when the action is editpost.
	 *
	 * @dataProvider providerGetSubmittedSettingValue_WhenEditpostActionForQuizGradeType_ReturnsMatchingValue
	 */
	public function testGetSubmittedSettingValue_WhenEditpostActionForQuizGradeType_ReturnsMatchingValue( $field_value, $expected ): void {
		/* Arrange */
		$_POST['action']          = 'editpost';
		$_POST['quiz_grade_type'] = $field_value;

		$field = [
			'id' => 'quiz_grade_type',
		];

		$lesson = new Sensei_Lesson();

		/* Act */
		$result = $lesson->get_submitted_setting_value( $field );

		/* Assert */
		self::assertSame( $expected, $result );
	}

	public function providerGetSubmittedSettingValue_WhenEditpostActionForQuizGradeType_ReturnsMatchingValue(): array {
		return [
			'auto'   => [ 'on', 'auto' ],
			'manual' => [ 'off', 'manual' ],
		];
	}

	public function testSetQuickEditAdminDefaults_WhenCalled_LocalizesScripts(): void {
		/* Arrange */
		global $wp_scripts;
		$old_wp_scripts = $wp_scripts;
		$wp_scripts     = $this->createMock( WP_Scripts::class );

		$post = $this->factory->lesson->create_and_get();
		$quiz = $this->factory->quiz->create_and_get();

		update_post_meta( $post->ID, '_lesson_prerequisite', 1 );
		update_post_meta( $post->ID, '_lesson_course', 2 );
		update_post_meta( $post->ID, '_lesson_preview', 3 );
		update_post_meta( $post->ID, '_lesson_length', 4 );
		update_post_meta( $post->ID, '_lesson_complexity', 5 );
		update_post_meta( $post->ID, '_lesson_video_embed', 6 );

		$sensei_lesson = $this->createMock( Sensei_Lesson::class );
		$sensei_lesson->method( 'lesson_quizzes' )->willReturn( $quiz->ID );
		Sensei()->lesson = $sensei_lesson;

		$sensei_quiz              = $this->createMock( Sensei_Quiz::class );
		$sensei_quiz->meta_fields = [ 'b' ];
		Sensei()->quiz            = $sensei_quiz;
		update_post_meta( $quiz->ID, '_b', 7 );

		$lesson = new Sensei_Lesson();

		/* Expect & Act */
		$wp_scripts
			->expects( self::once() )
			->method( 'localize' )
			->with(
				'sensei-lesson-quick-edit',
				'sensei_quick_edit_' . $post->ID,
				[
					'lesson_prerequisite' => '1',
					'lesson_course'       => '2',
					'lesson_preview'      => '3',
					'lesson_length'       => '4',
					'lesson_complexity'   => '5',
					'lesson_video_embed'  => '6',
					'b'                   => '7',
				]
			);
		$lesson->set_quick_edit_admin_defaults( 'lesson-course', $post->ID );

		/* Reset */
		$wp_scripts = $old_wp_scripts;
	}

	public function testMetaBoxSave_PostIdGiven_UpdatesPostMeta(): void {
		/* Arrange */
		global $current_user;
		$current_user = $this->factory->user->create_and_get( [ 'role' => 'administrator' ] );

		$nonce                       = wp_create_nonce( 'sensei-save-post-meta' );
		$_POST['woo_lesson_nonce']   = $nonce;
		$_POST['post_type']          = 'post';
		$_POST['lesson_video_embed'] = 'a';
		$_POST['lesson_preview']     = 'b';
		$_POST['lesson_length']      = '5';

		$post = $this->factory->lesson->create_and_get();

		$lesson              = new Sensei_Lesson();
		$lesson->meta_fields = [ 'lesson_video_embed', 'lesson_preview', 'lesson_length' ];

		/* Act */
		$lesson->meta_box_save( $post->ID );

		/* Assert */
		$expected = [
			'_lesson_video_embed' => [ 'a' ],
			'_lesson_preview'     => [ 'b' ],
			'_lesson_length'      => [ '5' ],
		];
		$actual   = get_post_meta( $post->ID );
		self::assertSame( $expected, $actual );
	}

	public function testQuizPanelQuestion_WhenMultipleChoiceQuestion_OutputsQuestion(): void {
		/* Arrange */
		$question = $this->factory->quiz->create_and_get( [ 'post_title' => 'Test Question' ] );

		update_post_meta( $question->ID, '_question_right_answer', 'a' );
		update_post_meta( $question->ID, '_question_wrong_answers', [ 'b', 'c', 'd' ] );

		Sensei()->question = $this->createMock( Sensei_Question::class );
		Sensei()->question->method( 'get_question_grade' )->willReturn( '1' );

		$lesson = new Sensei_Lesson();

		/* Act */
		$result = $lesson->quiz_panel_question( '', 0, $question->ID, 'quiz', [] );

		/* Assert */
		self::assertStringContainsString( 'Test Question', $result );
	}

	public function testLessonQuizQuestions_WhenCalled_ReturnsTheQuizQuestions(): void {
		/* Arrange */
		$lesson_id = $this->factory->lesson->create();
		$quiz_id   = $this->factory->quiz->create(
			[
				'post_parent' => $lesson_id,
				'meta_input'  => [
					'_quiz_lesson' => $lesson_id,
				],
			]
		);

		$question_1 = $this->factory->question->create( [ 'quiz_id' => $quiz_id ] );
		$question_2 = $this->factory->question->create( [ 'quiz_id' => $quiz_id ] );

		/* Act */
		$questions    = Sensei()->lesson->lesson_quiz_questions( $quiz_id );
		$question_ids = wp_list_pluck( $questions, 'ID' );

		/* Assert */
		$this->assertSame( [ $question_1, $question_2 ], $question_ids );
	}

	public function testLessonQuizQuestions_WhenUserAlreadySubmittedTheQuizButQuestionsHaveChanged_ReturnsTheInitiallyAskedQuestions(): void {
		/* Arrange. */
		$user_id = $this->factory->user->create();
		wp_set_current_user( $user_id );

		$lesson_id = $this->factory->lesson->create();
		$quiz_id   = $this->factory->quiz->create(
			[
				'post_parent' => $lesson_id,
				'meta_input'  => [
					'_quiz_lesson' => $lesson_id,
				],
			]
		);

		// Submit the quiz with two question.
		$question_1 = $this->factory->question->create( [ 'quiz_id' => $quiz_id ] );
		$question_2 = $this->factory->question->create( [ 'quiz_id' => $quiz_id ] );
		$answers    = $this->factory->generate_user_quiz_answers( $quiz_id );
		Sensei_Quiz::submit_answers_for_grading( $answers, [], $lesson_id, $user_id );

		// Add another question.
		$question_3 = $this->factory->question->create( [ 'quiz_id' => $quiz_id ] );

		/* Act. */
		$questions    = Sensei()->lesson->lesson_quiz_questions( $quiz_id );
		$question_ids = wp_list_pluck( $questions, 'ID' );

		/* Assert. */
		$this->assertSame( [ $question_1, $question_2 ], $question_ids );
	}

	public function testSaveAllLessonsEditFields_WhenCalled_UpdatesPostMeta(): void {
		/* Arrange */
		$lesson_id = $this->factory->lesson->create();
		$course_id = $this->factory->course->create();
		$data      = array(
			'_edit_lessons_nonce' => wp_create_nonce( 'bulk-edit-lessons' ),
			'lesson_course'       => $course_id,
			'lesson_complexity'   => 'hard',
		);
		$lesson    = new Sensei_Lesson();

		/* Act */
		$lesson->save_all_lessons_edit_fields( array( $lesson_id ), $data );

		/* Assert */
		$expected = array(
			'_lesson_course'     => $course_id,
			'_lesson_complexity' => 'hard',
		);
		$actual   = array(
			'_lesson_course'     => (int) get_post_meta( $lesson_id, '_lesson_course', true ),
			'_lesson_complexity' => get_post_meta( $lesson_id, '_lesson_complexity', true ),
		);
		self::assertSame( $expected, $actual );
	}

	public function testBulkEditSavePost_WhenCalled_UpdatesPostMeta(): void {
		/* Arrange */
		$lesson_id = $this->factory->lesson->create();
		$course_id = $this->factory->course->create();
		$_REQUEST  = array(
			'_edit_lessons_nonce' => wp_create_nonce( 'bulk-edit-lessons' ),
			'lesson_course'       => $course_id,
			'lesson_complexity'   => 'hard',
		);
		$lesson    = new Sensei_Lesson();

		/* Act */
		$lesson->bulk_edit_save_post( $lesson_id );

		/* Assert */
		$expected = array(
			'_lesson_course'     => $course_id,
			'_lesson_complexity' => 'hard',
		);
		$actual   = array(
			'_lesson_course'     => (int) get_post_meta( $lesson_id, '_lesson_course', true ),
			'_lesson_complexity' => get_post_meta( $lesson_id, '_lesson_complexity', true ),
		);
		self::assertSame( $expected, $actual );
	}
}
