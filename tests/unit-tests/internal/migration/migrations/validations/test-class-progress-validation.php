<?php

namespace SenseiTest\Internal\Migration\Validations;

use Sensei\Internal\Migration\Migration_Job_Scheduler;
use Sensei\Internal\Migration\Migrations\Student_Progress_Migration;
use Sensei\Internal\Migration\Validations\Progress_Validation;
use Sensei\Internal\Student_Progress\Course_Progress\Repositories\Tables_Based_Course_Progress_Repository;
use Sensei\Internal\Student_Progress\Lesson_Progress\Repositories\Tables_Based_Lesson_Progress_Repository;
use Sensei\Internal\Student_Progress\Quiz_Progress\Repositories\Tables_Based_Quiz_Progress_Repository;
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

	/**
	 * Factory for creating test data.
	 *
	 * @var Sensei_Factory
	 */
	protected $factory;

	protected function setUp(): void {
		parent::setUp();

		$this->factory = new Sensei_Factory();
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

	public function testRun_WhenHasQuizProgressInTable_HasNoErrors(): void {
		/* Arrange. */
		$course_data         = $this->factory->get_course_with_lessons();
		$lesson_id           = $course_data['lesson_ids'][0];
		$quiz_id             = $course_data['quiz_ids'][0];
		$progress_validation = new Progress_Validation();

		add_filter( 'sensei_is_enrolled', '__return_true' );
		$answers = $this->factory->generate_user_quiz_answers( $quiz_id );
		Sensei_Quiz::save_user_answers( $answers, [], $lesson_id, 1 );
		$this->migrate_progress();

		/* Act. */
		$progress_validation->run();

		/* Assert. */
		$this->assertFalse( $progress_validation->has_errors() );
	}

	public function testRun_WhenHasNoQuizProgressInTable_HasError(): void {
		/* Arrange. */
		global $wpdb;
		$course_data         = $this->factory->get_course_with_lessons();
		$lesson_id           = $course_data['lesson_ids'][0];
		$quiz_id             = $course_data['quiz_ids'][0];
		$progress_validation = new Progress_Validation();
		$progress_repository = new Tables_Based_Quiz_Progress_Repository( $wpdb );

		add_filter( 'sensei_is_enrolled', '__return_true' );
		$answers = $this->factory->generate_user_quiz_answers( $quiz_id );
		Sensei_Quiz::save_user_answers( $answers, [], $lesson_id, 1 );
		$this->migrate_progress();
		$progress_repository->delete_for_quiz( $quiz_id );

		/* Act. */
		$progress_validation->run();

		/* Assert. */
		$this->assertSame(
			'Quiz tables based progress not found.',
			$this->get_first_error_message( $progress_validation )
		);
	}

	public function testRun_WhenHasQuizProgressButDataIsNotMatching_HasError(): void {
		/* Arrange. */
		global $wpdb;
		$course_data         = $this->factory->get_course_with_lessons();
		$lesson_id           = $course_data['lesson_ids'][0];
		$quiz_id             = $course_data['quiz_ids'][0];
		$progress_validation = new Progress_Validation();
		$progress_repository = new Tables_Based_Quiz_Progress_Repository( $wpdb );

		add_filter( 'sensei_is_enrolled', '__return_true' );
		$answers = $this->factory->generate_user_quiz_answers( $quiz_id );
		Sensei_Quiz::save_user_answers( $answers, [], $lesson_id, 1 );
		$this->migrate_progress();
		$progress = $progress_repository->get( $quiz_id, 1 );
		$progress->pass();
		$progress_repository->save( $progress );

		/* Act. */
		$progress_validation->run();

		/* Assert. */
		$this->assertSame(
			'Data mismatch between comments and tables based progress.',
			$this->get_first_error_message( $progress_validation )
		);
	}

	private function migrate_progress() {
		$this->cleanup_custom_tables();

		( new Student_Progress_Migration() )->run( false );

		$this->mask_migration_as_complete();
	}

	private function cleanup_custom_tables() {
		global $wpdb;
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}sensei_lms_progress" );
		$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}sensei_lms_quiz_grades" );
		$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}sensei_lms_quiz_answers" );
		$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}sensei_lms_quiz_submissions" );
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
