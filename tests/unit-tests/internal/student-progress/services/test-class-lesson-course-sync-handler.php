<?php
/**
 * File containing the Lesson_Course_Sync_Handler_Test class.
 *
 * @package sensei
 */

namespace SenseiTest\Internal\Student_Progress\Services;

use Sensei\Internal\Student_Progress\Services\Lesson_Course_Sync_Handler;

/**
 * Tests for Lesson_Course_Sync_Handler.
 *
 * @covers \Sensei\Internal\Student_Progress\Services\Lesson_Course_Sync_Handler
 */
class Lesson_Course_Sync_Handler_Test extends \WP_UnitTestCase {
	/**
	 * Sensei factory.
	 *
	 * @var \Sensei_Factory
	 */
	protected $factory;

	/**
	 * Set up before each test.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->factory = new \Sensei_Factory();
	}

	/**
	 * Tear down after each test.
	 */
	public function tearDown(): void {
		parent::tearDown();
		$this->factory->tearDown();
	}

	/**
	 * Tests that init registers the postmeta hooks.
	 */
	public function testInit_WhenCalled_AddsHooks(): void {
		/* Arrange. */
		global $wpdb;
		$handler = new Lesson_Course_Sync_Handler( $wpdb );

		/* Act. */
		$handler->init();

		/* Assert. */
		self::assertSame( 10, has_action( 'added_post_meta', array( $handler, 'handle_meta_change' ) ), 'added_post_meta hook should be registered.' );
		self::assertSame( 10, has_action( 'updated_post_meta', array( $handler, 'handle_meta_change' ) ), 'updated_post_meta hook should be registered.' );
		self::assertSame( 10, has_action( 'deleted_post_meta', array( $handler, 'handle_meta_delete' ) ), 'deleted_post_meta hook should be registered.' );

		remove_action( 'added_post_meta', array( $handler, 'handle_meta_change' ), 10 );
		remove_action( 'updated_post_meta', array( $handler, 'handle_meta_change' ), 10 );
		remove_action( 'deleted_post_meta', array( $handler, 'handle_meta_delete' ), 10 );
	}

	/**
	 * Tests that updating _lesson_course updates parent_post_id on every matching row.
	 */
	public function testHandleMetaChange_LessonCourseUpdated_UpdatesParentPostIdOnAllRows(): void {
		/* Arrange. */
		global $wpdb;
		$lesson_id  = $this->factory->lesson->create();
		$old_course = 100;
		$new_course = 200;

		$row_one = $this->insert_progress_row( $lesson_id, 11, $old_course );
		$row_two = $this->insert_progress_row( $lesson_id, 22, $old_course );

		$handler = new Lesson_Course_Sync_Handler( $wpdb );

		/* Act. */
		$handler->handle_meta_change( 1, $lesson_id, '_lesson_course', $new_course );

		/* Assert. */
		self::assertSame( $new_course, $this->get_parent_post_id( $row_one ), 'First user row should be updated to the new course.' );
		self::assertSame( $new_course, $this->get_parent_post_id( $row_two ), 'Second user row should be updated to the new course.' );
	}

	/**
	 * Tests that updating _lesson_course on a non-lesson post does not touch progress rows.
	 */
	public function testHandleMetaChange_NonLessonPost_DoesNotUpdateRows(): void {
		/* Arrange. */
		global $wpdb;
		$course_id  = $this->factory->course->create();
		$lesson_id  = $this->factory->lesson->create();
		$old_course = 100;
		$row        = $this->insert_progress_row( $lesson_id, 11, $old_course );

		$handler = new Lesson_Course_Sync_Handler( $wpdb );

		/* Act. */
		$handler->handle_meta_change( 1, $course_id, '_lesson_course', 999 );

		/* Assert. */
		self::assertSame( $old_course, $this->get_parent_post_id( $row ) );
	}

	/**
	 * Tests that updates to unrelated meta keys do not touch progress rows.
	 */
	public function testHandleMetaChange_UnrelatedMetaKey_DoesNotUpdateRows(): void {
		/* Arrange. */
		global $wpdb;
		$lesson_id  = $this->factory->lesson->create();
		$old_course = 100;
		$row        = $this->insert_progress_row( $lesson_id, 11, $old_course );

		$handler = new Lesson_Course_Sync_Handler( $wpdb );

		/* Act. */
		$handler->handle_meta_change( 1, $lesson_id, '_some_other_meta', 999 );

		/* Assert. */
		self::assertSame( $old_course, $this->get_parent_post_id( $row ) );
	}

	/**
	 * Tests that quiz progress rows (which use parent_post_id for the lesson) are left alone.
	 */
	public function testHandleMetaChange_DoesNotTouchQuizRows(): void {
		/* Arrange. */
		global $wpdb;
		$lesson_id = $this->factory->lesson->create();
		// Quiz row uses parent_post_id for the lesson — not the course — but we
		// match on type='lesson', so it must be left alone.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->insert(
			$wpdb->prefix . 'sensei_lms_progress',
			array(
				'post_id'        => 999,
				'user_id'        => 11,
				'parent_post_id' => $lesson_id,
				'type'           => 'quiz',
				'status'         => 'in-progress',
				'created_at'     => current_time( 'mysql' ),
				'updated_at'     => current_time( 'mysql' ),
			)
		);
		$quiz_row = (int) $wpdb->insert_id;

		// And a lesson row that should be updated.
		$lesson_row = $this->insert_progress_row( $lesson_id, 11, 100 );

		$handler = new Lesson_Course_Sync_Handler( $wpdb );

		/* Act. */
		$handler->handle_meta_change( 1, $lesson_id, '_lesson_course', 200 );

		/* Assert. */
		self::assertSame( 200, $this->get_parent_post_id( $lesson_row ), 'Lesson row should be updated to the new course.' );
		self::assertSame( $lesson_id, $this->get_parent_post_id( $quiz_row ), 'Quiz row should be left untouched.' );
	}

	/**
	 * Tests that a zero/invalid course value clears parent_post_id to NULL.
	 */
	public function testHandleMetaChange_ZeroOrInvalidValue_SetsParentPostIdToNull(): void {
		/* Arrange. */
		global $wpdb;
		$lesson_id = $this->factory->lesson->create();
		$row       = $this->insert_progress_row( $lesson_id, 11, 100 );

		$handler = new Lesson_Course_Sync_Handler( $wpdb );

		/* Act. */
		$handler->handle_meta_change( 1, $lesson_id, '_lesson_course', 0 );

		/* Assert. */
		self::assertNull( $this->get_parent_post_id( $row ) );
	}

	/**
	 * Tests that deleting _lesson_course clears parent_post_id to NULL.
	 */
	public function testHandleMetaDelete_LessonCourseDeleted_SetsParentPostIdToNull(): void {
		/* Arrange. */
		global $wpdb;
		$lesson_id = $this->factory->lesson->create();
		$row       = $this->insert_progress_row( $lesson_id, 11, 100 );

		$handler = new Lesson_Course_Sync_Handler( $wpdb );

		/* Act. */
		$handler->handle_meta_delete( array( 1 ), $lesson_id, '_lesson_course' );

		/* Assert. */
		self::assertNull( $this->get_parent_post_id( $row ) );
	}

	/**
	 * Tests that calling update_post_meta() triggers the registered hook end-to-end.
	 */
	public function testHandleMetaChange_TriggersFromUpdatePostMeta_UpdatesRow(): void {
		/* Arrange. */
		global $wpdb;
		$lesson_id = $this->factory->lesson->create();
		$row       = $this->insert_progress_row( $lesson_id, 11, 100 );

		$handler = new Lesson_Course_Sync_Handler( $wpdb );
		$handler->init();

		/* Act. */
		update_post_meta( $lesson_id, '_lesson_course', 200 );

		/* Assert. */
		self::assertSame( 200, $this->get_parent_post_id( $row ) );

		remove_action( 'added_post_meta', array( $handler, 'handle_meta_change' ), 10 );
		remove_action( 'updated_post_meta', array( $handler, 'handle_meta_change' ), 10 );
		remove_action( 'deleted_post_meta', array( $handler, 'handle_meta_delete' ), 10 );
	}

	/**
	 * Insert a lesson progress row and return its ID.
	 *
	 * @param int $lesson_id Lesson ID (post_id column).
	 * @param int $user_id   User ID.
	 * @param int $course_id Parent course ID.
	 * @return int Inserted row ID.
	 */
	private function insert_progress_row( int $lesson_id, int $user_id, int $course_id ): int {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->insert(
			$wpdb->prefix . 'sensei_lms_progress',
			array(
				'post_id'        => $lesson_id,
				'user_id'        => $user_id,
				'parent_post_id' => $course_id,
				'type'           => 'lesson',
				'status'         => 'in-progress',
				'created_at'     => current_time( 'mysql' ),
				'updated_at'     => current_time( 'mysql' ),
			)
		);
		return (int) $wpdb->insert_id;
	}

	/**
	 * Read the parent_post_id of a progress row by ID.
	 *
	 * @param int $row_id Row ID.
	 * @return int|null
	 */
	private function get_parent_post_id( int $row_id ): ?int {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$value = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT parent_post_id FROM {$wpdb->prefix}sensei_lms_progress WHERE id = %d",
				$row_id
			)
		);
		return null === $value ? null : (int) $value;
	}
}
