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

	use Sensei_Export_Task_Tests;

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

	public function testQuestionImageMediaExported() {

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

	public function testQuestionAudioMediaExported() {

		$image_id = $this->factory->attachment->create(
			[
				'file'           => 'question-sound.mp3',
				'post_mime_type' => 'audio/mp3',
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
				'media' => 'http://example.org/wp-content/uploads/question-sound.mp3',
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
				'random_order'           => 'no',
			]
		);

		$result = $this->export();

		$this->assertArraySubset(
			[
				'answer'              => 'Right:Right answer,Wrong:Wrong 1,"Wrong:Wrong,comma","Wrong:Wrong,comma,\"quote\""',
				'random answer order' => '',
			],
			$result[0]
		);
	}

	public function testMultipleChoiceRandomOrder() {
		$this->factory->question->create(
			[
				'quiz_id'       => null,
				'question_type' => 'multiple-choice',
				'random_order'  => 'yes',
			]
		);

		$result = $this->export();

		$this->assertArraySubset(
			[
				'random answer order' => '1',
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

	protected function get_task_class() {
		return Sensei_Export_Questions::class;
	}
}
