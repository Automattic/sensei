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

	public function testRun_WhenTablesBasedSubmissinDidntExist_HasErrors(): void {
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

		$comments_based_submission_repository = new Comments_Based_Submission_Repository();
		$comments_based_submission_repository->create( $quiz_id, 2 );

		$this->mask_migration_as_complete();
		$quiz_submission_validation = new Quiz_Submission_Validation();

		/* Act. */
		$quiz_submission_validation->run();

		/* Assert. */
		$this->assertSame(
			'Tables-based quiz submission not found.',
			$this->get_first_error_message( $quiz_submission_validation )
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
