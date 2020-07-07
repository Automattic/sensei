<?php
/**
 * This file contains the Sensei_Import_Lesson_Model_Test class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once SENSEI_TEST_FRAMEWORK_DIR . '/data-port/trait-sensei-data-port-test-helpers.php';

/**
 * Tests for Sensei_Import_Lesson_Model class.
 *
 * @group data-port
 */
class Sensei_Import_Lesson_Model_Test extends WP_UnitTestCase {
	use Sensei_Data_Port_Test_Helpers;

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
	 * Test to make sure prerequisites are queued for processing after all other lines have been processed.
	 */
	public function testPrerequisiteQueued() {
		$lesson_id = $this->factory->lesson->create(
			[
				'post_name' => 'the-last-lesson',
			]
		);
		$job       = Sensei_Import_Job::create( 'test', 0 );
		$task      = $this->getMockBuilder( Sensei_Import_Lessons::class )
						->setConstructorArgs( [ $job ] )
						->setMethods( [ 'add_prerequisite_task' ] )
						->getMock();

		$data_a = [
			Sensei_Data_Port_Lesson_Schema::COLUMN_ID    => '1234',
			Sensei_Data_Port_Lesson_Schema::COLUMN_TITLE => 'lesson title a',
			Sensei_Data_Port_Lesson_Schema::COLUMN_SLUG  => 'the-last-lesson',
			Sensei_Data_Port_Lesson_Schema::COLUMN_PREREQUISITE => 'slug:a-prereq-lesson',
		];

		$data_b = [
			Sensei_Data_Port_Lesson_Schema::COLUMN_ID    => '1235',
			Sensei_Data_Port_Lesson_Schema::COLUMN_TITLE => 'lesson title b',
			Sensei_Data_Port_Lesson_Schema::COLUMN_SLUG  => 'the-very-last-lesson',
			Sensei_Data_Port_Lesson_Schema::COLUMN_PREREQUISITE => '',
		];

		$task->expects( $this->once() )
			->method( 'add_prerequisite_task' )
			->with(
				$this->equalTo( $lesson_id ),
				$this->equalTo( 'slug:a-prereq-lesson' ),
				$this->equalTo( 1 )
			);

		$model_a = Sensei_Import_Lesson_Model::from_source_array( 1, $data_a, new Sensei_Data_Port_Lesson_Schema(), $task );
		$model_a->sync_post();

		$model_b = Sensei_Import_Lesson_Model::from_source_array( 2, $data_b, new Sensei_Data_Port_Lesson_Schema(), $task );
		$model_b->sync_post();
	}

	/**
	 * Returns an array with the data used by the tests. Each element is an array of line input data and expected
	 * output following the format of Sensei_Data_Port_Lesson_Model::data.
	 *
	 * The first and second elements of the array, refer to the same Lesson and are used in the test scenario which
	 * creates a post and then updates it.
	 */
	public function lineData() {
		return [
			[
				[
					Sensei_Data_Port_Lesson_Schema::COLUMN_ID             => '<tag>id</tag>',
					Sensei_Data_Port_Lesson_Schema::COLUMN_TITLE          => 'Lesson <randomtag>title</randomtag>',
					Sensei_Data_Port_Lesson_Schema::COLUMN_SLUG           => '<randomtag>slug</randomtag>',
					Sensei_Data_Port_Lesson_Schema::COLUMN_DESCRIPTION    => '<randomtag>description</randomtag>',
					Sensei_Data_Port_Lesson_Schema::COLUMN_EXCERPT        => '<randomtag>excerpt</randomtag>',
					Sensei_Data_Port_Lesson_Schema::COLUMN_STATUS         => 'publish',
					Sensei_Data_Port_Lesson_Schema::COLUMN_PREREQUISITE   => '<randomtag>prerequisite</randomtag>',
					Sensei_Data_Port_Lesson_Schema::COLUMN_PREVIEW        => 'badvalue',
					Sensei_Data_Port_Lesson_Schema::COLUMN_TAGS           => '<randomtag>   First,Second   </randomtag>',
					Sensei_Data_Port_Lesson_Schema::COLUMN_IMAGE          => 'localfilename.png',
					Sensei_Data_Port_Lesson_Schema::COLUMN_LENGTH         => '12',
					Sensei_Data_Port_Lesson_Schema::COLUMN_COMPLEXITY     => 'easy',
					Sensei_Data_Port_Lesson_Schema::COLUMN_VIDEO          => '<randomtag>video</randomtag>',
					Sensei_Data_Port_Lesson_Schema::COLUMN_PASS_REQUIRED  => 'true',
					Sensei_Data_Port_Lesson_Schema::COLUMN_PASSMARK       => 23,
					Sensei_Data_Port_Lesson_Schema::COLUMN_NUM_QUESTIONS  => 'b4',
					Sensei_Data_Port_Lesson_Schema::COLUMN_RANDOMIZE      => 'false',
					Sensei_Data_Port_Lesson_Schema::COLUMN_AUTO_GRADE     => 'false',
					Sensei_Data_Port_Lesson_Schema::COLUMN_QUIZ_RESET     => 'true',
					Sensei_Data_Port_Lesson_Schema::COLUMN_ALLOW_COMMENTS => 'true',
				],
				[
					Sensei_Data_Port_Lesson_Schema::COLUMN_ID             => 'id',
					Sensei_Data_Port_Lesson_Schema::COLUMN_TITLE          => 'Lesson title',
					Sensei_Data_Port_Lesson_Schema::COLUMN_SLUG           => 'slug',
					Sensei_Data_Port_Lesson_Schema::COLUMN_DESCRIPTION    => 'description',
					Sensei_Data_Port_Lesson_Schema::COLUMN_EXCERPT        => 'excerpt',
					Sensei_Data_Port_Lesson_Schema::COLUMN_STATUS         => 'publish',
					Sensei_Data_Port_Lesson_Schema::COLUMN_PREREQUISITE   => 'prerequisite',
					Sensei_Data_Port_Lesson_Schema::COLUMN_PREVIEW        => null,
					Sensei_Data_Port_Lesson_Schema::COLUMN_TAGS           => 'First,Second',
					Sensei_Data_Port_Lesson_Schema::COLUMN_IMAGE          => 'localfilename.png',
					Sensei_Data_Port_Lesson_Schema::COLUMN_LENGTH         => 12,
					Sensei_Data_Port_Lesson_Schema::COLUMN_COMPLEXITY     => 'easy',
					Sensei_Data_Port_Lesson_Schema::COLUMN_VIDEO          => 'video',
					Sensei_Data_Port_Lesson_Schema::COLUMN_PASS_REQUIRED  => true,
					Sensei_Data_Port_Lesson_Schema::COLUMN_PASSMARK       => 23,
					Sensei_Data_Port_Lesson_Schema::COLUMN_NUM_QUESTIONS  => 0,
					Sensei_Data_Port_Lesson_Schema::COLUMN_RANDOMIZE      => false,
					Sensei_Data_Port_Lesson_Schema::COLUMN_AUTO_GRADE     => false,
					Sensei_Data_Port_Lesson_Schema::COLUMN_QUIZ_RESET     => true,
					Sensei_Data_Port_Lesson_Schema::COLUMN_ALLOW_COMMENTS => true,
				],
			],
			[
				[
					Sensei_Data_Port_Lesson_Schema::COLUMN_ID             => 'id',
					Sensei_Data_Port_Lesson_Schema::COLUMN_TITLE          => 'Updated Lesson title',
					Sensei_Data_Port_Lesson_Schema::COLUMN_SLUG           => 'slug',
					Sensei_Data_Port_Lesson_Schema::COLUMN_DESCRIPTION    => 'Updated description',
					Sensei_Data_Port_Lesson_Schema::COLUMN_EXCERPT        => 'Updated excerpt',
					Sensei_Data_Port_Lesson_Schema::COLUMN_STATUS         => 'draft',
					Sensei_Data_Port_Lesson_Schema::COLUMN_PREVIEW        => 'false',
					Sensei_Data_Port_Lesson_Schema::COLUMN_TAGS           => 'New First, New Second ',
					Sensei_Data_Port_Lesson_Schema::COLUMN_LENGTH         => 15,
					Sensei_Data_Port_Lesson_Schema::COLUMN_COMPLEXITY     => 'hard',
					Sensei_Data_Port_Lesson_Schema::COLUMN_VIDEO          => 'Updated video',
					Sensei_Data_Port_Lesson_Schema::COLUMN_PASS_REQUIRED  => 'false',
					Sensei_Data_Port_Lesson_Schema::COLUMN_PASSMARK       => 0,
					Sensei_Data_Port_Lesson_Schema::COLUMN_NUM_QUESTIONS  => 6,
					Sensei_Data_Port_Lesson_Schema::COLUMN_RANDOMIZE      => 'false',
					Sensei_Data_Port_Lesson_Schema::COLUMN_AUTO_GRADE     => 'false',
					Sensei_Data_Port_Lesson_Schema::COLUMN_QUIZ_RESET     => 'false',
					Sensei_Data_Port_Lesson_Schema::COLUMN_ALLOW_COMMENTS => 'false',
				],
				[
					Sensei_Data_Port_Lesson_Schema::COLUMN_ID             => 'id',
					Sensei_Data_Port_Lesson_Schema::COLUMN_TITLE          => 'Updated Lesson title',
					Sensei_Data_Port_Lesson_Schema::COLUMN_SLUG           => 'slug',
					Sensei_Data_Port_Lesson_Schema::COLUMN_DESCRIPTION    => 'Updated description',
					Sensei_Data_Port_Lesson_Schema::COLUMN_EXCERPT        => 'Updated excerpt',
					Sensei_Data_Port_Lesson_Schema::COLUMN_STATUS         => 'draft',
					Sensei_Data_Port_Lesson_Schema::COLUMN_PREVIEW        => false,
					Sensei_Data_Port_Lesson_Schema::COLUMN_TAGS           => 'New First, New Second',
					Sensei_Data_Port_Lesson_Schema::COLUMN_LENGTH         => 15,
					Sensei_Data_Port_Lesson_Schema::COLUMN_COMPLEXITY     => 'hard',
					Sensei_Data_Port_Lesson_Schema::COLUMN_VIDEO          => 'Updated video',
					Sensei_Data_Port_Lesson_Schema::COLUMN_PASS_REQUIRED  => false,
					Sensei_Data_Port_Lesson_Schema::COLUMN_PASSMARK       => 0,
					Sensei_Data_Port_Lesson_Schema::COLUMN_NUM_QUESTIONS  => 6,
					Sensei_Data_Port_Lesson_Schema::COLUMN_RANDOMIZE      => false,
					Sensei_Data_Port_Lesson_Schema::COLUMN_AUTO_GRADE     => false,
					Sensei_Data_Port_Lesson_Schema::COLUMN_QUIZ_RESET     => false,
					Sensei_Data_Port_Lesson_Schema::COLUMN_ALLOW_COMMENTS => false,
				],
			],
			[
				[
					Sensei_Data_Port_Lesson_Schema::COLUMN_TITLE          => 'Lesson <p>title</p>',
					Sensei_Data_Port_Lesson_Schema::COLUMN_DESCRIPTION    => '<p>description</p>',
					Sensei_Data_Port_Lesson_Schema::COLUMN_EXCERPT        => '<p>excerpt</p>',
					Sensei_Data_Port_Lesson_Schema::COLUMN_STATUS         => 'random_status',
					Sensei_Data_Port_Lesson_Schema::COLUMN_MODULE         => '<randomtag>Module</randomtag>',
					Sensei_Data_Port_Lesson_Schema::COLUMN_COURSE         => '<randomtag>course_id</randomtag>',
					Sensei_Data_Port_Lesson_Schema::COLUMN_IMAGE          => 'http://randomurl<>.com/nice%20image.png',
					Sensei_Data_Port_Lesson_Schema::COLUMN_LENGTH         => '12',
					Sensei_Data_Port_Lesson_Schema::COLUMN_COMPLEXITY     => 'random_complexity',
					Sensei_Data_Port_Lesson_Schema::COLUMN_VIDEO          => '<video autoplay>video</video>',
					Sensei_Data_Port_Lesson_Schema::COLUMN_QUESTIONS      => '<randomtag>id:44</randomtag>',
				],
				[
					Sensei_Data_Port_Lesson_Schema::COLUMN_TITLE          => 'Lesson <p>title</p>',
					Sensei_Data_Port_Lesson_Schema::COLUMN_DESCRIPTION    => '<p>description</p>',
					Sensei_Data_Port_Lesson_Schema::COLUMN_EXCERPT        => '<p>excerpt</p>',
					Sensei_Data_Port_Lesson_Schema::COLUMN_STATUS         => null,
					Sensei_Data_Port_Lesson_Schema::COLUMN_MODULE         => 'Module',
					Sensei_Data_Port_Lesson_Schema::COLUMN_COURSE         => 'course_id',
					Sensei_Data_Port_Lesson_Schema::COLUMN_IMAGE          => 'http://randomurl.com/nice%20image.png',
					Sensei_Data_Port_Lesson_Schema::COLUMN_LENGTH         => 12,
					Sensei_Data_Port_Lesson_Schema::COLUMN_COMPLEXITY     => null,
					Sensei_Data_Port_Lesson_Schema::COLUMN_VIDEO          => '<video autoplay>video</video>',
					Sensei_Data_Port_Lesson_Schema::COLUMN_QUESTIONS      => 'id:44',
				],
			],
		];
	}

	/**
	 * Make sure that input coming from the CSV file is sanitized properly.
	 *
	 * @dataProvider lineData
	 */
	public function testInputIsSanitized( $input_line, $expected_model_content ) {
		$task          = new Sensei_Import_Lessons( Sensei_Import_Job::create( 'test', 0 ) );
		$model         = Sensei_Import_Lesson_Model::from_source_array( 1, $input_line, new Sensei_Data_Port_Lesson_Schema(), $task );
		$tested_fields = [
			Sensei_Data_Port_Lesson_Schema::COLUMN_ID,
			Sensei_Data_Port_Lesson_Schema::COLUMN_TITLE,
			Sensei_Data_Port_Lesson_Schema::COLUMN_SLUG,
			Sensei_Data_Port_Lesson_Schema::COLUMN_DESCRIPTION,
			Sensei_Data_Port_Lesson_Schema::COLUMN_EXCERPT,
			Sensei_Data_Port_Lesson_Schema::COLUMN_STATUS,
			Sensei_Data_Port_Lesson_Schema::COLUMN_COURSE,
			Sensei_Data_Port_Lesson_Schema::COLUMN_MODULE,
			Sensei_Data_Port_Lesson_Schema::COLUMN_PREREQUISITE,
			Sensei_Data_Port_Lesson_Schema::COLUMN_PREVIEW,
			Sensei_Data_Port_Lesson_Schema::COLUMN_TAGS,
			Sensei_Data_Port_Lesson_Schema::COLUMN_IMAGE,
			Sensei_Data_Port_Lesson_Schema::COLUMN_LENGTH,
			Sensei_Data_Port_Lesson_Schema::COLUMN_COMPLEXITY,
			Sensei_Data_Port_Lesson_Schema::COLUMN_VIDEO,
			Sensei_Data_Port_Lesson_Schema::COLUMN_PASS_REQUIRED,
			Sensei_Data_Port_Lesson_Schema::COLUMN_PASSMARK,
			Sensei_Data_Port_Lesson_Schema::COLUMN_NUM_QUESTIONS,
			Sensei_Data_Port_Lesson_Schema::COLUMN_RANDOMIZE,
			Sensei_Data_Port_Lesson_Schema::COLUMN_AUTO_GRADE,
			Sensei_Data_Port_Lesson_Schema::COLUMN_QUIZ_RESET,
			Sensei_Data_Port_Lesson_Schema::COLUMN_ALLOW_COMMENTS,
			Sensei_Data_Port_Lesson_Schema::COLUMN_QUESTIONS,
		];

		foreach ( $tested_fields as $tested_field ) {
			if ( isset( $expected_model_content[ $tested_field ] ) ) {
				$this->assertEquals( $expected_model_content[ $tested_field ], $model->get_value( $tested_field ), "The field {$tested_field} did not match what was expected" );
			}
		}
	}

	/**
	 * Tests passmark validation.
	 */
	public function testPassmarkValidation() {
		$lesson_data_with_invalid_minimum_passmark = [
			Sensei_Data_Port_Lesson_Schema::COLUMN_TITLE => 'Required title',
			Sensei_Data_Port_Lesson_Schema::COLUMN_PASSMARK => -1,
		];
		$lesson_data_with_invalid_maximum_passmark = [
			Sensei_Data_Port_Lesson_Schema::COLUMN_TITLE => 'Required title',
			Sensei_Data_Port_Lesson_Schema::COLUMN_PASSMARK => 101,
		];
		$lesson_data_with_valid_passmark           = [
			Sensei_Data_Port_Lesson_Schema::COLUMN_TITLE => 'Required title',
			Sensei_Data_Port_Lesson_Schema::COLUMN_PASSMARK => 50,
		];

		$model = Sensei_Import_Lesson_Model::from_source_array( 1, $lesson_data_with_invalid_minimum_passmark, new Sensei_Data_Port_Lesson_Schema() );
		$this->assertFalse( $model->is_valid() );

		$model = Sensei_Import_Lesson_Model::from_source_array( 1, $lesson_data_with_invalid_maximum_passmark, new Sensei_Data_Port_Lesson_Schema() );
		$this->assertFalse( $model->is_valid() );

		$model = Sensei_Import_Lesson_Model::from_source_array( 1, $lesson_data_with_valid_passmark, new Sensei_Data_Port_Lesson_Schema() );
		$this->assertTrue( $model->is_valid() );
	}

	/**
	 * Tests creating a lesson and updating all its values.
	 */
	public function testLessonIsInsertedAndUpdated() {
		$thumbnail_id = $this->factory->attachment->create( [ 'file' => 'localfilename.png' ] );
		$task         = new Sensei_Import_Lessons( Sensei_Import_Job::create( 'test', 0 ) );
		$model        = Sensei_Import_Lesson_Model::from_source_array( 1, $this->lineData()[0][0], new Sensei_Data_Port_Lesson_Schema(), $task );
		$result       = $model->sync_post();
		$post         = get_post( $model->get_post_id() );
		$this->assertTrue( $result, 'Lesson with correct data should be created successfully.' );

		$created_post = get_posts(
			[
				'post_type'      => 'lesson',
				'post_name__in'  => [ 'slug' ],
				'posts_per_page' => 1,
				'post_status'    => 'any',
			]
		)[0];

		$this->verify_lesson( $created_post, $this->lineData()[0][1], $thumbnail_id );

		$task   = new Sensei_Import_Lessons( Sensei_Import_Job::create( 'test', 0 ) );
		$model  = Sensei_Import_Lesson_Model::from_source_array( 1, $this->lineData()[1][0], new Sensei_Data_Port_Lesson_Schema(), $task );
		$result = $model->sync_post();

		$this->assertTrue( $result, 'Lesson with correct data should be updated successfully.' );

		$updated_post = get_posts(
			[
				'post_type'      => 'lesson',
				'post_name__in'  => [ 'slug' ],
				'posts_per_page' => 1,
				'post_status'    => 'any',
			]
		)[0];

		$this->assertEquals( $created_post->ID, $updated_post->ID );

		$this->verify_lesson( $updated_post, $this->lineData()[1][1], $thumbnail_id );
	}

	/**
	 * Helper method to verify that all the fields of a lesson are correct.
	 *
	 * @param WP_Post $lesson        The lesson to verify.
	 * @param array   $line_data     An array which has all the expected values. It follows the format of Sensei_Data_Port_Lesson_Model::data.
	 * @param int     $thumbnail_id  The post id of the image attachment.
	 */
	private function verify_lesson( WP_Post $lesson, $line_data, $thumbnail_id = 0 ) {

		// Assert that post columns have the correct values.
		$this->assertEquals( $line_data[ Sensei_Data_Port_Lesson_Schema::COLUMN_TITLE ], $lesson->post_title );
		$this->assertEquals( $line_data[ Sensei_Data_Port_Lesson_Schema::COLUMN_SLUG ], $lesson->post_name );
		$this->assertEquals( $line_data[ Sensei_Data_Port_Lesson_Schema::COLUMN_DESCRIPTION ], $lesson->post_content );
		$this->assertEquals( $line_data[ Sensei_Data_Port_Lesson_Schema::COLUMN_EXCERPT ], $lesson->post_excerpt );
		$this->assertEquals( $line_data[ Sensei_Data_Port_Lesson_Schema::COLUMN_STATUS ], $lesson->post_status );

		if ( true === (bool) $line_data[ Sensei_Data_Port_Lesson_Schema::COLUMN_ALLOW_COMMENTS ] ) {
			$this->assertEquals( 'open', $lesson->comment_status );
		} else {
			$this->assertEquals( 'closed', $lesson->comment_status );
		}

		// Assert that post meta have the correct values.
		$this->assertEquals( $line_data[ Sensei_Data_Port_Lesson_Schema::COLUMN_VIDEO ], get_post_meta( $lesson->ID, '_lesson_video_embed', true ) );
		$this->assertEquals( $line_data[ Sensei_Data_Port_Lesson_Schema::COLUMN_COMPLEXITY ], get_post_meta( $lesson->ID, '_lesson_complexity', true ) );
		$this->assertEquals( $line_data[ Sensei_Data_Port_Lesson_Schema::COLUMN_LENGTH ], get_post_meta( $lesson->ID, '_lesson_length', true ) );

		if ( true === (bool) $line_data[ Sensei_Data_Port_Lesson_Schema::COLUMN_PREVIEW ] ) {
			$this->assertEquals( 'preview', get_post_meta( $lesson->ID, '_lesson_preview', true ) );
		} else {
			$this->assertEmpty( get_post_meta( $lesson->ID, '_lesson_preview', true ) );
		}

		// Assert that the lesson has the correct terms.
		$expected_lesson_tags = Sensei_Data_Port_Utilities::split_list_safely( $line_data[ Sensei_Data_Port_Lesson_Schema::COLUMN_TAGS ] );
		if ( ! empty( $expected_lesson_tags ) ) {
			$actual_lesson_tags = wp_get_object_terms( $lesson->ID, 'lesson-tag', [ 'fields' => 'names' ] );
			$this->assertArraySubset( $expected_lesson_tags, $actual_lesson_tags, false, 'Lesson should have the supplied tags.' );
		}

		if ( 0 !== $thumbnail_id ) {
			$this->assertEquals( $thumbnail_id, get_post_meta( $lesson->ID, '_thumbnail_id', true ), 'Lesson should have the supplied thumbnail.' );
		}
	}

	/**
	 * Tests that the course is linked correctly to a course.
	 */
	public function testLessonCourseIsLinkedCorrectly() {
		$job                     = Sensei_Import_Job::create( 'test', 0 );
		$lesson_data_with_course = [
			Sensei_Data_Port_Lesson_Schema::COLUMN_TITLE  => 'Course lesson',
			Sensei_Data_Port_Lesson_Schema::COLUMN_SLUG   => 'course-lesson',
			Sensei_Data_Port_Lesson_Schema::COLUMN_COURSE => 'id:the-import-id',
		];

		$task   = new Sensei_Import_Lessons( $job );
		$model  = Sensei_Import_Lesson_Model::from_source_array( 1, $lesson_data_with_course, new Sensei_Data_Port_Lesson_Schema(), $task );
		$result = $model->sync_post();

		$this->assertTrue( $result, 'Lesson should still be created when a course which does not exist is supplied.' );
		$this->assertJobHasLogEntry( $job, "Course does not exist: {$lesson_data_with_course[ Sensei_Data_Port_Lesson_Schema::COLUMN_COURSE ]}." );

		$course_data = [
			Sensei_Data_Port_Course_Schema::COLUMN_ID    => 'the-import-id',
			Sensei_Data_Port_Course_Schema::COLUMN_TITLE => 'Course title',
			Sensei_Data_Port_Course_Schema::COLUMN_SLUG  => 'course-slug',
		];

		$task         = new Sensei_Import_Lessons( $job );
		$course_model = Sensei_Import_Course_Model::from_source_array( 1, $course_data, new Sensei_Data_Port_Course_Schema(), $task );
		$course_model->sync_post();

		$created_course = get_posts(
			[
				'post_type'      => 'course',
				'title'          => 'Course title',
				'posts_per_page' => 1,
				'post_status'    => 'any',
			]
		)[0];

		$task   = new Sensei_Import_Lessons( $job );
		$model  = Sensei_Import_Lesson_Model::from_source_array( 1, $lesson_data_with_course, new Sensei_Data_Port_Lesson_Schema(), $task );
		$result = $model->sync_post();

		$this->assertTrue( $result, 'Lesson creation should not fail when the linked course exists.' );

		$created_lesson = get_posts(
			[
				'post_type'      => 'lesson',
				'post_name__in'  => [ 'course-lesson' ],
				'posts_per_page' => 1,
				'post_status'    => 'any',
			]
		)[0];

		$this->assertEquals( $created_course->ID, get_post_meta( $created_lesson->ID, '_lesson_course', true ), 'Lesson should be linked to the supplied course.' );

		$lesson_with_course_slug = [
			Sensei_Data_Port_Lesson_Schema::COLUMN_TITLE  => 'Slug lesson',
			Sensei_Data_Port_Lesson_Schema::COLUMN_SLUG   => 'lesson-slug',
			Sensei_Data_Port_Lesson_Schema::COLUMN_COURSE => 'slug:course-slug',
		];

		$task  = new Sensei_Import_Lessons( $job );
		$model = Sensei_Import_Lesson_Model::from_source_array( 1, $lesson_with_course_slug, new Sensei_Data_Port_Lesson_Schema(), $task );
		$model->sync_post();

		$lesson_with_slug = get_posts(
			[
				'post_type'      => 'lesson',
				'post_name__in'  => [ 'lesson-slug' ],
				'posts_per_page' => 1,
				'post_status'    => 'any',
			]
		)[0];

		$this->assertEquals( $created_course->ID, get_post_meta( $lesson_with_slug->ID, '_lesson_course', true ), 'Lesson should be linked to the supplied course.' );
	}

	/**
	 * Tests that the module is linked correctly to a lesson.
	 */
	public function testLessonModuleIsLinkedCorrectly() {
		$job                     = Sensei_Import_Job::create( 'test', 0 );
		$lesson_data_with_module = [
			Sensei_Data_Port_Lesson_Schema::COLUMN_TITLE  => 'Module lesson',
			Sensei_Data_Port_Lesson_Schema::COLUMN_SLUG   => 'module-lesson',
			Sensei_Data_Port_Lesson_Schema::COLUMN_MODULE => 'the-module',
		];

		$task   = new Sensei_Import_Lessons( $job );
		$model  = Sensei_Import_Lesson_Model::from_source_array( 1, $lesson_data_with_module, new Sensei_Data_Port_Lesson_Schema(), $task );
		$result = $model->sync_post();

		$this->assertTrue( $result, 'Lesson should still be created when a module supplied when no course is.' );
		$this->assertJobHasLogEntry( $job, 'Module is defined while no course is specified.' );

		$course_model = Sensei_Import_Course_Model::from_source_array(
			1,
			[
				Sensei_Data_Port_Course_Schema::COLUMN_ID => 'the-import-id',
				Sensei_Data_Port_Course_Schema::COLUMN_TITLE => 'Course title',
			],
			new Sensei_Data_Port_Course_Schema(),
			$task
		);
		$course_model->sync_post();
		$lesson_data_with_module[ Sensei_Data_Port_Lesson_Schema::COLUMN_COURSE ] = 'id:the-import-id';

		$task   = new Sensei_Import_Lessons( $job );
		$model  = Sensei_Import_Lesson_Model::from_source_array( 1, $lesson_data_with_module, new Sensei_Data_Port_Lesson_Schema(), $task );
		$result = $model->sync_post();

		$this->assertTrue( $result, 'Lesson should still be created when a module supplied for a lesson does not exist.' );
		$this->assertJobHasLogEntry( $job, "Module does not exist: {$lesson_data_with_module[ Sensei_Data_Port_Lesson_Schema::COLUMN_MODULE ]}." );

		$term   = Sensei_Data_Port_Utilities::get_term( 'the-module', 'module' );
		$model  = Sensei_Import_Lesson_Model::from_source_array( 1, $lesson_data_with_module, new Sensei_Data_Port_Lesson_Schema(), $task );
		$result = $model->sync_post();

		$this->assertTrue( $result, 'Lesson should still be created when a module supplied for a lesson is not associated with the course.' );
		$this->assertJobHasLogEntry( $job, "Module the-module is not part of course {$course_model->get_post_id()}." );

		$created_course = get_posts(
			[
				'post_type'      => 'course',
				'title'          => 'Course title',
				'posts_per_page' => 1,
				'post_status'    => 'any',
			]
		)[0];
		wp_set_object_terms( $created_course->ID, $term->term_id, 'module' );

		$task   = new Sensei_Import_Lessons( $job );
		$model  = Sensei_Import_Lesson_Model::from_source_array( 1, $lesson_data_with_module, new Sensei_Data_Port_Lesson_Schema(), $task );
		$result = $model->sync_post();

		$this->assertTrue( $result, 'Lesson should be created successfully when module data are correct.' );
	}

	/**
	 * Tests that the lesson order is created correctly.
	 */
	public function testLessonOrderIsGeneratedCorrectly() {
		$job          = Sensei_Import_Job::create( 'test', 0 );
		$task         = new Sensei_Import_Lessons( $job );
		$course_model = Sensei_Import_Course_Model::from_source_array(
			1,
			[
				Sensei_Data_Port_Course_Schema::COLUMN_ID => 'the-import-id',
				Sensei_Data_Port_Course_Schema::COLUMN_TITLE => 'Course title',
			],
			new Sensei_Data_Port_Course_Schema(),
			$task
		);
		$course_model->sync_post();

		$course_id = get_posts(
			[
				'post_type'      => 'course',
				'title'          => 'Course title',
				'posts_per_page' => 1,
				'post_status'    => 'any',
			]
		)[0]->ID;

		$lessons = [
			[
				Sensei_Data_Port_Lesson_Schema::COLUMN_TITLE  => 'First lesson',
				Sensei_Data_Port_Lesson_Schema::COLUMN_SLUG   => 'first',
				Sensei_Data_Port_Lesson_Schema::COLUMN_COURSE => 'id:the-import-id',
			],
			[
				Sensei_Data_Port_Lesson_Schema::COLUMN_TITLE => 'Second lesson',
				Sensei_Data_Port_Lesson_Schema::COLUMN_SLUG  => 'second',
			],
			[
				Sensei_Data_Port_Lesson_Schema::COLUMN_TITLE  => 'Third lesson',
				Sensei_Data_Port_Lesson_Schema::COLUMN_SLUG   => 'third',
				Sensei_Data_Port_Lesson_Schema::COLUMN_COURSE => 'id:the-import-id',
			],
			[
				Sensei_Data_Port_Lesson_Schema::COLUMN_TITLE  => 'Fourth lesson',
				Sensei_Data_Port_Lesson_Schema::COLUMN_SLUG   => 'fourth',
				Sensei_Data_Port_Lesson_Schema::COLUMN_COURSE => 'id:the-import-id',
			],
		];

		$lesson_ids = [];
		foreach ( $lessons as $lesson ) {
			$model = Sensei_Import_Lesson_Model::from_source_array( 1, $lesson, new Sensei_Data_Port_Lesson_Schema(), $task );
			$model->sync_post();

			$lesson_ids[] = get_posts(
				[
					'post_type'      => 'lesson',
					'post_name__in'  => [ $lesson[ Sensei_Data_Port_Lesson_Schema::COLUMN_SLUG ] ],
					'posts_per_page' => 1,
					'post_status'    => 'any',
				]
			)[0]->ID;
		}

		unset( $lesson_ids[1] );

		$this->assertEquals( implode( ',', $lesson_ids ), get_post_meta( $course_id, '_lesson_order', true ) );

		$order = 1;
		foreach ( $lesson_ids as $lesson_id ) {
			$this->assertEquals( $order, get_post_meta( $lesson_id, '_order_' . $course_id, true ) );
			$order++;
		}
	}

	/**
	 * Tests creation and updating of quizzes.
	 */
	public function testQuizIsInsertedAndUpdated() {
		$this->factory->attachment->create( [ 'file' => 'localfilename.png' ] );
		$task   = new Sensei_Import_Lessons( Sensei_Import_Job::create( 'test', 0 ) );
		$model  = Sensei_Import_Lesson_Model::from_source_array( 1, $this->lineData()[0][0], new Sensei_Data_Port_Lesson_Schema(), $task );
		$result = $model->sync_post();

		$this->assertTrue( $result, 'Quiz with correct data should be created successfully.' );

		$created_post = get_posts(
			[
				'post_type'      => 'quiz',
				'posts_per_page' => 1,
				'post_status'    => 'any',
			]
		)[0];

		$this->verify_quiz( $created_post, $this->lineData()[0][1] );

		$task   = new Sensei_Import_Lessons( Sensei_Import_Job::create( 'test', 0 ) );
		$model  = Sensei_Import_Lesson_Model::from_source_array( 1, $this->lineData()[1][0], new Sensei_Data_Port_Lesson_Schema(), $task );
		$result = $model->sync_post();

		$this->assertTrue( $result, 'Quiz with correct data should be updated successfully.' );

		$updated_post = get_posts(
			[
				'post_type'      => 'quiz',
				'posts_per_page' => 1,
				'post_status'    => 'any',
			]
		)[0];

		$this->assertEquals( $created_post->ID, $updated_post->ID );

		$this->verify_quiz( $updated_post, $this->lineData()[1][1] );
	}

	/**
	 * Helper method to verify that all the fields of a quiz are correct.
	 *
	 * @param WP_Post $quiz        The quiz to verify.
	 * @param array   $line_data   An array which has all the expected values. It follows the format of Sensei_Data_Port_Lesson_Model::data.
	 */
	private function verify_quiz( WP_Post $quiz, $line_data ) {

		// Assert that post columns have the correct values.
		$this->assertEquals( $line_data[ Sensei_Data_Port_Lesson_Schema::COLUMN_TITLE ], $quiz->post_title );
		$this->assertEquals( $line_data[ Sensei_Data_Port_Lesson_Schema::COLUMN_STATUS ], $quiz->post_status );

		// Assert that post meta have the correct values.
		$this->assertEquals( $line_data[ Sensei_Data_Port_Lesson_Schema::COLUMN_NUM_QUESTIONS ], get_post_meta( $quiz->ID, '_show_questions', true ) );

		if ( true === (bool) $line_data[ Sensei_Data_Port_Lesson_Schema::COLUMN_PASS_REQUIRED ] ) {
			$this->assertEquals( 'on', get_post_meta( $quiz->ID, '_pass_required', true ) );
			$this->assertEquals( $line_data[ Sensei_Data_Port_Lesson_Schema::COLUMN_PASSMARK ], get_post_meta( $quiz->ID, '_quiz_passmark', true ) );
		} else {
			$this->assertEmpty( get_post_meta( $quiz->ID, '_pass_required', true ) );
			$this->assertEquals( 0, get_post_meta( $quiz->ID, '_quiz_passmark', true ), 'Passmark should be 0 when pass is not required.' );
		}

		if ( true === (bool) $line_data[ Sensei_Data_Port_Lesson_Schema::COLUMN_RANDOMIZE ] ) {
			$this->assertEquals( 'yes', get_post_meta( $quiz->ID, '_random_question_order', true ) );
		} else {
			$this->assertEquals( 'no', get_post_meta( $quiz->ID, '_random_question_order', true ) );
		}

		if ( true === (bool) $line_data[ Sensei_Data_Port_Lesson_Schema::COLUMN_AUTO_GRADE ] ) {
			$this->assertEquals( 'auto', get_post_meta( $quiz->ID, '_quiz_grade_type', true ) );
		} else {
			$this->assertEquals( 'manual', get_post_meta( $quiz->ID, '_quiz_grade_type', true ) );
		}

		if ( true === (bool) $line_data[ Sensei_Data_Port_Lesson_Schema::COLUMN_QUIZ_RESET ] ) {
			$this->assertEquals( 'on', get_post_meta( $quiz->ID, '_enable_quiz_reset', true ) );
		} else {
			$this->assertEmpty( get_post_meta( $quiz->ID, '_enable_quiz_reset', true ) );
		}
	}

	/**
	 * Tests that the quiz question order is created correctly.
	 */
	public function testQuizOrderIsGeneratedCorrectly() {
		// Create the common job and 3 questions.
		$job           = Sensei_Import_Job::create( 'test', 0 );
		$task          = new Sensei_Import_Lessons( $job );
		$question_task = new Sensei_Import_Questions( $job );
		$questions     = [
			[
				Sensei_Data_Port_Question_Schema::COLUMN_ID => 'burgers',
				Sensei_Data_Port_Question_Schema::COLUMN_TITLE => 'Do you like burgers?',
				Sensei_Data_Port_Question_Schema::COLUMN_ANSWER => 'Right:Yes, Wrong: No',
				Sensei_Data_Port_Question_Schema::COLUMN_SLUG  => 'do-you-like-burgers',
			],
			[
				Sensei_Data_Port_Question_Schema::COLUMN_ID => 'ice-cream',
				Sensei_Data_Port_Question_Schema::COLUMN_TITLE => 'Do you like ice cream?',
				Sensei_Data_Port_Question_Schema::COLUMN_ANSWER => 'Right:Yes, Wrong: No',
				Sensei_Data_Port_Question_Schema::COLUMN_SLUG  => 'do-you-like-ice-cream',
			],
			[
				Sensei_Data_Port_Question_Schema::COLUMN_ID => 'pizza',
				Sensei_Data_Port_Question_Schema::COLUMN_TITLE => 'Do you like pizza?',
				Sensei_Data_Port_Question_Schema::COLUMN_ANSWER => 'Right:Yes, Wrong: No',
				Sensei_Data_Port_Question_Schema::COLUMN_SLUG  => 'do-you-like-pizza',
			],
		];

		$question_ids = [];
		foreach ( $questions as $question ) {
			$model = Sensei_Import_Question_Model::from_source_array( 1, $question, new Sensei_Data_Port_Question_Schema(), $question_task );
			$model->sync_post();

			$question_ids[ $question[ Sensei_Data_Port_Question_Schema::COLUMN_ID ] ] = get_posts(
				[
					'post_type'      => 'question',
					'post_name__in'  => [ $question[ Sensei_Data_Port_Question_Schema::COLUMN_SLUG ] ],
					'posts_per_page' => 1,
					'post_status'    => 'any',
				]
			)[0]->ID;
		}

		// Create 2 lessons and quizzes and specify an order.
		$lessons = [
			[
				Sensei_Data_Port_Lesson_Schema::COLUMN_TITLE     => 'First lesson',
				Sensei_Data_Port_Lesson_Schema::COLUMN_SLUG      => 'first',
				Sensei_Data_Port_Lesson_Schema::COLUMN_QUESTIONS => 'id:ice-cream,id:pizza,id:burgers',
			],
			[
				Sensei_Data_Port_Lesson_Schema::COLUMN_TITLE     => 'Second lesson',
				Sensei_Data_Port_Lesson_Schema::COLUMN_SLUG      => 'second',
				Sensei_Data_Port_Lesson_Schema::COLUMN_QUESTIONS => 'id:burgers,id:pizza',
			],
		];

		$quiz_ids = [];
		foreach ( $lessons as $lesson ) {
			$model = Sensei_Import_Lesson_Model::from_source_array( 1, $lesson, new Sensei_Data_Port_Lesson_Schema(), $task );
			$model->sync_post();

			$quiz_ids[] = get_posts(
				[
					'post_type'      => 'quiz',
					'title'          => $lesson[ Sensei_Data_Port_Lesson_Schema::COLUMN_TITLE ],
					'posts_per_page' => 1,
					'post_status'    => 'any',
				]
			)[0]->ID;
		}

		// Assert that the correct order meta values are stored.
		$first_quiz_order  = [ (string) $question_ids['ice-cream'], (string) $question_ids['pizza'], (string) $question_ids['burgers'] ];
		$second_quiz_order = [ (string) $question_ids['burgers'], (string) $question_ids['pizza'] ];

		$this->assertEquals( $first_quiz_order, get_post_meta( $quiz_ids[0], '_question_order', true ) );
		$this->assertEquals( $second_quiz_order, get_post_meta( $quiz_ids[1], '_question_order', true ) );
		$this->assertEquals( [ $quiz_ids[0], $quiz_ids[1] ], get_post_meta( $question_ids['pizza'], '_quiz_id' ) );
		$this->assertEquals( [ $quiz_ids[0] ], get_post_meta( $question_ids['ice-cream'], '_quiz_id' ) );
		$this->assertEquals( $quiz_ids[0] . '000' . 3, get_post_meta( $question_ids['burgers'], '_quiz_question_order' . $quiz_ids[0], true ) );
		$this->assertEquals( $quiz_ids[1] . '000' . 1, get_post_meta( $question_ids['burgers'], '_quiz_question_order' . $quiz_ids[1], true ) );

		// Update the order in one of the quizzes.
		$model = Sensei_Import_Lesson_Model::from_source_array(
			1,
			[
				Sensei_Data_Port_Lesson_Schema::COLUMN_SLUG      => 'first',
				Sensei_Data_Port_Lesson_Schema::COLUMN_QUESTIONS => 'id:pizza,id:ice-cream,id:burgers',
				Sensei_Data_Port_Lesson_Schema::COLUMN_TITLE     => 'First lesson',
			],
			new Sensei_Data_Port_Lesson_Schema(),
			$task
		);
		$model->sync_post();

		// Assert that the correct order meta values are updated correctly.
		$quiz_order = [ (string) $question_ids['pizza'], (string) $question_ids['ice-cream'], (string) $question_ids['burgers'] ];

		$this->assertEquals( $quiz_order, get_post_meta( $quiz_ids[0], '_question_order', true ) );
		$this->assertEquals( $quiz_ids[0] . '000' . 3, get_post_meta( $question_ids['burgers'], '_quiz_question_order' . $quiz_ids[0], true ) );
		$this->assertEquals( $quiz_ids[0] . '000' . 1, get_post_meta( $question_ids['pizza'], '_quiz_question_order' . $quiz_ids[0], true ) );
	}
}
