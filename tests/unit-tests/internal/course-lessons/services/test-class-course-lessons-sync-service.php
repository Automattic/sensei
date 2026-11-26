<?php

namespace SenseiTest\Internal\Course_Lessons\Services;

/**
 * Tests for Course_Lessons_Sync_Service.
 *
 * The service registered by Sensei is already hooked to the meta actions (the test bootstrap turns the
 * `course_lessons_table` flag on), so the tests write real metas.
 *
 * @covers \Sensei\Internal\Course_Lessons\Services\Course_Lessons_Sync_Service
 */
class Course_Lessons_Sync_Service_Test extends \WP_UnitTestCase {
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
	 * A lesson created with its course meta gets the row of that course.
	 *
	 * @covers \Sensei\Internal\Course_Lessons\Services\Course_Lessons_Sync_Service::handle_meta_change
	 */
	public function testHandleMetaChange_LessonCreatedWithCourseMeta_InsertsItsRow(): void {
		/* Arrange. */
		$course_id = $this->factory->course->create();

		/* Act. */
		$lesson_id = $this->factory->lesson->create( array( 'meta_input' => array( '_lesson_course' => $course_id ) ) );

		/* Assert. */
		self::assertSame( array( (string) $course_id ), array_column( $this->get_rows( $lesson_id ), 'course_id' ) );
	}

	/**
	 * Moving a lesson to another course leaves it with the row of the new course only.
	 *
	 * @covers \Sensei\Internal\Course_Lessons\Services\Course_Lessons_Sync_Service::handle_meta_change
	 */
	public function testHandleMetaChange_CourseMetaChangedToAnotherCourse_ReplacesTheRow(): void {
		/* Arrange. */
		$old_course_id = $this->factory->course->create();
		$new_course_id = $this->factory->course->create();
		$lesson_id     = $this->factory->lesson->create( array( 'meta_input' => array( '_lesson_course' => $old_course_id ) ) );

		/* Act. */
		update_post_meta( $lesson_id, '_lesson_course', $new_course_id );

		/* Assert. */
		self::assertSame( array( (string) $new_course_id ), array_column( $this->get_rows( $lesson_id ), 'course_id' ) );
	}

	/**
	 * A lesson detached from its course has no row.
	 *
	 * @covers \Sensei\Internal\Course_Lessons\Services\Course_Lessons_Sync_Service::handle_meta_change
	 */
	public function testHandleMetaChange_CourseMetaDeleted_DeletesTheRow(): void {
		/* Arrange. */
		$course_id = $this->factory->course->create();
		$lesson_id = $this->factory->lesson->create( array( 'meta_input' => array( '_lesson_course' => $course_id ) ) );

		/* Act. */
		delete_post_meta( $lesson_id, '_lesson_course' );

		/* Assert. */
		self::assertSame( array(), $this->get_rows( $lesson_id ) );
	}

	/**
	 * Read the rows of a lesson.
	 *
	 * @param int $lesson_id Lesson ID.
	 * @return array[]
	 */
	private function get_rows( int $lesson_id ): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_results(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->prepare( "SELECT course_id FROM {$wpdb->prefix}sensei_lms_course_lessons WHERE lesson_id = %d ORDER BY id", $lesson_id ),
			ARRAY_A
		);
	}
}
