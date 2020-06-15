<?php
/**
 * This file contains the Sensei_Data_Port_Question_Model_Test class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tests for Sensei_Data_Port_Question_Model class.
 *
 * @group data-port
 */
class Sensei_Data_Port_Question_Model_Test extends WP_UnitTestCase {
	/**
	 * Sensei factory object.
	 *
	 * @var Sensei_Factory
	 */
	private $factory;

	/**
	 * Setup function.
	 */
	public function setUp() {
		parent::setUp();

		$this->factory = new Sensei_Factory();
	}

	/**
	 * Check to make sure an existing post with a matching slug is matched up with the model.
	 */
	public function testExistingPostFound() {
		$data = [
			Sensei_Data_Port_Question_Model::COLUMN_QUESTION => 'Do you like dinosaurs?',
			Sensei_Data_Port_Question_Model::COLUMN_ANSWER => 'Right:Yes, Wrong: No',
			Sensei_Data_Port_Question_Model::COLUMN_SLUG   => 'do-you-like-dinosaurs',
		];

		$post_id = $this->factory->post->create(
			[
				'post_type' => 'question',
				'post_name' => $data[ Sensei_Data_Port_Question_Model::COLUMN_SLUG ],
			]
		);

		$model = Sensei_Data_Port_Question_Model::from_source_array( $data );

		$this->assertEquals( $post_id, $model->get_post_id() );
	}

	/**
	 * Check to make sure an existing post with a similar slug is not matched up with the model.
	 */
	public function testCloseExistingPostNotFound() {
		$data    = [
			Sensei_Data_Port_Question_Model::COLUMN_QUESTION => 'Do you like dinosaurs?',
			Sensei_Data_Port_Question_Model::COLUMN_ANSWER => 'Right:Yes, Wrong: No',
			Sensei_Data_Port_Question_Model::COLUMN_SLUG   => 'do-you-like-dinosaurs',
		];
		$post_id = $this->factory->post->create(
			[
				'post_type' => 'question',
				'post_name' => $data[ Sensei_Data_Port_Question_Model::COLUMN_SLUG ] . '-2',
			]
		);

		$model = Sensei_Data_Port_Question_Model::from_source_array( $data );

		$this->assertEquals( null, $model->get_post_id() );
	}

	/**
	 * Returns an array with the data used by the tests. Each element is an array of line input data and expected
	 * output following the format of Sensei_Data_Port_Question_Model::data.
	 *
	 * The first and second elements of the array, refer to the same course and are used in the test scenario which
	 * creates a post and then updates it.
	 */
	public function lineData() {
		return [
			[
				[
					Sensei_Data_Port_Question_Model::COLUMN_ID              => '<strong>1234</strong>',
					Sensei_Data_Port_Question_Model::COLUMN_QUESTION        => 'Do you like dogs? <script>alert("Uhoh");</script>',
					Sensei_Data_Port_Question_Model::COLUMN_ANSWER          => 'Right:Yes, Wrong:No',
					Sensei_Data_Port_Question_Model::COLUMN_SLUG            => ' do-you-like-dogs  ',
					Sensei_Data_Port_Question_Model::COLUMN_DESCRIPTION     => '  This is a really great question.   ',
					Sensei_Data_Port_Question_Model::COLUMN_STATUS          => 'publish',
					Sensei_Data_Port_Question_Model::COLUMN_TYPE            => 'multiple-choice',
					Sensei_Data_Port_Question_Model::COLUMN_GRADE           => ' 22',
					Sensei_Data_Port_Question_Model::COLUMN_RANDOMISE       => '1',
					Sensei_Data_Port_Question_Model::COLUMN_MEDIA           => 'https://example.com/test.jpg',
					Sensei_Data_Port_Question_Model::COLUMN_CATEGORIES      => ' <strong>Test</strong>, Long > Category ',
					Sensei_Data_Port_Question_Model::COLUMN_FEEDBACK        => ' I <strong>hope</strong> they did well.',
					Sensei_Data_Port_Question_Model::COLUMN_TEXT_BEFORE_GAP => '<random>Before gap</random>',
					Sensei_Data_Port_Question_Model::COLUMN_GAP             => '<media>during gap</media>',
					Sensei_Data_Port_Question_Model::COLUMN_TEXT_AFTER_GAP  => '<after>   <strong>after gap</strong> </after>',
					Sensei_Data_Port_Question_Model::COLUMN_UPLOAD_NOTES    => ' This is an upload <strong>note</strong>.    ',
					Sensei_Data_Port_Question_Model::COLUMN_TEACHER_NOTES   => '<bingo>Bad comment</bingo>',
				],
				[
					Sensei_Data_Port_Question_Model::COLUMN_ID              => '1234',
					Sensei_Data_Port_Question_Model::COLUMN_QUESTION        => 'Do you like dogs? alert("Uhoh");',
					Sensei_Data_Port_Question_Model::COLUMN_ANSWER          => 'Right:Yes, Wrong:No',
					Sensei_Data_Port_Question_Model::COLUMN_SLUG            => 'do-you-like-dogs',
					Sensei_Data_Port_Question_Model::COLUMN_DESCRIPTION     => 'This is a really great question.',
					Sensei_Data_Port_Question_Model::COLUMN_STATUS          => 'publish',
					Sensei_Data_Port_Question_Model::COLUMN_TYPE            => 'multiple-choice',
					Sensei_Data_Port_Question_Model::COLUMN_GRADE           => '22',
					Sensei_Data_Port_Question_Model::COLUMN_RANDOMISE       => '1',
					Sensei_Data_Port_Question_Model::COLUMN_MEDIA           => 'https://example.com/test.jpg',
					Sensei_Data_Port_Question_Model::COLUMN_CATEGORIES      => 'Test, Long > Category',
					Sensei_Data_Port_Question_Model::COLUMN_FEEDBACK        => 'I <strong>hope</strong> they did well.',
					Sensei_Data_Port_Question_Model::COLUMN_TEXT_BEFORE_GAP => 'Before gap',
					Sensei_Data_Port_Question_Model::COLUMN_GAP             => 'during gap',
					Sensei_Data_Port_Question_Model::COLUMN_TEXT_AFTER_GAP  => '<strong>after gap</strong>',
					Sensei_Data_Port_Question_Model::COLUMN_UPLOAD_NOTES    => 'This is an upload <strong>note</strong>.',
					Sensei_Data_Port_Question_Model::COLUMN_TEACHER_NOTES   => 'Bad comment',
				],
				true,
			],
			[
				[
					Sensei_Data_Port_Question_Model::COLUMN_ID              => '<strong>1234</strong>',
					Sensei_Data_Port_Question_Model::COLUMN_ANSWER          => '',
					Sensei_Data_Port_Question_Model::COLUMN_TYPE            => 'multiple-choice',
				],
				[
					Sensei_Data_Port_Question_Model::COLUMN_ID              => '1234',
					Sensei_Data_Port_Question_Model::COLUMN_ANSWER          => '',
					Sensei_Data_Port_Question_Model::COLUMN_TYPE            => 'multiple-choice',
				],
				false,
			],
		];
	}
	/**
	 * Make sure that input coming from the CSV file is sanitized properly.
	 *
	 * @dataProvider lineData
	 */
	public function testInputIsSanitized( $input_line, $expected_model_content, $is_valid ) {
		$model         = Sensei_Data_Port_Question_Model::from_source_array( $input_line, 1 );
		$tested_fields = [
			Sensei_Data_Port_Question_Model::COLUMN_QUESTION,
			Sensei_Data_Port_Question_Model::COLUMN_ANSWER,
			Sensei_Data_Port_Question_Model::COLUMN_ID,
			Sensei_Data_Port_Question_Model::COLUMN_SLUG,
			Sensei_Data_Port_Question_Model::COLUMN_DESCRIPTION,
			Sensei_Data_Port_Question_Model::COLUMN_STATUS,
			Sensei_Data_Port_Question_Model::COLUMN_TYPE,
			Sensei_Data_Port_Question_Model::COLUMN_GRADE,
			Sensei_Data_Port_Question_Model::COLUMN_RANDOMISE,
			Sensei_Data_Port_Question_Model::COLUMN_MEDIA,
			Sensei_Data_Port_Question_Model::COLUMN_CATEGORIES,
			Sensei_Data_Port_Question_Model::COLUMN_FEEDBACK,
			Sensei_Data_Port_Question_Model::COLUMN_TEXT_BEFORE_GAP,
			Sensei_Data_Port_Question_Model::COLUMN_GAP,
			Sensei_Data_Port_Question_Model::COLUMN_TEXT_AFTER_GAP,
			Sensei_Data_Port_Question_Model::COLUMN_UPLOAD_NOTES,
			Sensei_Data_Port_Question_Model::COLUMN_TEACHER_NOTES,
		];

		$this->assertEquals( $is_valid, $model->is_valid(), 'Model valid status did not match' );

		foreach ( $tested_fields as $tested_field ) {
			if ( isset( $expected_model_content[ $tested_field ] ) ) {
				$this->assertEquals( $expected_model_content[ $tested_field ], $model->get_value( $tested_field ) );
			}
		}
	}

}
