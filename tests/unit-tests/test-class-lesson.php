<?php

class Sensei_Class_Lesson_Test extends WP_UnitTestCase {
	use Sensei_Course_Enrolment_Manual_Test_Helpers;

	/**
	 * @var Sensei_Factory
	 */
	private $factory;

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
	public function setup() {
		parent::setup();

		$this->factory = new Sensei_Factory();
		Sensei_Test_Events::reset();
	}

	public function tearDown() {
		parent::tearDown();
		$this->factory->tearDown();
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
	 * Verify if the method get_course_id returns the expected course ID
	 *
	 * @covers Sensei_Lesson::get_course_id
	 */
	public function testGetCourseId() {
		$this->assertTrue(
			method_exists( 'WooThemes_Sensei_Lesson', 'get_course_id' ),
			'The lesson class method `get_course_id` does not exist '
		);
		$course_ids             = $this->factory->course->create_many( 3 );
		$lesson_ids             = $this->factory->lesson->create_many( 9 );
		$lesson_id_to_course_id = array();
		foreach ( $lesson_ids as $lesson_id ) {
			$course_index                         = array_rand( $course_ids );
			$course_id                            = $course_ids[ $course_index ];
			$lesson_id_to_course_id[ $lesson_id ] = $course_id;
			update_post_meta( $lesson_id, '_lesson_course', $course_id );
		}
		foreach ( $lesson_id_to_course_id as $lesson_id => $expected_course_id ) {
			$course_id = Sensei()->lesson->get_course_id( $lesson_id );
			$this->assertEquals(
				$expected_course_id,
				$course_id,
				"Lesson with ID {$lesson_id} has course ID {$course_id}, expected {$expected_course_id}"
			);
		}
	}

	/**
	 * Verify if the method get_course_ids returns the same result as get_course_id
	 *
	 * @covers Sensei_Lesson::get_course_ids
	 */
	public function testGetCourseIds() {
		$this->assertTrue(
			method_exists( 'WooThemes_Sensei_Lesson', 'get_course_ids' ),
			'The lesson class method `get_course_ids` does not exist '
		);
		$course_ids             = $this->factory->course->create_many( 3 );
		$lesson_ids             = $this->factory->lesson->create_many( 9 );
		$lesson_id_to_course_id = array();
		foreach ( $lesson_ids as $lesson_id_index => $lesson_id ) {
			$course_index                         = $lesson_id_index % count( $course_ids );
			$course_id                            = $course_ids[ $course_index ];
			$lesson_id_to_course_id[ $lesson_id ] = $course_id;
			update_post_meta( $lesson_id, '_lesson_course', $course_id );
		}
		$courses_id = Sensei()->lesson->get_course_ids( $lesson_ids );
		foreach ( $lesson_id_to_course_id as $lesson_id => $expected_course_id ) {
			$course_id            = $courses_id[ $lesson_id ];
			$get_course_id_result = Sensei()->lesson->get_course_id( $lesson_id );
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
	}

	/**
	 * Verify if the method getCourseIds is being cached properly
	 *
	 * @covers Sensei_Lesson::get_course_ids
	 */
	public function testGetCourseIdsCache() {
		$this->assertTrue(
			method_exists( 'WooThemes_Sensei_Lesson', 'get_course_ids' ),
			'The lesson class method `get_course_ids` does not exist '
		);
		$course_ids             = $this->factory->course->create_many( 3 );
		$lesson_ids             = $this->factory->lesson->create_many( 9 );
		$lesson_id_to_course_id = array();
		foreach ( $lesson_ids as $lesson_id_index => $lesson_id ) {
			$course_index                         = $lesson_id_index % count( $course_ids );
			$course_id                            = $course_ids[ $course_index ];
			$lesson_id_to_course_id[ $lesson_id ] = $course_id;
			update_post_meta( $lesson_id, '_lesson_course', $course_id );
		}
		$courses_id = Sensei()->lesson->get_course_ids( $lesson_ids );
		foreach ( $lesson_id_to_course_id as $lesson_id => $expected_course_id ) {
			$course_id            = $courses_id[ $lesson_id ];
			$get_course_id_result = Sensei()->lesson->get_course_id( $lesson_id );
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
		shuffle( $lesson_ids );
		$courses_id = Sensei()->lesson->get_course_ids( $lesson_ids );
		foreach ( $lesson_id_to_course_id as $lesson_id => $expected_course_id ) {
			$course_id = $courses_id[ $lesson_id ];
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
		update_post_meta( $a_lesson_assigned_to_an_invalid_course_id, '_lesson_course', -123 );

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

		Sensei()->lesson->add_lesson_to_course_order( -12 );
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
		$this->assertEquals( -1, $event['url_args']['course_id'] );
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
		$this->assertEquals( -1, $event['url_args']['module_id'] );
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
}
