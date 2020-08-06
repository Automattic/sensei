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
class Sensei_Export_Courses_Tests extends Sensei_Export_Task_Tests {

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

		$thumbnail_id = $this->factory->attachment->create(
			[
				'file'           => 'course-img.png',
				'post_mime_type' => 'image/png',
			]
		);
		set_post_thumbnail( $course, $thumbnail_id );

		$result = $this->export();

		$this->assertArraySubset(
			[
				'image' => 'http://example.org/wp-content/uploads/course-img.png',
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

	public function testAllPostStatusCoursesExporterd() {
		$course_published = $this->factory->course->create( [ 'post_status' => 'publish' ] );
		$course_draft     = $this->factory->course->create( [ 'post_status' => 'draft' ] );

		$result = $this->export();

		$this->assertEqualSets( [ $course_published, $course_draft ], array_column( $result, 'id' ) );

	}

	protected function get_task_class() {
		return Sensei_Export_Courses::class;
	}
}
