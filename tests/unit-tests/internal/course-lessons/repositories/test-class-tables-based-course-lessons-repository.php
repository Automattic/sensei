<?php

namespace SenseiTest\Internal\Course_Lessons\Repositories;

use Sensei\Internal\Course_Lessons\Models\Course_Lesson;
use Sensei\Internal\Course_Lessons\Repositories\Tables_Based_Course_Lessons_Repository;

/**
 * Tests for Tables_Based_Course_Lessons_Repository.
 *
 * @covers \Sensei\Internal\Course_Lessons\Repositories\Tables_Based_Course_Lessons_Repository
 */
class Tables_Based_Course_Lessons_Repository_Test extends \WP_UnitTestCase {
	/**
	 * Repository under test.
	 *
	 * @var Tables_Based_Course_Lessons_Repository
	 */
	private $repository;

	public function setUp(): void {
		global $wpdb;

		parent::setUp();
		$this->repository = new Tables_Based_Course_Lessons_Repository( $wpdb );
	}

	/**
	 * A new row stores every column of the course lesson, with NULL for a missing order.
	 *
	 * @covers \Sensei\Internal\Course_Lessons\Repositories\Tables_Based_Course_Lessons_Repository::save
	 */
	public function testSave_NoRowForTheLessonInTheCourse_InsertsIt(): void {
		/* Act. */
		$this->repository->save( new Course_Lesson( 10, 20, 30, null, 40 ) );

		/* Assert. */
		$rows = $this->get_rows( 20 );
		self::assertCount( 1, $rows, 'One row should be inserted.' );
		self::assertSame(
			array(
				'course_id'              => '10',
				'lesson_id'              => '20',
				'module_term_id'         => '30',
				'lesson_order'           => null,
				'prerequisite_lesson_id' => '40',
			),
			$this->without_id_and_dates( $rows[0] ),
			'The row should hold the course lesson, with NULL for a missing order.'
		);
	}

	/**
	 * Saving an existing (course, lesson) pair again updates the row in place: its id must never change,
	 * because the oldest course of a lesson is the row with the lowest id.
	 *
	 * @covers \Sensei\Internal\Course_Lessons\Repositories\Tables_Based_Course_Lessons_Repository::save
	 */
	public function testSave_RowForTheLessonInTheCourseExists_UpdatesItKeepingItsId(): void {
		/* Arrange. */
		$this->repository->save( new Course_Lesson( 10, 20, 30, null, 40 ) );
		$id = $this->get_rows( 20 )[0]['id'];

		/* Act. */
		$this->repository->save( new Course_Lesson( 10, 20, 0, 3, 0 ) );

		/* Assert. */
		$rows = $this->get_rows( 20 );
		self::assertCount( 1, $rows, 'The row should be updated, not duplicated.' );
		self::assertSame( $id, $rows[0]['id'], 'The row id should not change.' );
		self::assertSame(
			array(
				'course_id'              => '10',
				'lesson_id'              => '20',
				'module_term_id'         => '0',
				'lesson_order'           => '3',
				'prerequisite_lesson_id' => '0',
			),
			$this->without_id_and_dates( $rows[0] ),
			'The row should hold the new values.'
		);
	}

	/**
	 * The installer creates the table on a version bump only, so a write before that must do nothing
	 * instead of raising a database error.
	 *
	 * @covers \Sensei\Internal\Course_Lessons\Repositories\Tables_Based_Course_Lessons_Repository::save
	 */
	public function testSave_TableMissing_RaisesNoDatabaseError(): void {
		/* Arrange. */
		$missing_wpdb = $this->get_wpdb_without_table();
		$repository   = new Tables_Based_Course_Lessons_Repository( $missing_wpdb );

		/* Act. */
		$repository->save( new Course_Lesson( 10, 20 ) );

		/* Assert. */
		self::assertSame( '', $missing_wpdb->last_error );
	}

	/**
	 * Deleting the rows of a lesson keeps the row of the given course only, and the rows of other lessons.
	 *
	 * @dataProvider providerDeleteForLesson
	 * @covers \Sensei\Internal\Course_Lessons\Repositories\Tables_Based_Course_Lessons_Repository::delete_for_lesson
	 *
	 * @param int|null $except_course_id Course to keep.
	 * @param int[]    $expected_courses Courses left in the table for the lesson.
	 */
	public function testDeleteForLesson_LessonInTwoCourses_DeletesTheRowsExceptTheGivenCourse( ?int $except_course_id, array $expected_courses ): void {
		/* Arrange. */
		$this->repository->save( new Course_Lesson( 10, 20 ) );
		$this->repository->save( new Course_Lesson( 11, 20 ) );
		$this->repository->save( new Course_Lesson( 10, 21 ) );

		/* Act. */
		$this->repository->delete_for_lesson( 20, $except_course_id );

		/* Assert. */
		self::assertSame( $expected_courses, array_map( 'intval', array_column( $this->get_rows( 20 ), 'course_id' ) ), 'Only the given course should keep its row.' );
		self::assertCount( 1, $this->get_rows( 21 ), 'Other lessons should keep their rows.' );
	}

	/**
	 * Courses to keep and the courses left for the lesson.
	 *
	 * @return array
	 */
	public function providerDeleteForLesson(): array {
		return array(
			'no course kept'  => array( null, array() ),
			'one course kept' => array( 11, array( 11 ) ),
		);
	}

	/**
	 * The installer creates the table on a version bump only, so a delete before that must do nothing
	 * instead of raising a database error.
	 *
	 * @covers \Sensei\Internal\Course_Lessons\Repositories\Tables_Based_Course_Lessons_Repository::delete_for_lesson
	 */
	public function testDeleteForLesson_TableMissing_RaisesNoDatabaseError(): void {
		/* Arrange. */
		$missing_wpdb = $this->get_wpdb_without_table();
		$repository   = new Tables_Based_Course_Lessons_Repository( $missing_wpdb );

		/* Act. */
		$repository->delete_for_lesson( 20 );

		/* Assert. */
		self::assertSame( '', $missing_wpdb->last_error );
	}

	/**
	 * Get a database object whose prefix has no course lessons table.
	 *
	 * @return \wpdb
	 */
	private function get_wpdb_without_table(): \wpdb {
		global $wpdb;

		$missing_wpdb             = clone $wpdb;
		$missing_wpdb->prefix     = 'no_such_prefix_';
		$missing_wpdb->last_error = '';

		return $missing_wpdb;
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
			$wpdb->prepare( "SELECT * FROM {$wpdb->prefix}sensei_lms_course_lessons WHERE lesson_id = %d ORDER BY id", $lesson_id ),
			ARRAY_A
		);
	}

	/**
	 * Drop the columns whose value the test does not control.
	 *
	 * @param array $row A table row.
	 * @return array
	 */
	private function without_id_and_dates( array $row ): array {
		unset( $row['id'], $row['created_at'], $row['updated_at'] );

		return $row;
	}
}
