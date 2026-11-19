<?php

namespace SenseiTest\Internal\Student_Progress\Services;

use Sensei\Internal\Quiz_Submission\Submission\Repositories\Submission_Repository_Interface;
use Sensei\Internal\Student_Progress\Lesson_Progress\Repositories\Lesson_Progress_Repository_Interface;
use Sensei\Internal\Student_Progress\Services\Lesson_Deleted_Handler;
use Sensei\Internal\Student_Progress\Services\Quiz_Deleted_Handler;

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
	 * Permanently deleting a lesson clears every student's quiz data and preserves other lessons.
	 *
	 * @dataProvider progress_storage_modes
	 * @covers \Sensei\Internal\Student_Progress\Services\Lesson_Deleted_Handler::delete_quiz_data
	 * @covers \Sensei\Internal\Student_Progress\Services\Lesson_Deleted_Handler::handle
	 */
	public function testWpDeletePost_LessonPermanentlyDeleted_RemovesAllQuizData( string $mode ): void {
		$fixture          = $this->create_lesson_with_submissions( $mode );
		$lesson_id        = $fixture['lesson_ids'][0];
		$quiz_id          = $fixture['quiz_ids'][0];
		$before           = $this->get_quiz_row_counts( $quiz_id, $fixture['question_ids'][0] );
		$unrelated_before = $this->get_quiz_row_counts( $fixture['quiz_ids'][1], $fixture['question_ids'][1] );

		wp_trash_post( $lesson_id );
		self::assertSame( $before, $this->get_quiz_row_counts( $quiz_id, $fixture['question_ids'][0] ), 'Moving a lesson to Trash must preserve its quiz data.' );
		wp_delete_post( $lesson_id, true );

		self::assertNull( get_post( $lesson_id ), 'The lesson is permanently deleted.' );
		self::assertNull( get_post( $quiz_id ), 'The associated quiz is permanently deleted.' );
		self::assertSame( array( 0, 0, 0 ), $this->get_quiz_row_counts( $quiz_id, $fixture['question_ids'][0] ), 'Remove all HPPS submissions, answers, and grades for the deleted quiz.' );
		self::assertSame( $unrelated_before, $this->get_quiz_row_counts( $fixture['quiz_ids'][1], $fixture['question_ids'][1] ), 'Preserve quiz data belonging to other lessons.' );
		foreach ( $fixture['student_ids'] as $student_id ) {
			self::assertNotNull( Sensei()->quiz_submission_repository->get( $fixture['quiz_ids'][1], $student_id ), 'Other lessons retain their comments-based and active-store submissions.' );
		}
	}

	/**
	 * A failed quiz lookup or cleanup leaves the lesson available for another deletion attempt.
	 *
	 * @dataProvider deletion_failure_storage_modes
	 * @covers \Sensei\Internal\Student_Progress\Services\Lesson_Deleted_Handler::delete_quiz_data
	 */
	public function testWpDeletePost_QuizCleanupFails_KeepsLessonForRetry( string $mode, string $failed_query ): void {
		global $wpdb;

		$fixture   = $this->create_lesson_with_submissions( $mode );
		$lesson_id = $fixture['lesson_ids'][0];
		$quiz_id   = $fixture['quiz_ids'][0];
		$target    = sprintf( $failed_query, $wpdb->prefix );
		$fault     = static function ( string $query ) use ( $target ): string {
			return 0 === strpos( str_replace( '`', '', $query ), $target ) ? '' : $query;
		};
		add_filter( 'query', $fault );
		try {
			$result = wp_delete_post( $lesson_id, true );
		} finally {
			remove_filter( 'query', $fault );
		}

		self::assertFalse( $result, 'Stop deleting the lesson when quiz cleanup fails.' );
		self::assertNotNull( get_post( $lesson_id ), 'Keep the lesson available for retry.' );
		self::assertNotNull( get_post( $quiz_id ), 'Keep the quiz available for retry.' );
		self::assertSame( array( 2, 2, 2 ), $this->get_quiz_row_counts( $quiz_id, $fixture['question_ids'][0] ), 'Keep linked quiz records when cleanup cannot complete.' );

		wp_delete_post( $lesson_id, true );

		self::assertNull( get_post( $lesson_id ), 'A subsequent deletion succeeds.' );
		self::assertSame( array( 0, 0, 0 ), $this->get_quiz_row_counts( $quiz_id, $fixture['question_ids'][0] ), 'Retry removes the remaining quiz data.' );
	}

	public static function deletion_failure_storage_modes(): array {
		$mode = self::is_hpps_tables_mode() ? 'tables' : 'comments';
		return array(
			'submission lookup fails' => array( $mode, 'SELECT user_id FROM %ssensei_lms_quiz_submissions' ),
			'grade deletion fails'    => array( $mode, 'DELETE FROM %ssensei_lms_quiz_grades' ),
		);
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
		( new Lesson_Deleted_Handler( Sensei()->lesson_progress_repository, Sensei()->quiz_submission_repository ) )->init();
		( new Quiz_Deleted_Handler( Sensei()->quiz_progress_repository ) )->init();

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
