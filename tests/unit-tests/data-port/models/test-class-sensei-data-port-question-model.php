<?php
/**
 * This file contains the Sensei_Import_Question_Model_Test class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tests for Sensei_Import_Question_Model class.
 *
 * @group data-port
 */
class Sensei_Import_Question_Model_Test extends WP_UnitTestCase {
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
			Sensei_Data_Port_Question_Schema::COLUMN_TITLE => 'Do you like dinosaurs?',
			Sensei_Data_Port_Question_Schema::COLUMN_ANSWER => 'Right:Yes, Wrong: No',
			Sensei_Data_Port_Question_Schema::COLUMN_SLUG  => 'do-you-like-dinosaurs',
		];

		$post_id = $this->factory->post->create(
			[
				'post_type' => 'question',
				'post_name' => $data[ Sensei_Data_Port_Question_Schema::COLUMN_SLUG ],
			]
		);

		$model = Sensei_Import_Question_Model::from_source_array( 1, $data, new Sensei_Data_Port_Question_Schema() );

		$this->assertEquals( $post_id, $model->get_post_id() );
	}

	/**
	 * Check to make sure an existing post with a similar slug is not matched up with the model.
	 */
	public function testCloseExistingPostNotFound() {
		$data    = [
			Sensei_Data_Port_Question_Schema::COLUMN_TITLE => 'Do you like dinosaurs?',
			Sensei_Data_Port_Question_Schema::COLUMN_ANSWER => 'Right:Yes, Wrong: No',
			Sensei_Data_Port_Question_Schema::COLUMN_SLUG  => 'do-you-like-dinosaurs',
		];
		$post_id = $this->factory->post->create(
			[
				'post_type' => 'question',
				'post_name' => $data[ Sensei_Data_Port_Question_Schema::COLUMN_SLUG ] . '-2',
			]
		);

		$model = Sensei_Import_Question_Model::from_source_array( 1, $data, new Sensei_Data_Port_Question_Schema() );

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
			'full'                            => [
				[
					Sensei_Data_Port_Question_Schema::COLUMN_ID              => '<strong>1234</strong>',
					Sensei_Data_Port_Question_Schema::COLUMN_TITLE           => 'Do you like dogs? <script>alert("Uhoh");</script>',
					Sensei_Data_Port_Question_Schema::COLUMN_ANSWER          => 'Wrong:No, Right:Yes, Wrong:"Maybe, it depends"',
					Sensei_Data_Port_Question_Schema::COLUMN_SLUG            => ' do-you-like-dogs  ',
					Sensei_Data_Port_Question_Schema::COLUMN_DESCRIPTION     => '  This is a really great question.   ',
					Sensei_Data_Port_Question_Schema::COLUMN_STATUS          => 'publish',
					Sensei_Data_Port_Question_Schema::COLUMN_TYPE            => 'multiple-choice',
					Sensei_Data_Port_Question_Schema::COLUMN_GRADE           => ' 22',
					Sensei_Data_Port_Question_Schema::COLUMN_RANDOM_ORDER    => '1',
					Sensei_Data_Port_Question_Schema::COLUMN_MEDIA           => '',
					Sensei_Data_Port_Question_Schema::COLUMN_CATEGORIES      => ' <strong>Test</strong>, Long > Category ',
					Sensei_Data_Port_Question_Schema::COLUMN_FEEDBACK        => ' I <strong>hope</strong> they did well.',
					Sensei_Data_Port_Question_Schema::COLUMN_TEXT_BEFORE_GAP => '<random>Before gap</random>',
					Sensei_Data_Port_Question_Schema::COLUMN_GAP             => '<media>during gap</media>',
					Sensei_Data_Port_Question_Schema::COLUMN_TEXT_AFTER_GAP  => '<after>   <strong>after gap</strong> </after>',
					Sensei_Data_Port_Question_Schema::COLUMN_UPLOAD_NOTES    => ' This is an upload <strong>note</strong>.    ',
					Sensei_Data_Port_Question_Schema::COLUMN_TEACHER_NOTES   => '<bingo>Bad comment</bingo>',
				],
				[
					Sensei_Data_Port_Question_Schema::COLUMN_ID              => '1234',
					Sensei_Data_Port_Question_Schema::COLUMN_TITLE           => 'Do you like dogs? alert("Uhoh");',
					Sensei_Data_Port_Question_Schema::COLUMN_ANSWER          => 'Wrong:No, Right:Yes, Wrong:"Maybe, it depends"',
					Sensei_Data_Port_Question_Schema::COLUMN_SLUG            => 'do-you-like-dogs',
					Sensei_Data_Port_Question_Schema::COLUMN_DESCRIPTION     => 'This is a really great question.',
					Sensei_Data_Port_Question_Schema::COLUMN_STATUS          => 'publish',
					Sensei_Data_Port_Question_Schema::COLUMN_TYPE            => 'multiple-choice',
					Sensei_Data_Port_Question_Schema::COLUMN_GRADE           => '22',
					Sensei_Data_Port_Question_Schema::COLUMN_RANDOM_ORDER    => '1',
					Sensei_Data_Port_Question_Schema::COLUMN_MEDIA           => '',
					Sensei_Data_Port_Question_Schema::COLUMN_CATEGORIES      => 'Test, Long > Category',
					Sensei_Data_Port_Question_Schema::COLUMN_FEEDBACK        => 'I <strong>hope</strong> they did well.',
					Sensei_Data_Port_Question_Schema::COLUMN_TEXT_BEFORE_GAP => 'Before gap',
					Sensei_Data_Port_Question_Schema::COLUMN_GAP             => 'during gap',
					Sensei_Data_Port_Question_Schema::COLUMN_TEXT_AFTER_GAP  => '<strong>after gap</strong>',
					Sensei_Data_Port_Question_Schema::COLUMN_UPLOAD_NOTES    => 'This is an upload <strong>note</strong>.',
					Sensei_Data_Port_Question_Schema::COLUMN_TEACHER_NOTES   => 'Bad comment',
				],
				true,
			],
			'invalid-partial-multiple-choice' => [
				[
					Sensei_Data_Port_Question_Schema::COLUMN_ID     => '<strong>1234</strong>',
					Sensei_Data_Port_Question_Schema::COLUMN_ANSWER => '',
					Sensei_Data_Port_Question_Schema::COLUMN_TYPE   => 'multiple-choice',
				],
				[
					Sensei_Data_Port_Question_Schema::COLUMN_ID     => '1234',
					Sensei_Data_Port_Question_Schema::COLUMN_ANSWER => '',
					Sensei_Data_Port_Question_Schema::COLUMN_TYPE   => 'multiple-choice',
				],
				false,
			],
			'valid-gap-fill'                  => [
				[
					Sensei_Data_Port_Question_Schema::COLUMN_ID              => '<strong>1234</strong>',
					Sensei_Data_Port_Question_Schema::COLUMN_TITLE           => 'Simple gap fill',
					Sensei_Data_Port_Question_Schema::COLUMN_ANSWER          => '',
					Sensei_Data_Port_Question_Schema::COLUMN_STATUS          => 'publish',
					Sensei_Data_Port_Question_Schema::COLUMN_TYPE            => 'gap-fill',
					Sensei_Data_Port_Question_Schema::COLUMN_TEXT_BEFORE_GAP => '<random>Before gap</random>',
					Sensei_Data_Port_Question_Schema::COLUMN_GAP             => '<media>during gap</media>',
					Sensei_Data_Port_Question_Schema::COLUMN_TEXT_AFTER_GAP  => '<after>   <strong>after gap</strong> </after>',
				],
				[
					Sensei_Data_Port_Question_Schema::COLUMN_ID              => '1234',
					Sensei_Data_Port_Question_Schema::COLUMN_TITLE           => 'Simple gap fill',
					Sensei_Data_Port_Question_Schema::COLUMN_ANSWER          => '',
					Sensei_Data_Port_Question_Schema::COLUMN_STATUS          => 'publish',
					Sensei_Data_Port_Question_Schema::COLUMN_TYPE            => 'gap-fill',
					Sensei_Data_Port_Question_Schema::COLUMN_TEXT_BEFORE_GAP => 'Before gap',
					Sensei_Data_Port_Question_Schema::COLUMN_GAP             => 'during gap',
					Sensei_Data_Port_Question_Schema::COLUMN_TEXT_AFTER_GAP  => '<strong>after gap</strong>',
				],
				true,
			],
			'valid-file-upload'               => [
				[
					Sensei_Data_Port_Question_Schema::COLUMN_ID            => '12345',
					Sensei_Data_Port_Question_Schema::COLUMN_TITLE         => 'File upload',
					Sensei_Data_Port_Question_Schema::COLUMN_ANSWER        => '',
					Sensei_Data_Port_Question_Schema::COLUMN_STATUS        => 'draft',
					Sensei_Data_Port_Question_Schema::COLUMN_TYPE          => 'file-upload',
					Sensei_Data_Port_Question_Schema::COLUMN_UPLOAD_NOTES  => '<random>Upload notes</random>',
					Sensei_Data_Port_Question_Schema::COLUMN_TEACHER_NOTES => '<media>Teacher notes</media>',
				],
				[
					Sensei_Data_Port_Question_Schema::COLUMN_ID            => '12345',
					Sensei_Data_Port_Question_Schema::COLUMN_TITLE         => 'File upload',
					Sensei_Data_Port_Question_Schema::COLUMN_ANSWER        => '',
					Sensei_Data_Port_Question_Schema::COLUMN_STATUS        => 'draft',
					Sensei_Data_Port_Question_Schema::COLUMN_TYPE          => 'file-upload',
					Sensei_Data_Port_Question_Schema::COLUMN_UPLOAD_NOTES  => 'Upload notes',
					Sensei_Data_Port_Question_Schema::COLUMN_TEACHER_NOTES => 'Teacher notes',
				],
				true,
			],
			'valid-boolean'                   => [
				[
					Sensei_Data_Port_Question_Schema::COLUMN_ID     => '12345',
					Sensei_Data_Port_Question_Schema::COLUMN_TITLE  => 'Boolean question',
					Sensei_Data_Port_Question_Schema::COLUMN_ANSWER => '1',
					Sensei_Data_Port_Question_Schema::COLUMN_STATUS => 'draft',
					Sensei_Data_Port_Question_Schema::COLUMN_TYPE   => 'boolean',
				],
				[
					Sensei_Data_Port_Question_Schema::COLUMN_ID     => '12345',
					Sensei_Data_Port_Question_Schema::COLUMN_TITLE  => 'Boolean question',
					Sensei_Data_Port_Question_Schema::COLUMN_ANSWER => '1',
					Sensei_Data_Port_Question_Schema::COLUMN_STATUS => 'draft',
					Sensei_Data_Port_Question_Schema::COLUMN_TYPE   => 'boolean',
				],
				true,
			],
			'valid-single-line'               => [
				[
					Sensei_Data_Port_Question_Schema::COLUMN_ID     => '12345',
					Sensei_Data_Port_Question_Schema::COLUMN_TITLE  => 'Single line question',
					Sensei_Data_Port_Question_Schema::COLUMN_ANSWER => 'Fill the answer below',
					Sensei_Data_Port_Question_Schema::COLUMN_STATUS => 'draft',
					Sensei_Data_Port_Question_Schema::COLUMN_TYPE   => 'single-line',
				],
				[
					Sensei_Data_Port_Question_Schema::COLUMN_ID     => '12345',
					Sensei_Data_Port_Question_Schema::COLUMN_TITLE  => 'Single line question',
					Sensei_Data_Port_Question_Schema::COLUMN_ANSWER => 'Fill the answer below',
					Sensei_Data_Port_Question_Schema::COLUMN_STATUS => 'draft',
					Sensei_Data_Port_Question_Schema::COLUMN_TYPE   => 'single-line',
				],
				true,
			],
		];
	}

	/**
	 * Make sure that input coming from the CSV file is sanitized properly.
	 *
	 * @dataProvider lineData
	 */
	public function testInputIsSanitized( $input_line, $expected_model_content, $is_valid ) {
		$model         = Sensei_Import_Question_Model::from_source_array( 1, $input_line, new Sensei_Data_Port_Question_Schema() );
		$tested_fields = [
			Sensei_Data_Port_Question_Schema::COLUMN_TITLE,
			Sensei_Data_Port_Question_Schema::COLUMN_ANSWER,
			Sensei_Data_Port_Question_Schema::COLUMN_ID,
			Sensei_Data_Port_Question_Schema::COLUMN_SLUG,
			Sensei_Data_Port_Question_Schema::COLUMN_DESCRIPTION,
			Sensei_Data_Port_Question_Schema::COLUMN_STATUS,
			Sensei_Data_Port_Question_Schema::COLUMN_TYPE,
			Sensei_Data_Port_Question_Schema::COLUMN_GRADE,
			Sensei_Data_Port_Question_Schema::COLUMN_RANDOM_ORDER,
			Sensei_Data_Port_Question_Schema::COLUMN_MEDIA,
			Sensei_Data_Port_Question_Schema::COLUMN_CATEGORIES,
			Sensei_Data_Port_Question_Schema::COLUMN_FEEDBACK,
			Sensei_Data_Port_Question_Schema::COLUMN_TEXT_BEFORE_GAP,
			Sensei_Data_Port_Question_Schema::COLUMN_GAP,
			Sensei_Data_Port_Question_Schema::COLUMN_TEXT_AFTER_GAP,
			Sensei_Data_Port_Question_Schema::COLUMN_UPLOAD_NOTES,
			Sensei_Data_Port_Question_Schema::COLUMN_TEACHER_NOTES,
		];

		$this->assertEquals( $is_valid, $model->is_valid(), 'Model valid status did not match' );

		foreach ( $tested_fields as $tested_field ) {
			if ( isset( $expected_model_content[ $tested_field ] ) ) {
				$this->assertEquals( $expected_model_content[ $tested_field ], $model->get_value( $tested_field ) );
			}
		}
	}

	/**
	 * Check to make sure post is fully created.
	 */
	public function testValidFullPostCreated() {
		$teacher_id = $this->factory->user->create( [ 'role' => 'teacher' ] );
		wp_set_current_user( $teacher_id );

		$test_data             = $this->lineData()['full'][0];
		$expected_data         = $this->lineData()['full'][1];
		$expected_answer_order = implode( ',', [ md5( 'No' ), md5( 'Yes' ), md5( 'Maybe, it depends' ) ] );

		$task   = new Sensei_Import_Questions( Sensei_Import_Job::create( 'test', 0 ) );
		$model  = Sensei_Import_Question_Model::from_source_array( 1, $test_data, new Sensei_Data_Port_Question_Schema(), $task );
		$result = $model->sync_post();
		$this->assertTrue( $result );

		$post = get_post( $model->get_post_id() );

		$this->assertEquals( $expected_data[ Sensei_Data_Port_Question_Schema::COLUMN_TITLE ], $post->post_title, 'Post title should match the title column' );
		$this->assertEquals( $expected_data[ Sensei_Data_Port_Question_Schema::COLUMN_DESCRIPTION ], $post->post_content, 'Post content should match the description column' );
		$this->assertEquals( $expected_data[ Sensei_Data_Port_Question_Schema::COLUMN_STATUS ], $post->post_status, 'Post status should match the status column' );
		$this->assertEquals( $expected_data[ Sensei_Data_Port_Question_Schema::COLUMN_SLUG ], $post->post_name, 'Post name should match the slug column' );

		$this->assertEquals( [ 'Yes' ], get_post_meta( $post->ID, '_question_right_answer', true ) );
		$this->assertEquals(
			[
				'No',
				'Maybe, it depends',
			],
			get_post_meta( $post->ID, '_question_wrong_answers', true )
		);
		$this->assertEquals( 1, get_post_meta( $post->ID, '_right_answer_count', true ) );
		$this->assertEquals( 2, get_post_meta( $post->ID, '_wrong_answer_count', true ) );
		$this->assertEquals( 'yes', get_post_meta( $post->ID, '_random_order', true ) );
		$this->assertEquals( $expected_data[ Sensei_Data_Port_Question_Schema::COLUMN_GRADE ], get_post_meta( $post->ID, '_question_grade', true ) );

		$this->assertEquals( $expected_answer_order, get_post_meta( $post->ID, '_answer_order', true ) );
		$this->assertTrue( has_term( 'multiple-choice', Sensei_Data_Port_Question_Schema::TAXONOMY_QUESTION_TYPE, $post->ID ), 'Expected the question type to be correct' );
	}

	/**
	 * Check to make sure gap fill question is set up correctly.
	 */
	public function testValidGapFillPostCreated() {
		$teacher_id = $this->factory->user->create( [ 'role' => 'teacher' ] );
		wp_set_current_user( $teacher_id );

		$test_data            = $this->lineData()['valid-gap-fill'][0];
		$expected_data        = $this->lineData()['valid-gap-fill'][1];
		$expected_answer_data = implode(
			'||',
			[
				$expected_data[ Sensei_Data_Port_Question_Schema::COLUMN_TEXT_BEFORE_GAP ],
				$expected_data[ Sensei_Data_Port_Question_Schema::COLUMN_GAP ],
				$expected_data[ Sensei_Data_Port_Question_Schema::COLUMN_TEXT_AFTER_GAP ],
			]
		);

		$task   = new Sensei_Import_Questions( Sensei_Import_Job::create( 'test', 0 ) );
		$model  = Sensei_Import_Question_Model::from_source_array( 1, $test_data, new Sensei_Data_Port_Question_Schema(), $task );
		$result = $model->sync_post();
		$this->assertTrue( $result );

		$post = get_post( $model->get_post_id() );
		$this->commit_transaction();
		$this->assertEquals( $expected_data[ Sensei_Data_Port_Question_Schema::COLUMN_TITLE ], $post->post_title, 'Post title should match the title column' );
		$this->assertEquals( '', $post->post_content, 'Post content should match the description column' );
		$this->assertEquals( 'publish', $post->post_status, 'Post status should match the status column' );
		$this->assertEquals( sanitize_title( $expected_data[ Sensei_Data_Port_Question_Schema::COLUMN_TITLE ] ), $post->post_name, 'Post name should match the slug column' );

		$this->assertEquals( $expected_answer_data, get_post_meta( $post->ID, '_question_right_answer', true ) );
		$this->assertEquals( null, get_post_meta( $post->ID, '_question_wrong_answers', true ) );
		$this->assertEquals( null, get_post_meta( $post->ID, '_right_answer_count', true ) );
		$this->assertEquals( null, get_post_meta( $post->ID, '_wrong_answer_count', true ) );

		$this->assertEquals( null, get_post_meta( $post->ID, '_answer_order', true ) );
		$this->assertTrue( has_term( 'gap-fill', Sensei_Data_Port_Question_Schema::TAXONOMY_QUESTION_TYPE, $post->ID ), 'Expected the question type to be correct' );
	}

	/**
	 * Check to make sure file upload question is set up correctly.
	 */
	public function testValidFileUploadPostCreated() {
		$teacher_id = $this->factory->user->create( [ 'role' => 'teacher' ] );
		wp_set_current_user( $teacher_id );

		$test_data     = $this->lineData()['valid-file-upload'][0];
		$expected_data = $this->lineData()['valid-file-upload'][1];

		$task   = new Sensei_Import_Questions( Sensei_Import_Job::create( 'test', 0 ) );
		$model  = Sensei_Import_Question_Model::from_source_array( 1, $test_data, new Sensei_Data_Port_Question_Schema(), $task );
		$result = $model->sync_post();
		$this->assertTrue( $result );

		$post = get_post( $model->get_post_id() );
		$this->commit_transaction();
		$this->assertEquals( $expected_data[ Sensei_Data_Port_Question_Schema::COLUMN_TITLE ], $post->post_title, 'Post title should match the title column' );
		$this->assertEquals( '', $post->post_content, 'Post content should match the description column' );
		$this->assertEquals( 'draft', $post->post_status, 'Post status should be draft by default' );
		$this->assertEquals( '', $post->post_name, 'Post name should be empty for drafts' );

		$this->assertEquals( $expected_data[ Sensei_Data_Port_Question_Schema::COLUMN_UPLOAD_NOTES ], get_post_meta( $post->ID, '_question_right_answer', true ) );
		$this->assertEquals( $expected_data[ Sensei_Data_Port_Question_Schema::COLUMN_TEACHER_NOTES ], get_post_meta( $post->ID, '_question_wrong_answers', true ) );
		$this->assertEquals( null, get_post_meta( $post->ID, '_right_answer_count', true ) );
		$this->assertEquals( null, get_post_meta( $post->ID, '_wrong_answer_count', true ) );

		$this->assertEquals( null, get_post_meta( $post->ID, '_answer_order', true ) );
		$this->assertTrue( has_term( 'file-upload', Sensei_Data_Port_Question_Schema::TAXONOMY_QUESTION_TYPE, $post->ID ), 'Expected the question type to be correct' );
	}

	/**
	 * Check to make sure boolean question is set up correctly.
	 */
	public function testValidBooleanPostCreated() {
		$teacher_id = $this->factory->user->create( [ 'role' => 'teacher' ] );
		wp_set_current_user( $teacher_id );

		$test_data     = $this->lineData()['valid-boolean'][0];
		$expected_data = $this->lineData()['valid-boolean'][1];

		$task   = new Sensei_Import_Questions( Sensei_Import_Job::create( 'test', 0 ) );
		$model  = Sensei_Import_Question_Model::from_source_array( 1, $test_data, new Sensei_Data_Port_Question_Schema(), $task );
		$result = $model->sync_post();
		$this->assertTrue( $result );

		$post = get_post( $model->get_post_id() );
		$this->commit_transaction();
		$this->assertEquals( $expected_data[ Sensei_Data_Port_Question_Schema::COLUMN_TITLE ], $post->post_title, 'Post title should match the title column' );
		$this->assertEquals( '', $post->post_content, 'Post content should match the description column' );
		$this->assertEquals( 'draft', $post->post_status, 'Post status should be draft by default' );
		$this->assertEquals( '', $post->post_name, 'Post name should be empty for drafts' );

		$this->assertEquals( $expected_data[ Sensei_Data_Port_Question_Schema::COLUMN_ANSWER ], get_post_meta( $post->ID, '_question_right_answer', true ) );
		$this->assertEquals( null, get_post_meta( $post->ID, '_question_wrong_answers', true ) );
		$this->assertEquals( null, get_post_meta( $post->ID, '_right_answer_count', true ) );
		$this->assertEquals( null, get_post_meta( $post->ID, '_wrong_answer_count', true ) );

		$this->assertEquals( null, get_post_meta( $post->ID, '_answer_order', true ) );
		$this->assertTrue( has_term( 'boolean', Sensei_Data_Port_Question_Schema::TAXONOMY_QUESTION_TYPE, $post->ID ), 'Expected the question type to be correct' );
	}

	/**
	 * Check to make sure single line  question is set up correctly.
	 */
	public function testValidSingleLinePostCreated() {
		$teacher_id = $this->factory->user->create( [ 'role' => 'teacher' ] );
		wp_set_current_user( $teacher_id );

		$test_data     = $this->lineData()['valid-single-line'][0];
		$expected_data = $this->lineData()['valid-single-line'][1];

		$task   = new Sensei_Import_Questions( Sensei_Import_Job::create( 'test', 0 ) );
		$model  = Sensei_Import_Question_Model::from_source_array( 1, $test_data, new Sensei_Data_Port_Question_Schema(), $task );
		$result = $model->sync_post();
		$this->assertTrue( $result );

		$post = get_post( $model->get_post_id() );
		$this->commit_transaction();
		$this->assertEquals( $expected_data[ Sensei_Data_Port_Question_Schema::COLUMN_TITLE ], $post->post_title, 'Post title should match the title column' );
		$this->assertEquals( '', $post->post_content, 'Post content should match the description column' );
		$this->assertEquals( 'draft', $post->post_status, 'Post status should be draft by default' );
		$this->assertEquals( '', $post->post_name, 'Post name should be empty for drafts' );

		$this->assertEquals( $expected_data[ Sensei_Data_Port_Question_Schema::COLUMN_ANSWER ], get_post_meta( $post->ID, '_question_right_answer', true ) );
		$this->assertEquals( null, get_post_meta( $post->ID, '_question_wrong_answers', true ) );
		$this->assertEquals( null, get_post_meta( $post->ID, '_right_answer_count', true ) );
		$this->assertEquals( null, get_post_meta( $post->ID, '_wrong_answer_count', true ) );

		$this->assertEquals( null, get_post_meta( $post->ID, '_answer_order', true ) );
		$this->assertTrue( has_term( 'single-line', Sensei_Data_Port_Question_Schema::TAXONOMY_QUESTION_TYPE, $post->ID ), 'Expected the question type to be correct' );
	}
}
