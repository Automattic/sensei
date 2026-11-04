<?php
/**
 * Tests for the course enrolment debug tool.
 *
 * @package sensei-tests
 */

use Sensei\Internal\Student_Progress\Course_Progress\Repositories\Tables_Based_Course_Progress_Repository;

/**
 * Tests the debug tool using real progress repositories.
 *
 * @group tools
 */
class Sensei_Tool_Enrolment_Debug_Test extends WP_UnitTestCase {
	use Sensei_HPPS_Helpers;

	/**
	 * Sensei fixtures.
	 *
	 * @var Sensei_Factory
	 */
	protected $factory;

	public function setUp(): void {
		parent::setUp();

		$this->factory = new Sensei_Factory();
		update_option( 'timezone_string', 'UTC' );
		update_option( 'date_format', 'F j, Y' );
		update_option( 'time_format', 'g:i a' );
		$this->maybe_enable_hpps_tables_repository();
		wp_set_current_user( $this->factory->user->create( [ 'role' => 'administrator' ] ) );
	}

	public function tearDown(): void {
		$this->restore_progress_storage_mode();
		$this->maybe_reset_hpps_repository();
		$this->factory->tearDown();

		parent::tearDown();
	}

	/**
	 * Completed progress is reported from the selected repository.
	 *
	 * @covers Sensei_Tool_Enrolment_Debug::process
	 */
	public function testProcess_CompletedCourseGiven_ShowsCompletedProgress() {
		$course_id  = $this->factory->course->create();
		$student_id = $this->factory->user->create();
		$progress   = Sensei()->course_progress_repository->create( $course_id, $student_id );
		$progress->start( new DateTimeImmutable( '2024-01-02 12:00:00 UTC' ) );
		$progress->complete();
		Sensei()->course_progress_repository->save( $progress );

		$output = $this->get_tool_output( $student_id, $course_id );

		self::assertStringContainsString(
			'Completed (100%)',
			$output,
			'Completed status should come from the selected progress repository.'
		);
		self::assertStringContainsString(
			'Started on January 2, 2024 12:00 pm',
			$output,
			'The stored course start date should be displayed.'
		);
	}

	/**
	 * Lesson progress contributes to percentage and last activity.
	 *
	 * @covers Sensei_Tool_Enrolment_Debug::process
	 */
	public function testProcess_InProgressCourseWithCompletedLessonGiven_ShowsPercentageAndLessonActivity() {
		global $wpdb;

		$course_id  = $this->factory->course->create();
		$student_id = $this->factory->user->create();
		$lesson_ids = $this->factory->lesson->create_many(
			2,
			[ 'meta_input' => [ '_lesson_course' => $course_id ] ]
		);
		Sensei()->course_progress_repository->create( $course_id, $student_id );
		$lesson_progress = Sensei()->lesson_progress_repository->create( $lesson_ids[0], $student_id );
		$lesson_progress->complete();
		Sensei()->lesson_progress_repository->save( $lesson_progress );

		$course_comments_repository = Sensei()->course_progress_repository_factory->create_comments_based_repository();
		$course_comment_progress    = $course_comments_repository->get( $course_id, $student_id );
		wp_update_comment(
			[
				'comment_ID'       => $course_comment_progress->get_id(),
				'comment_date'     => '2024-01-01 12:00:00',
				'comment_date_gmt' => '2024-01-01 12:00:00',
			]
		);
		$wpdb->update(
			$wpdb->prefix . 'sensei_lms_progress',
			[ 'updated_at' => '2024-01-01 12:00:00' ],
			[
				'post_id' => $course_id,
				'user_id' => $student_id,
				'type'    => 'course',
			]
		);

		$lesson_comments_repository = Sensei()->lesson_progress_repository_factory->create_comments_based_repository();
		$lesson_comment_progress    = $lesson_comments_repository->get( $lesson_ids[0], $student_id );
		wp_update_comment(
			[
				'comment_ID'       => $lesson_comment_progress->get_id(),
				'comment_date'     => '2024-02-03 14:00:00',
				'comment_date_gmt' => '2024-02-03 14:00:00',
			]
		);
		$wpdb->update(
			$wpdb->prefix . 'sensei_lms_progress',
			[ 'updated_at' => '2024-02-03 14:00:00' ],
			[
				'post_id' => $lesson_ids[0],
				'user_id' => $student_id,
				'type'    => 'lesson',
			]
		);
		wp_cache_flush();

		$output = $this->get_tool_output( $student_id, $course_id );

		self::assertStringContainsString(
			'In Progress (50%)',
			$output,
			'Completed lessons should determine the in-progress percentage.'
		);
		self::assertStringContainsString(
			'Last activity on February 3, 2024 2:00 pm',
			$output,
			'A newer lesson update should determine last activity.'
		);
	}

	/**
	 * Missing progress is reported without a progress summary.
	 *
	 * @covers Sensei_Tool_Enrolment_Debug::process
	 */
	public function testProcess_ProgressMissingGiven_ShowsNoProgress() {
		$course_id  = $this->factory->course->create();
		$student_id = $this->factory->user->create();

		$output = $this->get_tool_output( $student_id, $course_id );

		self::assertStringContainsString( 'No Progress', $output );
	}

	/**
	 * Existing comments dates retain their diagnostic formatting.
	 *
	 * @dataProvider legacy_start_dates
	 * @covers Sensei_Tool_Enrolment_Debug::process
	 * @param string $start_date     Stored start metadata.
	 * @param string $expected_start Expected formatted start date.
	 */
	public function testProcess_CommentsSelectedInNonUtcTimezoneGiven_PreservesLegacyDates( string $start_date, string $expected_start ) {
		$this->set_progress_storage_mode( 'comments-only' );
		update_option( 'timezone_string', 'America/Toronto' );
		$course_id  = $this->factory->course->create();
		$student_id = $this->factory->user->create();
		$progress   = Sensei()->course_progress_repository->create( $course_id, $student_id );
		update_comment_meta( $progress->get_id(), 'start', $start_date );
		wp_update_comment(
			[
				'comment_ID'       => $progress->get_id(),
				'comment_date'     => '2024-01-03 14:00:00',
				'comment_date_gmt' => '2024-01-03 19:00:00',
			]
		);

		$output = $this->get_tool_output( $student_id, $course_id );

		self::assertStringContainsString(
			'Started on ' . $expected_start,
			$output,
			'Raw start metadata should retain its existing display in comments mode.'
		);
		self::assertStringContainsString(
			'Last activity on January 3, 2024 2:00 pm',
			$output,
			'The course comment GMT date should remain the comments activity baseline.'
		);
	}

	/**
	 * Existing start metadata and missing metadata have distinct legacy displays.
	 *
	 * @return array
	 */
	public static function legacy_start_dates(): array {
		return [
			'stored date'  => [ '2024-01-02 12:00:00', 'January 2, 2024 7:00 am' ],
			'missing date' => [ '', 'December 31, 1969 7:00 pm' ],
		];
	}

	/**
	 * A table progress ID must never identify an unrelated WordPress comment.
	 *
	 * @covers Sensei_Tool_Enrolment_Debug::process
	 */
	public function testProcess_TableProgressIdCollidedWithUnrelatedCommentGiven_ShowsSelectedProgress() {
		global $wpdb;

		$this->set_progress_storage_mode( 'tables' );
		$course_id        = $this->factory->course->create();
		$student_id       = $this->factory->user->create();
		$table_repository = new Tables_Based_Course_Progress_Repository( $wpdb );
		$progress         = $table_repository->create( $course_id, $student_id );
		$progress->start( new DateTimeImmutable( '2024-01-02 12:00:00 UTC' ) );
		$progress->complete();
		$table_repository->save( $progress );
		$comment_id = $this->factory->comment->create( [ 'comment_approved' => '0' ] );
		update_comment_meta( $comment_id, 'start', '1999-01-01 00:00:00' );
		$wpdb->update(
			$wpdb->prefix . 'sensei_lms_progress',
			[ 'id' => $comment_id, 'updated_at' => '2024-01-03 14:00:00' ],
			[ 'id' => $progress->get_id() ]
		);
		wp_cache_flush();

		$output = $this->get_tool_output( $student_id, $course_id );

		self::assertStringContainsString(
			'Completed (100%)',
			$output,
			'An unrelated comment must not override table progress status.'
		);
		self::assertStringContainsString(
			'Started on January 2, 2024 12:00 pm',
			$output,
			'The table start date should be used without a matching progress comment.'
		);
		self::assertStringContainsString(
			'Last activity on January 3, 2024 2:00 pm',
			$output,
			'The table update date should be the course activity baseline.'
		);
	}

	/**
	 * Unavailable table start dates must not be shown as actual start dates.
	 *
	 * @dataProvider unavailable_table_start_dates
	 * @covers Sensei_Tool_Enrolment_Debug::process
	 * @param string|null $started_at Stored table start date.
	 */
	public function testProcess_TableStartDateUnavailableGiven_OmitsStartDate( ?string $started_at ) {
		global $wpdb;

		$this->set_progress_storage_mode( 'tables' );
		$course_id        = $this->factory->course->create();
		$student_id       = $this->factory->user->create();
		$table_repository = new Tables_Based_Course_Progress_Repository( $wpdb );
		$progress         = $table_repository->create( $course_id, $student_id );
		$wpdb->update(
			$wpdb->prefix . 'sensei_lms_progress',
			[ 'started_at' => $started_at ],
			[ 'id' => $progress->get_id() ]
		);
		wp_cache_flush();

		$output = $this->get_tool_output( $student_id, $course_id );

		self::assertStringContainsString(
			'In Progress (0%)',
			$output,
			'Progress should remain visible when its start date is unavailable.'
		);
		self::assertStringNotContainsString(
			'Started on ',
			$output,
			'An unavailable table start date must not be presented as an actual date.'
		);
	}

	/**
	 * Tables can contain missing start dates and zero dates from migrated data.
	 *
	 * @return array
	 */
	public static function unavailable_table_start_dates(): array {
		return [
			'missing date'       => [ null ],
			'migrated zero date' => [ '0000-00-00 00:00:00' ],
		];
	}

	/**
	 * Run the public tool request and capture its rendered results.
	 *
	 * @param int $student_id Student ID.
	 * @param int $course_id  Course ID.
	 * @return string
	 */
	private function get_tool_output( int $student_id, int $course_id ): string {
		$original_get = $_GET;
		$_GET         = [
			'user_id'   => $student_id,
			'course_id' => $course_id,
			'_wpnonce'  => wp_create_nonce( Sensei_Tool_Enrolment_Debug::NONCE_ACTION ),
		];
		$tool         = new Sensei_Tool_Enrolment_Debug();

		try {
			$tool->process();
			ob_start();
			$tool->output();

			return ob_get_clean();
		} finally {
			$_GET = $original_get;
		}
	}
}
