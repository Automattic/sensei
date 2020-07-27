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

		$terms = [
			$this->factory->term->create(
				[
					'taxonomy' => 'course-category',
					'name'     => 'Course Category Parent',
				]
			),
			$this->factory->term->create(
				[
					'taxonomy' => 'course-category',
					'name'     => 'Course Category Child',
				]
			),
		];
		$this->factory->term->add_post_terms( $course->ID, $terms[1], 'course-category', false );

		$post   = get_post( $course->ID );
		$result = $this->export();

		$this->assertEquals(
			'Course Category Parent > Course Category Child',
			$result[0]['categories']
		);

	}

	public function testModulesSerialized() {
	}

	/**
	 * Test that course details are exported correctly.
	 */
	public function testCourseExported() {
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

	public function testCourseMetaExported() {
	}

	/**
	 * Test to make sure prerequisites are exported correctly.
	 */
	public function testPrerequisiteExported() {
		$course_ids = $this->factory->course->create_many( 2 );
		add_post_meta( $course_ids[0], '_course_prerequisite', $course_ids[1] );

		$job  = Sensei_Export_Job::create( 'test', 0 );
		$task = new Sensei_Export_Courses( $job );
		$task->run();

		$result = self::read_csv( $job->get_file_path( 'course' ) );

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
		$key = array_search( $course_id, array_column( $result, 'id' ), true );
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
