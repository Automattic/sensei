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
