<?php

namespace SenseiTest\Internal\Student_Progress\Services;

use Sensei\Internal\Quiz_Submission\Submission\Repositories\Submission_Repository_Interface;
use Sensei\Internal\Student_Progress\Lesson_Progress\Repositories\Lesson_Progress_Repository_Interface;
use Sensei\Internal\Student_Progress\Services\Lesson_Deleted_Handler;

class Lesson_Deleted_Handler_Test extends \WP_UnitTestCase {
	use \Sensei_HPPS_Helpers;

	protected function tearDown(): void {
		$this->restore_progress_storage_mode();
		parent::tearDown();
	}

	/**
	 * Tests that the handler is initialized.
	 */
	public function testInit_WhenCalled_AddsAction() {
		/* Arrange. */
		$lesson_progress_repository = $this->createMock( Lesson_Progress_Repository_Interface::class );
		$handler                    = new Lesson_Deleted_Handler( $lesson_progress_repository, $this->createMock( Submission_Repository_Interface::class ) );

		/* Act. */
		$handler->init();

		/* Assert. */
		$this->assertEquals( 10, has_action( 'deleted_post', [ $handler, 'handle' ] ) );
	}

	public function testHandle_WhenPostWasNotLesson_DoesntCallDeleteForLesson() {
		/* Arrange. */
		$deleted_post               = new \WP_Post( (object) [ 'post_type' => 'post' ] );
		$lesson_progress_repository = $this->createMock( Lesson_Progress_Repository_Interface::class );

		$handler = new Lesson_Deleted_Handler( $lesson_progress_repository, $this->createMock( Submission_Repository_Interface::class ) );

		/* Expect & Act. */
		$lesson_progress_repository
			->expects( $this->never() )
			->method( 'delete_for_lesson' );
		$handler->handle( 1, $deleted_post );
	}

	public function testHandle_WhenLessonGiven_CallsDeleteForLesson() {
		/* Arrange. */
		$deleted_post               = new \WP_Post( (object) [ 'post_type' => 'lesson' ] );
		$lesson_progress_repository = $this->createMock( Lesson_Progress_Repository_Interface::class );

		$handler = new Lesson_Deleted_Handler( $lesson_progress_repository, $this->createMock( Submission_Repository_Interface::class ) );

		/* Expect & Act. */
		$lesson_progress_repository
			->expects( $this->once() )
			->method( 'delete_for_lesson' )
			->with( 1 );
		$handler->handle( 1, $deleted_post );
	}

	/**
	 * Quiz cleanup removes every student's submission, answers, and grades for the lesson.
	 *
	 * @dataProvider progress_storage_modes
	 * @covers \Sensei\Internal\Student_Progress\Services\Lesson_Deleted_Handler::delete_quiz_data
	 */
	public function testDeleteQuizData_LessonWithSubmissionsGiven_RemovesAllQuizData( string $mode ): void {
		$fixture          = $this->create_lesson_with_submissions( $mode );
		$lesson_id        = $fixture['lesson_ids'][0];
		$quiz_id          = $fixture['quiz_ids'][0];
		$unrelated_before = $this->get_quiz_row_counts( $fixture['quiz_ids'][1], $fixture['question_ids'][1] );
		$handler          = new Lesson_Deleted_Handler( Sensei()->lesson_progress_repository, Sensei()->quiz_submission_repository );

		$result = $handler->delete_quiz_data( null, get_post( $lesson_id ) );

		self::assertNull( $result, 'Allow lesson deletion after quiz cleanup succeeds.' );
		self::assertSame( array( 0, 0, 0 ), $this->get_quiz_row_counts( $quiz_id, $fixture['question_ids'][0] ), 'Remove all HPPS submissions, answers, and grades for the lesson\'s quiz.' );
		self::assertSame( $unrelated_before, $this->get_quiz_row_counts( $fixture['quiz_ids'][1], $fixture['question_ids'][1] ), 'Preserve quiz data belonging to other lessons.' );
		foreach ( $fixture['student_ids'] as $student_id ) {
			self::assertNull( Sensei()->quiz_submission_repository->get( $quiz_id, $student_id ), 'Remove each student\'s submission from the active store.' );
			self::assertNotNull( Sensei()->quiz_submission_repository->get( $fixture['quiz_ids'][1], $student_id ), 'Other lessons retain their comments-based and active-store submissions.' );
		}
	}

	/**
	 * Failed quiz cleanup returns false and succeeds when retried after the failure clears.
	 *
	 * @dataProvider deletion_failure_storage_modes
	 * @covers \Sensei\Internal\Student_Progress\Services\Lesson_Deleted_Handler::delete_quiz_data
	 */
	public function testDeleteQuizData_QuizCleanupFails_ReturnsFalseAndAllowsRetry( string $mode, string $failed_query ): void {
		global $wpdb;

		$fixture   = $this->create_lesson_with_submissions( $mode );
		$lesson_id = $fixture['lesson_ids'][0];
		$quiz_id   = $fixture['quiz_ids'][0];
		$handler   = new Lesson_Deleted_Handler( Sensei()->lesson_progress_repository, Sensei()->quiz_submission_repository );
		$lesson    = get_post( $lesson_id );
		$target    = sprintf( $failed_query, $wpdb->prefix );
		$fault     = static function ( string $query ) use ( $target ): string {
			return 0 === strpos( str_replace( '`', '', $query ), $target ) ? '' : $query;
		};
		add_filter( 'query', $fault );
		try {
			$result = $handler->delete_quiz_data( null, $lesson );
		} finally {
			remove_filter( 'query', $fault );
		}

		self::assertFalse( $result, 'Stop deleting the lesson when quiz cleanup fails.' );
		self::assertSame( array( 2, 2, 2 ), $this->get_quiz_row_counts( $quiz_id, $fixture['question_ids'][0] ), 'Keep linked quiz records when cleanup cannot complete.' );
		foreach ( $fixture['student_ids'] as $student_id ) {
			$submission = Sensei()->quiz_submission_repository->get( $quiz_id, $student_id );
			self::assertNotNull( $submission, 'Keep each primary-store submission available after a failed delete.' );
			self::assertCount( 1, Sensei()->quiz_answer_repository->get_all( $submission->get_id() ), 'Keep the primary-store answers after a failed delete.' );
			self::assertCount( 1, Sensei()->quiz_grade_repository->get_all( $submission->get_id() ), 'Keep the primary-store grades after a failed delete.' );
		}

		$retry_result = $handler->delete_quiz_data( null, $lesson );

		self::assertNull( $retry_result, 'Allow lesson deletion after retrying quiz cleanup.' );
		self::assertSame( array( 0, 0, 0 ), $this->get_quiz_row_counts( $quiz_id, $fixture['question_ids'][0] ), 'Retry removes the remaining quiz data.' );
		foreach ( $fixture['student_ids'] as $student_id ) {
			self::assertNull( Sensei()->quiz_submission_repository->get( $quiz_id, $student_id ), 'Retry removes each student\'s submission from the active store.' );
		}
	}

	public static function deletion_failure_storage_modes(): array {
		$mode      = self::is_hpps_tables_mode() ? 'tables' : 'comments';
		$scenarios = array(
			'submission lookup fails' => array( $mode, 'SELECT user_id FROM %ssensei_lms_quiz_submissions' ),
			'grade deletion fails'    => array( $mode, 'DELETE FROM %ssensei_lms_quiz_grades' ),
		);
		if ( self::is_hpps_tables_mode() ) {
			$scenarios['secondary comments grade deletion fails'] = array( $mode, 'DELETE FROM %scommentmeta' );
		}
		return $scenarios;
	}

	private function create_lesson_with_submissions( string $mode ): array {
		$this->set_progress_storage_mode( $mode );
		$factory                = new \Sensei_Factory();
		$course                 = $factory->get_course_with_lessons(
			array(
				'lesson_count'   => 2,
				'question_count' => 1,
			)
		);
		$course['student_ids']  = $factory->user->create_many( 2 );
		$course['question_ids'] = array();
		foreach ( $course['lesson_ids'] as $index => $lesson_id ) {
			$quiz_id                  = $course['quiz_ids'][ $index ];
			$questions                = \Sensei_Utils::sensei_get_quiz_questions( $quiz_id );
			$question_id              = $questions[0]->ID;
			$course['question_ids'][] = $question_id;
			foreach ( $course['student_ids'] as $student_id ) {
				Sensei()->lesson_progress_repository->create( $lesson_id, $student_id );
				Sensei()->quiz_progress_repository->create( $quiz_id, $student_id );
				$submission = Sensei()->quiz_submission_repository->create( $quiz_id, $student_id, 100 );
				$answer     = Sensei()->quiz_answer_repository->create( $submission, $question_id, 'answer' );
				Sensei()->quiz_grade_repository->create( $submission, $answer, $question_id, 1 );
			}
		}
		return $course;
	}

	private function get_quiz_row_counts( int $quiz_id, int $question_id ): array {
		global $wpdb;

		// phpcs:disable WordPress.DB.DirectDatabaseQuery -- Verify persisted rows rather than cached repository results.
		return array(
			(int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %i WHERE quiz_id = %d', $wpdb->prefix . 'sensei_lms_quiz_submissions', $quiz_id ) ),
			(int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %i WHERE question_id = %d', $wpdb->prefix . 'sensei_lms_quiz_answers', $question_id ) ),
			(int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %i WHERE question_id = %d', $wpdb->prefix . 'sensei_lms_quiz_grades', $question_id ) ),
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery
	}
}
