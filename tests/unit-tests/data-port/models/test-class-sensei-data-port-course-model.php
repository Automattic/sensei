<?php
/**
 * This file contains the Sensei_Import_Course_Model_Test class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tests for Sensei_Import_Course_Model class.
 *
 * @group data-port
 */
class Sensei_Import_Course_Model_Test extends WP_UnitTestCase {
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
		$course_id = $this->factory->course->create(
			[
				'post_name' => 'the-last-course',
			]
		);
		$job       = Sensei_Import_Job::create( 'test', 0 );
		$task      = $this->getMockBuilder( Sensei_Import_Courses::class )
						->setConstructorArgs( [ $job ] )
						->setMethods( [ 'add_prerequisite_task' ] )
						->getMock();

		$data_a = [
			Sensei_Data_Port_Course_Schema::COLUMN_ID    => '1234',
			Sensei_Data_Port_Course_Schema::COLUMN_TITLE => 'Course title a',
			Sensei_Data_Port_Course_Schema::COLUMN_SLUG  => 'the-last-course',
			Sensei_Data_Port_Course_Schema::COLUMN_PREREQUISITE => 'slug:a-prereq-course',
		];

		$data_b = [
			Sensei_Data_Port_Course_Schema::COLUMN_ID    => '1235',
			Sensei_Data_Port_Course_Schema::COLUMN_TITLE => 'Course title b',
			Sensei_Data_Port_Course_Schema::COLUMN_SLUG  => 'the-very-last-course',
			Sensei_Data_Port_Course_Schema::COLUMN_PREREQUISITE => '',
		];

		$task->expects( $this->once() )
			->method( 'add_prerequisite_task' )
			->with(
				$this->equalTo( $course_id ),
				$this->equalTo( 'slug:a-prereq-course' ),
				$this->equalTo( 1 )
			);

		$model_a = Sensei_Import_Course_Model::from_source_array( 1, $data_a, new Sensei_Data_Port_Course_Schema(), $task );
		$model_a->sync_post();

		$model_b = Sensei_Import_Course_Model::from_source_array( 2, $data_b, new Sensei_Data_Port_Course_Schema(), $task );
		$model_b->sync_post();
	}

	/**
	 * Returns an array with the data used by the tests. Each element is an array of line input data and expected
	 * output following the format of Sensei_Data_Port_Course_Model::data.
	 *
	 * The first and second elements of the array, refer to the same course and are used in the test scenario which
	 * creates a post and then updates it.
	 */
	public function lineData() {
		return [
			[
				[
					Sensei_Data_Port_Course_Schema::COLUMN_ID               => '<tag>id</tag>',
					Sensei_Data_Port_Course_Schema::COLUMN_TITLE            => 'Course <randomtag>title</randomtag>',
					Sensei_Data_Port_Course_Schema::COLUMN_SLUG             => '<randomtag>slug</randomtag>',
					Sensei_Data_Port_Course_Schema::COLUMN_DESCRIPTION      => '<randomtag>description</randomtag>',
					Sensei_Data_Port_Course_Schema::COLUMN_EXCERPT          => '<randomtag>excerpt</randomtag>',
					Sensei_Data_Port_Course_Schema::COLUMN_TEACHER_USERNAME => '<p>username@</p>',
					Sensei_Data_Port_Course_Schema::COLUMN_TEACHER_EMAIL    => 'em\<ail#@host.com',
					Sensei_Data_Port_Course_Schema::COLUMN_MODULES          => '<randomtag>   First,Second   </randomtag>',
					Sensei_Data_Port_Course_Schema::COLUMN_PREREQUISITE     => '<randomtag>prerequisite</randomtag>',
					Sensei_Data_Port_Course_Schema::COLUMN_FEATURED         => 'true',
					Sensei_Data_Port_Course_Schema::COLUMN_CATEGORIES       => '<randomtag>   First,Second   </randomtag>',
					Sensei_Data_Port_Course_Schema::COLUMN_IMAGE            => 'localfilename.png',
					Sensei_Data_Port_Course_Schema::COLUMN_VIDEO            => '<randomtag>video</randomtag>',
					Sensei_Data_Port_Course_Schema::COLUMN_NOTIFICATIONS    => '<randomtag>notifications</randomtag>',
				],
				[
					Sensei_Data_Port_Course_Schema::COLUMN_ID               => 'id',
					Sensei_Data_Port_Course_Schema::COLUMN_TITLE            => 'Course title',
					Sensei_Data_Port_Course_Schema::COLUMN_SLUG             => 'slug',
					Sensei_Data_Port_Course_Schema::COLUMN_DESCRIPTION      => 'description',
					Sensei_Data_Port_Course_Schema::COLUMN_EXCERPT          => 'excerpt',
					Sensei_Data_Port_Course_Schema::COLUMN_TEACHER_USERNAME => 'username@',
					Sensei_Data_Port_Course_Schema::COLUMN_TEACHER_EMAIL    => 'email#@host.com',
					Sensei_Data_Port_Course_Schema::COLUMN_MODULES          => 'First,Second',
					Sensei_Data_Port_Course_Schema::COLUMN_PREREQUISITE     => 'prerequisite',
					Sensei_Data_Port_Course_Schema::COLUMN_FEATURED         => true,
					Sensei_Data_Port_Course_Schema::COLUMN_CATEGORIES       => 'First,Second',
					Sensei_Data_Port_Course_Schema::COLUMN_IMAGE            => 'localfilename.png',
					Sensei_Data_Port_Course_Schema::COLUMN_VIDEO            => 'video',
					Sensei_Data_Port_Course_Schema::COLUMN_NOTIFICATIONS    => null,
				],
			],
			[
				[
					Sensei_Data_Port_Course_Schema::COLUMN_ID               => 'id',
					Sensei_Data_Port_Course_Schema::COLUMN_TITLE            => 'Updated title',
					Sensei_Data_Port_Course_Schema::COLUMN_SLUG             => 'slug',
					Sensei_Data_Port_Course_Schema::COLUMN_DESCRIPTION      => '<p>Updated description</p>',
					Sensei_Data_Port_Course_Schema::COLUMN_EXCERPT          => '<p>Updated excerpt</p>',
					Sensei_Data_Port_Course_Schema::COLUMN_TEACHER_USERNAME => 'otheruser',
					Sensei_Data_Port_Course_Schema::COLUMN_TEACHER_EMAIL    => 'otheremail@host.com',
					Sensei_Data_Port_Course_Schema::COLUMN_MODULES          => 'Second,First',
					Sensei_Data_Port_Course_Schema::COLUMN_PREREQUISITE     => 'Updated prerequisite',
					Sensei_Data_Port_Course_Schema::COLUMN_FEATURED         => 'false',
					Sensei_Data_Port_Course_Schema::COLUMN_CATEGORIES       => 'First,Third',
					Sensei_Data_Port_Course_Schema::COLUMN_IMAGE            => 'updatedfilename.png',
					Sensei_Data_Port_Course_Schema::COLUMN_VIDEO            => 'Updated video',
					Sensei_Data_Port_Course_Schema::COLUMN_NOTIFICATIONS    => 'false',
				],
				[
					Sensei_Data_Port_Course_Schema::COLUMN_ID               => 'id',
					Sensei_Data_Port_Course_Schema::COLUMN_TITLE            => 'Updated title',
					Sensei_Data_Port_Course_Schema::COLUMN_SLUG             => 'slug',
					Sensei_Data_Port_Course_Schema::COLUMN_DESCRIPTION      => '<p>Updated description</p>',
					Sensei_Data_Port_Course_Schema::COLUMN_EXCERPT          => '<p>Updated excerpt</p>',
					Sensei_Data_Port_Course_Schema::COLUMN_TEACHER_USERNAME => 'otheruser',
					Sensei_Data_Port_Course_Schema::COLUMN_TEACHER_EMAIL    => 'otheremail@host.com',
					Sensei_Data_Port_Course_Schema::COLUMN_MODULES          => 'Second,First',
					Sensei_Data_Port_Course_Schema::COLUMN_PREREQUISITE     => 'Updated prerequisite',
					Sensei_Data_Port_Course_Schema::COLUMN_FEATURED         => false,
					Sensei_Data_Port_Course_Schema::COLUMN_CATEGORIES       => 'First,Third',
					Sensei_Data_Port_Course_Schema::COLUMN_IMAGE            => 'updatedfilename.png',
					Sensei_Data_Port_Course_Schema::COLUMN_VIDEO            => 'Updated video',
					Sensei_Data_Port_Course_Schema::COLUMN_NOTIFICATIONS    => false,
				],
			],
			[
				[
					Sensei_Data_Port_Course_Schema::COLUMN_TITLE       => 'Course <p>title</p>',
					Sensei_Data_Port_Course_Schema::COLUMN_DESCRIPTION => '<p>description</p>',
					Sensei_Data_Port_Course_Schema::COLUMN_EXCERPT     => '<p>excerpt</p>',
					Sensei_Data_Port_Course_Schema::COLUMN_IMAGE       => 'http://randomurl<>.com/nice%20image.png',
					Sensei_Data_Port_Course_Schema::COLUMN_VIDEO       => '<video autoplay>video</video>',
				],
				[
					Sensei_Data_Port_Course_Schema::COLUMN_TITLE         => 'Course <p>title</p>',
					Sensei_Data_Port_Course_Schema::COLUMN_DESCRIPTION   => '<p>description</p>',
					Sensei_Data_Port_Course_Schema::COLUMN_EXCERPT       => '<p>excerpt</p>',
					Sensei_Data_Port_Course_Schema::COLUMN_FEATURED      => false,
					Sensei_Data_Port_Course_Schema::COLUMN_IMAGE         => 'http://randomurl.com/nice%20image.png',
					Sensei_Data_Port_Course_Schema::COLUMN_VIDEO         => '<video autoplay>video</video>',
					Sensei_Data_Port_Course_Schema::COLUMN_NOTIFICATIONS => false,
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
		$task          = new Sensei_Import_Courses( Sensei_Import_Job::create( 'test', 0 ) );
		$model         = Sensei_Import_Course_Model::from_source_array( 1, $input_line, new Sensei_Data_Port_Course_Schema(), $task );
		$tested_fields = [
			Sensei_Data_Port_Course_Schema::COLUMN_ID,
			Sensei_Data_Port_Course_Schema::COLUMN_TITLE,
			Sensei_Data_Port_Course_Schema::COLUMN_SLUG,
			Sensei_Data_Port_Course_Schema::COLUMN_DESCRIPTION,
			Sensei_Data_Port_Course_Schema::COLUMN_EXCERPT,
			Sensei_Data_Port_Course_Schema::COLUMN_TEACHER_USERNAME,
			Sensei_Data_Port_Course_Schema::COLUMN_TEACHER_EMAIL,
			Sensei_Data_Port_Course_Schema::COLUMN_MODULES,
			Sensei_Data_Port_Course_Schema::COLUMN_PREREQUISITE,
			Sensei_Data_Port_Course_Schema::COLUMN_FEATURED,
			Sensei_Data_Port_Course_Schema::COLUMN_CATEGORIES,
			Sensei_Data_Port_Course_Schema::COLUMN_IMAGE,
			Sensei_Data_Port_Course_Schema::COLUMN_VIDEO,
			Sensei_Data_Port_Course_Schema::COLUMN_NOTIFICATIONS,
		];

		foreach ( $tested_fields as $tested_field ) {
			if ( isset( $expected_model_content[ $tested_field ] ) ) {
				$this->assertEquals( $expected_model_content[ $tested_field ], $model->get_value( $tested_field ), "Field {$tested_field} did not match the expected value" );
			}
		}
	}

	/**
	 * Tests that error data has the correct values.
	 */
	public function testErrorDataAreGeneratedCorrectly() {
		$model      = Sensei_Import_Course_Model::from_source_array( 1, $this->lineData()[0][0], new Sensei_Data_Port_Course_Schema() );
		$error_data = $model->get_error_data( [ 'line' => 1 ] );

		$expected = [
			'line'        => 1,
			'entry_id'    => 'id',
			'entry_title' => 'Course title',
			'type'        => 'course',
		];
		$this->assertEquals( $expected, $error_data );
	}

	/**
	 * Tests creating a course and the updating all its values.
	 */
	public function testCourseIsInsertedAndUpdated() {
		$thumbnail_id = $this->factory->attachment->create( [ 'file' => 'localfilename.png' ] );
		$task         = new Sensei_Import_Courses( Sensei_Import_Job::create( 'test', 0 ) );
		$model        = Sensei_Import_Course_Model::from_source_array( 1, $this->lineData()[0][0], new Sensei_Data_Port_Course_Schema(), $task );
		$result       = $model->sync_post();

		$this->assertTrue( $result );

		$created_post = get_posts(
			[
				'post_type'      => 'course',
				'post_name__in'  => [ 'slug' ],
				'posts_per_page' => 1,
				'post_status'    => 'any',
			]
		)[0];

		$this->verify_course( $created_post, $this->lineData()[0][1], $thumbnail_id );

		$thumbnail_id = $this->factory->attachment->create( [ 'file' => 'updatedfilename.png' ] );
		$task         = new Sensei_Import_Courses( Sensei_Import_Job::create( 'test', 0 ) );
		$model        = Sensei_Import_Course_Model::from_source_array( 1, $this->lineData()[1][0], new Sensei_Data_Port_Course_Schema(), $task );
		$result       = $model->sync_post();

		$this->assertTrue( $result );

		$updated_post = get_posts(
			[
				'post_type'      => 'course',
				'post_name__in'  => [ 'slug' ],
				'posts_per_page' => 1,
				'post_status'    => 'any',
			]
		)[0];

		$this->assertEquals( $created_post->ID, $updated_post->ID );

		$this->verify_course( $updated_post, $this->lineData()[1][1], $thumbnail_id );
	}

	/**
	 * Helper method to verify that all the fields of a course are correct.
	 *
	 * @param WP_Post $course       The course to verify.
	 * @param array   $line_data     An array which has all the expected values. It follows the format of Sensei_Data_Port_Course_Model::data.
	 * @param int     $thumbnail_id  The post id of the image attachment.
	 */
	private function verify_course( WP_Post $course, $line_data, $thumbnail_id = 0 ) {
		$teacher = get_user_by( 'login', $line_data[ Sensei_Data_Port_Course_Schema::COLUMN_TEACHER_USERNAME ] );

		// Assert that post columns have the correct values.
		$this->assertEquals( $teacher->ID, $course->post_author );
		$this->assertEquals( $line_data[ Sensei_Data_Port_Course_Schema::COLUMN_TITLE ], $course->post_title );
		$this->assertEquals( $line_data[ Sensei_Data_Port_Course_Schema::COLUMN_SLUG ], $course->post_name );
		$this->assertEquals( $line_data[ Sensei_Data_Port_Course_Schema::COLUMN_DESCRIPTION ], $course->post_content );
		$this->assertEquals( $line_data[ Sensei_Data_Port_Course_Schema::COLUMN_EXCERPT ], $course->post_excerpt );

		// Assert that post meta have the correct values.
		if ( true === $line_data[ Sensei_Data_Port_Course_Schema::COLUMN_FEATURED ] ) {
			$this->assertEquals( 'featured', get_post_meta( $course->ID, '_course_featured', true ) );
		} else {
			$this->assertEmpty( get_post_meta( $course->ID, '_course_featured', true ) );
		}

		$this->assertEquals( $line_data[ Sensei_Data_Port_Course_Schema::COLUMN_VIDEO ], get_post_meta( $course->ID, '_course_video_embed', true ) );
		$this->assertEquals( $line_data[ Sensei_Data_Port_Course_Schema::COLUMN_NOTIFICATIONS ], get_post_meta( $course->ID, 'disable_notification', true ) );

		// Calculate the module order and compare it with the post's one.
		$module_names           = Sensei_Data_Port_Utilities::split_list_safely( $line_data[ Sensei_Data_Port_Course_Schema::COLUMN_MODULES ], true );
		$expected_modules_order = array_map(
			function( $module_name ) use ( $teacher ) {
				return (string) Sensei_Data_Port_Utilities::get_term( $module_name, 'module', $teacher->ID )->term_id;
			},
			$module_names
		);
		$this->assertEquals( $expected_modules_order, get_post_meta( $course->ID, '_module_order', true ) );

		// Calculate the category ids and assert that they are the same with the post's.
		$actual_category_ids   = wp_get_object_terms( $course->ID, 'course-category', [ 'fields' => 'ids' ] );
		$category_names        = Sensei_Data_Port_Utilities::split_list_safely( $line_data[ Sensei_Data_Port_Course_Schema::COLUMN_CATEGORIES ], true );
		$expected_category_ids = array_map(
			function( $module_name ) {
				return (string) Sensei_Data_Port_Utilities::get_term( $module_name, 'course-category' )->term_id;
			},
			$category_names
		);

		$this->assertCount( count( $expected_category_ids ), $actual_category_ids );
		foreach ( $expected_category_ids as $expected_category_id ) {
			$this->assertContains( $expected_category_id, $actual_category_ids );
		}

		if ( 0 !== $thumbnail_id ) {
			$this->assertEquals( $thumbnail_id, get_post_meta( $course->ID, '_thumbnail_id', true ) );
		}
	}

	/**
	 * Tests that a warning is logged when the attachment does not exist.
	 */
	public function testSyncPostWarnsWhenAttachmentNotFound() {
		$job    = Sensei_Import_Job::create( 'test', 0 );
		$task   = new Sensei_Import_Courses( $job );
		$model  = Sensei_Import_Course_Model::from_source_array( 1, $this->lineData()[0][0], new Sensei_Data_Port_Course_Schema(), $task );
		$result = $model->sync_post();

		$this->assertTrue( $result );
		$this->assertJobHasLogEntry( $job, 'No attachment with the specified file name was found.' );
	}
}
