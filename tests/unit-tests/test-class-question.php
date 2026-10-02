<?php

class Sensei_Class_Question_Test extends WP_UnitTestCase {

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
		Sensei_Test_Events::reset();
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
		$this->assertTrue( isset( Sensei()->question ), 'Sensei Question class is not loaded' );

	}

	/**
	 * This tests Woothemes_Sensei()->quiz->get_question_type
	 */
	public function testGetQuestionType() {
		$this->factory->generate_basic_setup();

		// doe this method exist on the quiz class?
		$this->assertTrue(
			method_exists( 'WooThemes_Sensei_Quiz', 'submit_answers_for_grading' ),
			'The method get_question_type does not exist '
		);

		// does this method return false for the wrong data?
		$should_be_false = Sensei()->question->get_question_type( '' );
		$this->assertFalse(
			$should_be_false,
			'The method get_question_type should return false for an empty string parameter'
		);

		// does this method return false for the wrong data?
		$should_be_false = Sensei()->question->get_question_type( '' );
		$this->assertFalse(
			$should_be_false,
			'The method get_question_type should return false for an empty string parameter'
		);
		$should_be_false = Sensei()->question->get_question_type( 2000 );
		$this->assertFalse(
			$should_be_false,
			'The method get_question_type should return false for an empty string parameter'
		);

		// does this method return a string for a valid question id
		$questions               = get_posts( 'post_type=question' );
		$should_be_question_type = Sensei()->question->get_question_type( $questions[ array_rand( $questions ) ]->ID );
		$sensei_question_types   = array_keys( Sensei()->question->question_types() );
		$this->assertTrue(
			in_array( $should_be_question_type, $sensei_question_types ),
			'The method get_question_type should return false for an empty string parameter'
		);

	}

	/**
	 * Test initial publish logging default property values.
	 *
	 * @covers Sensei_Question::log_initial_publish_event
	 */
	public function testLogInitialPublishDefaultPropertyValues() {
		$quiz_id     = $this->factory->quiz->create();
		$question_id = $this->factory->question->create(
			[
				'post_status' => 'draft',
				'quiz_id'     => $quiz_id,
			]
		);

		// Publish question.
		wp_update_post(
			[
				'ID'          => $question_id,
				'post_status' => 'publish',
			]
		);

		Sensei()->post_types->fire_scheduled_initial_publish_actions();
		$events = Sensei_Test_Events::get_logged_events( 'sensei_question_add' );
		$this->assertCount( 1, $events );

		// Ensure default values are correct.
		$event = $events[0];
		$this->assertEquals( 'unknown', $event['url_args']['page'] );
		$this->assertEquals( Sensei()->question->get_question_type( $question_id ), $event['url_args']['question_type'] );
	}

	public function testFileUploadLoadQuestionData_OtherUsersAttachmentGiven_ReturnsNoMediaUrl() {
		/* Arrange. */
		$question_id   = $this->factory->question->create( array( 'question_type' => 'file-upload' ) );
		$attachment_id = $this->factory->attachment->create(
			array(
				'file'        => 'other-user-answer.pdf',
				'post_author' => $this->factory->user->create(),
			)
		);
		wp_set_current_user( $this->factory->user->create() );

		$question_data = array(
			'user_answer_entry'      => (string) $attachment_id,
			'question_wrong_answers' => array(),
		);

		/* Act. */
		$question_data = Sensei_Question::file_upload_load_question_data( $question_data, $question_id, 0 );

		/* Assert. */
		$this->assertSame( '', $question_data['answer_media_url'] );
	}

	public function testFileUploadLoadQuestionData_OwnAttachmentGiven_ReturnsTheMediaUrl() {
		/* Arrange. */
		$user_id       = $this->factory->user->create();
		$question_id   = $this->factory->question->create( array( 'question_type' => 'file-upload' ) );
		$attachment_id = $this->factory->attachment->create(
			array(
				'file'        => 'own-answer.pdf',
				'post_author' => $user_id,
			)
		);
		wp_set_current_user( $user_id );

		$question_data = array(
			'user_answer_entry'      => (string) $attachment_id,
			'question_wrong_answers' => array(),
		);

		/* Act. */
		$question_data = Sensei_Question::file_upload_load_question_data( $question_data, $question_id, 0 );

		/* Assert. */
		$this->assertStringEndsWith( 'own-answer.pdf', $question_data['answer_media_url'] );
	}
}
