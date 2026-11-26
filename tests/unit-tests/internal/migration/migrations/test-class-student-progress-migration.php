<?php

namespace SenseiTest\Internal\Migration\Migrations;

use Sensei\Internal\Migration\Migrations\Student_Progress_Migration;
use Sensei\Internal\Migration\Validations\Progress_Validation;
use Sensei\Internal\Student_Progress\Lesson_Progress\Repositories\Comments_Based_Lesson_Progress_Repository;
use Sensei\Internal\Student_Progress\Lesson_Progress\Repositories\Tables_Based_Lesson_Progress_Repository;
use Sensei\Internal\Student_Progress\Quiz_Progress\Repositories\Comments_Based_Quiz_Progress_Repository;
use Sensei\Internal\Student_Progress\Quiz_Progress\Repositories\Tables_Based_Quiz_Progress_Repository;
use Sensei\Internal\Student_Progress\Course_Progress\Repositories\Tables_Based_Course_Progress_Repository;
use Sensei_Factory;
use Sensei_Utils;

/**
 * Class Student_Progress_Migration_Test
 *
 * @covers \Sensei\Internal\Migration\Migrations\Student_Progress_Migration
 */
class Student_Progress_Migration_Test extends \WP_UnitTestCase {
	use \Sensei_HPPS_Helpers;

	/**
	 * Migration instance.
	 *
	 * @var \Sensei\Internal\Migration\Migrations\Student_Progress_Migration
	 */
	private $migration;

	protected $factory;

	protected function setUp(): void {
		parent::setUp();

		$this->migration = new Student_Progress_Migration();
		$this->factory   = new Sensei_Factory();

		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( 'TRUNCATE TABLE ' . $wpdb->prefix . 'sensei_lms_progress' );
	}

	protected function tearDown(): void {
		$this->restore_progress_storage_mode();

		parent::tearDown();
	}

	public function testGetErrors_MigrationDidntRun_ReturnsEmptyArray(): void {
		/* Act. */
		$actual = $this->migration->get_errors();

		/* Assert. */
		$this->assertEmpty( $actual );
	}

	public function testRun_NoCommentsExist_ReturnsZero(): void {
		/* Arrange. */
		$expected = 0;

		/* Act. */
		$actual = $this->migration->run( $dry_run = false ); // phpcs:ignore Squiz.PHP.DisallowMultipleAssignments.Found

		/* Assert. */
		$this->assertEquals( $expected, $actual );
	}

	public function testRun_CommentsExist_ReturnsMatchingNumberOfInserts(): void {
		/* Arrange. */
		$course_id = $this->factory->course->create( array( 'post_title' => 'Course 1' ) );
		$lesson_id = $this->factory->lesson->create(
			array(
				'post_title'  => 'Lesson 1',
				'post_parent' => $course_id,
			)
		);

		Sensei_Utils::start_user_on_course( 1, $course_id );
		Sensei_Utils::user_start_lesson( 1, $lesson_id, true );

		update_option( 'sensei_migrated_progress_last_comment_id', 0 );

		/* Act. */
		$this->migration->run( $dry_run = false ); // phpcs:ignore Squiz.PHP.DisallowMultipleAssignments.Found

		/* Assert. */
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$actual_rows = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}sensei_lms_progress" );
		$this->assertEquals( 2, $actual_rows );
	}


	/**
	 * Migrated progress is readable even when missing rows were cached earlier.
	 *
	 * @covers \Sensei\Internal\Migration\Migrations\Student_Progress_Migration::run
	 */
	public function testRun_CommentsExist_CreatesProgressMatchingEntriesInCustomTables(): void {
		/* Arrange. */
		$course_id = $this->factory->course->create( array( 'post_title' => 'Course 1' ) );
		$quiz_id   = $this->factory->quiz->create(
			array(
				'post_title' => 'Quiz 1',
			)
		);
		$lesson_id = $this->factory->lesson->create(
			array(
				'post_title'  => 'Lesson 1',
				'post_parent' => $course_id,
				'meta_input'  => array(
					'_lesson_quiz' => $quiz_id,
				),
			)
		);
		$user_1    = $this->factory->user->create();
		$user_2    = $this->factory->user->create();

		update_post_meta( $quiz_id, '_quiz_lesson', $lesson_id );

		$this->set_progress_storage_mode( 'comments-only' );
		Sensei_Utils::start_user_on_course( $user_1, $course_id );
		Sensei_Utils::user_start_lesson( $user_1, $lesson_id, true );
		Sensei_Utils::user_passed_quiz( $quiz_id, $user_1 );

		Sensei_Utils::start_user_on_course( $user_2, $course_id );
		Sensei_Utils::user_start_lesson( $user_2, $lesson_id, true );
		Sensei_Utils::user_passed_quiz( $quiz_id, $user_2 );
		$this->restore_progress_storage_mode();

		$this->set_progress_storage_mode( 'tables' );
		Sensei()->course_progress_repository->get( $course_id, $user_1 );
		Sensei()->lesson_progress_repository->get( $lesson_id, $user_1 );
		Sensei()->quiz_progress_repository->get( $quiz_id, $user_1 );

		update_option( 'sensei_migrated_progress_last_comment_id', 0 );

		/* Act. */
		$this->migration->run( $dry_run = false ); // phpcs:ignore Squiz.PHP.DisallowMultipleAssignments.Found

		/* Assert. */
		$this->assertNotNull( Sensei()->course_progress_repository->get( $course_id, $user_1 ), 'The migrated course progress should replace its cached miss.' );
		$this->assertNotNull( Sensei()->lesson_progress_repository->get( $lesson_id, $user_1 ), 'The migrated lesson progress should replace its cached miss.' );
		$this->assertNotNull( Sensei()->quiz_progress_repository->get( $quiz_id, $user_1 ), 'The migrated quiz progress should replace its cached miss.' );
		$actual_rows = $this->get_table_based_progress();
		$expected    = array(
			array(
				'user_id' => $user_1,
				'post_id' => $course_id,
				'status'  => 'in-progress',
				'type'    => 'course',
			),
			array(
				'user_id' => $user_1,
				'post_id' => $lesson_id,
				'status'  => 'complete',
				'type'    => 'lesson',
			),
			array(
				'user_id' => $user_1,
				'post_id' => $quiz_id,
				'status'  => 'passed',
				'type'    => 'quiz',
			),
			array(
				'user_id' => $user_2,
				'post_id' => $course_id,
				'status'  => 'in-progress',
				'type'    => 'course',
			),
			array(
				'user_id' => $user_2,
				'post_id' => $lesson_id,
				'status'  => 'complete',
				'type'    => 'lesson',
			),
			array(
				'user_id' => $user_2,
				'post_id' => $quiz_id,
				'status'  => 'passed',
				'type'    => 'quiz',
			),
		);
		$this->assertSame( $expected, $actual_rows );
	}

	/**
	 * Migration preserves completion and leaves absent start metadata unknown.
	 *
	 * @covers \Sensei\Internal\Migration\Migrations\Student_Progress_Migration::run
	 */
	public function testRun_ProgressCommentsWithoutStartMetadata_PreservesUnknownStartsAndCompletionDates(): void {
		global $wpdb;

		update_option( 'timezone_string', 'America/Toronto' );
		$user_id      = $this->factory->user->create();
		$course       = $this->factory->get_course_with_lessons( array( 'question_count' => 1 ) );
		$completed_at = '2024-05-01 15:45:00';
		$fixtures     = array(
			'sensei_course_status' => $course['course_id'],
			'sensei_lesson_status' => $course['lesson_ids'][0],
		);

		foreach ( $fixtures as $type => $post_id ) {
			wp_insert_comment(
				array(
					'comment_post_ID'  => $post_id,
					'user_id'          => $user_id,
					'comment_type'     => $type,
					'comment_approved' => 'complete',
					'comment_date'     => '2024-05-01 11:45:00',
					'comment_date_gmt' => $completed_at,
				)
			);
		}

		$this->migration->run( false );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT type, started_at, completed_at FROM {$wpdb->prefix}sensei_lms_progress WHERE user_id = %d ORDER BY type",
				$user_id
			),
			ARRAY_N
		);

		self::assertSame(
			array(
				array( 'course', null, $completed_at ),
				array( 'lesson', null, $completed_at ),
				array( 'quiz', null, $completed_at ),
			),
			$rows
		);
	}

	/**
	 * Trashing content must not discard its saved progress or publish the content.
	 *
	 * @covers \Sensei\Internal\Migration\Migrations\Student_Progress_Migration::run
	 */
	public function testRun_CompletedCourseWasTrashed_PreservesCompletion(): void {
		$course_id  = $this->factory->course->create();
		$student_id = $this->factory->user->create();
		$comment_id = wp_insert_comment(
			array(
				'comment_post_ID'  => $course_id,
				'user_id'          => $student_id,
				'comment_type'     => 'sensei_course_status',
				'comment_approved' => 'complete',
				'comment_date_gmt' => '2024-05-01 15:45:00',
			)
		);
		wp_trash_post( $course_id );

		$this->migration->run( false );

		global $wpdb;
		$progress = ( new Tables_Based_Course_Progress_Repository( $wpdb ) )->get( $course_id, $student_id );
		self::assertSame( 'complete', $progress->get_status(), 'The saved course completion survives trashing.' );
		self::assertSame( '2024-05-01 15:45:00', $progress->get_completed_at()->format( 'Y-m-d H:i:s' ), 'The original completion date survives migration.' );
		self::assertSame( 'trash', get_post_status( $course_id ), 'Migration keeps the course in Trash.' );
		self::assertSame( 'post-trashed', get_comment( $comment_id )->comment_approved, 'Migration leaves the source comment untouched.' );
	}

	/**
	 * Failed quizzes stay undated while optional lessons retain their completion.
	 *
	 * @dataProvider failed_quiz_completion_cases
	 * @covers \Sensei\Internal\Migration\Migrations\Student_Progress_Migration::run
	 */
	public function testRun_FailedQuizWasSaved_PreservesLessonAndQuizCompletionSemantics(
		string $pass_required,
		string $lesson_status,
		?string $lesson_completed_at
	): void {
		global $wpdb;
		$this->set_progress_storage_mode( 'comments-only' );
		$course     = $this->factory->get_course_with_lessons();
		$lesson_id  = $course['lesson_ids'][0];
		$quiz_id    = $course['quiz_ids'][0];
		$student_id = $this->factory->user->create();
		update_post_meta( $quiz_id, '_pass_required', $pass_required );
		( new Comments_Based_Lesson_Progress_Repository() )->create( $lesson_id, $student_id );
		$quiz_repository = new Comments_Based_Quiz_Progress_Repository();
		$quiz_progress   = $quiz_repository->create( $quiz_id, $student_id );
		$quiz_progress->start( new \DateTimeImmutable( '2024-05-01 10:00:00', new \DateTimeZone( 'UTC' ) ) );
		$quiz_progress->fail();
		$quiz_repository->save( $quiz_progress );
		wp_update_comment(
			array(
				'comment_ID'       => $quiz_progress->get_id(),
				'comment_date'     => '2024-05-01 15:45:00',
				'comment_date_gmt' => '2024-05-01 15:45:00',
			)
		);

		$this->migration->run( false );

		$lesson_comments = ( new Comments_Based_Lesson_Progress_Repository() )->get( $lesson_id, $student_id );
		$lesson_tables   = ( new Tables_Based_Lesson_Progress_Repository( $wpdb ) )->get( $lesson_id, $student_id );
		$quiz_comments   = $quiz_repository->get( $quiz_id, $student_id );
		$quiz_tables     = ( new Tables_Based_Quiz_Progress_Repository( $wpdb ) )->get( $quiz_id, $student_id );
		$dates           = array();
		foreach ( array( $lesson_comments, $lesson_tables, $quiz_comments, $quiz_tables ) as $progress ) {
			$date    = $progress->get_completed_at();
			$dates[] = $date ? gmdate( 'Y-m-d H:i:s', $date->getTimestamp() ) : null;
		}
		self::assertSame(
			array( $lesson_completed_at, $lesson_completed_at, null, null ),
			$dates,
			'Normal reads and migrated reads must agree on completion dates.'
		);
		self::assertSame( $lesson_status, $lesson_comments->get_status(), 'Comments normalize optional failed lessons as complete.' );
		self::assertSame( $lesson_status, $lesson_tables->get_status(), 'Migration preserves the same lesson status.' );
		self::assertSame( 'failed', $quiz_tables->get_status(), 'A failed optional quiz must not become passed.' );
		$validation = new Progress_Validation();
		self::assertSame(
			array(),
			$validation->inspect( 'lesson', $lesson_id, $student_id )['errors'],
			'Lesson validation compares the completion date.'
		);
		self::assertSame(
			array(),
			$validation->inspect( 'quiz', $quiz_id, $student_id )['errors'],
			'Quiz validation compares the absent completion date.'
		);
	}

	/**
	 * Passing requirements determine lesson completion, independently of quiz failure.
	 *
	 * @return array
	 */
	public static function failed_quiz_completion_cases(): array {
		return array(
			'optional quiz' => array( '', 'complete', '2024-05-01 15:45:00' ),
			'required quiz' => array( 'on', 'in-progress', null ),
		);
	}

	public function testRun_TimeExceeded_StopsEarlyAndAdvancesCursor(): void {
		/* Arrange. */
		$course_id = $this->factory->course->create();

		// Create progress for multiple users.
		for ( $i = 0; $i < 5; $i++ ) {
			$user_id = $this->factory->user->create();
			Sensei_Utils::start_user_on_course( $user_id, $course_id );
		}

		update_option( 'sensei_migrated_progress_last_comment_id', 0 );

		$migration = new Student_Progress_Migration( 250 );
		// Set a zero time budget so it stops after the first comment.
		$migration->set_time_budget( 0.0 );

		/* Act. */
		$result = $migration->run( false );

		/* Assert. */
		// At least one comment should be processed (time check is after insert).
		$this->assertGreaterThan( 0, $result, 'Expected at least one row to be inserted.' );

		// Cursor should have advanced (not stuck at 0).
		$cursor = (int) get_option( 'sensei_migrated_progress_last_comment_id' );
		$this->assertGreaterThan( 0, $cursor, 'Expected cursor to advance after processing at least one comment.' );
	}

	public function testRun_TimeExceeded_DoesNotProcessAllComments(): void {
		/* Arrange. */
		$course_id = $this->factory->course->create();

		for ( $i = 0; $i < 5; $i++ ) {
			$user_id = $this->factory->user->create();
			Sensei_Utils::start_user_on_course( $user_id, $course_id );
		}

		update_option( 'sensei_migrated_progress_last_comment_id', 0 );

		$migration = new Student_Progress_Migration( 250 );
		// Zero budget so time is immediately exceeded after first comment.
		$migration->set_time_budget( 0.0 );

		/* Act. */
		$result = $migration->run( false );

		/* Assert. */
		// Should have inserted fewer than all 5 course progress rows.
		$this->assertLessThan( 5, $result, 'Expected fewer than 5 rows because time budget should stop processing early.' );
	}

	/**
	 * Duplicate-only retries still process comments and refresh cached misses.
	 *
	 * @covers \Sensei\Internal\Migration\Migrations\Student_Progress_Migration::run
	 */
	public function testRun_AllRowsWereDuplicates_ReturnsGreaterThanZero(): void {
		/* Arrange. */
		$course_id = $this->factory->course->create( array( 'post_title' => 'Course 1' ) );
		$user_id   = $this->factory->user->create();

		Sensei_Utils::start_user_on_course( $user_id, $course_id );

		update_option( Student_Progress_Migration::LAST_COMMENT_ID_OPTION_NAME, 0 );

		// First run: inserts rows normally.
		$this->migration->run( false );

		$this->set_progress_storage_mode( 'tables' );
		$repository = new Tables_Based_Course_Progress_Repository( $GLOBALS['wpdb'] );
		// phpcs:disable WordPress.DB -- Restore the existing row while retaining a cached miss.
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}sensei_lms_progress WHERE post_id = %d AND user_id = %d AND type = 'course'", $course_id, $user_id ), ARRAY_A );
		$repository->delete( $repository->get( $course_id, $user_id ) );
		$repository->get( $course_id, $user_id );
		$wpdb->insert( $wpdb->prefix . 'sensei_lms_progress', $row );
		// phpcs:enable WordPress.DB

		// Simulate a crash before cursor update by resetting the cursor.
		update_option( Student_Progress_Migration::LAST_COMMENT_ID_OPTION_NAME, 0 );

		/* Act. */
		// Second run: all rows are duplicates (INSERT IGNORE returns 0).
		$result = $this->migration->run( false );

		/* Assert. */
		$this->assertGreaterThan( 0, $result, 'run() should return > 0 when comments were processed, even if all inserts were duplicates' );
		$this->assertNotNull( $repository->get( $course_id, $user_id ), 'A duplicate-only batch should clear an existing progress cache miss.' );
	}

	public function testRun_AllRowsWereDuplicates_AdvancesCursorPastDuplicates(): void {
		/* Arrange. */
		$course_id = $this->factory->course->create( array( 'post_title' => 'Course 1' ) );
		$user_id   = $this->factory->user->create();

		Sensei_Utils::start_user_on_course( $user_id, $course_id );

		update_option( Student_Progress_Migration::LAST_COMMENT_ID_OPTION_NAME, 0 );

		// First run: inserts rows normally.
		$this->migration->run( false );

		// Simulate a crash before cursor update by resetting the cursor.
		update_option( Student_Progress_Migration::LAST_COMMENT_ID_OPTION_NAME, 0 );

		/* Act. */
		// Second run: all rows are duplicates.
		$this->migration->run( false );

		/* Assert. */
		$cursor = (int) get_option( Student_Progress_Migration::LAST_COMMENT_ID_OPTION_NAME );
		$this->assertGreaterThan( 0, $cursor, 'Cursor should advance past duplicates' );
	}

	private function get_table_based_progress(): array {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}sensei_lms_progress ORDER BY user_id" );

		$result = array();
		foreach ( $rows as $row ) {
			$result[] = array(
				'user_id' => (int) $row->user_id,
				'post_id' => (int) $row->post_id,
				'status'  => $row->status,
				'type'    => $row->type,
			);
		}

		return $result;
	}
}
