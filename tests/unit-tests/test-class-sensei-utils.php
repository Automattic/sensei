<?php

require_once SENSEI_TEST_FRAMEWORK_DIR . '/trait-sensei-progress-insert-failure-helpers.php';

use Sensei\Internal\Quiz_Submission\Answer\Repositories\Answer_Repository_Interface;
use Sensei\Internal\Quiz_Submission\Grade\Repositories\Grade_Repository_Interface;
use Sensei\Internal\Quiz_Submission\Submission\Models\Submission_Interface;
use Sensei\Internal\Quiz_Submission\Submission\Repositories\Submission_Repository_Interface;

require_once SENSEI_TEST_FRAMEWORK_DIR . '/trait-sensei-file-system-helper.php';

/**
 * Class for testing Sensei_Utils class.
 *
 * @group utils
 *
 * phpcs:disable Generic.Commenting.DocComment.MissingShort
 */
class Sensei_Utils_Test extends WP_UnitTestCase {
	use Sensei_HPPS_Helpers;
	use Sensei_Progress_Insert_Failure_Helpers;
	use \Sensei_File_System_Helper;
	use \Sensei_Clock_Helpers;
	use \Sensei_Course_Enrolment_Manual_Test_Helpers;

	/**
	 * Setup function.
	 *
	 * This function sets up the lessons, quizzes and their questions.
	 * This function runs before every single test in this class.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->factory = new Sensei_Factory();

		// remove this action so that no emails are sent during this test
		remove_all_actions( 'sensei_user_course_start' );

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
		$this->assertTrue( class_exists( 'WooThemes_Sensei_Utils' ), 'Sensei Utils class constant is not loaded' );
	}

	/**
	 * This tests Woothemes_Sensei_Utils::update_user_data
	 */
	public function testUpdateUserData() {

		// setup data needed for this test
		$test_user_id = wp_create_user( 'testUpdateUserData', 'testUpdateUserData', 'testUpdateUserData@test.com' );

		// does this function add_user_data exist?
		$this->assertTrue(
			method_exists( 'WooThemes_Sensei_Utils', 'update_user_data' ),
			'The utils class function `update_user_data` does not exist '
		);

		// does it return false for invalid data
		$invalid_data_message = 'This function does not check false data correctly';
		$this->assertFalse(
			WooThemes_Sensei_Utils::update_user_data( '', '', '', '' ),
			$invalid_data_message . ": '','','','' "
		);
		$this->assertFalse(
			WooThemes_Sensei_Utils::update_user_data( ' ', ' ', ' ', ' ' ),
			$invalid_data_message . ": ' ', ' ', ' ', ' ' "
		);
		$this->assertFalse(
			WooThemes_Sensei_Utils::update_user_data( -1, -2, -3, -1 ),
			$invalid_data_message . ': -1,-2, -3, -1 '
		);
		$this->assertFalse(
			WooThemes_Sensei_Utils::update_user_data( 'key', 500, 'val', 5000 ),
			$invalid_data_message . ": 'key', 500, 'val', 5000 "
		);

		// does this function return false when attempting to add user data on non sensei post types
		$test_post = $this->factory->post->create();
		$this->assertFalse(
			WooThemes_Sensei_Utils::update_user_data( 'key', $test_post, 'val', $test_user_id ),
			'This function does not reject unsupported post types'
		);

		// does this function return false when attempting to add user data on non sensei post types
		$test_array     = array( 1, 2, 3, 4 );
		$test_course_id = $this->factory->post->create( array( 'post_type' => 'course' ) );
		$test_data_key  = 'test_key';
		WooThemes_Sensei_Utils::update_user_data( $test_data_key, $test_course_id, $test_array, $test_user_id );
		$course_status = WooThemes_Sensei_Utils::user_course_status( $test_course_id, $test_user_id );

		// is the status updated on the passed in sensei post type ?
		$this->assertTrue(
			isset( $course_status->comment_ID ),
			'This function did not create the status on the passed in sensei post type'
		);

		// setup the next group of assertions
		$retrieved_array = get_comment_meta( $course_status->comment_ID, $test_data_key, true );

		// is the data saved still intact
		$this->assertEquals( $test_array, $retrieved_array, 'The saved and retrieved data does not match' );

	}

	/**
	 * This tests Woothemes_Sensei_Utils::get_user_data
	 */
	public function testGetUserData() {

		// setup data needed for this test
		$test_user_id = wp_create_user( 'testGetUserData', 'testGetUserData', 'testGetUserData@test.com' );

		// does this function add_user_data exist?
		$this->assertTrue(
			method_exists( 'WooThemes_Sensei_Utils', 'get_user_data' ),
			'The utils class function `get_user_data` does not exist '
		);

		// does it return false for invalid the parameters?
		$invalid_data_message = 'This function does not check false data correctly';
		$this->assertFalse(
			WooThemes_Sensei_Utils::get_user_data( '', '', '' ),
			$invalid_data_message . ": '','','' "
		);
		$this->assertFalse(
			WooThemes_Sensei_Utils::get_user_data( ' ', ' ', ' ' ),
			$invalid_data_message . ": ' ', ' ', ' ' "
		);
		$this->assertFalse(
			WooThemes_Sensei_Utils::get_user_data( -1, -2, -3 ),
			$invalid_data_message . ': -1,-2, -3 '
		);
		$this->assertFalse(
			WooThemes_Sensei_Utils::get_user_data( 'key', 500, 5000 ),
			$invalid_data_message . ": Key, '500', 5000"
		);

		// setup the data for the next assertions
		$test_array     = array( 1, 2, 3, 4 );
		$test_course_id = $this->factory->post->create( array( 'post_type' => 'course' ) );
		$test_data_key  = 'test_key';

		// does this function return false when there is no lesson status?
		$this->assertFalse(
			WooThemes_Sensei_Utils::get_user_data( $test_data_key, $test_course_id, $test_user_id ),
			'This function should return false when the status has not be set for the given post type'
		);

		// setup assertion data
		WooThemes_Sensei_Utils::update_user_data( $test_data_key, $test_course_id, $test_array, $test_user_id );
		$retrieved_value = WooThemes_Sensei_Utils::get_user_data( $test_data_key, $test_course_id, $test_user_id );

		// doest this function return the data that was saved?
		$this->assertEquals( $test_array, $retrieved_value, 'This function does not retrieve the data that was saved' );

	}

	/**
	 * This tests Woothemes_Sensei_Utils::delete_user_data
	 */
	public function testDeleteUserData() {

		// does this function add_user_data exist?
		$this->assertTrue(
			method_exists( 'WooThemes_Sensei_Utils', 'delete_user_data' ),
			'The utils class function `delete_user_data` does not exist '
		);

		// does it return false for invalid the parameters?
		$invalid_data_message = 'This function does not check false data correctly';
		$this->assertFalse(
			WooThemes_Sensei_Utils::delete_user_data( '', '', '' ),
			$invalid_data_message . ": '','','' "
		);
		$this->assertFalse(
			WooThemes_Sensei_Utils::delete_user_data( ' ', ' ', ' ' ),
			$invalid_data_message . ": ' ', ' ', ' ' "
		);
		$this->assertFalse(
			WooThemes_Sensei_Utils::delete_user_data( -1, -2, -3 ),
			$invalid_data_message . ': -1,-2, -3 '
		);
		$this->assertFalse(
			WooThemes_Sensei_Utils::delete_user_data( 'key', 500, 5000 ),
			$invalid_data_message . ": 500, 'key', 5000"
		);

		// setup the data for the next assertions
		$test_user_id   = wp_create_user( 'testDeleteUserData', 'testDeleteUserData', 'testDeleteUserData@test.com' );
		$test_array     = array( 1, 2, 3, 4 );
		$test_lesson_id = $this->factory->post->create( array( 'post_type' => 'lesson' ) );
		$test_data_key  = 'test_key';

		// does this function return false when there is no lesson status?
		$this->assertFalse(
			WooThemes_Sensei_Utils::get_user_data( $test_data_key, $test_lesson_id, $test_user_id ),
			'This function should return false when the status has not be set for the given post type'
		);

		// setup assertion data
		WooThemes_Sensei_Utils::update_user_data( $test_data_key, $test_lesson_id, $test_array, $test_user_id );
		$deleted         = WooThemes_Sensei_Utils::delete_user_data( $test_data_key, $test_lesson_id, $test_user_id );
		$retrieved_value = WooThemes_Sensei_Utils::get_user_data( $test_data_key, $test_lesson_id, $test_user_id );

		// doest the function successfully delete existing Sensei user data ?
		$this->assertTrue( $deleted, 'The user data should have been deleted, but was not' );
		$this->assertEmpty( $retrieved_value, 'After deleting the user data should return false' );

	}

	/**
	 * This tests Woothemes_Sensei_Utils::round
	 */
	public function testRound() {

		$this->assertSame( 2.0, WooThemes_Sensei_Utils::round( 2.12, 0 ), '2.12 rounded with 0 precision should be 2' );
		$this->assertSame( 3.3, WooThemes_Sensei_Utils::round( 3.3333, 1 ), '3.3333 rounded with 1 precision should be 3.3' );
		$this->assertSame( doubleval( 2.13 ), WooThemes_Sensei_Utils::round( 2.1256, 2 ), '2.1256 rounded with 2 precision should be 2.12' );
		$this->assertSame( 3.0, WooThemes_Sensei_Utils::round( 2.5, 0 ), '2.5 rounded with 0 precision should be 3' );

	}

	/**
	 * Test the array zip utility function
	 *
	 * @since 1.9.0
	 */
	public function testArrayZipMerge() {

		$this->assertTrue( method_exists( 'Sensei_Utils', 'array_zip_merge' ), 'Sensei_Utils::array_zip_merge does not exist.' );

		// test if the function works
		$array_1      = array( 1, 2, 3 );
		$array_2      = array( 5, 6, 7, 8, 9 );
		$array_zipped = Sensei_Utils::array_zip_merge( $array_1, $array_2 );
		$expected     = array( 1, 5, 2, 6, 3, 7, 8, 9 );
		$this->assertEquals( $expected, $array_zipped );
	}

	public function testOutputQueryParamsAsInputs_NoUrlIsProvided_OutputInputsUsingCurrentUrlParams() {
		/* Arrange. */
		$_GET = [
			'param_1' => 'value_1',
			'param_2' => 'value_2',
		];

		/* Act. */
		ob_start();
		Sensei_Utils::output_query_params_as_inputs();
		$result = ob_get_clean();

		/* Assert. */
		$this->assertEqualHTML( '<input type="hidden" name="param_1" value="value_1"><input type="hidden" name="param_2" value="value_2">', $result );
	}

	public function testOutputQueryParamsAsInputs_WhenUrlIsProvidedAndEchoIsFalse_ReturnsCorrectInputs() {
		/* Arrange. */
		$url = 'https://example.com?param_1=value_1&param_2=value_2';

		/* Act. */
		$result = Sensei_Utils::output_query_params_as_inputs( [], $url, false );

		/* Assert. */
		$this->assertEqualHTML( '<input type="hidden" name="param_1" value="value_1"><input type="hidden" name="param_2" value="value_2">', $result );
	}

	public function testOutputQueryParamsAsInputs_WhenAParamIsExcluded_ReturnsCorrectInputs() {
		/* Arrange. */
		$url = 'https://example.com?param_1=value_1&param_2=value_2';

		/* Act. */
		$result = Sensei_Utils::output_query_params_as_inputs( [ 'param_2' ], $url, false );

		/* Assert. */
		$this->assertEqualHTML( '<input type="hidden" name="param_1" value="value_1">', $result );
	}

	/**
	 * Tests that last activity date formatting function is working correctly.
	 *
	 * @dataProvider lastActivityDateTestingData
	 */
	public function testFormatLastActivityDate_WhenCalled_ReturnsCorrectlyFormattedDates( $seconds_count, $expected_output ) {
		/* Arrange */
		$current_datetime = new DateTimeImmutable( 'now', new DateTimeZone( 'GMT' ) );
		$this->set_clock_to( $current_datetime->getTimestamp() );

		$test_time          = $current_datetime->getTimestamp() - $seconds_count;
		$gmt_time           = gmdate( 'Y-m-d H:i:s', $test_time );
		$date_as_per_format = wp_date( get_option( 'date_format' ), $test_time, new DateTimeZone( 'GMT' ) );

		/* Act */
		$actual = Sensei_Utils::format_last_activity_date( $gmt_time );

		/* Assert */
		$expected = empty( $expected_output ) ? $date_as_per_format : $expected_output;
		self::assertEquals( $expected, $actual, 'Last activity date is not being formatted correctly' );

		$this->reset_clock();
	}

	public function testSenseiGradeQuiz_WhenCalled_UpdatesTheFinalGrade() {
		/* Arrange. */
		$user_id   = $this->factory->user->create();
		$lesson_id = $this->factory->lesson->create();
		$quiz_id   = $this->factory->quiz->create(
			[
				'post_parent' => $lesson_id,
				'meta_input'  => [
					'_quiz_lesson' => $lesson_id,
				],
			]
		);

		$submission_id = Sensei_Utils::user_start_lesson( $user_id, $lesson_id );

		update_comment_meta( $submission_id, 'questions_asked', '1,2' );

		/* Act. */
		Sensei_Utils::sensei_grade_quiz( $quiz_id, 12.34, $user_id );

		/* Assert. */
		$quiz_submission = Sensei()->quiz_submission_repository->get( $quiz_id, $user_id );

		$this->assertSame( 12.34, $quiz_submission->get_final_grade() );
	}

	/**
	 * A missing course record must be created before changing an existing grade.
	 *
	 * @dataProvider progress_storage_modes
	 * @covers Sensei_Utils::sensei_grade_quiz
	 */
	public function testSenseiGradeQuiz_CourseInsertFailed_PreservesExistingGrade( string $mode ): void {
		$data      = $this->factory->get_course_with_lessons( array( 'question_count' => 1 ) );
		$user_id   = $this->factory->user->create();
		$lesson_id = $data['lesson_ids'][0];
		$quiz_id   = $data['quiz_ids'][0];

		$this->with_failed_progress_insert(
			$mode,
			'course',
			function () use ( $lesson_id, $quiz_id, $user_id ) {
				Sensei()->lesson_progress_repository->create( $lesson_id, $user_id );
				$comments = Sensei()->lesson_progress_repository_factory->create_comments_based_repository()->get( $lesson_id, $user_id );
				update_comment_meta( $comments->get_id(), 'questions_asked', '1' );
				$submission = Sensei()->quiz_submission_repository->get_or_create( $quiz_id, $user_id );
				$submission->set_final_grade( 75 );
				Sensei()->quiz_submission_repository->save( $submission );

				$result = Sensei_Utils::sensei_grade_quiz( $quiz_id, 25, $user_id );

				self::assertFalse( $result, 'Missing required course progress must reject grading.' );
				self::assertEquals( 75, Sensei()->quiz_submission_repository->get( $quiz_id, $user_id )->get_final_grade(), 'An existing grade must remain unchanged.' );
			}
		);
	}

	/**
	 * Starting a lesson also starts its course for the student.
	 *
	 * @covers Sensei_Utils::sensei_start_lesson
	 */
	public function testSenseiStartLesson_WhenLessonHadCourse_StartsCourse() {
		/* Arrange */
		$course_lessons = $this->factory->get_course_with_lessons( array( 'question_count' => 0 ) );
		$user_id        = $this->factory->user->create();

		/* Act */
		Sensei_Utils::sensei_start_lesson( $course_lessons['lesson_ids'][0], $user_id );

		/* Assert */
		self::assertNotFalse( Sensei_Utils::has_started_course( $course_lessons['course_id'], $user_id ) );
	}

	/**
	 * A failed lesson insert must stop its start and completion events.
	 *
	 * @dataProvider progress_storage_modes
	 * @covers Sensei_Utils::sensei_start_lesson
	 */
	public function testSenseiStartLesson_PrimaryInsertFailed_ReturnsFalseWithoutCompletion( string $mode ): void {
		$course_lessons = $this->factory->get_course_with_lessons( array( 'question_count' => 0 ) );
		$lesson_id      = $course_lessons['lesson_ids'][0];
		$user_id        = $this->factory->user->create();
		$completed      = did_action( 'sensei_user_lesson_end' );
		$started        = did_action( 'sensei_user_lesson_start' );

		$this->with_failed_progress_insert(
			$mode,
			'lesson',
			function () use ( $lesson_id, $user_id, $completed, $started ) {
				$result = Sensei_Utils::sensei_start_lesson( $lesson_id, $user_id, true );

				self::assertFalse( $result, 'Lesson creation must report failure.' );
				self::assertNull( Sensei()->lesson_progress_repository->get( $lesson_id, $user_id ), 'No lesson progress may be returned.' );
				self::assertSame( $started + 1, did_action( 'sensei_user_lesson_start' ), 'The start hook must retain its position before progress creation.' );
				self::assertSame( $completed, did_action( 'sensei_user_lesson_end' ), 'The failed lesson must not announce completion.' );
			}
		);
	}

	/**
	 * Start listeners can initialize progress before the lesson is created.
	 *
	 * @covers Sensei_Utils::sensei_start_lesson
	 */
	public function testSenseiStartLesson_NewLessonStarted_FiresStartBeforeProgressCreation(): void {
		$this->maybe_enable_hpps_tables_repository();
		$lesson_id = $this->factory->lesson->create();
		$user_id   = $this->factory->user->create();
		$started   = null;
		$observer  = static function ( $student_id, $started_lesson_id ) use ( &$started ) {
			$started = Sensei_Utils::user_started_lesson( $started_lesson_id, $student_id );
		};
		add_action( 'sensei_user_lesson_start', $observer, 10, 2 );

		try {
			$result = Sensei_Utils::sensei_start_lesson( $lesson_id, $user_id );

			self::assertNotFalse( $result, 'The lesson should start successfully.' );
			self::assertFalse( $started, 'Start listeners must still observe that lesson progress does not exist yet.' );
		} finally {
			remove_action( 'sensei_user_lesson_start', $observer, 10 );
			$this->maybe_reset_hpps_repository();
		}
	}

	/**
	 * Course failures stop lesson creation and preserve the failure return.
	 *
	 * @dataProvider progress_storage_modes
	 * @covers Sensei_Utils::sensei_start_lesson
	 * @covers Sensei_Utils::user_start_course
	 * @covers Sensei_Utils::start_user_on_course
	 */
	public function testSenseiStartLesson_CourseInsertFailed_DoesNotCreateLesson( string $mode ): void {
		$course_lessons = $this->factory->get_course_with_lessons( array( 'question_count' => 0 ) );
		$lesson_id      = $course_lessons['lesson_ids'][0];
		$user_id        = $this->factory->user->create();

		$this->with_failed_progress_insert(
			$mode,
			'course',
			function () use ( $lesson_id, $user_id ) {
				$result = Sensei_Utils::sensei_start_lesson( $lesson_id, $user_id );

				self::assertFalse( $result, 'The enclosing start must report failure.' );
				self::assertNull( Sensei()->lesson_progress_repository->get( $lesson_id, $user_id ), 'Dependent lesson progress must not be created.' );
			}
		);
	}

	/**
	 * Aggregate save failures stop completion at the public helper boundary.
	 *
	 * @dataProvider dual_write_storage_modes
	 * @covers Sensei_Utils::sensei_start_lesson
	 */
	public function testSenseiStartLesson_RequiredSynchronizationInsertFailed_StopsCompletion( string $mode, string $failed_store ): void {
		$lesson_id = $this->factory->lesson->create();
		$user_id   = $this->factory->user->create();
		$completed = did_action( 'sensei_user_lesson_end' );

		$this->with_failed_progress_insert(
			$mode,
			'lesson',
			function () use ( $mode, $lesson_id, $user_id, $completed ) {
				$factory = Sensei()->lesson_progress_repository_factory;
				$primary = 'tables' === $mode ? new \Sensei\Internal\Student_Progress\Lesson_Progress\Repositories\Tables_Based_Lesson_Progress_Repository( $GLOBALS['wpdb'] ) : $factory->create_comments_based_repository();
				$primary->create( $lesson_id, $user_id );

				$result = Sensei_Utils::sensei_start_lesson( $lesson_id, $user_id, true );

				self::assertFalse( $result, 'The enclosing operation must report the required save failure.' );
				self::assertSame( $completed, did_action( 'sensei_user_lesson_end' ), 'Failed synchronization must not announce completed progress.' );
			},
			$failed_store
		);
	}

	/**
	 * Course failures reach the lesson operation without interrupting extension callbacks.
	 *
	 * @dataProvider dual_write_storage_modes
	 * @covers Sensei_Utils::sensei_start_lesson
	 * @covers Sensei_Course::update_status_after_lesson_change
	 */
	public function testSenseiStartLesson_CourseSynchronizationInsertFailed_PreservesFollowingObservers( string $mode, string $failed_store ): void {
		$data      = $this->factory->get_course_with_lessons( array( 'question_count' => 0 ) );
		$course_id = $data['course_id'];
		$lesson_id = $data['lesson_ids'][0];
		$user_id   = $this->factory->user->create();
		$observed  = false;
		$observer  = static function () use ( &$observed ) {
			$observed = true;
		};
		add_action( 'sensei_user_lesson_end', $observer, 20 );

		try {
			$this->with_failed_progress_insert(
				$mode,
				'course',
				function () use ( $mode, $course_id, $lesson_id, $user_id, &$observed ) {
					$factory = Sensei()->course_progress_repository_factory;
					$primary = 'tables' === $mode ? new \Sensei\Internal\Student_Progress\Course_Progress\Repositories\Tables_Based_Course_Progress_Repository( $GLOBALS['wpdb'] ) : $factory->create_comments_based_repository();
					$primary->create( $course_id, $user_id );

					$result = Sensei_Utils::sensei_start_lesson( $lesson_id, $user_id, true );

					self::assertFalse( $result, 'Nested course persistence failure must reach the lesson boundary.' );
					self::assertTrue( $observed, 'Existing completion callbacks must still be allowed to finish.' );
				},
				$failed_store
			);
		} finally {
			remove_action( 'sensei_user_lesson_end', $observer, 20 );
		}
	}

	public function testIsRestRequest_WhenNotRestRequest_ReturnsFalse() {
		/* Act. */
		$is_rest_request = Sensei_Utils::is_rest_request();

		/* Assert. */
		$this->assertFalse( $is_rest_request );
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function testIsRestRequest_WhenRestRequest_ReturnsTrue() {
		/* Arrange. */
		define( 'REST_REQUEST', true );

		/* Act. */
		$is_rest_request = Sensei_Utils::is_rest_request();

		/* Assert. */
		$this->assertTrue( $is_rest_request );
	}

	public function testIsFrontendRequest_WhenFrontendRequest_ReturnsTrue() {
		/* Act. */
		$is_frontend_request = Sensei_Utils::is_frontend_request();

		/* Assert. */
		$this->assertTrue( $is_frontend_request );
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function testIsFrontendRequest_WhenRestRequest_ReturnsFalse() {
		/* Arrange. */
		define( 'REST_REQUEST', true );

		/* Act. */
		$is_frontend_request = Sensei_Utils::is_frontend_request();

		/* Assert. */
		$this->assertFalse( $is_frontend_request );
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function testIsFrontendRequest_WhenAdminRequest_ReturnsFalse() {
		/* Arrange. */
		define( 'WP_ADMIN', true );

		/* Act. */
		$is_frontend_request = Sensei_Utils::is_frontend_request();

		/* Assert. */
		$this->assertFalse( $is_frontend_request );
	}

	/**
	 * Completing a new lesson with questions initializes its comment grade.
	 *
	 * @covers Sensei_Utils::sensei_start_lesson
	 */
	public function testSenseiStartLesson_NewLessonWithQuestionsCompleted_InitializesLessonCommentGrade() {
		$this->maybe_enable_hpps_tables_repository();
		try {
			$user_id = $this->factory->user->create();
			$course  = $this->factory->get_course_with_lessons(
				array(
					'lesson_count'   => 1,
					'question_count' => 1,
				)
			);
			Sensei_Utils::start_user_on_course( $user_id, $course['course_id'] );

			Sensei_Utils::sensei_start_lesson( $course['lesson_ids'][0], $user_id, true );

			$comment = Sensei_Utils::user_lesson_status( $course['lesson_ids'][0], $user_id );

			self::assertSame( '0', get_comment_meta( $comment->comment_ID, 'grade', true ), 'The completed lesson comment should have its initial grade.' );
		} finally {
			$this->maybe_reset_hpps_repository();
		}
	}

	public function testSenseiDeleteQuizAnswers_WhenNoQuizProvided_ReturnsFalse() {
		/* Act. */
		$result = Sensei_Utils::sensei_delete_quiz_answers( 0, 1 );

		/* Assert. */
		$this->assertFalse( $result );
	}

	public function testSenseiDeleteQuizAnswers_WhenNoUserProvidedAndNoUserLoggedIn_ReturnsFalse() {
		/* Act. */
		$result = Sensei_Utils::sensei_delete_quiz_answers( 1, 0 );

		/* Assert. */
		$this->assertFalse( $result );
	}

	public function testSenseiDeleteQuizAnswers_WhenNoQuizSubmission_ReturnsFalse() {
		/* Act. */
		$result = Sensei_Utils::sensei_delete_quiz_answers( 1, 1 );

		/* Assert. */
		$this->assertFalse( $result );
	}

	public function testSenseiDeleteQuizAnswers_WhenHasQuizSubmission_ReturnsTrue() {
		/* Arrange. */
		$user_id   = $this->factory->user->create();
		$lesson_id = $this->factory->lesson->create();
		$quiz_id   = $this->factory->quiz->create(
			[
				'post_parent' => $lesson_id,
				'meta_input'  => [
					'_quiz_lesson' => $lesson_id,
				],
			]
		);

		$this->factory->question->create( [ 'quiz_id' => $quiz_id ] );
		$this->factory->question->create( [ 'quiz_id' => $quiz_id ] );

		$answers = $this->factory->generate_user_quiz_answers( $quiz_id );

		Sensei_Quiz::save_user_answers( $answers, [], $lesson_id, $user_id );

		/* Act. */
		$result = Sensei_Utils::sensei_delete_quiz_answers( $quiz_id, $user_id );

		/* Assert. */
		$this->assertTrue( $result );
	}

	public function testSenseiDeleteQuizAnswers_WhenHasQuizSubmission_DeletesTheQuizData() {
		/* Arrange. */
		$submission_id = 123;
		$submission    = $this->createMock( Submission_Interface::class );
		$submission->method( 'get_id' )->willReturn( $submission_id );

		$quiz_answer_repository     = Sensei()->quiz_answer_repository;
		$quiz_grade_repository      = Sensei()->quiz_grade_repository;
		$quiz_submission_repository = Sensei()->quiz_submission_repository;

		Sensei()->quiz_answer_repository     = $this->createMock( Answer_Repository_Interface::class );
		Sensei()->quiz_grade_repository      = $this->createMock( Grade_Repository_Interface::class );
		Sensei()->quiz_submission_repository = $this->createMock( Submission_Repository_Interface::class );
		Sensei()->quiz_submission_repository
			->method( 'get' )
			->willReturn( $submission );

		/* Act & Assert. */
		Sensei()->quiz_submission_repository
			->expects( $this->once() )
			->method( 'delete' )
			->with( $submission );

		Sensei()->quiz_answer_repository
			->expects( $this->once() )
			->method( 'delete_all' )
			->with( $submission );

		Sensei()->quiz_grade_repository
			->expects( $this->once() )
			->method( 'delete_all' )
			->with( $submission );

		Sensei_Utils::sensei_delete_quiz_answers( 1, 1 );

		/* Reset. */
		Sensei()->quiz_answer_repository     = $quiz_answer_repository;
		Sensei()->quiz_grade_repository      = $quiz_grade_repository;
		Sensei()->quiz_submission_repository = $quiz_submission_repository;
	}

	/**
	 * Missing course progress must not become a completed invalid model.
	 *
	 * @dataProvider progress_storage_modes
	 * @covers Sensei_Utils::user_complete_course
	 */
	public function testUserCompleteCourse_PrimaryInsertFailed_DoesNotCompleteCourse( string $mode ): void {
		$course_id = $this->factory->course->create();
		$user_id   = $this->factory->user->create();
		$completed = did_action( 'sensei_user_course_end' );

		$this->with_failed_progress_insert(
			$mode,
			'course',
			function () use ( $course_id, $user_id, $completed ) {
				$result = Sensei_Utils::user_complete_course( $course_id, $user_id );

				self::assertFalse( $result, 'Failed course completion must report failure.' );
				self::assertSame( $completed, did_action( 'sensei_user_course_end' ), 'The failed course must not announce completion.' );
			}
		);
	}

	/**
	 * Existing subclasses can keep their original completion override.
	 *
	 * @covers Sensei_Utils::user_complete_course
	 */
	public function testUserCompleteCourse_ExistingSubclassOverride_LoadsAndRuns(): void {
		$extension = new class() extends Sensei_Utils {
			/**
			 * @inheritDoc
			 */
			public static function user_complete_course( $course_id = 0, $user_id = 0, $trigger_completion_action = true ) {
				return 'extension completion';
			}
		};

		$result = $extension::user_complete_course();

		self::assertSame( 'extension completion', $result );
	}

	/**
	 * Course completion stores the lesson counts on its mirrored comment.
	 *
	 * @covers Sensei_Utils::user_complete_course
	 * @dataProvider course_metadata_completion_provider
	 */
	public function testUserCompleteCourse_LessonsCompleted_UpdatesCourseCommentMetadata( $completed_lessons, $percent, $status ) {
		$this->maybe_enable_hpps_tables_repository();
		try {
			$user_id = $this->factory->user->create();
			$course  = $this->factory->get_course_with_lessons(
				array(
					'lesson_count'   => 2,
					'question_count' => 0,
				)
			);
			Sensei()->course_progress_repository->create( $course['course_id'], $user_id );
			foreach ( array_slice( $course['lesson_ids'], 0, $completed_lessons ) as $lesson_id ) {
				$progress = Sensei()->lesson_progress_repository->create( $lesson_id, $user_id );
				$progress->complete();
				Sensei()->lesson_progress_repository->save( $progress );
			}

			Sensei_Utils::user_complete_course( $course['course_id'], $user_id );

			$comment = Sensei_Utils::user_course_status( $course['course_id'], $user_id );

			self::assertSame( (string) $percent, get_comment_meta( $comment->comment_ID, 'percent', true ), 'The course comment should reflect the completion percentage.' );
			self::assertSame( (string) $completed_lessons, get_comment_meta( $comment->comment_ID, 'complete', true ), 'The course comment should reflect the completed lesson count.' );
			self::assertSame( $status, Sensei()->course_progress_repository->get( $course['course_id'], $user_id )->get_status(), 'Course status should reflect lesson completion.' );
		} finally {
			$this->maybe_reset_hpps_repository();
		}
	}

	/**
	 * Completion counts for a two-lesson course.
	 *
	 * @return array
	 */
	public static function course_metadata_completion_provider(): array {
		return array(
			'partially completed' => array( 1, 50, 'in-progress' ),
			'fully completed'     => array( 2, 100, 'complete' ),
		);
	}

	/**
	 * Returns an associative array with parameters needed to run lesson completion test.
	 *
	 * @return array
	 */
	public function lastActivityDateTestingData() {
		global $wp_version;

		// Account for the string change introduced in https://core.trac.wordpress.org/ticket/61535
		$minutes_text = version_compare( $wp_version, '6.6.2', '<=' ) ? 'mins' : 'minutes';

		return [
			'days'    => [ ( 5 * 24 * 60 * 60 ), '5 days ago' ],
			'hours'   => [ 60 * 5 * 60, '5 hours ago' ],
			'minutes' => [ 20 * 60, sprintf( '20 %s ago', $minutes_text ) ],
			'seconds' => [ 20, '20 seconds ago' ],
			'date'    => [ 8 * 24 * 60 * 60, null ],
		];
	}

	/**
	 * An enrolled student who has not taken the lesson quiz gets the "not started" status.
	 *
	 * @covers Sensei_Utils::sensei_user_quiz_status_message
	 */
	public function testSenseiUserQuizStatusMessage_WhenStudentWasEnrolledInLessonCourse_ReturnsNotStartedStatus() {
		/* Arrange */
		$course_lessons = $this->factory->get_course_with_lessons( array( 'question_count' => 0 ) );
		$user_id        = $this->factory->user->create();
		wp_set_current_user( $user_id );
		$this->manuallyEnrolStudentInCourse( $user_id, $course_lessons['course_id'] );

		/* Act */
		$status = Sensei_Utils::sensei_user_quiz_status_message( $course_lessons['lesson_ids'][0], $user_id, true );

		/* Assert */
		self::assertSame( 'not_started', $status['status'] );
	}

	public function testUserCountByRole_WhenCalled_ReturnsCorrectNumberOfStudents() {
		$this->factory->user->create_many( 3 );
		$this->factory->user->create_many( 2, array( 'role' => 'student' ) );

		$result = Sensei_Utils::get_user_count_for_role( 'student' );

		$this->assertEquals( 2, $result );
	}

	public function testGetTargetResumeId_WhenPreviousLessonWasCompleted_ReturnsNextLessonId() {
		/* Arrange */
		$course_lessons = $this->factory->get_course_with_lessons(
			array(
				'lesson_count' => 3,
			)
		);
		$user_id        = $this->factory->user->create();

		Sensei_Utils::sensei_start_lesson( $course_lessons['lesson_ids'][0], $user_id, true );

		/* Act */
		$result = Sensei_Utils::get_target_page_post_id_for_continue_url( $course_lessons['course_id'], $user_id );

		/* Assert */
		$this->assertEquals( $course_lessons['lesson_ids'][1], $result );
	}

	public function testGetTargetResumeId_WhenPreviousLessonWasNotCompleted_ReturnsLastLessonId() {
		/* Arrange */
		$course_lessons = $this->factory->get_course_with_lessons(
			array(
				'lesson_count' => 3,
			)
		);
		$user_id        = $this->factory->user->create();

		Sensei_Utils::sensei_start_lesson( $course_lessons['lesson_ids'][0], $user_id, false );

		/* Act */
		$result = Sensei_Utils::get_target_page_post_id_for_continue_url( $course_lessons['course_id'], $user_id );

		/* Assert */
		$this->assertEquals( $course_lessons['lesson_ids'][0], $result );
	}

	public function testGetTargetPagePostIdForContinueUrl_WhenNoLessonIsCompleted_ReturnsFirstLessonId() {
		/* Arrange */
		$course_lessons = $this->factory->get_course_with_lessons(
			array(
				'lesson_count' => 3,
			)
		);
		$user_id        = $this->factory->user->create();

		/* Act */
		$result = Sensei_Utils::get_target_page_post_id_for_continue_url( $course_lessons['course_id'], $user_id );

		/* Assert */
		$this->assertEquals( $course_lessons['lesson_ids'][0], $result );
	}

	public function testGetTargetResumeId_WhenAllLessonsAreCompleted_ReturnsCourseId() {
		/* Arrange */
		$course_lessons = $this->factory->get_course_with_lessons(
			array(
				'lesson_count' => 3,
			)
		);
		$user_id        = $this->factory->user->create();

		foreach ( $course_lessons['lesson_ids'] as $lesson_id ) {
			Sensei_Utils::sensei_start_lesson( $lesson_id, $user_id, true );
		}

		/* Act */
		$result = Sensei_Utils::get_target_page_post_id_for_continue_url( $course_lessons['course_id'], $user_id );

		/* Assert */
		$this->assertEquals( $course_lessons['course_id'], $result );
	}

	public function testGetTargetResumeId_WhenItHasNoLessons_ReturnsCourseId() {
		/* Arrange */
		$course_id = $this->factory->course->create();
		$user_id   = $this->factory->user->create();

		/* Act */
		$result = Sensei_Utils::get_target_page_post_id_for_continue_url( $course_id, $user_id );

		/* Assert */
		$this->assertEquals( $course_id, $result );
	}

	public function testIsFseTheme_WhenBlockTemplatesIndexAvailable_ReturnsTrue() {
		/* Arrange */
		$theme_directory = get_template_directory() . '/block-templates';
		$index_file      = $theme_directory . '/index.html';

		// Remove the 'block-templates/index.html' file if it exists.
		if ( file_exists( $index_file ) ) {
			unlink( $index_file );
		}

		// Create the 'block-templates' directory if it doesn't exist.
		if ( ! is_dir( $theme_directory ) ) {
			mkdir( $theme_directory );
		}

		$this->create_index_file( $index_file );

		/* Act */
		$result = Sensei_Utils::is_fse_theme();

		/* Assert */
		$this->assertTrue( $result );

		unlink( $index_file );
		rmdir( $theme_directory );
	}

	public function testIsFseTheme_WhenIndexHtmlAvailable_ReturnsTrue() {
		/* Arrange */
		$theme_directory = get_template_directory() . '/templates';
		$index_file      = $theme_directory . '/index.html';

		// Remove the 'templates/index.html' file if it exists.
		if ( file_exists( $index_file ) ) {
			unlink( $index_file );
		}

		// Create the 'templates' directory if it doesn't exist.
		if ( ! is_dir( $theme_directory ) ) {
			mkdir( $theme_directory );
		}

		$this->create_index_file( $index_file );

		/* Act */
		$result = Sensei_Utils::is_fse_theme();

		/* Assert */
		$this->assertTrue( $result );

		unlink( $index_file );
		rmdir( $theme_directory );
	}

	public function testUpdateCourseStatus_WhenPreviousStatusNotFound_PassesNullAsPreviousStatusToTheAction(): void {
		/* Arrange. */
		$previous_status = 'not-set';
		$action          = function ( $status, $user_id, $course_id, $comment_id, $prev_status ) use ( &$previous_status ) {
			$previous_status = $prev_status;
		};
		add_action( 'sensei_course_status_updated', $action, 10, 5 );

		/* Act. */
		Sensei_Utils::update_course_status( 1, 2, 'in-progress' );

		/* Assert. */
		$this->assertNull( $previous_status );
	}

	public function testUpdateCourseStatus_WhenPreviousStatusFound_PassesSameStatusAsPreviousStatusToTheAction(): void {
		/* Arrange. */
		Sensei()->course_progress_repository->create( 2, 1 );

		$previous_status = 'not-set';
		$action          = function ( $status, $user_id, $course_id, $comment_id, $prev_status ) use ( &$previous_status ) {
			$previous_status = $prev_status;
		};
		add_action( 'sensei_course_status_updated', $action, 10, 5 );

		/* Act. */
		Sensei_Utils::update_course_status( 1, 2, 'complete' );

		/* Assert. */
		$this->assertSame( 'in-progress', $previous_status );
	}

	/**
	 * Tests that the course grade counts a lesson's quiz when a plugin filter hides the
	 * quiz from post queries, as multilingual plugins do when the quiz belongs to another
	 * language.
	 *
	 * @covers Sensei_Utils::sensei_course_user_grade
	 */
	public function testSenseiCourseUserGrade_QuizHiddenFromPostQueries_ReturnsTheGrade() {
		/* Arrange. */
		$user_id   = $this->factory->user->create();
		$created   = $this->factory->get_course_with_lessons(
			array(
				'lesson_count'   => 1,
				'question_count' => 1,
			)
		);
		$course_id = $created['course_id'];
		$lesson_id = $created['lesson_ids'][0];
		$quiz_id   = $created['quiz_ids'][0];

		$question_id = Sensei()->quiz->get_questions( $quiz_id )[0]->ID;

		// The quiz progress piggybacks on the lesson progress, and the submission is only readable once it has an answer.
		Sensei()->lesson_progress_repository->create( $lesson_id, $user_id );
		Sensei()->quiz_progress_repository->create( $quiz_id, $user_id );
		$submission = Sensei()->quiz_submission_repository->create( $quiz_id, $user_id, 80 );
		Sensei()->quiz_answer_repository->create( $submission, $question_id, base64_encode( 'Answer' ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- Mirrors the stored format.

		$language_filter = function ( $where, $query ) {
			if ( 'quiz' === $query->get( 'post_type' ) ) {
				$where .= ' AND 1=0';
			}
			return $where;
		};
		add_filter( 'posts_where', $language_filter, 10, 2 );

		/* Act. */
		$course_grade = Sensei_Utils::sensei_course_user_grade( $course_id, $user_id );

		/* Clean up & Assert. */
		remove_filter( 'posts_where', $language_filter );
		$this->assertSame( 80.0, (float) $course_grade, 'The course grade should count the quiz when it is hidden from post queries.' );
	}

	public function testSenseiRemoveUserFromCourse_LessonsHiddenByQueryFilters_DeletesTheLessonProgress(): void {
		/* Arrange. */
		$user_id   = $this->factory->user->create();
		$course_id = $this->factory->course->create();
		$lesson_id = $this->factory->lesson->create( array( 'meta_input' => array( '_lesson_course' => $course_id ) ) );
		Sensei()->lesson_progress_repository->create( $lesson_id, $user_id );

		$language_filter = function ( $where, $query ) {
			if ( 'lesson' === $query->get( 'post_type' ) ) {
				$where .= ' AND 1=0';
			}
			return $where;
		};
		add_filter( 'posts_where', $language_filter, 10, 2 );

		/* Act. */
		Sensei_Utils::sensei_remove_user_from_course( $course_id, $user_id );

		/* Clean up & Assert. */
		remove_filter( 'posts_where', $language_filter );
		$this->assertFalse( Sensei()->lesson_progress_repository->has( $lesson_id, $user_id ) );
	}

	public function testUploadFile_FileGiven_CreatesPrivateAttachment(): void {
		/* Arrange. */
		$file = $this->create_upload_file_array();

		/* Act. */
		$attachment_id = Sensei_Utils::upload_file( $file );

		/* Assert. */
		$this->assertSame( 'private', get_post_status( $attachment_id ) );
	}

	public function testUploadFile_FileGiven_AttachmentIsNotReadableAnonymously(): void {
		/* Arrange. */
		$attachment_id = Sensei_Utils::upload_file( $this->create_upload_file_array() );
		wp_set_current_user( 0 );

		/* Act. */
		$response = rest_do_request( new WP_REST_Request( 'GET', '/wp/v2/media/' . $attachment_id ) );

		/* Assert. */
		$this->assertSame( 401, $response->get_status() );
	}

	private function create_upload_file_array(): array {
		$tmp_file = wp_tempnam( 'sensei.png' );
		copy( dirname( __DIR__ ) . '/images/sensei.png', $tmp_file );

		add_filter(
			'sensei_file_upload_args',
			function ( $args ) {
				$args['action'] = 'sensei_unit_test_upload';
				return $args;
			}
		);

		return array(
			'name'     => 'sensei.png',
			'type'     => 'image/png',
			'tmp_name' => $tmp_file,
			'error'    => 0,
			'size'     => filesize( $tmp_file ),
		);
	}
}
