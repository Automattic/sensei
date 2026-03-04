<?php
/**
 * Tests for Progress_Aggregate_Query.
 *
 * @package sensei-tests
 */

// phpcs:disable WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid -- Using PHPUnit conventions.
// phpcs:disable PSR2.Classes.PropertyDeclaration.Underscore

use Sensei\Internal\Student_Progress\Query\Progress_Aggregate_Query;

/**
 * Class Progress_Aggregate_Query_Test.
 *
 * Tests sum_grades in both comments and HPPS tables modes.
 *
 * @covers \Sensei\Internal\Student_Progress\Query\Progress_Aggregate_Query
 */
class Progress_Aggregate_Query_Test extends WP_UnitTestCase {

	use Sensei_HPPS_Helpers;

	/**
	 * Factory for creating test data.
	 *
	 * @var Sensei_Factory
	 */
	protected $factory;

	/**
	 * Set up before each test.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->factory = new Sensei_Factory();
	}

	/**
	 * Tear down after each test.
	 */
	public function tearDown(): void {
		parent::tearDown();
		$this->factory->tearDown();
	}

	/**
	 * Test that sum_grades returns correct sum in comments mode.
	 */
	public function testSumGrades_CommentsMode_ReturnsCorrectSum() {
		/* Arrange. */
		$course_id  = $this->factory->course->create();
		$lesson_ids = $this->factory->lesson->create_many(
			2,
			[
				'meta_input' => [
					'_lesson_course'      => $course_id,
					'_quiz_has_questions' => 1,
				],
			]
		);
		$user_ids   = $this->factory->user->create_many( 2 );

		$comment_ids   = [];
		$comment_ids[] = $this->create_lesson_status( $lesson_ids[0], $user_ids[0], 'graded' );
		$comment_ids[] = $this->create_lesson_status( $lesson_ids[0], $user_ids[1], 'passed' );
		$comment_ids[] = $this->create_lesson_status( $lesson_ids[1], $user_ids[0], 'failed' );

		add_comment_meta( $comment_ids[0], 'grade', '30' );
		add_comment_meta( $comment_ids[1], 'grade', '80' );
		add_comment_meta( $comment_ids[2], 'grade', '40' );

		$query = new Progress_Aggregate_Query( false );

		/* Act. */
		$sum = $query->sum_grades( $course_id );

		/* Assert. */
		$this->assertSame( 150, $sum );
	}

	/**
	 * Test that sum_grades returns 0 for an empty course.
	 */
	public function testSumGrades_CommentsMode_EmptyCourse_ReturnsZero() {
		/* Arrange. */
		$course_id = $this->factory->course->create();
		$query     = new Progress_Aggregate_Query( false );

		/* Act. */
		$sum = $query->sum_grades( $course_id );

		/* Assert. */
		$this->assertSame( 0, $sum );
	}

	/**
	 * Test that sum_grades excludes in-progress statuses in comments mode.
	 */
	public function testSumGrades_CommentsMode_ExcludesInProgress() {
		/* Arrange. */
		$course_id = $this->factory->course->create();
		$lesson_id = $this->factory->lesson->create(
			[
				'meta_input' => [
					'_lesson_course'      => $course_id,
					'_quiz_has_questions' => 1,
				],
			]
		);
		$user_id   = $this->factory->user->create();

		$graded_comment      = $this->create_lesson_status( $lesson_id, $user_id, 'graded' );
		$in_progress_comment = $this->create_lesson_status( $lesson_id, $this->factory->user->create(), 'in-progress' );

		add_comment_meta( $graded_comment, 'grade', '75' );
		add_comment_meta( $in_progress_comment, 'grade', '50' );

		$query = new Progress_Aggregate_Query( false );

		/* Act. */
		$sum = $query->sum_grades( $course_id );

		/* Assert. */
		$this->assertSame( 75, $sum );
	}

	/**
	 * Test that sum_grades returns correct sum in tables mode.
	 */
	public function testSumGrades_TablesMode_ReturnsCorrectSum() {
		/* Arrange. */
		$this->enable_hpps_tables_repository();

		$course_id  = $this->factory->course->create();
		$lesson_ids = $this->factory->lesson->create_many(
			2,
			[
				'meta_input' => [
					'_lesson_course'      => $course_id,
					'_quiz_has_questions' => 1,
				],
			]
		);
		$quiz_ids   = [];
		foreach ( $lesson_ids as $lesson_id ) {
			$quiz_ids[] = $this->factory->quiz->create( [ 'post_parent' => $lesson_id ] );
		}
		$user_ids = $this->factory->user->create_many( 2 );

		$this->insert_quiz_progress( $quiz_ids[0], $user_ids[0], 'graded' );
		$this->insert_quiz_submission( $quiz_ids[0], $user_ids[0], 30.0 );

		$this->insert_quiz_progress( $quiz_ids[0], $user_ids[1], 'passed' );
		$this->insert_quiz_submission( $quiz_ids[0], $user_ids[1], 80.0 );

		$this->insert_quiz_progress( $quiz_ids[1], $user_ids[0], 'failed' );
		$this->insert_quiz_submission( $quiz_ids[1], $user_ids[0], 40.0 );

		$query = new Progress_Aggregate_Query( true );

		/* Act. */
		$sum = $query->sum_grades( $course_id );

		/* Assert. */
		$this->assertSame( 150, $sum );

		/* Cleanup. */
		$this->reset_hpps_repository();
	}

	/**
	 * Test that sum_grades returns 0 for an empty course in tables mode.
	 */
	public function testSumGrades_TablesMode_EmptyCourse_ReturnsZero() {
		/* Arrange. */
		$this->enable_hpps_tables_repository();

		$course_id = $this->factory->course->create();
		$query     = new Progress_Aggregate_Query( true );

		/* Act. */
		$sum = $query->sum_grades( $course_id );

		/* Assert. */
		$this->assertSame( 0, $sum );

		/* Cleanup. */
		$this->reset_hpps_repository();
	}

	/**
	 * Test that sum_grades excludes in-progress statuses in tables mode.
	 */
	public function testSumGrades_TablesMode_ExcludesInProgress() {
		/* Arrange. */
		$this->enable_hpps_tables_repository();

		$course_id = $this->factory->course->create();
		$lesson_id = $this->factory->lesson->create(
			[
				'meta_input' => [
					'_lesson_course'      => $course_id,
					'_quiz_has_questions' => 1,
				],
			]
		);
		$quiz_id   = $this->factory->quiz->create( [ 'post_parent' => $lesson_id ] );
		$user_ids  = $this->factory->user->create_many( 2 );

		$this->insert_quiz_progress( $quiz_id, $user_ids[0], 'graded' );
		$this->insert_quiz_submission( $quiz_id, $user_ids[0], 75.0 );

		$this->insert_quiz_progress( $quiz_id, $user_ids[1], 'in-progress' );
		$this->insert_quiz_submission( $quiz_id, $user_ids[1], 50.0 );

		$query = new Progress_Aggregate_Query( true );

		/* Act. */
		$sum = $query->sum_grades( $course_id );

		/* Assert. */
		$this->assertSame( 75, $sum );

		/* Cleanup. */
		$this->reset_hpps_repository();
	}

	/**
	 * Test the delegated call in Sensei_Grading works in comments mode.
	 */
	public function testGetCourseUsersGradesSum_Delegated_CommentsMode_ReturnsCorrectSum() {
		$this->skip_in_hpps_mode( 'Delegated test uses Sensei() which is wired at boot time.' );

		/* Arrange. */
		$course_id  = $this->factory->course->create();
		$lesson_id  = $this->factory->lesson->create(
			[
				'meta_input' => [
					'_lesson_course'      => $course_id,
					'_quiz_has_questions' => 1,
				],
			]
		);
		$user_id    = $this->factory->user->create();
		$comment_id = $this->create_lesson_status( $lesson_id, $user_id, 'passed' );
		add_comment_meta( $comment_id, 'grade', '95' );

		/* Act. */
		$sum = Sensei_Grading::get_course_users_grades_sum( $course_id );

		/* Assert. */
		$this->assertSame( 95, $sum );
	}

	/**
	 * Test the delegated call in Sensei_Grading works in HPPS tables mode.
	 */
	public function testGetCourseUsersGradesSum_Delegated_TablesMode_ReturnsCorrectSum() {
		/* Arrange. */
		$this->enable_hpps_tables_repository();

		$original_query                        = Sensei()->progress_aggregate_query;
		Sensei()->progress_aggregate_query = new Progress_Aggregate_Query( true );

		$course_id = $this->factory->course->create();
		$lesson_id = $this->factory->lesson->create(
			[
				'meta_input' => [
					'_lesson_course'      => $course_id,
					'_quiz_has_questions' => 1,
				],
			]
		);
		$quiz_id   = $this->factory->quiz->create( [ 'post_parent' => $lesson_id ] );
		$user_id   = $this->factory->user->create();

		$this->insert_quiz_progress( $quiz_id, $user_id, 'passed' );
		$this->insert_quiz_submission( $quiz_id, $user_id, 95.0 );

		/* Act. */
		$sum = Sensei_Grading::get_course_users_grades_sum( $course_id );

		/* Assert. */
		$this->assertSame( 95, $sum );

		/* Cleanup. */
		Sensei()->progress_aggregate_query = $original_query;
		$this->reset_hpps_repository();
	}

	/**
	 * Create a lesson status comment.
	 *
	 * @param int    $lesson_id Lesson ID.
	 * @param int    $user_id   User ID.
	 * @param string $status    Status.
	 * @return int Comment ID.
	 */
	private function create_lesson_status( int $lesson_id, int $user_id, string $status ): int {
		return $this->factory->comment->create(
			[
				'comment_type'     => 'sensei_lesson_status',
				'comment_approved' => $status,
				'comment_post_ID'  => $lesson_id,
				'user_id'          => $user_id,
			]
		);
	}

	/**
	 * Insert a quiz progress record into the HPPS progress table.
	 *
	 * @param int    $quiz_id Quiz ID.
	 * @param int    $user_id User ID.
	 * @param string $status  Status.
	 */
	private function insert_quiz_progress( int $quiz_id, int $user_id, string $status ): void {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Test helper.
		$wpdb->insert(
			$wpdb->prefix . 'sensei_lms_progress',
			[
				'post_id'    => $quiz_id,
				'user_id'    => $user_id,
				'type'       => 'quiz',
				'status'     => $status,
				'created_at' => current_time( 'mysql', true ),
				'updated_at' => current_time( 'mysql', true ),
			]
		);
	}

	/**
	 * Insert a quiz submission record into the HPPS quiz submissions table.
	 *
	 * @param int   $quiz_id     Quiz ID.
	 * @param int   $user_id     User ID.
	 * @param float $final_grade Final grade.
	 */
	private function insert_quiz_submission( int $quiz_id, int $user_id, float $final_grade ): void {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Test helper.
		$wpdb->insert(
			$wpdb->prefix . 'sensei_lms_quiz_submissions',
			[
				'quiz_id'     => $quiz_id,
				'user_id'     => $user_id,
				'final_grade' => $final_grade,
				'created_at'  => current_time( 'mysql', true ),
				'updated_at'  => current_time( 'mysql', true ),
			]
		);
	}
}
