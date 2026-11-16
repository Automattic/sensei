<?php // phpcs:disable WordPress.Files.FileName.InvalidClassFileName -- Follow the test suite's file naming convention.
/**
 * Integration tests for reading and saving progress start dates.
 *
 * @package sensei-tests
 */

namespace SenseiTest\Internal\Student_Progress;

use Sensei_Factory;
use Sensei_HPPS_Helpers;
use WP_UnitTestCase;

/**
 * Tests progress start dates across the comments and tables repositories.
 */
class Progress_Start_Date_Storage_Test extends WP_UnitTestCase {
	use Sensei_HPPS_Helpers;

	protected function tearDown(): void {
		$this->restore_progress_storage_mode();
		parent::tearDown();
	}

	/**
	 * Saving progress without a recorded start leaves it unknown in both stores.
	 *
	 * @dataProvider start_date_storage_cases
	 * @covers \Sensei\Internal\Student_Progress\Course_Progress\Repositories\Comment_Reading_Aggregate_Course_Progress_Repository::save
	 * @covers \Sensei\Internal\Student_Progress\Course_Progress\Repositories\Table_Reading_Aggregate_Course_Progress_Repository::save
	 * @covers \Sensei\Internal\Student_Progress\Lesson_Progress\Repositories\Comment_Reading_Aggregate_Lesson_Progress_Repository::save
	 * @covers \Sensei\Internal\Student_Progress\Lesson_Progress\Repositories\Table_Reading_Aggregate_Lesson_Progress_Repository::save
	 * @covers \Sensei\Internal\Student_Progress\Quiz_Progress\Repositories\Comment_Reading_Aggregate_Quiz_Progress_Repository::save
	 * @covers \Sensei\Internal\Student_Progress\Quiz_Progress\Repositories\Table_Reading_Aggregate_Quiz_Progress_Repository::save
	 *
	 * @param string      $mode The primary store.
	 * @param string      $type The kind of progress to save.
	 * @param string|null $table_start The original table start when a row exists.
	 * @param bool        $mirror_exists Whether the secondary record exists before the save.
	 */
	public function testSave_ProgressWithoutRecordedStart_PreservesUnknownStartInBothStores( string $mode, string $type, ?string $table_start, bool $mirror_exists ): void {
		global $wpdb;

		$this->set_progress_storage_mode( $mode );
		$factory      = new Sensei_Factory();
		$user_id      = $factory->user->create();
		$posts        = $this->create_progress_posts( $factory );
		$completed_at = '2024-05-01 15:45:00';
		$is_course    = 'course' === $type;
		$post_id      = $posts[ $type ];

		if ( 'comments' === $mode || $mirror_exists ) {
			wp_insert_comment(
				array(
					'comment_post_ID'  => $is_course ? $post_id : $posts['lesson'],
					'user_id'          => $user_id,
					'comment_type'     => $is_course ? 'sensei_course_status' : 'sensei_lesson_status',
					'comment_approved' => 'complete',
					'comment_date'     => $completed_at,
					'comment_date_gmt' => $completed_at,
				)
			);
		}

		if ( 'tables' === $mode || $mirror_exists ) {
			$this->insert_table_progress( $post_id, $user_id, $type, $table_start, $completed_at );
		}

		$repository = Sensei()->{ $type . '_progress_repository' };
		$progress   = $repository->get( $post_id, $user_id );
		$repository->save( $progress );

		$this->restore_progress_storage_mode();
		$this->set_progress_storage_mode( 'comments-only' );
		$comments_repository = Sensei()->{ $type . '_progress_repository' };
		$comment_progress    = $comments_repository->get( $post_id, $user_id );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$table_progress = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT started_at FROM {$wpdb->prefix}sensei_lms_progress WHERE post_id = %d AND user_id = %d AND type = %s",
				$post_id,
				$user_id,
				$type
			)
		);

		self::assertNull( $progress->get_started_at(), 'The primary repository should read the start as unknown.' );
		self::assertNull( $comment_progress->get_started_at(), 'The saved comment should have no recorded start date.' );
		self::assertFalse( metadata_exists( 'comment', $comment_progress->get_id(), 'start' ), 'The comment save should leave start metadata absent.' );
		self::assertNull( $table_progress->started_at, 'The table save should store SQL NULL for the unknown start.' );
	}

	/**
	 * Find must recognize unknown dates in existing progress rows.
	 *
	 * @covers \Sensei\Internal\Student_Progress\Course_Progress\Repositories\Tables_Based_Course_Progress_Repository::find
	 * @covers \Sensei\Internal\Student_Progress\Lesson_Progress\Repositories\Tables_Based_Lesson_Progress_Repository::find
	 * @covers \Sensei\Internal\Student_Progress\Quiz_Progress\Repositories\Tables_Based_Quiz_Progress_Repository::find
	 * @dataProvider progress_types
	 *
	 * @param string $type The kind of progress to find.
	 */
	public function testFind_LegacyZeroStartDateGiven_ReturnsUnknownStartDate( string $type ): void {
		$this->set_progress_storage_mode( 'tables' );
		$factory = new Sensei_Factory();
		$user_id = $factory->user->create();
		$posts   = $this->create_progress_posts( $factory );
		$post_id = $posts[ $type ];
		$this->insert_table_progress( $post_id, $user_id, $type, '0000-00-00 00:00:00', '2024-05-01 15:45:00' );

		$repository = Sensei()->{ $type . '_progress_repository' };
		$progress   = $repository->find(
			array(
				'user_id'     => $user_id,
				$type . '_id' => $post_id,
			)
		);

		self::assertCount( 1, $progress, 'The find should return the existing progress row.' );
		self::assertNull( reset( $progress )->get_started_at(), 'The row should have no known start.' );
	}

	/**
	 * Missing comment metadata, legacy HPPS dates, and missing secondary records.
	 *
	 * @return array
	 */
	public static function start_date_storage_cases(): array {
		$cases = array();

		foreach ( self::dual_write_storage_modes() as $mode_label => list( $mode ) ) {
			$table_start = 'tables' === $mode ? '0000-00-00 00:00:00' : null;
			foreach ( self::progress_types() as $type_label => list( $type ) ) {
				$cases[ "$mode_label, $type_label, unknown start" ] = array( $mode, $type, $table_start, true );
			}

			$cases[ "$mode_label, course, missing mirror" ] = array( $mode, 'course', null, false );
		}

		return $cases;
	}

	/**
	 * Progress types with their own table rows.
	 *
	 * @return array
	 */
	public static function progress_types(): array {
		return array(
			'course' => array( 'course' ),
			'lesson' => array( 'lesson' ),
			'quiz'   => array( 'quiz' ),
		);
	}

	/**
	 * Create related posts for course, lesson, and quiz progress.
	 *
	 * @param Sensei_Factory $factory Test data factory.
	 * @return array
	 */
	private function create_progress_posts( Sensei_Factory $factory ): array {
		$course = $factory->get_course_with_lessons( array( 'question_count' => 1 ) );
		$lesson = $course['lesson_ids'][0];

		return array(
			'course' => $course['course_id'],
			'lesson' => $lesson,
			'quiz'   => (int) Sensei()->lesson->lesson_quizzes( $lesson ),
		);
	}

	/**
	 * Insert a table fixture with the given raw dates.
	 *
	 * @param int         $post_id The content ID.
	 * @param int         $user_id The student ID.
	 * @param string      $type The progress type.
	 * @param string|null $started_at The start date as it appears in storage.
	 * @param string      $completed_at The historical completion date.
	 */
	private function insert_table_progress( int $post_id, int $user_id, string $type, ?string $started_at, string $completed_at ): void {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->insert(
			$wpdb->prefix . 'sensei_lms_progress',
			array(
				'post_id'      => $post_id,
				'user_id'      => $user_id,
				'type'         => $type,
				'status'       => 'quiz' === $type ? 'passed' : 'complete',
				'started_at'   => $started_at,
				'completed_at' => $completed_at,
				'created_at'   => $completed_at,
				'updated_at'   => $completed_at,
			)
		);
	}
}
