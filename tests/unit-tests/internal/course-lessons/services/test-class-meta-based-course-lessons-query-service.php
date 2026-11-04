<?php

namespace SenseiTest\Internal\Course_Lessons\Services;

use Sensei\Internal\Course_Lessons\Services\Meta_Based_Course_Lessons_Query_Service;
use WP_Query;

/**
 * Tests for Meta_Based_Course_Lessons_Query_Service.
 *
 * The service registered by Sensei is already hooked to `pre_get_posts`, so the tests run real queries.
 *
 * @covers \Sensei\Internal\Course_Lessons\Services\Meta_Based_Course_Lessons_Query_Service
 */
class Meta_Based_Course_Lessons_Query_Service_Test extends \WP_UnitTestCase {
	/**
	 * Sensei factory.
	 *
	 * @var \Sensei_Factory
	 */
	protected $factory;

	public function setUp(): void {
		parent::setUp();
		$this->factory = new \Sensei_Factory();
	}

	public function tearDown(): void {
		parent::tearDown();
		$this->factory->tearDown();
	}

	/**
	 * Initialising the service hooks it into the query preparation.
	 *
	 * @covers \Sensei\Internal\Course_Lessons\Services\Meta_Based_Course_Lessons_Query_Service::init
	 */
	public function testInit_WhenCalled_AddsAction(): void {
		/* Arrange. */
		$service = new Meta_Based_Course_Lessons_Query_Service();

		/* Act. */
		$service->init();

		/* Assert. */
		self::assertSame( 10, has_action( 'pre_get_posts', array( $service, 'filter_lessons_by_course' ) ) );
	}

	/**
	 * A lesson query without the `sensei_course` query var is left untouched and returns the lessons of every course.
	 *
	 * @covers \Sensei\Internal\Course_Lessons\Services\Meta_Based_Course_Lessons_Query_Service::filter_lessons_by_course
	 */
	public function testFilterLessonsByCourse_NoCourseQueryVarGiven_LeavesTheQueryUntouched(): void {
		/* Arrange. */
		$lesson_ids = array(
			$this->factory->lesson->create( array( 'meta_input' => array( '_lesson_course' => $this->factory->course->create() ) ) ),
			$this->factory->lesson->create( array( 'meta_input' => array( '_lesson_course' => $this->factory->course->create() ) ) ),
		);

		/* Act. */
		$actual = ( new WP_Query(
			array(
				'post_type'      => 'lesson',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'orderby'        => 'ID',
				'order'          => 'ASC',
			)
		) )->posts;

		/* Assert. */
		self::assertSame( $lesson_ids, $actual );
	}

	/**
	 * A lesson query with the `sensei_course` query var returns the lessons of that course only.
	 *
	 * @covers \Sensei\Internal\Course_Lessons\Services\Meta_Based_Course_Lessons_Query_Service::filter_lessons_by_course
	 */
	public function testFilterLessonsByCourse_CourseQueryVarGiven_ReturnsTheLessonsOfThatCourse(): void {
		/* Arrange. */
		$course_id = $this->factory->course->create();
		$lesson_id = $this->factory->lesson->create( array( 'meta_input' => array( '_lesson_course' => $course_id ) ) );
		$this->factory->lesson->create( array( 'meta_input' => array( '_lesson_course' => $this->factory->course->create() ) ) );
		$this->factory->lesson->create();

		/* Act. */
		$actual = ( new WP_Query(
			array(
				'post_type'      => 'lesson',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'sensei_course'  => $course_id,
			)
		) )->posts;

		/* Assert. */
		self::assertSame( array( $lesson_id ), $actual );
	}

	/**
	 * A query for course 0 returns no lessons of real courses instead of every lesson.
	 *
	 * @covers \Sensei\Internal\Course_Lessons\Services\Meta_Based_Course_Lessons_Query_Service::filter_lessons_by_course
	 */
	public function testFilterLessonsByCourse_ZeroCourseGiven_ReturnsNoLessons(): void {
		/* Arrange. */
		$this->factory->lesson->create( array( 'meta_input' => array( '_lesson_course' => $this->factory->course->create() ) ) );

		/* Act. */
		$actual = ( new WP_Query(
			array(
				'post_type'      => 'lesson',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'sensei_course'  => 0,
			)
		) )->posts;

		/* Assert. */
		self::assertSame( array(), $actual );
	}

	/**
	 * The course condition is added to the query's own meta clauses instead of replacing them.
	 *
	 * @covers \Sensei\Internal\Course_Lessons\Services\Meta_Based_Course_Lessons_Query_Service::filter_lessons_by_course
	 */
	public function testFilterLessonsByCourse_QueryWithOwnMetaClauseGiven_KeepsThatClause(): void {
		/* Arrange. */
		$course_id      = $this->factory->course->create();
		$preview_lesson = $this->factory->lesson->create(
			array(
				'meta_input' => array(
					'_lesson_course'  => $course_id,
					'_lesson_preview' => 'preview',
				),
			)
		);
		$this->factory->lesson->create( array( 'meta_input' => array( '_lesson_course' => $course_id ) ) );

		/* Act. */
		$actual = ( new WP_Query(
			array(
				'post_type'      => 'lesson',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'sensei_course'  => $course_id,
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Test fixture.
					array(
						'key'   => '_lesson_preview',
						'value' => 'preview',
					),
				),
			)
		) )->posts;

		/* Assert. */
		self::assertSame( array( $preview_lesson ), $actual );
	}
}
