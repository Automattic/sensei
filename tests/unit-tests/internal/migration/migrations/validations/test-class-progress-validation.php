<?php

namespace SenseiTest\Internal\Migration\Validations;

use Sensei\Internal\Migration\Migration_Job_Scheduler;
use Sensei\Internal\Migration\Migrations\Student_Progress_Migration;
use Sensei\Internal\Migration\Validations\Progress_Validation;
use Sensei\Internal\Student_Progress\Course_Progress\Repositories\Tables_Based_Course_Progress_Repository;
use Sensei\Internal\Student_Progress\Lesson_Progress\Repositories\Comments_Based_Lesson_Progress_Repository;
use Sensei\Internal\Student_Progress\Lesson_Progress\Repositories\Tables_Based_Lesson_Progress_Repository;
use Sensei\Internal\Student_Progress\Quiz_Progress\Repositories\Tables_Based_Quiz_Progress_Repository;
use Sensei_Course_Enrolment;
use Sensei_Factory;
use Sensei_Quiz;
use Sensei_Utils;

/**
 * Class Progress_Validation_Test
 *
 * @covers \Sensei\Internal\Migration\Validations\Progress_Validation
 */
class Progress_Validation_Test extends \WP_UnitTestCase {
	use \Sensei_Course_Enrolment_Manual_Test_Helpers;
	use \Sensei_HPPS_Helpers;

	/**
	 * Factory for creating test data.
	 *
	 * @var Sensei_Factory
	 */
	protected $factory;

	protected function setUp(): void {
		parent::setUp();

		self::resetCourseEnrolmentManager();
		$this->factory = new Sensei_Factory();
	}

	public function tearDown(): void {
		$this->restore_progress_storage_mode();
		parent::tearDown();
	}

	public function testRun_WhenProgressMigrationIsComplete_HasNoErrors(): void {
		/* Arrange. */
		$progress_validation = new Progress_Validation();

		$this->mask_migration_as_complete();

		/* Act. */
		$progress_validation->run();

		/* Assert. */
		$this->assertFalse( $progress_validation->has_errors() );
	}

	public function testRun_WhenProgressMigrationIsNotComplete_HasError(): void {
		/* Arrange. */
		$progress_validation = new Progress_Validation();

		/* Act. */
		$progress_validation->run();

		/* Assert. */
		$this->assertSame(
			'The progress migration is not complete. Please run the progress migration first.',
			$this->get_first_error_message( $progress_validation )
		);
	}

	public function testRun_WhenHasCourseProgressInTable_HasNoErrors(): void {
		/* Arrange. */
		$course_id           = $this->factory->course->create();
		$progress_validation = new Progress_Validation();

		$this->directlyEnrolStudent( 1, $course_id );
		$this->migrate_progress();

		/* Act. */
		$progress_validation->run();

		/* Assert. */
		$this->assertFalse( $progress_validation->has_errors() );
	}

	public function testRun_WhenHasNoCourseProgressInTable_HasError(): void {
		/* Arrange. */
		global $wpdb;
		$course_id           = $this->factory->course->create();
		$progress_validation = new Progress_Validation();
		$progress_repository = new Tables_Based_Course_Progress_Repository( $wpdb );

		$this->directlyEnrolStudent( 1, $course_id );
		$this->migrate_progress();
		$progress_repository->delete_for_course( $course_id );

		/* Act. */
		$progress_validation->run();

		/* Assert. */
		$this->assertSame(
			'Course tables based progress not found.',
			$this->get_first_error_message( $progress_validation )
		);
	}

	public function testRun_WhenHasCourseProgressButDataIsNotMatching_HasError(): void {
		/* Arrange. */
		global $wpdb;
		$course_id           = $this->factory->course->create();
		$progress_validation = new Progress_Validation();
		$progress_repository = new Tables_Based_Course_Progress_Repository( $wpdb );

		$this->directlyEnrolStudent( 1, $course_id );
		$this->migrate_progress();

		$progress = $progress_repository->get( $course_id, 1 );
		$progress->complete();
		$progress_repository->save( $progress );

		/* Act. */
		$progress_validation->run();

		/* Assert. */
		$this->assertSame(
			'Data mismatch between comments and tables based progress.',
			$this->get_first_error_message( $progress_validation )
		);
	}

	public function testRun_WhenHasLessonProgressInTable_HasNoErrors(): void {
		/* Arrange. */
		$lesson_id           = $this->factory->lesson->create();
		$progress_validation = new Progress_Validation();

		Sensei_Utils::user_start_lesson( 1, $lesson_id );
		$this->migrate_progress();

		/* Act. */
		$progress_validation->run();

		/* Assert. */
		$this->assertFalse( $progress_validation->has_errors() );
	}

	public function testRun_WhenHasNoLessonProgressInTable_HasError(): void {
		/* Arrange. */
		global $wpdb;
		$lesson_id           = $this->factory->lesson->create();
		$progress_validation = new Progress_Validation();
		$progress_repository = new Tables_Based_Lesson_Progress_Repository( $wpdb );

		Sensei_Utils::user_start_lesson( 1, $lesson_id );
		$this->migrate_progress();
		$progress_repository->delete_for_lesson( $lesson_id );

		/* Act. */
		$progress_validation->run();

		/* Assert. */
		$this->assertSame(
			'Lesson tables based progress not found.',
			$this->get_first_error_message( $progress_validation )
		);
	}

	public function testRun_WhenHasLessonProgressButDataIsNotMatching_HasError(): void {
		/* Arrange. */
		global $wpdb;
		$lesson_id           = $this->factory->lesson->create();
		$progress_validation = new Progress_Validation();
		$progress_repository = new Tables_Based_Lesson_Progress_Repository( $wpdb );

		Sensei_Utils::user_start_lesson( 1, $lesson_id );
		$this->migrate_progress();
		$progress = $progress_repository->get( $lesson_id, 1 );
		$progress->complete();
		$progress_repository->save( $progress );

		/* Act. */
		$progress_validation->run();

		/* Assert. */
		$this->assertSame(
			'Data mismatch between comments and tables based progress.',
			$this->get_first_error_message( $progress_validation )
		);
	}

	/**
	 * Quiz submission can replace the shared comment's lesson start with the quiz start.
	 *
	 * @covers \Sensei\Internal\Migration\Validations\Progress_Validation::run
	 */
	public function testRun_WhenQuizWasSubmittedAfterLessonStarted_WarnsWhenLessonStartCannotBeVerified(): void {
		/* Arrange. */
		[ $lesson_id, $quiz_id, $student_id ] = $this->create_enrolled_quiz_student();
		Sensei_Utils::sensei_start_lesson( $lesson_id, $student_id );
		$lesson_progress = Sensei()->lesson_progress_repository->get( $lesson_id, $student_id );
		$lesson_progress->start( new \DateTimeImmutable( '2025-01-01T00:00:00Z' ) );
		Sensei()->lesson_progress_repository->save( $lesson_progress );
		$this->submit_quiz_for_grading( $lesson_id, $quiz_id, $student_id );
		$validation = new Progress_Validation();

		$expected_warnings = self::is_hpps_tables_mode()
			? array(
				array(
					'Lesson start date could not be verified because the shared comment now records the quiz start.',
					array(
						'lesson_id' => $lesson_id,
						'user_id'   => $student_id,
					),
				),
			)
			: array();

		/* Act. */
		$validation->run();

		/* Assert. */
		self::assertSame( array(), $validation->get_errors(), 'The overwritten lesson start must not cause a mismatch error.' );
		self::assertSame(
			$expected_warnings,
			array_map(
				static function ( $warning ): array {
					return array( $warning->get_message(), $warning->get_data() );
				},
				$validation->get_warnings()
			),
			'Report the unverifiable lesson start separately from errors, with lesson and student IDs.'
		);
	}

	/**
	 * Differing start dates remain errors when the shared comment does not match the quiz start.
	 *
	 * @covers \Sensei\Internal\Migration\Validations\Progress_Validation::run
	 */
	public function testRun_WhenSubmittedQuizStartDifferedFromSharedComment_ReportsLessonAndQuizDifferences(): void {
		/* Arrange. */
		global $wpdb;

		[ $lesson_id, $quiz_id, $student_id ] = $this->create_enrolled_quiz_student();
		Sensei_Utils::sensei_start_lesson( $lesson_id, $student_id );
		$lesson_progress = Sensei()->lesson_progress_repository->get( $lesson_id, $student_id );
		$lesson_progress->start( new \DateTimeImmutable( '2025-01-01T00:00:00Z' ) );
		Sensei()->lesson_progress_repository->save( $lesson_progress );
		$this->submit_quiz_for_grading( $lesson_id, $quiz_id, $student_id );
		$lesson_repository = new Tables_Based_Lesson_Progress_Repository( $wpdb );
		$lesson_progress   = $lesson_repository->get( $lesson_id, $student_id );
		$lesson_progress->start( new \DateTimeImmutable( '2024-01-01T00:00:00Z' ) );
		$lesson_repository->save( $lesson_progress );
		$repository    = new Tables_Based_Quiz_Progress_Repository( $wpdb );
		$quiz_progress = $repository->get( $quiz_id, $student_id );
		$quiz_progress->start( new \DateTimeImmutable( '@' . ( $quiz_progress->get_started_at()->getTimestamp() + 1 ) ) );
		$quiz_progress->ungrade();
		$repository->save( $quiz_progress );
		$validation = new Progress_Validation();

		/* Act. */
		$validation->run();

		/* Assert. */
		$errors = $validation->get_errors();
		self::assertCount( 2, $errors, 'Report both start differences when the quiz start does not explain the lesson start.' );
		self::assertSame( 'Data mismatch between comments and tables based progress.', $errors[0]->get_message(), 'Keep reporting mismatched progress.' );
		self::assertSame( $lesson_id, $errors[0]->get_data()[0]['post_id'], 'Identify the affected lesson.' );
		self::assertSame( $quiz_id, $errors[1]->get_data()[0]['post_id'], 'Identify the affected quiz.' );
		self::assertSame( array(), $validation->get_warnings(), 'Unexplained date differences remain errors rather than unverifiable-date warnings.' );
	}

	/**
	 * Find attempted quiz progress without an HPPS copy.
	 *
	 * @covers \Sensei\Internal\Migration\Validations\Progress_Validation::run
	 */
	public function testRun_WhenQuizProgressWasMissingFromTables_ReportsQuizAndStudent(): void {
		/* Arrange. */
		global $wpdb;

		[ $lesson_id, $quiz_id, $student_id ] = $this->create_enrolled_quiz_student();
		$this->submit_quiz_for_grading( $lesson_id, $quiz_id, $student_id );
		$progress_repository = new Tables_Based_Quiz_Progress_Repository( $wpdb );
		$progress_repository->delete_for_quiz( $quiz_id );
		$validation = new Progress_Validation();

		/* Act. */
		$validation->run();

		/* Assert. */
		$errors = $validation->get_errors();
		self::assertCount( 1, $errors, 'Report the missing quiz progress once.' );
		self::assertSame( 'Quiz tables based progress not found.', $errors[0]->get_message(), 'Identify which store is missing progress.' );
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

	/**
	 * Report differing quiz progress once when both stores have a copy.
	 *
	 * @covers \Sensei\Internal\Migration\Validations\Progress_Validation::run
	 */
	public function testRun_WhenSubmittedQuizStillShowedInProgressInTables_ReportsTheDifferenceOnce(): void {
		/* Arrange. */
		global $wpdb;

		[ $lesson_id, $quiz_id, $student_id ] = $this->create_enrolled_quiz_student();
		$this->submit_quiz_for_grading( $lesson_id, $quiz_id, $student_id );
		$tables_repository = new Tables_Based_Quiz_Progress_Repository( $wpdb );
		$progress          = $tables_repository->get( $quiz_id, $student_id );
		$progress->start( $progress->get_started_at() );
		$tables_repository->save( $progress );
		$validation = new Progress_Validation();

		/* Act. */
		$validation->run();

		/* Assert. */
		$errors = $validation->get_errors();
		self::assertCount( 1, $errors, 'Report the quiz progress difference once.' );
		self::assertSame( 'Data mismatch between comments and tables based progress.', $errors[0]->get_message(), 'Report a value mismatch for a pair with both copies.' );
		self::assertSame( $quiz_id, $errors[0]->get_data()[0]['post_id'], 'Report the affected quiz ID.' );
		self::assertSame( 'ungraded', $errors[0]->get_data()[0]['status'], 'Comments record that the quiz was submitted for grading.' );
		self::assertSame( 'in-progress', $errors[0]->get_data()[1]['status'], 'Report the table status that still shows an unsubmitted quiz.' );
	}

	/**
	 * A failed quiz progress write must remain detectable after the grade was saved.
	 *
	 * @covers \Sensei\Internal\Migration\Validations\Progress_Validation::run
	 */
	public function testRun_WhenGradedQuizProgressWriteFailed_ReportsTheDifference(): void {
		/* Arrange. */
		global $wpdb;

		[ $lesson_id, $quiz_id, $student_id ] = $this->create_enrolled_quiz_student(
			array(
				'question_count' => 1,
				'question_args'  => array( 'question_type' => 'boolean' ),
			)
		);
		wp_set_current_user( $student_id );
		update_post_meta( $quiz_id, '_quiz_grade_type', 'auto' );
		delete_post_meta( $quiz_id, '_pass_required' );
		Sensei_Utils::sensei_start_lesson( $lesson_id, $student_id );
		Sensei()->quiz->maybe_create_quiz_progress( $quiz_id, $student_id );

		$answers          = $this->factory->generate_user_quiz_answers( $quiz_id );
		$fail_quiz_update = static function ( string $query ) use ( $wpdb ): string {
			$graded_progress_update = "UPDATE `{$wpdb->prefix}sensei_lms_progress` SET `status` = 'graded'";
			return 0 === strpos( $query, $graded_progress_update ) ? '' : $query;
		};
		add_filter( 'query', $fail_quiz_update );
		try {
			Sensei_Quiz::submit_answers_for_grading( $answers, array(), $lesson_id, $student_id );
		} finally {
			remove_filter( 'query', $fail_quiz_update );
		}

		$validation = new Progress_Validation();

		/* Act. */
		$validation->run();

		/* Assert. */
		$errors = $validation->get_errors();
		self::assertCount( 1, $errors, 'Report the failed quiz progress update once.' );
		self::assertSame( 'Data mismatch between comments and tables based progress.', $errors[0]->get_message(), 'A saved grade must expose the stale quiz progress.' );
		self::assertSame( $quiz_id, $errors[0]->get_data()[0]['post_id'], 'Identify the quiz whose progress update failed.' );
		self::assertSame( 'in-progress', $errors[0]->get_data()[1]['status'], 'Report the quiz progress that remained unchanged.' );
	}

	/**
	 * Find lesson and quiz progress that outlives the shared status comment.
	 *
	 * @covers \Sensei\Internal\Migration\Validations\Progress_Validation::run
	 */
	public function testRun_WhenLessonCommentWasDeleted_ReportsMissingLessonAndQuizProgress(): void {
		/* Arrange. */
		[ $lesson_id, $quiz_id, $student_id ] = $this->create_enrolled_quiz_student();
		Sensei()->quiz->maybe_create_quiz_progress( $quiz_id, $student_id );
		$answers = $this->factory->generate_user_quiz_answers( $quiz_id );
		self::assertTrue( Sensei_Quiz::save_user_answers( $answers, array(), $lesson_id, $student_id ) );
		$comments_repository = new Comments_Based_Lesson_Progress_Repository();
		$comments_repository->delete( $comments_repository->get( $lesson_id, $student_id ) );
		$validation = new Progress_Validation();

		/* Act. */
		$validation->run();

		/* Assert. */
		$errors = $validation->get_errors();
		self::assertCount( 2, $errors, 'The lesson and quiz should both be reported.' );
		self::assertSame(
			array(
				'lesson_id' => $lesson_id,
				'user_id'   => $student_id,
				'source'    => 'comments',
			),
			$errors[0]->get_data(),
			'The missing lesson record should identify the student and store.'
		);
		self::assertSame(
			array(
				'quiz_id' => $quiz_id,
				'user_id' => $student_id,
				'source'  => 'comments',
			),
			$errors[1]->get_data(),
			'The missing quiz record should identify the student and store.'
		);
	}

	/**
	 * Starting a lesson creates a shared comment even when the student has not opened its quiz.
	 *
	 * @covers \Sensei\Internal\Migration\Validations\Progress_Validation::run
	 */
	public function testRun_WhenQuizWasNeverAttempted_AllowsMissingQuizTableRow(): void {
		/* Arrange. */
		global $wpdb;

		$course     = $this->factory->get_course_with_lessons();
		$lesson_id  = $course['lesson_ids'][0];
		$quiz_id    = $course['quiz_ids'][0];
		$student_id = 1;
		( new Comments_Based_Lesson_Progress_Repository() )->create( $lesson_id, $student_id );
		$this->migrate_progress();
		( new Tables_Based_Quiz_Progress_Repository( $wpdb ) )->delete_for_quiz( $quiz_id );
		$validation = new Progress_Validation();

		/* Act. */
		$validation->run();

		/* Assert. */
		self::assertFalse( $validation->has_errors() );
	}

	/**
	 * Opening a quiz can record a quiz start in HPPS and leave comments tracking the lesson.
	 *
	 * @dataProvider quiz_visit_scenarios
	 * @covers \Sensei\Internal\Migration\Validations\Progress_Validation::run
	 */
	public function testRun_WhenQuizWasNotSubmitted_HasNoErrors( bool $lesson_started, bool $quiz_opened, bool $lesson_completed ): void {
		/* Arrange. */
		[ $lesson_id, $quiz_id, $student_id ] = $this->create_enrolled_quiz_student();
		delete_post_meta( $quiz_id, '_pass_required' );

		if ( $lesson_started ) {
			Sensei_Utils::sensei_start_lesson( $lesson_id, $student_id );
			$lesson_progress = Sensei()->lesson_progress_repository->get( $lesson_id, $student_id );
			$lesson_progress->start( new \DateTimeImmutable( '2025-01-01T00:00:00Z' ) );
			Sensei()->lesson_progress_repository->save( $lesson_progress );
		}
		if ( $quiz_opened ) {
			Sensei()->quiz->maybe_create_quiz_progress( $quiz_id, $student_id );
		}
		if ( $quiz_opened && $lesson_completed ) {
			$answers = $this->factory->generate_user_quiz_answers( $quiz_id );
			self::assertTrue( Sensei_Quiz::save_user_answers( $answers, array(), $lesson_id, $student_id ) );
		}
		if ( $lesson_completed ) {
			self::assertNotFalse( Sensei_Utils::sensei_start_lesson( $lesson_id, $student_id, true ) );
		}

		$validation = new Progress_Validation();

		/* Act. */
		$validation->run();

		/* Assert. */
		self::assertSame( array(), $validation->get_errors() );
	}

	public static function quiz_visit_scenarios(): array {
		return array(
			'quiz opened before the lesson'             => array( false, true, false ),
			'quiz opened after the lesson'              => array( true, true, false ),
			'lesson completed without opening the quiz' => array( true, false, true ),
			'lesson completed with a saved quiz draft'  => array( true, true, true ),
		);
	}

	/**
	 * Saved pre-trash progress must be compared, including completion dates.
	 *
	 * @dataProvider trashed_progress_cases
	 * @covers \Sensei\Internal\Migration\Validations\Progress_Validation::run
	 */
	public function testRun_ProgressWasTrashed_ValidatesSavedStatus( string $type, string $status ): void {
		$course     = $this->factory->get_course_with_lessons();
		$student_id = $this->factory->user->create();
		$post_id    = 'course' === $type ? $course['course_id'] : $course['lesson_ids'][0];
		wp_insert_comment(
			array(
				'comment_post_ID'  => $post_id,
				'user_id'          => $student_id,
				'comment_type'     => 'course' === $type ? 'sensei_course_status' : 'sensei_lesson_status',
				'comment_approved' => $status,
				'comment_date_gmt' => '2024-05-01 15:45:00',
			)
		);
		wp_trash_post( $post_id );
		$this->migrate_progress();
		$validation = new Progress_Validation();

		$validation->run();

		self::assertSame( array(), $validation->get_errors() );
	}

	/**
	 * Saved statuses with distinct course, lesson, and quiz normalization.
	 *
	 * @return array
	 */
	public static function trashed_progress_cases(): array {
		return array(
			'completed course'     => array( 'course', 'complete' ),
			'course in progress'   => array( 'course', 'in-progress' ),
			'completed lesson'     => array( 'lesson', 'complete' ),
			'passed lesson quiz'   => array( 'lesson', 'passed' ),
			'ungraded lesson quiz' => array( 'lesson', 'ungraded' ),
		);
	}

	/**
	 * A saved completion must still expose a previously migrated incomplete copy.
	 *
	 * @covers \Sensei\Internal\Migration\Validations\Progress_Validation::inspect
	 */
	public function testInspect_CompletedTrashedCourseWasMigratedIncomplete_ReportsMismatch(): void {
		global $wpdb;
		$course_id  = $this->factory->course->create();
		$student_id = $this->factory->user->create();
		wp_insert_comment(
			array(
				'comment_post_ID'  => $course_id,
				'user_id'          => $student_id,
				'comment_type'     => 'sensei_course_status',
				'comment_approved' => 'complete',
			)
		);
		wp_trash_post( $course_id );
		$repository = new Tables_Based_Course_Progress_Repository( $wpdb );
		$repository->create( $course_id, $student_id );

		$result = ( new Progress_Validation() )->inspect( 'course', $course_id, $student_id );

		self::assertSame( 'complete', $result['comments']['status'], 'Validation uses the saved pre-trash status.' );
		self::assertSame( 'Data mismatch between comments and tables based progress.', $result['errors'][0]->get_message(), 'The incomplete HPPS copy must remain an error.' );
	}

	/**
	 * Missing trash history cannot establish an expected progress value.
	 *
	 * @covers \Sensei\Internal\Migration\Validations\Progress_Validation::inspect
	 */
	public function testInspect_TrashedStatusHistoryWasMissing_ReportsUncertainty(): void {
		$course_id  = $this->factory->course->create();
		$student_id = $this->factory->user->create();
		wp_insert_comment(
			array(
				'comment_post_ID'  => $course_id,
				'user_id'          => $student_id,
				'comment_type'     => 'sensei_course_status',
				'comment_approved' => 'post-trashed',
			)
		);

		$result = ( new Progress_Validation() )->inspect( 'course', $course_id, $student_id );

		self::assertSame( 'Original progress status could not be established for the trashed comment.', $result['errors'][0]->get_message() );
	}

	/**
	 * Duplicate progress is compared against the lowest-ID comment used by migration.
	 *
	 * @dataProvider duplicate_comment_cases
	 * @covers \Sensei\Internal\Migration\Validations\Progress_Validation::inspect
	 */
	public function testInspect_DuplicateProgressCommentsWereStored_ValidatesLowestIdCopy( string $type, bool $migrate, bool $change_copy, array $expected_errors ): void {
		global $wpdb;
		$course     = $this->factory->get_course_with_lessons();
		$student_id = $this->factory->user->create();
		$post_id    = 'course' === $type ? $course['course_id'] : $course['lesson_ids'][0];
		$content_id = 'quiz' === $type ? $course['quiz_ids'][0] : $post_id;
		$statuses   = array( 'course' === $type ? 'complete' : 'graded', 'in-progress' );
		foreach ( $statuses as $index => $status ) {
			$comment_id = wp_insert_comment(
				array(
					'comment_post_ID'  => $post_id,
					'user_id'          => $student_id,
					'comment_type'     => 'course' === $type ? 'sensei_course_status' : 'sensei_lesson_status',
					'comment_approved' => $status,
					'comment_date'     => $index ? '2025-01-01 00:00:00' : '2025-02-01 00:00:00',
					'comment_date_gmt' => $index ? '2025-01-01 00:00:00' : '2025-02-01 00:00:00',
				)
			);
			add_comment_meta( $comment_id, 'start', $index ? '2025-01-01 00:00:00' : '2025-02-01 00:00:00' );
		}
		if ( $migrate ) {
			$this->migrate_progress();
		}
		if ( $change_copy ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->update(
				$wpdb->prefix . 'sensei_lms_progress',
				array(
					'status'       => 'in-progress',
					'completed_at' => null,
				),
				array(
					'post_id' => $content_id,
					'user_id' => $student_id,
					'type'    => $type,
				)
			);
		}

		$result = ( new Progress_Validation() )->inspect( $type, $content_id, $student_id );

		self::assertSame(
			$expected_errors,
			array_map(
				static function ( $error ): string {
					return $error->get_message();
				},
				$result['errors']
			)
		);
	}

	/**
	 * Matching, missing, and different HPPS copies of duplicate progress.
	 *
	 * @return array
	 */
	public static function duplicate_comment_cases(): array {
		$mismatch = array( 'Data mismatch between comments and tables based progress.' );

		return array(
			'course matches first comment' => array( 'course', true, false, array() ),
			'lesson matches first comment' => array( 'lesson', true, false, array() ),
			'quiz matches first comment'   => array( 'quiz', true, false, array() ),
			'lesson without an HPPS copy'  => array( 'lesson', false, false, array( 'Lesson tables based progress not found.' ) ),
			'course matches later comment' => array( 'course', true, true, $mismatch ),
			'lesson matches later comment' => array( 'lesson', true, true, $mismatch ),
			'quiz matches later comment'   => array( 'quiz', true, true, $mismatch ),
		);
	}

	/**
	 * Repeated metadata uses the first stored value, as migration does.
	 *
	 * @dataProvider duplicate_metadata_cases
	 * @covers \Sensei\Internal\Migration\Validations\Progress_Validation::run
	 */
	public function testRun_DuplicateStartMetadataWasStored_ValidatesFirstValue( bool $has_tables_copy, bool $has_duplicate_comment ): void {
		$course_id  = $this->factory->course->create();
		$student_id = $this->factory->user->create();
		$comment_id = wp_insert_comment(
			array(
				'comment_post_ID'  => $course_id,
				'user_id'          => $student_id,
				'comment_type'     => 'sensei_course_status',
				'comment_approved' => 'in-progress',
			)
		);
		add_comment_meta( $comment_id, 'start', '2025-01-01 00:00:00' );
		add_comment_meta( $comment_id, 'start', 'unparseable date' );
		if ( $has_duplicate_comment ) {
			wp_insert_comment(
				array(
					'comment_post_ID'  => $course_id,
					'user_id'          => $student_id,
					'comment_type'     => 'sensei_course_status',
					'comment_approved' => 'complete',
				)
			);
		}
		if ( $has_tables_copy ) {
			$this->migrate_progress();
		}
		$this->mask_migration_as_complete();
		$validation = new Progress_Validation();

		$validation->run();

		$errors = $validation->get_errors();
		self::assertSame(
			$has_tables_copy ? array() : array( 'Course tables based progress not found.' ),
			array_map(
				static function ( $error ): string {
					return $error->get_message();
				},
				$errors
			),
			'Duplicates should not require review, but missing progress must still be reported.'
		);
		self::assertSame(
			array( '2025-01-01 00:00:00', 'unparseable date' ),
			get_comment_meta( $comment_id, 'start', false ),
			'Validation must retain both original metadata values.'
		);
	}

	/**
	 * Repeated metadata is resolved independently of HPPS copy presence.
	 *
	 * @return array
	 */
	public static function duplicate_metadata_cases(): array {
		return array(
			'no tables copy'           => array( false, false ),
			'tables copy exists'       => array( true, false ),
			'duplicate comment exists' => array( true, true ),
		);
	}

	private function create_enrolled_quiz_student( array $course_args = array() ): array {
		$this->set_progress_storage_mode( self::is_hpps_tables_mode() ? 'tables' : 'comments' );
		add_filter( 'sensei_feature_flag_tables_based_progress', '__return_true' );

		$course     = $this->factory->get_course_with_lessons( $course_args );
		$student_id = $this->factory->user->create();
		Sensei_Course_Enrolment::get_course_instance( $course['course_id'] )->enrol( $student_id );
		$course_progress = Sensei()->course_progress_repository->get( $course['course_id'], $student_id );
		// Synchronize the fixture dates after the two inserts, which can cross a second boundary.
		Sensei()->course_progress_repository->save( $course_progress );
		$this->mask_migration_as_complete();

		return array( $course['lesson_ids'][0], $course['quiz_ids'][0], $student_id );
	}

	private function submit_quiz_for_grading( int $lesson_id, int $quiz_id, int $student_id ): void {
		update_post_meta( $quiz_id, '_quiz_grade_type', 'manual' );
		Sensei_Utils::sensei_start_lesson( $lesson_id, $student_id );
		Sensei()->quiz->maybe_create_quiz_progress( $quiz_id, $student_id );
		$answers = $this->factory->generate_user_quiz_answers( $quiz_id );
		self::assertTrue( Sensei_Quiz::submit_answers_for_grading( $answers, array(), $lesson_id, $student_id ) );
	}

	private function migrate_progress() {
		$this->cleanup_custom_tables();

		( new Student_Progress_Migration() )->run( false );

		$this->mask_migration_as_complete();
	}

	private function cleanup_custom_tables() {
		global $wpdb;
		// Keep cleanup inside the test transaction.
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( "DELETE FROM {$wpdb->prefix}sensei_lms_progress" );
		$wpdb->query( "DELETE FROM {$wpdb->prefix}sensei_lms_quiz_grades" );
		$wpdb->query( "DELETE FROM {$wpdb->prefix}sensei_lms_quiz_answers" );
		$wpdb->query( "DELETE FROM {$wpdb->prefix}sensei_lms_quiz_submissions" );
		// phpcs:enable
	}

	private function mask_migration_as_complete(): void {
		update_option( Migration_Job_Scheduler::COMPLETED_OPTION_NAME, microtime( true ) );
	}

	private function get_first_error_message( Progress_Validation $progress_validation ): ?string {
		$errors = $progress_validation->get_errors();

		if ( empty( $errors ) ) {
			return null;
		}

		return $errors[0]->get_message();
	}
}
