<?php
/**
 * Sensei REST API: Sensei_REST_API_Lesson_Quiz_Controller_Tests tests
 *
 * @package sensei-lms
 * @since   3.9.0
 * @group   rest-api
 */

/**
 * Class Sensei_REST_API_Lesson_Quiz_Controller tests.
 */
class Sensei_REST_API_Lesson_Quiz_Controller_Tests extends WP_Test_REST_TestCase {
	use Sensei_Test_Login_Helpers;
	use Sensei_REST_API_Test_Helpers;

	/**
	 * A server instance that we use in tests to dispatch requests.
	 *
	 * @var WP_REST_Server $server
	 */
	protected $server;

	/**
	 * Sensei post factory.
	 *
	 * @var Sensei_Factory
	 */
	protected $factory;

	/**
	 * Quiz route.
	 */
	const REST_ROUTE = '/sensei-internal/v1/lesson-quiz/';

	/**
	 * Test specific setup.
	 */
	public function setUp() {
		parent::setUp();

		global $wp_rest_server;
		$wp_rest_server = new WP_REST_Server();
		$this->server   = $wp_rest_server;

		do_action( 'rest_api_init' );

		$this->factory = new Sensei_Factory();
	}

	/**
	 * Tests that a simple request response matches the schema.
	 */
	public function testGetSimple() {
		$this->login_as_teacher();

		$course_result = $this->factory->get_course_with_lessons(
			[
				'module_count'            => 1,
				'lesson_count'            => 1,
				'question_count'          => 4,
				'multiple_question_count' => 1,
			]
		);

		$request  = new WP_REST_Request( 'GET', self::REST_ROUTE . $course_result['lesson_ids'][0] );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( $response->get_status(), 200 );
		$this->assertCount( 5, $response->get_data()['questions'] );

		$controller = new Sensei_REST_API_Lesson_Quiz_Controller( '' );
		$this->assertMeetsSchema( $controller->get_item_schema(), $response->get_data() );

	}

	/**
	 * Tests getting a quiz that has a question owned by current user.
	 */
	public function testGetCurrentTeachersQuestion() {
		$this->login_as_teacher();

		list( $lesson_id, $quiz_id ) = $this->create_lesson_with_quiz();

		$this->factory->question->create(
			[
				'quiz_id'                              => $quiz_id,
				'question_type'                        => 'single-line',
				'add_question_right_answer_singleline' => 'NOTES',
			]
		);

		$response_data = $this->send_get_request( $lesson_id );

		$this->assertEquals( 'single-line', $response_data['questions'][0]['type'] );
		$this->assertEquals( true, $response_data['questions'][0]['editable'] );
	}

	/**
	 * Tests getting a quiz that has a question owned by another user.
	 */
	public function testGetAnotherTeachersQuestion() {
		$this->login_as_teacher();

		list( $lesson_id, $quiz_id ) = $this->create_lesson_with_quiz();

		$this->login_as_teacher_b();
		$this->factory->question->create(
			[
				'quiz_id'                              => $quiz_id,
				'question_type'                        => 'single-line',
				'add_question_right_answer_singleline' => 'NOTES',
			]
		);

		$this->login_as_teacher();
		$response_data = $this->send_get_request( $lesson_id );

		$this->assertEquals( 'single-line', $response_data['questions'][0]['type'] );
		$this->assertEquals( false, $response_data['questions'][0]['editable'] );
	}

	/**
	 * Tests that a quiz is only accessed by admins and the teacher that created it.
	 */
	public function testGetAnotherTeachersCourse() {
		$this->login_as_teacher();

		list( $lesson_id ) = $this->create_lesson_with_quiz();

		$request  = new WP_REST_Request( 'GET', self::REST_ROUTE . $lesson_id );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status(), 'Teacher which created the quiz cannot access it.' );

		$this->login_as_teacher_b();

		$request  = new WP_REST_Request( 'GET', self::REST_ROUTE . $lesson_id );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 403, $response->get_status(), 'Teacher which did not create the quiz can access it.' );

		$this->login_as_admin();

		$request  = new WP_REST_Request( 'GET', self::REST_ROUTE . $lesson_id );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status(), 'Admin user cannot access the quiz.' );

		$this->logout();

		$request  = new WP_REST_Request( 'GET', self::REST_ROUTE . $lesson_id );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 401, $response->get_status(), 'Logged out user can access the quiz.' );
	}

	/**
	 * Tests that endpoint returns 404 when quiz doesn't exist.
	 */
	public function testGetMissingQuiz() {
		$this->login_as_admin();

		$request  = new WP_REST_Request( 'GET', self::REST_ROUTE . '1234' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 404, $response->get_status() );
	}

	/**
	 * Tests that quiz options are returned correctly.
	 */
	public function testGetQuizProperties() {
		$this->login_as_teacher();

		$quiz_args         = [
			'meta_input' => [
				'_enable_quiz_reset'     => 'on',
				'_random_question_order' => 'yes',
				'_pass_required'         => 'on',
				'_quiz_passmark'         => 10,
				'_quiz_grade_type'       => 'auto',
				'_show_questions'        => '',
			],
		];
		list( $lesson_id ) = $this->create_lesson_with_quiz( $quiz_args );

		$response_data = $this->send_get_request( $lesson_id );

		$this->assertTrue( $response_data['options']['pass_required'] );
		$this->assertTrue( $response_data['options']['auto_grade'] );
		$this->assertTrue( $response_data['options']['allow_retakes'] );
		$this->assertTrue( $response_data['options']['random_question_order'] );
		$this->assertEquals( 10, $response_data['options']['quiz_passmark'] );
		$this->assertNull( $response_data['options']['show_questions'] );

		$another_quiz_args = [
			'meta_input' => [
				'_enable_quiz_reset'     => '',
				'_random_question_order' => 'no',
				'_pass_required'         => '',
				'_quiz_passmark'         => 0,
				'_quiz_grade_type'       => 'manual',
				'_show_questions'        => 3,
			],
		];
		list( $lesson_id ) = $this->create_lesson_with_quiz( $another_quiz_args );

		$response_data = $this->send_get_request( $lesson_id );

		$this->assertFalse( $response_data['options']['pass_required'] );
		$this->assertFalse( $response_data['options']['auto_grade'] );
		$this->assertFalse( $response_data['options']['allow_retakes'] );
		$this->assertFalse( $response_data['options']['random_question_order'] );
		$this->assertEquals( 0, $response_data['options']['quiz_passmark'] );
		$this->assertEquals( 3, $response_data['options']['show_questions'] );
	}

	/**
	 * Tests category question properties.
	 */
	public function testGetCategoryQuestion() {
		$this->login_as_teacher();

		list( $lesson_id, $quiz_id ) = $this->create_lesson_with_quiz();
		$this->factory->multiple_question->create(
			[
				'quiz_id'         => $quiz_id,
				'question_number' => 2,
			]
		);

		$response_data = $this->send_get_request( $lesson_id );

		$this->assertEquals( 'category-question', $response_data['questions'][0]['type'] );
		$this->assertEquals( 2, $response_data['questions'][0]['options']['number'] );
		$this->assertArrayHasKey( 'id', $response_data['questions'][0] );
		$this->assertArrayHasKey( 'category', $response_data['questions'][0]['options'] );
	}

	/**
	 * Tests multiple choice question properties.
	 */
	public function testGetMultipleChoiceQuestion() {
		$this->login_as_teacher();

		list( $lesson_id, $quiz_id ) = $this->create_lesson_with_quiz();
		$this->factory->question->create(
			[
				'quiz_id'                => $quiz_id,
				'question_type'          => 'multiple-choice',
				'question_right_answers' => [ 'Right answer' ],
				'question_wrong_answers' => [ 'Wrong,comma', 'Wrong 1' ],
				'random_order'           => 'no',
				'answer_order'           => 'ac70b9a3f24b5b657826b567057169a2,b13d55d1ff11d676253fa5e4b0517bd7,89dc5589bfebac1468e8823afd5a4861',
				'answer_feedback'        => 'Some feedback',
			]
		);
		$response_data    = $this->send_get_request( $lesson_id );
		$response_answers = $response_data['questions'][0]['answer']['answers'];

		$this->assertFalse( $response_data['questions'][0]['options']['randomOrder'] );
		$this->assertEquals( 'Some feedback', $response_data['questions'][0]['options']['answerFeedback'] );
		$this->assertEquals( 'multiple-choice', $response_data['questions'][0]['type'] );
		$this->assertEquals( 'Wrong 1', $response_answers[0]['label'] );
		$this->assertFalse( $response_answers[0]['correct'] );
		$this->assertEquals( 'Right answer', $response_answers[2]['label'] );
		$this->assertTrue( $response_answers[2]['correct'] );
	}

	/**
	 * Tests true/false question properties.
	 */
	public function testGetBooleanQuestion() {
		$this->login_as_teacher();

		list( $lesson_id, $quiz_id ) = $this->create_lesson_with_quiz();
		$this->factory->question->create(
			[
				'quiz_id'                       => $quiz_id,
				'question_type'                 => 'boolean',
				'answer_feedback'               => 'Some feedback',
				'question_right_answer_boolean' => 'false',
			]
		);

		$response_data = $this->send_get_request( $lesson_id );

		$this->assertFalse( $response_data['questions'][0]['answer']['correct'] );
		$this->assertEquals( 'Some feedback', $response_data['questions'][0]['options']['answerFeedback'] );
		$this->assertEquals( 'boolean', $response_data['questions'][0]['type'] );
	}

	/**
	 * Tests gap fill question properties.
	 */
	public function testGetGapFillQuestion() {
		$this->login_as_teacher();

		list( $lesson_id, $quiz_id ) = $this->create_lesson_with_quiz();
		$this->factory->question->create(
			[
				'quiz_id'                                => $quiz_id,
				'question_type'                          => 'gap-fill',
				'add_question_right_answer_gapfill_pre'  => 'BEFORE',
				'add_question_right_answer_gapfill_gap'  => 'THE GAP|GAP',
				'add_question_right_answer_gapfill_post' => 'AFTER',
			]
		);

		$response_data = $this->send_get_request( $lesson_id );

		$this->assertEquals( 'gap-fill', $response_data['questions'][0]['type'] );
		$this->assertEquals( 'BEFORE', $response_data['questions'][0]['answer']['before'] );
		$this->assertEquals( 'THE GAP', $response_data['questions'][0]['answer']['gap'][0] );
		$this->assertEquals( 'GAP', $response_data['questions'][0]['answer']['gap'][1] );
		$this->assertEquals( 'AFTER', $response_data['questions'][0]['answer']['after'] );
	}

	/**
	 * Tests single line question properties.
	 */
	public function testGetSingleLineQuestion() {
		$this->login_as_teacher();

		list( $lesson_id, $quiz_id ) = $this->create_lesson_with_quiz();
		$this->factory->question->create(
			[
				'quiz_id'                              => $quiz_id,
				'question_type'                        => 'single-line',
				'add_question_right_answer_singleline' => 'NOTES',
			]
		);

		$response_data = $this->send_get_request( $lesson_id );

		$this->assertEquals( 'single-line', $response_data['questions'][0]['type'] );
		$this->assertEquals( 'NOTES', $response_data['questions'][0]['options']['teacherNotes'] );
	}

	/**
	 * Tests multiple line question properties.
	 */
	public function testGetMultilineQuestion() {
		$this->login_as_teacher();

		list( $lesson_id, $quiz_id ) = $this->create_lesson_with_quiz();
		$this->factory->question->create(
			[
				'quiz_id'                             => $quiz_id,
				'question_type'                       => 'multi-line',
				'add_question_right_answer_multiline' => 'NOTES',
			]
		);

		$response_data = $this->send_get_request( $lesson_id );

		$this->assertEquals( 'multi-line', $response_data['questions'][0]['type'] );
		$this->assertEquals( 'NOTES', $response_data['questions'][0]['options']['teacherNotes'] );
	}

	/**
	 * Tests file upload question properties.
	 */
	public function testGetFileUploadQuestion() {
		$this->login_as_teacher();

		list( $lesson_id, $quiz_id ) = $this->create_lesson_with_quiz();
		$this->factory->question->create(
			[
				'quiz_id'                              => $quiz_id,
				'question_type'                        => 'file-upload',
				'add_question_right_answer_fileupload' => 'Teacher note',
				'add_question_wrong_answer_fileupload' => 'User note',
			]
		);

		$response_data = $this->send_get_request( $lesson_id );

		$this->assertEquals( 'file-upload', $response_data['questions'][0]['type'] );
		$this->assertEquals( 'Teacher note', $response_data['questions'][0]['options']['teacherNotes'] );
		$this->assertEquals( 'User note', $response_data['questions'][0]['options']['studentHelp'] );
	}

	/**
	 * Tests single line question properties.
	 */
	public function testGetQuestionCommonProperties() {
		$this->login_as_teacher();

		list( $lesson_id, $quiz_id ) = $this->create_lesson_with_quiz();
		$question_id                 = $this->factory->question->create(
			[
				'quiz_id'              => $lesson_id,
				'question_type'        => 'single-line',
				'question'             => 'Will it blend?',
				'question_description' => 'That is the question.',
				'question_grade'       => 10,
			]
		);

		add_post_meta( $question_id, '_quiz_id', $quiz_id, false );

		$response_data = $this->send_get_request( $lesson_id );

		$this->assertTrue( $response_data['questions'][0]['shared'] );
		$this->assertEquals( 'Will it blend?', $response_data['questions'][0]['title'] );
		$this->assertEquals( 'That is the question.', $response_data['questions'][0]['description'] );
		$this->assertEquals( 10, $response_data['questions'][0]['options']['grade'] );
	}

	/**
	 * Tests that a simple post request works.
	 */
	public function testPostSimple() {
		$this->login_as_teacher();

		$lesson_id = $this->factory->lesson->create();

		$body = [
			'options'   => [
				'pass_required'         => true,
				'quiz_passmark'         => 10,
				'auto_grade'            => false,
				'allow_retakes'         => false,
				'show_questions'        => 3,
				'random_question_order' => true,
			],
			'questions' => [],
		];

		$this->send_post_request( $lesson_id, $body );
		$quiz_meta = get_post_meta( Sensei()->lesson->lesson_quizzes( $lesson_id ) );

		$this->assertEquals( 'on', $quiz_meta['_pass_required'][0] );
		$this->assertEquals( '10', $quiz_meta['_quiz_passmark'][0] );
		$this->assertEquals( 'manual', $quiz_meta['_quiz_grade_type'][0] );
		$this->assertEquals( '', $quiz_meta['_enable_quiz_reset'][0] );
		$this->assertEquals( '3', $quiz_meta['_show_questions'][0] );
		$this->assertEquals( 'yes', $quiz_meta['_random_question_order'][0] );
	}

	/**
	 * Tests that a quiz is only edited by admins and the teacher that created it.
	 */
	public function testPostAnotherTeachersCourse() {
		$this->login_as_teacher();

		list( $lesson_id ) = $this->create_lesson_with_quiz();

		$response = $this->send_post_request(
			$lesson_id,
			[
				'options'   => [],
				'questions' => [],
			]
		);

		$this->assertEquals( 200, $response->get_status(), 'Teacher which created the quiz cannot edit it.' );

		$this->login_as_teacher_b();

		$response = $this->send_post_request(
			$lesson_id,
			[
				'options'   => [],
				'questions' => [],
			]
		);

		$this->assertEquals( 403, $response->get_status(), 'Teacher which did not create the quiz can edit it.' );

		$this->login_as_admin();

		$response = $this->send_post_request(
			$lesson_id,
			[
				'options'   => [],
				'questions' => [],
			]
		);

		$this->assertEquals( 200, $response->get_status(), 'Admin user cannot edit the quiz.' );

		$this->logout();

		$response = $this->send_post_request(
			$lesson_id,
			[
				'options'   => [],
				'questions' => [],
			]
		);

		$this->assertEquals( 401, $response->get_status(), 'Logged out user can edit the quiz.' );
	}

	/**
	 * Tests editing category question properties.
	 */
	public function testPostCategoryQuestion() {
		$this->login_as_teacher();

		list( $lesson_id, $quiz_id ) = $this->create_lesson_with_quiz();
		$category_id                 = $this->factory->question_category->create();
		$question_id                 = $this->factory->question->create(
			[
				'question_type'  => 'multiple-choice',
				'taxonomy_input' => [
					'question-category' => [ $category_id ],
				],
			]
		);

		$multiple_question_id = $this->factory->multiple_question->create();

		$body = [
			'options'   => [],
			'questions' => [
				[
					'title'   => 'Will it blend?',
					'type'    => 'single-line',
					'options' => [
						'teacherNotes' => 'Do well',
					],
				],
				[
					'type'    => 'category-question',
					'options' => [
						'category' => $category_id,
						'number'   => 1,
					],
				],
				[
					'id'      => $multiple_question_id,
					'type'    => 'category-question',
					'options' => [
						'category' => (int) get_post_meta( $multiple_question_id, 'category', true ),
						'number'   => 2,
					],
				],
			],
		];

		$this->send_post_request( $lesson_id, $body );

		$questions = Sensei()->quiz->get_questions( Sensei()->lesson->lesson_quizzes( $lesson_id ) );

		$this->assertCount( 3, $questions );

		$this->assertEquals( 'question', $questions[0]->post_type );
		$this->assertEquals( 'multiple_question', $questions[1]->post_type );
		$this->assertEquals( 'multiple_question', $questions[2]->post_type );
		$this->assertEquals( $multiple_question_id, $questions[2]->ID );
		$this->assertEquals( 2, (int) get_post_meta( $multiple_question_id, 'number', true ) );
	}

	/**
	 * Tests editing multiple choice question properties.
	 */
	public function testPostMultipleChoiceQuestion() {
		$this->login_as_teacher();

		list( $lesson_id, $quiz_id ) = $this->create_lesson_with_quiz();
		$question_id                 = $this->factory->question->create(
			[
				'question_type' => 'multiple-choice',
				'quiz_id'       => $quiz_id,
			]
		);

		$body = [
			'options'   => [],
			'questions' => [
				[
					'title'       => 'Will it blend?',
					'description' => 'That is the question.',
					'type'        => 'multiple-choice',
					'answer'      => [
						'answers' => [
							[
								'label'   => 'Yes.',
								'correct' => false,
							],
							[
								'label'   => 'Definitely.',
								'correct' => true,
							],
						],
					],
					'options'     => [
						'grade'          => 30,
						'randomOrder'    => true,
						'answerFeedback' => 'Don\'t breathe this!',
					],
				],
				[
					'id'    => $question_id,
					'title' => 'Updated title',
					'type'  => 'multiple-choice',
				],
			],
		];

		$this->send_post_request( $lesson_id, $body );

		$questions = Sensei()->quiz->get_questions( Sensei()->lesson->lesson_quizzes( $lesson_id ) );

		$this->assertCount( 2, $questions );

		$this->assertEquals( 'Will it blend?', $questions[0]->post_title );
		$this->assertEquals( 'That is the question.', $questions[0]->post_content );

		$this->assertEquals( '30', get_post_meta( $questions[0]->ID, '_question_grade', true ) );
		$this->assertEquals( 'yes', get_post_meta( $questions[0]->ID, '_random_order', true ) );
		$this->assertEquals( 'Don\'t breathe this!', get_post_meta( $questions[0]->ID, '_answer_feedback', true ) );
		$this->assertEquals( 'Yes.', get_post_meta( $questions[0]->ID, '_question_wrong_answers', true )[0] );
		$this->assertEquals( 'Definitely.', get_post_meta( $questions[0]->ID, '_question_right_answer', true )[0] );

		$this->assertEquals( 'Updated title', $questions[1]->post_title );
		$this->assertEquals( '', $questions[1]->post_content );
	}

	/**
	 * Tests editing true/false question properties.
	 */
	public function testPostBooleanQuestion() {
		$this->login_as_teacher();

		$lesson_id = $this->factory->lesson->create();

		$body = [
			'options'   => [],
			'questions' => [
				[
					'title'   => 'Will it blend?',
					'type'    => 'boolean',
					'answer'  => [ 'correct' => true ],
					'options' => [
						'answerFeedback' => 'Don\'t breathe this!',
					],
				],
			],
		];

		$this->send_post_request( $lesson_id, $body );

		$questions = Sensei()->quiz->get_questions( Sensei()->lesson->lesson_quizzes( $lesson_id ) );

		$this->assertCount( 1, $questions );

		$this->assertEquals( 'Will it blend?', $questions[0]->post_title );
		$this->assertEquals( 'true', get_post_meta( $questions[0]->ID, '_question_right_answer', true ) );
		$this->assertEquals( 'Don\'t breathe this!', get_post_meta( $questions[0]->ID, '_answer_feedback', true ) );
	}

	/**
	 * Tests editing gap fill question properties.
	 */
	public function testPostGapFillQuestion() {
		$this->login_as_teacher();

		$lesson_id = $this->factory->lesson->create();

		$body = [
			'options'   => [],
			'questions' => [
				[
					'title'  => 'Will it blend?',
					'type'   => 'gap-fill',
					'answer' => [
						'before' => 'Yes ',
						'gap'    => [
							'it',
							'he',
							'she',
						],
						'after'  => ' blends.',
					],
				],
			],
		];

		$this->send_post_request( $lesson_id, $body );

		$questions = Sensei()->quiz->get_questions( Sensei()->lesson->lesson_quizzes( $lesson_id ) );

		$this->assertCount( 1, $questions );

		$this->assertEquals( 'Will it blend?', $questions[0]->post_title );
		$this->assertEquals( 'Yes ||it|he|she|| blends.', get_post_meta( $questions[0]->ID, '_question_right_answer', true ) );
	}

	/**
	 * Tests editing single line and multiline question properties.
	 */
	public function testPostLineQuestion() {
		$this->login_as_teacher();

		$lesson_id = $this->factory->lesson->create();

		$body = [
			'options'   => [],
			'questions' => [
				[
					'title'   => 'Will it blend?',
					'type'    => 'single-line',
					'options' => [
						'teacherNotes' => 'Don\'t breathe this!',
					],
				],
				[
					'title'   => 'Please explain.',
					'type'    => 'multi-line',
					'options' => [
						'teacherNotes' => 'Teacher notes',
					],
				],
			],
		];

		$this->send_post_request( $lesson_id, $body );

		$questions = Sensei()->quiz->get_questions( Sensei()->lesson->lesson_quizzes( $lesson_id ) );

		$this->assertCount( 2, $questions );

		$this->assertEquals( 'Will it blend?', $questions[0]->post_title );
		$this->assertEquals( 'Don\'t breathe this!', get_post_meta( $questions[0]->ID, '_question_right_answer', true ) );
		$this->assertEquals( 'Please explain.', $questions[1]->post_title );
		$this->assertEquals( 'Teacher notes', get_post_meta( $questions[1]->ID, '_question_right_answer', true ) );
	}

	/**
	 * Tests editing file upload question properties.
	 */
	public function testPostFileUploadQuestion() {
		$this->login_as_teacher();

		$lesson_id = $this->factory->lesson->create();

		$body = [
			'options'   => [],
			'questions' => [
				[
					'title'   => 'Will it blend?',
					'type'    => 'file-upload',
					'options' => [
						'teacherNotes' => 'Teacher notes',
						'studentHelp'  => 'Upload instructions',
					],

				],
			],
		];

		$this->send_post_request( $lesson_id, $body );

		$questions = Sensei()->quiz->get_questions( Sensei()->lesson->lesson_quizzes( $lesson_id ) );

		$this->assertCount( 1, $questions );

		$this->assertEquals( 'Will it blend?', $questions[0]->post_title );
		$this->assertEquals( 'Teacher notes', get_post_meta( $questions[0]->ID, '_question_right_answer', true ) );
		$this->assertEquals( 'Upload instructions', get_post_meta( $questions[0]->ID, '_question_wrong_answers', true ) );
	}

	/**
	 * Tests updating quiz pagination settings.
	 */
	public function testUpdatingPaginationSettings() {
		$this->login_as_teacher();

		$lesson_id = $this->factory->lesson->create();

		$body = [
			'options'    => [],
			'questions'  => [],
			'pagination' => [
				'pagination_number'       => 3,
				'show_progress_bar'       => true,
				'progress_bar_radius'     => 10,
				'progress_bar_height'     => 10,
				'progress_bar_color'      => '#ffffff',
				'progress_bar_background' => '#eeeeee',
			],
		];

		$this->send_post_request( $lesson_id, $body );

		$pagination_meta = get_post_meta( Sensei()->lesson->lesson_quizzes( $lesson_id ), '_pagination', true );
		$json_array      = json_decode( $pagination_meta, true );

		$this->assertEquals( 3, $json_array['pagination_number'] );
		$this->assertEquals( true, $json_array['show_progress_bar'] );
		$this->assertEquals( 10, $json_array['progress_bar_radius'] );
		$this->assertEquals( 10, $json_array['progress_bar_height'] );
		$this->assertEquals( '#ffffff', $json_array['progress_bar_color'] );
		$this->assertEquals( '#eeeeee', $json_array['progress_bar_background'] );
	}

	/**
	 * Tests default values for quiz pagination settings.
	 */
	public function testPaginationSettingsDefaultValues() {
		$this->login_as_teacher();

		list( $lesson_id ) = $this->create_lesson_with_quiz();

		$response_data       = $this->send_get_request( $lesson_id );
		$pagination_settings = $response_data['pagination'];

		$this->assertNull( $pagination_settings['pagination_number'] );
		$this->assertFalse( $pagination_settings['show_progress_bar'] );
		$this->assertEquals( 5, $pagination_settings['progress_bar_radius'] );
		$this->assertEquals( 5, $pagination_settings['progress_bar_height'] );
		$this->assertNull( $pagination_settings['progress_bar_color'] );
		$this->assertNull( $pagination_settings['progress_bar_background'] );
	}

	/**
	 * Tests that invalid question data are validated.
	 */
	public function testQuestionValidationFails() {
		$this->login_as_teacher();

		$lesson_id = $this->factory->lesson->create();

		$body = [
			'options'   => [],
			'questions' => [
				[
					'title'   => 'Will it blend?',
					'type'    => 'boolean',
					'answer'  => 'A string',
					'options' => [
						'teacherNotes' => 'Don\'t breathe this!',
					],
				],
			],
		];

		$response = $this->send_post_request( $lesson_id, $body );

		$this->assertEquals( $response->get_status(), 400 );
	}

	/**
	 * Helper method to send and validate a GET request.
	 *
	 * @param int $lesson_id The lesson id.
	 *
	 * @return array Response data.
	 */
	private function send_get_request( int $lesson_id ): array {
		$request  = new WP_REST_Request( 'GET', self::REST_ROUTE . $lesson_id );
		$response = $this->server->dispatch( $request );

		return $response->get_data();
	}

	/**
	 * Helper method to send and validate a POST request.
	 *
	 * @param int   $lesson_id The lesson id.
	 * @param array $body      JSON body arguments.
	 *
	 * @return WP_REST_Response Response.
	 */
	private function send_post_request( int $lesson_id, array $body ): WP_REST_Response {
		$request = new WP_REST_Request( 'POST', self::REST_ROUTE . $lesson_id );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body( wp_json_encode( $body ) );

		return $this->server->dispatch( $request );
	}

	/**
	 * Helper method to create a lesson with a quiz.
	 *
	 * @param array $quiz_args The quiz args.
	 *
	 * @return array The lesson and quiz id.
	 */
	private function create_lesson_with_quiz( array $quiz_args = [] ): array {
		$lesson_id = $this->factory->lesson->create();
		$quiz_id   = $this->factory->quiz->create( array_merge( [ 'post_parent' => $lesson_id ], $quiz_args ) );

		return [ $lesson_id, $quiz_id ];
	}
}
