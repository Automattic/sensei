<?php
/**
 * This file contains the Sensei_Export_Courses_Tests class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tests for Sensei_Export_Courses class.
 *
 * @group data-port
 */
class Sensei_Export_Courses_Tests extends WP_UnitTestCase {

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

	/**
	 * Test that course categories are exported correctly.
	 */
	public function testCategoriesSerialized() {
		$course = $this->factory->course->create_and_get();

		$terms = [
			$this->factory->term->create(
				[
					'taxonomy' => 'course-category',
					'name'     => 'Course Category \'Single\'',
				]
			),
			$this->factory->term->create(
				[
					'taxonomy' => 'course-category',
					'name'     => 'Course Category "Double"',
				]
			),
		];
		$this->factory->term->add_post_terms( $course->ID, $terms, 'course-category', false );

		$post   = get_post( $course->ID );
		$result = $this->export();

		$this->assertEquals(
			'Course Category \'Single\',Course Category "Double"',
			$result[0]['categories']
		);
	}

	/**
	 * Test that course category hierarchies are exported correctly.
	 */
	public function testHierarchicalCategoriesSerialized() {

		$course = $this->factory->course->create_and_get();

		$term =
			$this->factory->term->create(
				[
					'taxonomy' => 'course-category',
					'name'     => 'Course Category Child',
					'parent'   => $this->factory->term->create(
						[
							'taxonomy' => 'course-category',
							'name'     => 'Course Category Parent',
						]
					),
				]
			);

		$this->factory->term->add_post_terms( $course->ID, $term, 'course-category', false );

		$post   = get_post( $course->ID );
		$result = $this->export();

		$this->assertEquals(
			'Course Category Parent > Course Category Child',
			$result[0]['categories']
		);

	}

	public function testModulesExported() {
		$course = $this->factory->course->create_and_get();

		$terms = [
			$this->factory->term->create(
				[
					'taxonomy' => Sensei()->modules->taxonomy,
					'name'     => 'Module A',
				]
			),
			$this->factory->term->create(
				[
					'taxonomy' => Sensei()->modules->taxonomy,
					'name'     => 'Module B',
				]
			),
		];
		$this->factory->term->add_post_terms( $course->ID, $terms, Sensei()->modules->taxonomy, false );

		$post   = get_post( $course->ID );
		$result = $this->export();

		$this->assertEquals(
			'Module A,Module B',
			$result[0]['modules']
		);
	}

	/**
	 * Test that course details are exported correctly.
	 */
	public function testCourseContentExported() {
		$course = $this->factory->course->create_and_get();

		$result = $this->export();

		$this->assertArraySubset(
			[
				'id'          => $course->ID,
				'course'      => $course->post_title,
				'slug'        => $course->post_name,
				'description' => $course->post_content,
				'excerpt'     => $course->post_excerpt,
			],
			$result[0]
		);
	}

	/**
	 * Test that course details are exported correctly.
	 */
	public function testCourseImageExported() {
		$course = $this->factory->course->create_and_get();

		$thumbnail_id = $this->factory->attachment->create( [ 'file' => 'localfilename.png' ] );
		set_post_thumbnail( $course, $thumbnail_id );

		$result = $this->export();

		$this->assertArraySubset(
			[
				'image' => wp_get_attachment_image_url( $thumbnail_id ),
			],
			$result[0]
		);
	}

	public function testCourseTeacherExported() {
		$teacher = $this->factory->user->create_and_get(
			[
				'user_login' => 'sensei',
				'user_email' => 'testuser@senseilms.com',
			]
		);
		$this->factory->course->create_and_get( [ 'post_author' => $teacher->ID ] );

		$result = $this->export();

		$this->assertArraySubset(
			[
				'teacher username' => $teacher->display_name,
				'teacher email'    => $teacher->user_email,

			],
			$result[0]
		);
	}

	public function testCourseMetaExported() {

		$course = $this->factory->course->create_and_get();

		update_post_meta( $course->ID, '_course_featured', true );
		update_post_meta( $course->ID, '_course_video_embed', '<iframe>' );
		update_post_meta( $course->ID, 'disable_notification', false );

		$result = $this->export();

		$this->assertArraySubset(
			[
				'featured'      => '1',
				'video'         => '<iframe>',
				'notifications' => '1',
			],
			$result[0]
		);

	}

	/**
	 * Test to make sure prerequisites are exported correctly.
	 */
	public function testPrerequisiteExported() {
		$course_ids = $this->factory->course->create_many( 2 );
		add_post_meta( $course_ids[0], '_course_prerequisite', $course_ids[1] );

		$result = $this->export();

		$course_1 = self::get_by_id( $result, $course_ids[0] );
		$this->assertEquals( $course_ids[1], $course_1['prerequisite'] );
	}

	protected static function read_csv( $filename ) {
		$reader = new Sensei_Import_CSV_Reader( $filename, 0, 1000 );
		return $reader->read_lines();
	}

	/**
	 * Find a course line by ID.
	 *
	 * @param array $result    Result data.
	 * @param int   $course_id The course id.
	 *
	 * @return array The line for the course.
	 */
	protected static function get_by_id( array $result, $course_id ) {
		$key = array_search( strval( $course_id ), array_column( $result, 'id' ), true );
		return $result[ $key ];
	}

	/**
	 * Run the export job and read back the created CSV.
	 *
	 * @return array The exported data as read from the CSV file.
	 */
	public function export() {
		$job  = Sensei_Export_Job::create( 'test', 0 );
		$task = new Sensei_Export_Courses( $job );
		$task->run();

		return self::read_csv( $job->get_file_path( 'course' ) );
	}

}
