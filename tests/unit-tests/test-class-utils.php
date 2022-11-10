<?php

use Sensei\Internal\Quiz_Submission\Answer\Repositories\Answer_Repository_Interface;
use Sensei\Internal\Quiz_Submission\Grade\Repositories\Grade_Repository_Interface;
use Sensei\Internal\Quiz_Submission\Submission\Models\Submission;
use Sensei\Internal\Quiz_Submission\Submission\Repositories\Submission_Repository_Interface;

class Sensei_Class_Utils_Test extends WP_UnitTestCase {

	/**
	 * setup function
	 *
	 * This function sets up the lessons, quizzes and their questions. This function runs before
	 * every single test in this class
	 */
	public function setup() {
		parent::setup();

		$this->factory = new Sensei_Factory();

		// remove this action so that no emails are sent during this test
		remove_all_actions( 'sensei_user_course_start' );

	}

	public function tearDown() {
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

		$this->assertTrue( 2 == WooThemes_Sensei_Utils::round( 2.12, 0 ), '2.12 rounded with 0 precision should be 2' );
		$this->assertTrue( 3.3 == WooThemes_Sensei_Utils::round( 3.3333, 1 ), '3.3333 rounded with 1 precision should be 3.3' );
		$this->assertTrue( doubleval( 2.13 ) == WooThemes_Sensei_Utils::round( 2.1256, 2 ), '2.1256 rounded with 2 precision should be 2.12' );
		$this->assertTrue( 3 == WooThemes_Sensei_Utils::round( 2.5, 0 ), '2.5 rounded with 0 precision should be 3' );

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

	/**
	 * Test that the query params inputs are correct.
	 *
	 * @covers Sensei_Utils::output_query_params_as_inputs
	 */
	public function testOutputQueryParamsAsInputs() {
		/* Arrange. */
		$_GET = [
			'param_1' => 'value_1',
			'param_2' => 'value_2',
		];

		/* Act. */
		ob_start();
		Sensei_Utils::output_query_params_as_inputs( [ 'param_2' ] );
		$output = ob_get_clean();

		/* Assert. */
		$this->assertContains( '<input type="hidden" name="param_1" value="value_1">', $output, 'Output should contain the query param input with the correct value.' );
		$this->assertNotContains( 'param_2', $output, 'Output should not contain the excluded query param input.' );
	}

	/**
	 * Tests that last activity date formatting function is working correctly.
	 *
	 * @dataProvider lastActivityDateTestingData
	 */
	public function testFormatLastActivityDate_WhenCalled_ReturnsCorrectlyFormattedDates( $minutes_count, $expected_output ) {
		/* Arrange */
		$gmt_time           = gmdate( 'Y-m-d H:i:s', strtotime( '-' . $minutes_count . ' seconds' ) );
		$date_as_per_format = wp_date( get_option( 'date_format' ), ( new DateTime( $gmt_time ) )->getTimestamp(), new DateTimeZone( 'GMT' ) );

		/* Act */
		$actual = Sensei_Utils::format_last_activity_date( $gmt_time );

		/* Assert */
		$expected = empty( $expected_output ) ? $date_as_per_format : $expected_output;
		self::assertEquals( $expected, $actual, 'Last activity date is not being formatted correctly' );
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
		$submission    = $this->createMock( Submission::class );
		$submission->method( 'get_id' )->willReturn( $submission_id );

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
			->with( $submission_id );

		Sensei()->quiz_answer_repository
			->expects( $this->once() )
			->method( 'delete_all' )
			->with( $submission_id );

		Sensei_Utils::sensei_delete_quiz_answers( 1, 1 );
	}

	/**
	 * Returns an associative array with parameters needed to run lesson completion test.
	 *
	 * @return array
	 */
	public function lastActivityDateTestingData() {
		return [
			'days'    => [ ( 5 * 24 * 60 * 60 ), '5 days ago' ],
			'hours'   => [ 60 * 5 * 60, '5 hours ago' ],
			'minutes' => [ 20 * 60, '20 mins ago' ],
			'seconds' => [ 20, '20 seconds ago' ],
			'date'    => [ 8 * 24 * 60 * 60, null ],
		];
	}
}
