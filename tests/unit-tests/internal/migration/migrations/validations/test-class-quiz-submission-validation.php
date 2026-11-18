<?php

namespace SenseiTest\Internal\Migration\Migrations\Validations;

use Sensei\Internal\Migration\Migration_Job_Scheduler;
use Sensei\Internal\Migration\Validations\Quiz_Submission_Validation;
use Sensei\Internal\Migration\Validations\Validation_Error;
use Sensei\Internal\Quiz_Submission\Answer\Repositories\Comments_Based_Answer_Repository;
use Sensei\Internal\Quiz_Submission\Answer\Repositories\Tables_Based_Answer_Repository;
use Sensei\Internal\Quiz_Submission\Grade\Repositories\Comments_Based_Grade_Repository;
use Sensei\Internal\Quiz_Submission\Grade\Repositories\Tables_Based_Grade_Repository;
use Sensei\Internal\Quiz_Submission\Submission\Repositories\Comments_Based_Submission_Repository;
use Sensei\Internal\Quiz_Submission\Submission\Repositories\Tables_Based_Submission_Repository;
use Sensei\Internal\Student_Progress\Lesson_Progress\Repositories\Comments_Based_Lesson_Progress_Repository;

class Quiz_Submission_Validation_Test extends \WP_UnitTestCase {
	use \Sensei_Course_Enrolment_Manual_Test_Helpers;

	/**
	 * Factory for creating test data.
	 *
	 * @var \Sensei_Factory
	 */
	protected $factory;

	protected function setUp(): void {
		parent::setUp();

		$this->factory = new \Sensei_Factory();
	}

	public function testRun_WhenProgressMigrationIsComplete_HasNoErrors(): void {
		/* Arrange. */
		$quiz_submission_validation = new Quiz_Submission_Validation();

		$this->mask_migration_as_complete();

		/* Act. */
		$quiz_submission_validation->run();

		/* Assert. */
		$this->assertFalse( $quiz_submission_validation->has_errors() );
	}

	public function testRun_WhenProgressMigrationIsNotComplete_HasError(): void {
		/* Arrange. */
		$quiz_submission_validation = new Quiz_Submission_Validation();

		/* Act. */
		$quiz_submission_validation->run();

		/* Assert. */
		$this->assertSame(
			'The progress migration is not complete. Please run the progress migration first.',
			$this->get_first_error_message( $quiz_submission_validation )
		);
	}

	/**
	 * Find quiz submissions without a table copy.
	 *
	 * @covers \Sensei\Internal\Migration\Validations\Quiz_Submission_Validation::run
	 */
	public function testRun_WhenQuizSubmissionIsMissingFromTables_ReportsQuizAndStudent(): void {
		$lesson_id   = $this->factory->lesson->create();
		$quiz_id     = $this->factory->maybe_create_quiz_for_lesson( $lesson_id );
		$question_id = $this->factory->question->create( array( 'quiz_id' => $quiz_id ) );
		$student_id  = 2;
		$progress    = ( new Comments_Based_Lesson_Progress_Repository() )->create( $lesson_id, $student_id );
		// The legacy repository uses this metadata to identify a submission.
		update_comment_meta( $progress->get_id(), 'questions_asked', (string) $question_id );
		$this->mask_migration_as_complete();
		$validation = new Quiz_Submission_Validation();

		$validation->run();

		$errors = $validation->get_errors();
		self::assertCount( 1, $errors, 'Report the missing submission once.' );
		self::assertSame( 'Tables-based quiz submission not found.', $errors[0]->get_message(), 'Identify which store is missing the submission.' );
		self::assertSame(
			array(
				'quiz_id' => $quiz_id,
				'user_id' => $student_id,
				'source'  => 'tables',
			),
			$errors[0]->get_data(),
			'Report the missing quiz and student record.'
		);
	}

	public function testRun_WhenCommentsBasedSubmissionDidntExist_HasErrors(): void {
		/* Arrange. */
		$lesson_id = $this->factory->lesson->create();
		$quiz_id   = $this->factory->quiz->create(
			array(
				'post_parent' => $lesson_id,
				'meta_input'  => array(
					'_quiz_lesson' => $lesson_id,
				),
			)
		);
		$this->factory->question->create_many(
			3,
			array(
				'quiz_id' => $quiz_id,
			)
		);

		global $wpdb;
		$tables_based_submission_repository = new Tables_Based_Submission_Repository( $wpdb );
		$tables_based_submission_repository->create( $quiz_id, 2 );

		$comments_based_lesson_progress_repository = new Comments_Based_Lesson_Progress_Repository();
		$comments_based_lesson_progress_repository->create( $lesson_id, 2 );

		$this->mask_migration_as_complete();
		$quiz_submission_validation = new Quiz_Submission_Validation();

		/* Act. */
		$quiz_submission_validation->run();

		/* Assert. */
		$this->assertSame(
			'Comments-based quiz submission not found.',
			$this->get_first_error_message( $quiz_submission_validation )
		);
	}

	/**
	 * Find a graded table submission after its lesson comment has been deleted.
	 *
	 * @covers \Sensei\Internal\Migration\Validations\Quiz_Submission_Validation::run
	 */
	public function testRun_WhenLessonCommentWasDeleted_ReportsMissingCommentsSubmission(): void {
		global $wpdb;

		$lesson_id   = $this->factory->lesson->create();
		$quiz_id     = $this->factory->maybe_create_quiz_for_lesson( $lesson_id );
		$question_id = $this->factory->question->create( array( 'quiz_id' => $quiz_id ) );
		$student_id  = 2;
		$comments    = new Comments_Based_Lesson_Progress_Repository();
		$comments->create( $lesson_id, $student_id );
		$tables_submissions = new Tables_Based_Submission_Repository( $wpdb );
		$tables_answers     = new Tables_Based_Answer_Repository( $wpdb );
		$tables_grades      = new Tables_Based_Grade_Repository( $wpdb );
		$submission         = $tables_submissions->create( $quiz_id, $student_id, 50 );
		$answer             = $tables_answers->create( $submission, $question_id, 'answer' );
		$tables_grades->create( $submission, $answer, $question_id, 1 );
		$comments->delete( $comments->get( $lesson_id, $student_id ) );
		$this->mask_migration_as_complete();
		$validation = new Quiz_Submission_Validation();

		$validation->run();

		$errors = $validation->get_errors();
		self::assertCount( 1, $errors, 'The table-only submission should be reported once.' );
		self::assertSame(
			'Comments-based quiz submission not found.',
			$errors[0]->get_message(),
			'The report should explain which store is missing the submission.'
		);
		self::assertSame(
			array(
				'quiz_id' => $quiz_id,
				'user_id' => $student_id,
				'source'  => 'comments',
			),
			$errors[0]->get_data(),
			'The report should include the quiz and student IDs.'
		);
		self::assertCount( 1, $tables_answers->get_all( $submission->get_id() ), 'Validation should preserve the answer.' );
		self::assertCount( 1, $tables_grades->get_all( $submission->get_id() ), 'Validation should preserve the grade.' );
	}

	public function testRun_WhenBothSubmissionsExist_HasNoErrors(): void {
		/* Arrange. */
		$lesson_id    = $this->factory->lesson->create();
		$quiz_id      = $this->factory->quiz->create(
			array(
				'post_parent' => $lesson_id,
				'meta_input'  => array(
					'_quiz_lesson' => $lesson_id,
				),
			)
		);
		$question_ids = $this->factory->question->create_many(
			3,
			array(
				'quiz_id' => $quiz_id,
			)
		);

		$comments_based_lesson_progress_repository = new Comments_Based_Lesson_Progress_Repository();
		$submission                                = $comments_based_lesson_progress_repository->create( $lesson_id, 2 );
		update_comment_meta( $submission->get_id(), 'questions_asked', implode( ',', $question_ids ) );

		global $wpdb;
		$tables_based_submission_repository = new Tables_Based_Submission_Repository( $wpdb );
		$tables_based_submission_repository->create( $quiz_id, 2 );

		$comments_based_submission_repository = new Comments_Based_Submission_Repository();
		$comments_based_submission_repository->create( $quiz_id, 2 );

		$this->mask_migration_as_complete();
		$quiz_submission_validation = new Quiz_Submission_Validation();

		/* Act. */
		$quiz_submission_validation->run();

		/* Assert. */
		$this->assertFalse( $quiz_submission_validation->has_errors() );
	}

	/**
	 * An ungraded submission differs from one that received zero.
	 *
	 * @covers \Sensei\Internal\Migration\Validations\Quiz_Submission_Validation::run
	 */
	public function testRun_WhenOneFinalGradeWasZeroAndOneWasAbsent_ReportsMismatchedGrades(): void {
		global $wpdb;

		$lesson_id   = $this->factory->lesson->create();
		$quiz_id     = $this->factory->maybe_create_quiz_for_lesson( $lesson_id );
		$question_id = $this->factory->question->create( array( 'quiz_id' => $quiz_id ) );
		$student_id  = 2;
		$progress    = ( new Comments_Based_Lesson_Progress_Repository() )->create( $lesson_id, $student_id );
		update_comment_meta( $progress->get_id(), 'questions_asked', (string) $question_id );
		( new Tables_Based_Submission_Repository( $wpdb ) )->create( $quiz_id, $student_id, 0.0 );
		$this->mask_migration_as_complete();
		$validation = new Quiz_Submission_Validation();

		$validation->run();

		self::assertSame(
			'Data mismatch between comments and tables based submissions.',
			$this->get_first_error_message( $validation )
		);
	}

	public function testRun_WhenTablesBasedAnswerDidntExist_HasErrors(): void {
		/* Arrange. */
		$lesson_id    = $this->factory->lesson->create();
		$quiz_id      = $this->factory->quiz->create(
			array(
				'post_parent' => $lesson_id,
				'meta_input'  => array(
					'_quiz_lesson' => $lesson_id,
				),
			)
		);
		$question_ids = $this->factory->question->create_many(
			3,
			array(
				'quiz_id' => $quiz_id,
			)
		);

		$comments_based_lesson_progress_repository = new Comments_Based_Lesson_Progress_Repository();
		$lesson_progress                           = $comments_based_lesson_progress_repository->create( $lesson_id, 2 );
		update_comment_meta( $lesson_progress->get_id(), 'questions_asked', implode( ',', $question_ids ) );

		global $wpdb;
		$tables_based_submission_repository = new Tables_Based_Submission_Repository( $wpdb );
		$tables_based_submission_repository->create( $quiz_id, 2 );

		$comments_based_submission_repository = new Comments_Based_Submission_Repository();
		$submission                           = $comments_based_submission_repository->create( $quiz_id, 2 );

		$comments_based_answer_repository = new Comments_Based_Answer_Repository();
		foreach ( $question_ids as $question_id ) {
			$comments_based_answer_repository->create( $submission, $question_id, 'answer' );
		}

		$this->mask_migration_as_complete();
		$quiz_submission_validation = new Quiz_Submission_Validation();

		/* Act. */
		$quiz_submission_validation->run();

		/* Assert. */
		$this->assertSame(
			array(
				'Number of answers does not match.',
				'Answers missing in tables based answers.',
				'Answers missing in tables based answers.',
				'Answers missing in tables based answers.',
			),
			$this->export_errors( $quiz_submission_validation )
		);
	}

	public function testRun_WhenCommentsBasedAnswerDidntExist_HasErrors(): void {
		/* Arrange. */
		$lesson_id    = $this->factory->lesson->create();
		$quiz_id      = $this->factory->quiz->create(
			array(
				'post_parent' => $lesson_id,
				'meta_input'  => array(
					'_quiz_lesson' => $lesson_id,
				),
			)
		);
		$question_ids = $this->factory->question->create_many(
			3,
			array(
				'quiz_id' => $quiz_id,
			)
		);

		$comments_based_lesson_progress_repository = new Comments_Based_Lesson_Progress_Repository();
		$lesson_progress                           = $comments_based_lesson_progress_repository->create( $lesson_id, 2 );
		update_comment_meta( $lesson_progress->get_id(), 'questions_asked', implode( ',', $question_ids ) );

		global $wpdb;
		$tables_based_submission_repository = new Tables_Based_Submission_Repository( $wpdb );
		$submission                         = $tables_based_submission_repository->create( $quiz_id, 2 );

		$comments_based_submission_repository = new Comments_Based_Submission_Repository();
		$comments_based_submission_repository->create( $quiz_id, 2 );

		$tables_based_answer_repository = new Tables_Based_Answer_Repository( $wpdb );
		foreach ( $question_ids as $question_id ) {
			$tables_based_answer_repository->create( $submission, $question_id, 'answer' );
		}

		$this->mask_migration_as_complete();
		$quiz_submission_validation = new Quiz_Submission_Validation();

		/* Act. */
		$quiz_submission_validation->run();

		/* Assert. */
		$this->assertSame(
			array(
				'Number of answers does not match.',
				'Answers missing in comments based answers.',
				'Answers missing in comments based answers.',
				'Answers missing in comments based answers.',
			),
			$this->export_errors( $quiz_submission_validation )
		);
	}

	public function testRun_WhenBothKindsOfAnswersExist_HasNoErrors(): void {
		/* Arrange. */
		$lesson_id    = $this->factory->lesson->create();
		$quiz_id      = $this->factory->quiz->create(
			array(
				'post_parent' => $lesson_id,
				'meta_input'  => array(
					'_quiz_lesson' => $lesson_id,
				),
			)
		);
		$question_ids = $this->factory->question->create_many(
			3,
			array(
				'quiz_id' => $quiz_id,
			)
		);

		$comments_based_lesson_progress_repository = new Comments_Based_Lesson_Progress_Repository();
		$lesson_progress                           = $comments_based_lesson_progress_repository->create( $lesson_id, 2 );
		update_comment_meta( $lesson_progress->get_id(), 'questions_asked', implode( ',', $question_ids ) );

		global $wpdb;
		$tables_based_submission_repository = new Tables_Based_Submission_Repository( $wpdb );
		$tables_based_submission            = $tables_based_submission_repository->create( $quiz_id, 2 );

		$comments_based_submission_repository = new Comments_Based_Submission_Repository();
		$comments_based_submission            = $comments_based_submission_repository->create( $quiz_id, 2 );

		$tables_based_answer_repository   = new Tables_Based_Answer_Repository( $wpdb );
		$comments_based_answer_repository = new Comments_Based_Answer_Repository();
		foreach ( $question_ids as $question_id ) {
			$tables_based_answer_repository->create( $tables_based_submission, $question_id, 'answer' );
			$comments_based_answer_repository->create( $comments_based_submission, $question_id, 'answer' );
		}

		$this->mask_migration_as_complete();
		$quiz_submission_validation = new Quiz_Submission_Validation();

		/* Act. */
		$quiz_submission_validation->run();

		/* Assert. */
		$this->assertFalse( $quiz_submission_validation->has_errors() );
	}

	public function testRun_WhenTablesBasedGradingDidntExist_HasErrors(): void {
		/* Arrange. */
		$lesson_id    = $this->factory->lesson->create();
		$quiz_id      = $this->factory->quiz->create(
			array(
				'post_parent' => $lesson_id,
				'meta_input'  => array(
					'_quiz_lesson' => $lesson_id,
				),
			)
		);
		$question_ids = $this->factory->question->create_many(
			3,
			array(
				'quiz_id' => $quiz_id,
			)
		);

		$comments_based_lesson_progress_repository = new Comments_Based_Lesson_Progress_Repository();
		$lesson_progress                           = $comments_based_lesson_progress_repository->create( $lesson_id, 2 );
		update_comment_meta( $lesson_progress->get_id(), 'questions_asked', implode( ',', $question_ids ) );

		global $wpdb;
		$tables_based_submission_repository = new Tables_Based_Submission_Repository( $wpdb );
		$tables_based_submission            = $tables_based_submission_repository->create( $quiz_id, 2 );

		$comments_based_submission_repository = new Comments_Based_Submission_Repository();
		$comments_based_submission            = $comments_based_submission_repository->create( $quiz_id, 2 );

		$tables_based_answer_repository   = new Tables_Based_Answer_Repository( $wpdb );
		$comments_based_answer_repository = new Comments_Based_Answer_Repository();
		$comments_based_grade_repository  = new Comments_Based_Grade_Repository();
		foreach ( $question_ids as $question_id ) {
			$tables_based_answer_repository->create( $tables_based_submission, $question_id, 'answer' );

			$answer = $comments_based_answer_repository->create( $comments_based_submission, $question_id, 'answer' );
			$comments_based_grade_repository->create( $comments_based_submission, $answer, $question_id, 1 );
		}

		$this->mask_migration_as_complete();
		$quiz_submission_validation = new Quiz_Submission_Validation();

		/* Act. */
		$quiz_submission_validation->run();

		/* Assert. */
		$this->assertSame(
			array(
				'Number of gradings does not match.',
				'Grades missing in tables based grades.',
				'Grades missing in tables based grades.',
				'Grades missing in tables based grades.',
			),
			$this->export_errors( $quiz_submission_validation )
		);
	}

	public function testRun_WhenCommentsBasedGradingDidntExist_HasErrors(): void {
		/* Arrange. */
		$lesson_id    = $this->factory->lesson->create();
		$quiz_id      = $this->factory->quiz->create(
			array(
				'post_parent' => $lesson_id,
				'meta_input'  => array(
					'_quiz_lesson' => $lesson_id,
				),
			)
		);
		$question_ids = $this->factory->question->create_many(
			3,
			array(
				'quiz_id' => $quiz_id,
			)
		);

		$comments_based_lesson_progress_repository = new Comments_Based_Lesson_Progress_Repository();
		$lesson_progress                           = $comments_based_lesson_progress_repository->create( $lesson_id, 2 );
		update_comment_meta( $lesson_progress->get_id(), 'questions_asked', implode( ',', $question_ids ) );

		global $wpdb;
		$tables_based_submission_repository = new Tables_Based_Submission_Repository( $wpdb );
		$tables_based_submission            = $tables_based_submission_repository->create( $quiz_id, 2 );

		$comments_based_submission_repository = new Comments_Based_Submission_Repository();
		$comments_based_submission            = $comments_based_submission_repository->create( $quiz_id, 2 );

		$tables_based_answer_repository   = new Tables_Based_Answer_Repository( $wpdb );
		$comments_based_answer_repository = new Comments_Based_Answer_Repository();
		$tables_based_grade_repository    = new Tables_Based_Grade_Repository( $wpdb );
		foreach ( $question_ids as $question_id ) {
			$comments_based_answer_repository->create( $comments_based_submission, $question_id, 'answer' );

			$answer = $tables_based_answer_repository->create( $tables_based_submission, $question_id, 'answer' );
			$tables_based_grade_repository->create( $tables_based_submission, $answer, $question_id, 1 );
		}

		$this->mask_migration_as_complete();
		$quiz_submission_validation = new Quiz_Submission_Validation();

		/* Act. */
		$quiz_submission_validation->run();

		/* Assert. */
		$this->assertSame(
			array(
				'Number of gradings does not match.',
				'Grades missing in comments based grades.',
				'Grades missing in comments based grades.',
				'Grades missing in comments based grades.',
			),
			$this->export_errors( $quiz_submission_validation )
		);
	}

	public function testRun_WhenBothTypesOfGradingExist_HasNoErrors(): void {
		/* Arrange. */
		$lesson_id    = $this->factory->lesson->create();
		$quiz_id      = $this->factory->quiz->create(
			array(
				'post_parent' => $lesson_id,
				'meta_input'  => array(
					'_quiz_lesson' => $lesson_id,
				),
			)
		);
		$question_ids = $this->factory->question->create_many(
			3,
			array(
				'quiz_id' => $quiz_id,
			)
		);

		$comments_based_lesson_progress_repository = new Comments_Based_Lesson_Progress_Repository();
		$lesson_progress                           = $comments_based_lesson_progress_repository->create( $lesson_id, 2 );
		update_comment_meta( $lesson_progress->get_id(), 'questions_asked', implode( ',', $question_ids ) );

		global $wpdb;
		$tables_based_submission_repository = new Tables_Based_Submission_Repository( $wpdb );
		$tables_based_submission            = $tables_based_submission_repository->create( $quiz_id, 2 );

		$comments_based_submission_repository = new Comments_Based_Submission_Repository();
		$comments_based_submission            = $comments_based_submission_repository->create( $quiz_id, 2 );

		$tables_based_answer_repository   = new Tables_Based_Answer_Repository( $wpdb );
		$tables_based_grade_repository    = new Tables_Based_Grade_Repository( $wpdb );
		$comments_based_grade_repository  = new Comments_Based_Grade_Repository();
		$comments_based_answer_repository = new Comments_Based_Answer_Repository();
		foreach ( $question_ids as $question_id ) {
			$tables_based_answer = $tables_based_answer_repository->create( $tables_based_submission, $question_id, 'answer' );
			$tables_based_grade_repository->create( $tables_based_submission, $tables_based_answer, $question_id, 1 );

			$comments_based_answer = $comments_based_answer_repository->create( $comments_based_submission, $question_id, 'answer' );
			$comments_based_grade_repository->create( $comments_based_submission, $comments_based_answer, $question_id, 1 );
		}

		$this->mask_migration_as_complete();
		$quiz_submission_validation = new Quiz_Submission_Validation();

		/* Act. */
		$quiz_submission_validation->run();

		/* Assert. */
		$this->assertSame( array(), $this->export_errors( $quiz_submission_validation ) );
	}

	/**
	 * An answer with no submission remains discoverable by its own row ID.
	 *
	 * @covers \Sensei\Internal\Migration\Validations\Quiz_Submission_Validation::run
	 */
	public function testRun_WhenAnswerSubmissionWasDeleted_ReportsTheOrphanedAnswer(): void {
		global $wpdb;

		$quiz_id     = $this->factory->quiz->create();
		$question_id = $this->factory->question->create( array( 'quiz_id' => $quiz_id ) );
		$submissions = new Tables_Based_Submission_Repository( $wpdb );
		$answers     = new Tables_Based_Answer_Repository( $wpdb );
		$submission  = $submissions->create( $quiz_id, 2 );
		$answer      = $answers->create( $submission, $question_id, 'answer' );
		$submissions->delete( $submission );
		$this->mask_migration_as_complete();
		$validation = new Quiz_Submission_Validation();

		$validation->run();

		$errors = $validation->get_errors();
		self::assertCount( 1, $errors, 'Report the orphaned answer even when neither store has a submission.' );
		self::assertSame( 'Tables-based quiz answer has no submission.', $errors[0]->get_message(), 'Explain why the answer is orphaned.' );
		self::assertSame(
			array(
				'answer_id'     => $answer->get_id(),
				'submission_id' => $submission->get_id(),
				'question_id'   => $question_id,
				'source'        => 'tables',
			),
			$errors[0]->get_data(),
			'Report only the identities linked to the surviving answer row.'
		);
	}

	/**
	 * A grade with no answer remains discoverable by its own row ID.
	 *
	 * @covers \Sensei\Internal\Migration\Validations\Quiz_Submission_Validation::run
	 */
	public function testRun_WhenGradeAnswerWasDeleted_ReportsTheOrphanedGrade(): void {
		global $wpdb;

		$quiz_id     = $this->factory->quiz->create();
		$question_id = $this->factory->question->create( array( 'quiz_id' => $quiz_id ) );
		$submissions = new Tables_Based_Submission_Repository( $wpdb );
		$answers     = new Tables_Based_Answer_Repository( $wpdb );
		$grades      = new Tables_Based_Grade_Repository( $wpdb );
		$submission  = $submissions->create( $quiz_id, 2 );
		$answer      = $answers->create( $submission, $question_id, 'answer' );
		$grade       = $grades->create( $submission, $answer, $question_id, 1 );
		$answers->delete_all( $submission );
		$submissions->delete( $submission );
		$this->mask_migration_as_complete();
		$validation = new Quiz_Submission_Validation();

		$validation->run();

		$errors = $validation->get_errors();
		self::assertCount( 1, $errors, 'Report the orphaned grade even when neither store has a submission.' );
		self::assertSame( 'Tables-based quiz grade has no answer.', $errors[0]->get_message(), 'Explain why the grade is orphaned.' );
		self::assertSame(
			array(
				'grade_id'    => $grade->get_id(),
				'answer_id'   => $answer->get_id(),
				'question_id' => $question_id,
				'source'      => 'tables',
			),
			$errors[0]->get_data(),
			'Report only the identities linked to the surviving grade row.'
		);
	}

	private function mask_migration_as_complete(): void {
		update_option( Migration_Job_Scheduler::COMPLETED_OPTION_NAME, microtime( true ) );
	}

	private function get_first_error_message( Quiz_Submission_Validation $quiz_submission_validation ): ?string {
		$errors = $quiz_submission_validation->get_errors();

		if ( empty( $errors ) ) {
			return null;
		}

		return $errors[0]->get_message();
	}

	private function export_errors( Quiz_Submission_Validation $quiz_submission_validation ): array {
		$errors = $quiz_submission_validation->get_errors();

		if ( empty( $errors ) ) {
			return array();
		}

		return array_map(
			fn ( Validation_Error $error ) => $error->get_message(),
			$errors
		);
	}
}
