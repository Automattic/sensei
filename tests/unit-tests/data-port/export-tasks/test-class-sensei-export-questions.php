<?php
/**
 * This file contains the Sensei_Export_Questions_Tests class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tests for Sensei_Export_Questions class.
 *
 * @group data-port
 */
class Sensei_Export_Questions_Tests extends WP_UnitTestCase {

	/**
	 * Factory helper.
	 *
	 * @var Sensei_Factory
	 */
	protected $factory;

	/**
	 * Setup function.
	 */
	public function setUp() {
		parent::setUp();

		$this->factory = new Sensei_Factory();
	}


	public function testQuestionDataExported() {

		$this->factory->question->create(
			[
				'quiz_id'                              => null,
				'question_type'                        => 'single-line',
				'question'                             => 'Question Title',
				'question_description'                 => 'Question Description',
				'question_grade'                       => '2',
				'answer_feedback'                      => 'Feedback',
				'add_question_right_answer_singleline' => 'ANSWER',
			]
		);

		$result = $this->export();

		$this->assertArraySubset(
			[
				'question'        => 'Question Title',
				'slug'            => 'question-title',
				'description'     => 'Question Description',
				'status'          => 'publish',
				'type'            => 'single-line',
				'grade'           => '2',
				'categories'      => '',
				'answer'          => 'ANSWER',
				'feedback'        => 'Feedback',
				'text before gap' => '',
				'gap'             => '',
				'text after gap'  => '',
				'upload notes'    => '',
				'teacher notes'   => '',
			],
			$result[0]
		);

	}

	public function testQuestionCategoriesExported() {
		$terms = [
			$this->factory->term->create(
				[
					'taxonomy' => 'question-category',
					'name'     => 'Test',
					'parent'   => $this->factory->term->create(
						[
							'taxonomy' => 'question-category',
							'name'     => 'Group',
						]
					),
				]
			),
			$this->factory->term->create(
				[
					'taxonomy' => 'question-category',
					'name'     => 'Single',
				]
			),
		];

		$question_id = $this->factory->question->create(
			[
				'quiz_id'       => null,
				'question_type' => 'single-line',
			]
		);

		$this->factory->term->add_post_terms( $question_id, $terms, 'question-category', false );
		$result = $this->export();

		$this->assertEquals(
			'Single,Group > Test',
			$result[0]['categories']
		);
	}

	public function testQuestionMediaExported() {

		$image_id = $this->factory->attachment->create(
			[
				'file'           => 'question-img.jpg',
				'post_mime_type' => 'image/jpg',
			]
		);
		$this->factory->question->create(
			[
				'quiz_id'        => null,
				'question_type'  => 'single-line',
				'question_media' => $image_id,
			]
		);

		$result = $this->export();

		$this->assertArraySubset(
			[
				'media' => 'http://example.org/wp-content/uploads/question-img.jpg',
			],
			$result[0]
		);
	}

	public function testMultipleChoiceAnswers() {
		$this->factory->question->create(
			[
				'quiz_id'                => null,
				'question_type'          => 'multiple-choice',
				'question_right_answers' => [ 'Right answer' ],
				'question_wrong_answers' => [ 'Wrong 1', 'Wrong,comma', 'Wrong,comma,"quote"' ],
				'random answer order'    => '1',
			]
		);

		$result = $this->export();

		$this->assertArraySubset(
			[
				'answer' => 'Right:Right answer,Wrong:Wrong 1,"Wrong:Wrong,comma","Wrong:Wrong,comma,\"quote\""',
			],
			$result[0]
		);
	}

	public function testBooleanAnswers() {
		$this->factory->question->create(
			[
				'quiz_id'       => null,
				'question_type' => 'boolean',
			]
		);

		$result = $this->export();

		$this->assertArraySubset(
			[ 'answer' => 'true' ],
			$result[0]
		);
	}

	public function testFileUploadAnswers() {
		$this->factory->question->create(
			[
				'quiz_id'                              => null,
				'question_type'                        => 'file-upload',
				'add_question_right_answer_fileupload' => 'Teacher note',
				'add_question_wrong_answer_fileupload' => 'User note',
			]
		);

		$result = $this->export();

		$this->assertArraySubset(
			[
				'upload notes'  => 'User note',
				'teacher notes' => 'Teacher note',
			],
			$result[0]
		);
	}

	public function testGapFillAnswers() {
		$this->factory->question->create(
			[
				'quiz_id'                                => null,
				'question_type'                          => 'gap-fill',
				'add_question_right_answer_gapfill_pre'  => 'BEFORE',
				'add_question_right_answer_gapfill_gap'  => 'THE GAP',
				'add_question_right_answer_gapfill_post' => 'AFTER',
			]
		);

		$result = $this->export();

		$this->assertArraySubset(
			[
				'text before gap' => 'BEFORE',
				'gap'             => 'THE GAP',
				'text after gap'  => 'AFTER',
			],
			$result[0]
		);
	}

	public function testSingleLineAnswers() {
		$this->factory->question->create(
			[
				'quiz_id'                              => null,
				'question_type'                        => 'single-line',
				'add_question_right_answer_singleline' => 'ANSWER',
			]
		);

		$result = $this->export();

		$this->assertArraySubset(
			[
				'answer' => 'ANSWER',
			],
			$result[0]
		);
	}

	public function testMultilineAnswers() {
		$this->factory->question->create(
			[
				'quiz_id'                             => null,
				'question_type'                       => 'multi-line',
				'add_question_right_answer_multiline' => 'NOTES',
			]
		);

		$result = $this->export();

		$this->assertArraySubset(
			[
				'teacher notes' => 'NOTES',
			],
			$result[0]
		);
	}

	/**
	 * Find a question line by ID.
	 *
	 * @param array $result      Result data.
	 * @param int   $question_id The question id.
	 *
	 * @return array The line for the question.
	 */
	protected static function get_by_id( array $result, $question_id ) {
		$key = array_search( strval( $question_id ), array_column( $result, 'id' ), true );
		return $result[ $key ];
	}

	/**
	 * Run the export job and read back the created CSV.
	 *
	 * @return array The exported data as read from the CSV file.
	 */
	public function export() {
		$job  = Sensei_Export_Job::create( 'test', 0 );
		$task = new Sensei_Export_Questions( $job );
		$task->run();

		return self::read_csv( $job->get_file_path( 'question' ) );
	}

	protected static function read_csv( $filename ) {
		$reader = new Sensei_Import_CSV_Reader( $filename, 0, 1000 );
		return $reader->read_lines();
	}
}
