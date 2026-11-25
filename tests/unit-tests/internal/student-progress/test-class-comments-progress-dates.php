<?php
/**
 * Tests for legacy progress dates at the comments and tables boundary.
 *
 * @package sensei-tests
 */

namespace SenseiTest\Internal\Student_Progress;

use Sensei\Internal\Services\Comments_Based_Reports_Listing_Service;

/**
 * Exercise real progress repositories across timezone changes.
 */
class Comments_Progress_Dates_Test extends \WP_UnitTestCase {
	private $sensei_factory;

	public function setUp(): void {
		parent::setUp();
		$this->sensei_factory = new \Sensei_Factory();
	}

	public function tearDown(): void {
		$this->sensei_factory->tearDown();
		parent::tearDown();
	}

	/**
	 * All comment readers use the recorded timezone for both active and completed progress.
	 *
	 * @covers \Sensei\Internal\Student_Progress\Course_Progress\Repositories\Comments_Based_Course_Progress_Repository::get
	 * @covers \Sensei\Internal\Student_Progress\Lesson_Progress\Repositories\Comments_Based_Lesson_Progress_Repository::get
	 * @covers \Sensei\Internal\Student_Progress\Quiz_Progress\Repositories\Comments_Based_Quiz_Progress_Repository::get
	 * @dataProvider progress_statuses
	 */
	public function testGet_TimezoneChanged_ReturnsRecordedStart( string $type, string $status ): void {
		list( $post_id, $user_id, $comment_id, $comments ) = $this->create_progress( $type, $status );
		update_option( 'timezone_string', 'America/Chicago' );

		$actual = $comments->get( $post_id, $user_id );

		self::assertSame( '2022-05-16 14:00:00 -05:00', $actual->get_started_at()->format( 'Y-m-d H:i:s P' ) );
	}

	/**
	 * Each reader must apply the same rule before and after completion.
	 *
	 * @return array
	 */
	public static function progress_statuses(): array {
		return array(
			'active course'    => array( 'course', 'in-progress' ),
			'completed course' => array( 'course', 'complete' ),
			'active lesson'    => array( 'lesson', 'in-progress' ),
			'completed lesson' => array( 'lesson', 'complete' ),
			'active quiz'      => array( 'quiz', 'in-progress' ),
			'passed quiz'      => array( 'quiz', 'passed' ),
		);
	}

	/**
	 * Saving an edited start repeatedly must retain completion through both dual-write directions.
	 *
	 * @covers \Sensei\Internal\Student_Progress\Course_Progress\Repositories\Comments_Based_Course_Progress_Repository::save
	 * @covers \Sensei\Internal\Student_Progress\Lesson_Progress\Repositories\Comments_Based_Lesson_Progress_Repository::save
	 * @covers \Sensei\Internal\Student_Progress\Quiz_Progress\Repositories\Comments_Based_Quiz_Progress_Repository::save
	 * @dataProvider completed_progress_types
	 */
	public function testSave_StartEditedAfterTimezoneChanged_PreservesInstantsThroughRoundTrip( string $type, string $status ): void {
		list( $post_id, $user_id, $comment_id, $comments, $tables, $comment_reading, $table_reading ) = $this->create_progress( $type, $status );
		update_option( 'timezone_string', 'America/Chicago' );
		$progress = $comments->get( $post_id, $user_id );
		// Model construction supports editing quiz dates without changing its shared lesson status.
		$model  = get_class( $progress );
		$edited = new $model(
			$progress->get_id(),
			$post_id,
			$user_id,
			$status,
			new \DateTimeImmutable( '2022-05-16 18:00:00', new \DateTimeZone( 'UTC' ) ),
			$progress->get_completed_at(),
			$progress->get_created_at(),
			$progress->get_updated_at()
		);

		$comment_reading->save( $edited );
		$comment_reading->save( $comments->get( $post_id, $user_id ) );
		$table_reading->save( $tables->get( $post_id, $user_id ) );
		$comment_reading->save( $comments->get( $post_id, $user_id ) );

		$comment = get_comment( $comment_id );
		self::assertSame( '2022-05-16 19:36:12', $comment->comment_date_gmt, 'Saving a start must preserve the recorded completion UTC.' );
		self::assertSame( '2022-05-16 14:36:12', $comment->comment_date, 'The local completion clock should match the current timezone.' );
		self::assertSame( '2022-05-16 13:00:00', get_comment_meta( $comment_id, 'start', true ), 'Edited starts should persist in the current site timezone.' );
		self::assertSame( '2022-05-16 18:00:00', $tables->get( $post_id, $user_id )->get_started_at()->format( 'Y-m-d H:i:s' ), 'Dual writes must preserve the normalized start UTC.' );
		self::assertSame( '2022-05-16 19:36:12', $tables->get( $post_id, $user_id )->get_completed_at()->format( 'Y-m-d H:i:s' ), 'Round trips must preserve completion UTC.' );
	}

	/**
	 * Completion is shared with quiz status only for the existing completed states.
	 *
	 * @return array
	 */
	public static function completed_progress_types(): array {
		return array(
			'course' => array( 'course', 'complete' ),
			'lesson' => array( 'lesson', 'complete' ),
			'quiz'   => array( 'quiz', 'passed' ),
		);
	}

	/**
	 * Explicit completion instants survive WordPress's ambiguous local clocks before hooks run.
	 *
	 * @covers \Sensei_Utils::sensei_log_activity
	 * @dataProvider completion_write_paths
	 */
	public function testSave_AmbiguousCompletionGiven_PersistsExactInstantBeforeStatusHook( string $type, string $status, bool $insert ): void {
		list( $post_id, $user_id, $comment_id, $comments ) = $this->create_progress( $type, $status );
		$progress  = $comments->get( $post_id, $user_id );
		$model     = get_class( $progress );
		$completed = new $model(
			$comment_id,
			$post_id,
			$user_id,
			$status,
			new \DateTimeImmutable( '2022-11-06 05:00:00 UTC' ),
			new \DateTimeImmutable( '2022-11-06 07:30:00 UTC' ),
			$progress->get_created_at(),
			$progress->get_updated_at()
		);
		if ( $insert ) {
			wp_delete_comment( $comment_id, true );
		}
		update_option( 'timezone_string', 'America/Chicago' );
		$observed = null;
		$observe  = static function ( $status, $student_id, $observed_post_id, $saved_id ) use ( &$observed ) {
			$comment  = get_comment( $saved_id );
			$observed = array( $comment->comment_date, $comment->comment_date_gmt );
		};
		$hook     = 'course' === $type ? 'sensei_course_status_updated' : 'sensei_lesson_status_updated';
		add_action( $hook, $observe, 10, 4 );

		try {
			$comments->save( $completed );
		} finally {
			remove_action( $hook, $observe, 10 );
		}

		$actual = $comments->get( $post_id, $user_id );
		self::assertSame( '2022-11-06 07:30:00 +00:00', $actual->get_completed_at()->setTimezone( new \DateTimeZone( 'UTC' ) )->format( 'Y-m-d H:i:s P' ), 'The second occurrence of 01:30 must retain its UTC instant.' );
		self::assertSame( array( '2022-11-06 01:30:00', '2022-11-06 07:30:00' ), $observed, 'Status hooks must observe the final persisted timestamp pair.' );
		self::assertSame( '', get_comment_meta( $actual->get_id(), '_sensei_completed_at', true ), 'Internal date transport must never be stored as metadata.' );
	}

	/**
	 * Cover insertion and update for each independent progress writer.
	 *
	 * @return array
	 */
	public static function completion_write_paths(): array {
		return array(
			'course insert' => array( 'course', 'complete', true ),
			'course update' => array( 'course', 'complete', false ),
			'lesson insert' => array( 'lesson', 'complete', true ),
			'lesson update' => array( 'lesson', 'complete', false ),
			'quiz insert'   => array( 'quiz', 'passed', true ),
			'quiz update'   => array( 'quiz', 'passed', false ),
		);
	}

	/**
	 * Completion after a timezone change must not move an already interpreted start.
	 *
	 * @covers \Sensei\Internal\Student_Progress\Course_Progress\Repositories\Comments_Based_Course_Progress_Repository::save
	 */
	public function testSave_ProgressCompletedAfterTimezoneChanged_PreservesStartedInstant(): void {
		list( $post_id, $user_id, $comment_id, $comments ) = $this->create_progress( 'course', 'in-progress' );
		update_option( 'timezone_string', 'America/Chicago' );
		$progress = $comments->get( $post_id, $user_id );
		$progress->complete( new \DateTimeImmutable( '2022-05-18 12:00:00 UTC' ) );

		$comments->save( $progress );
		$actual = $comments->get( $post_id, $user_id );

		self::assertSame( '2022-05-16 14:00:00 -05:00', $actual->get_started_at()->format( 'Y-m-d H:i:s P' ) );
	}

	/**
	 * An unusable offset must not cause a read to invent or overwrite a start date.
	 *
	 * @covers \Sensei\Internal\Student_Progress\Course_Progress\Repositories\Comments_Based_Course_Progress_Repository::get
	 */
	public function testGet_UnusableCommentTimestampPairGiven_RetainsUnknownStartMetadata(): void {
		list( $post_id, $user_id, $comment_id, $comments ) = $this->create_progress( 'course', 'in-progress' );
		$GLOBALS['wpdb']->update( $GLOBALS['wpdb']->comments, array( 'comment_date_gmt' => '0000-00-00 00:00:00' ), array( 'comment_ID' => $comment_id ) );
		clean_comment_cache( $comment_id );

		$actual = $comments->get( $post_id, $user_id );

		self::assertNull( $actual->get_started_at(), 'An unusable timestamp pair cannot establish an absolute start.' );
		self::assertSame( '2022-05-17 01:00:00', get_comment_meta( $comment_id, 'start', true ), 'Reading unknown progress must retain its legacy metadata.' );
	}

	/**
	 * Reports bypass repositories and must normalize their start dates too.
	 *
	 * @covers \Sensei\Internal\Services\Comments_Based_Reports_Listing_Service::get_course_students
	 */
	public function testGetCourseStudents_TimezoneChanged_ReturnsNormalizedStart(): void {
		list( $post_id, $user_id ) = $this->create_progress( 'course', 'complete' );
		update_option( 'timezone_string', 'America/Chicago' );
		$service = new Comments_Based_Reports_Listing_Service();

		$actual = $service->get_course_students(
			array(
				'post_id' => $post_id,
				'user_id' => $user_id,
				'type'    => 'sensei_course_status',
				'status'  => 'any',
			)
		);

		self::assertSame( '2022-05-16 14:00:00', $actual['items'][0]->started_at );
	}

	/**
	 * Create real repositories around a comment recorded in the former site timezone.
	 *
	 * @param string $type Progress type.
	 * @param string $status Stored status.
	 * @return array
	 */
	private function create_progress( string $type, string $status ): array {
		update_option( 'timezone_string', 'Asia/Dhaka' );
		$user_id           = $this->sensei_factory->user->create();
		$post_id           = $this->sensei_factory->$type->create();
		$comment_post_id   = $post_id;
		$lesson_repository = new \Sensei\Internal\Student_Progress\Lesson_Progress\Repositories\Comments_Based_Lesson_Progress_Repository();
		if ( 'quiz' === $type ) {
			$comment_post_id = $this->sensei_factory->lesson->create();
			wp_update_post(
				array(
					'ID'          => $post_id,
					'post_parent' => $comment_post_id,
				)
			);
		}
		$comment_id = wp_insert_comment(
			array(
				'comment_post_ID'  => $comment_post_id,
				'user_id'          => $user_id,
				'comment_type'     => 'quiz' === $type ? 'sensei_lesson_status' : 'sensei_' . $type . '_status',
				'comment_approved' => $status,
				'comment_date'     => '2022-05-17 01:36:12',
				'comment_date_gmt' => '2022-05-16 19:36:12',
			)
		);
		update_comment_meta( $comment_id, 'start', '2022-05-17 01:00:00' );
		$namespace             = 'Sensei\\Internal\\Student_Progress\\' . ucfirst( $type ) . '_Progress\\Repositories\\';
		$suffix                = ucfirst( $type ) . '_Progress_Repository';
		$comments_class        = $namespace . 'Comments_Based_' . $suffix;
		$tables_class          = $namespace . 'Tables_Based_' . $suffix;
		$comment_reading_class = $namespace . 'Comment_Reading_Aggregate_' . $suffix;
		$table_reading_class   = $namespace . 'Table_Reading_Aggregate_' . $suffix;
		$comments              = new $comments_class();
		$tables                = new $tables_class( $GLOBALS['wpdb'] );
		$comment_reading       = new $comment_reading_class( $comments, $tables );
		$table_reading         = 'quiz' === $type ? new $table_reading_class( $comments, $tables, $lesson_repository ) : new $table_reading_class( $comments, $tables );

		return array( $post_id, $user_id, $comment_id, $comments, $tables, $comment_reading, $table_reading );
	}
}
