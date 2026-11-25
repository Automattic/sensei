<?php
/**
 * Tests for legacy progress dates at the comments and tables boundary.
 *
 * @package sensei-tests
 */

namespace SenseiTest\Internal\Student_Progress;

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
