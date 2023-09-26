<?php

namespace SenseiTest\Internal\Student_Progress\Repositories;

use Sensei\Internal\Student_Progress\Quiz_Progress\Models\Quiz_Progress_Interface;
use Sensei\Internal\Student_Progress\Quiz_Progress\Repositories\Comments_Based_Quiz_Progress_Repository;

/**
 * Tests for the Quiz_Progress_Comments_Repository class.
 *
 * @covers \Sensei\Internal\Student_Progress\Quiz_Progress\Repositories\Comments_Based_Quiz_Progress_Repository
 */
class Comments_Based_Quiz_Progress_Repository_Test extends \WP_UnitTestCase {
	/**
	 * Sensei factory.
	 *
	 * @var \Sensei_Factory
	 */
	protected $factory;

	public function setUp(): void {
		parent::setUp();
		$this->factory = new \Sensei_Factory();
	}

	public function tearDown(): void {
		parent::tearDown();
		$this->factory->tearDown();
	}
	public function testCreate_WhenLessonStatusFound_ReturnsQuizProgress(): void {
		/* Arrange. */
		$lesson_id  = $this->factory->lesson->create();
		$user_id    = $this->factory->user->create();
		$quiz_id    = $this->factory->quiz->create( [ 'post_parent' => $lesson_id ] );
		$repository = new Comments_Based_Quiz_Progress_Repository();
		\Sensei_Utils::update_lesson_status( $user_id, $lesson_id, 'in-progress' );

		/* Act. */
		$progress = $repository->create( $quiz_id, $user_id );

		/* Assert. */
		$expected = [
			'user_id' => $user_id,
			'quiz_id' => $quiz_id,
			'status'  => 'in-progress',
		];
		self::assertSame( $expected, $this->export_progress( $progress ) );
	}

	public function testCreate_WhenLessonStatusNotFound_ThrowsException(): void {
		/* Arrange. */
		$lesson_id  = $this->factory->lesson->create();
		$user_id    = $this->factory->user->create();
		$quiz_id    = $this->factory->quiz->create( [ 'post_parent' => $lesson_id ] );
		$repository = new \Sensei\Internal\Student_Progress\Quiz_Progress\Repositories\Comments_Based_Quiz_Progress_Repository();

		/* Expect & Act. */
		$this->expectException( \RuntimeException::class );
		$this->expectExceptionMessage( 'Cannot create quiz progress' );
		$repository->create( $quiz_id, $user_id );
	}

	public function testGet_WhenStatusFound_ReturnsQuizProgress(): void {
		/* Arrange. */
		$lesson_id  = $this->factory->lesson->create();
		$user_id    = $this->factory->user->create();
		$quiz_id    = $this->factory->quiz->create( [ 'post_parent' => $lesson_id ] );
		$repository = new \Sensei\Internal\Student_Progress\Quiz_Progress\Repositories\Comments_Based_Quiz_Progress_Repository();
		\Sensei_Utils::update_lesson_status( $user_id, $lesson_id, 'in-progress' );

		/* Act. */
		$progress = $repository->get( $quiz_id, $user_id );

		/* Assert. */
		$expected = [
			'user_id' => $user_id,
			'quiz_id' => $quiz_id,
			'status'  => 'in-progress',
		];
		self::assertSame( $expected, $this->export_progress( $progress ) );
	}

	public function testGet_WhenStatusNotFound_ReturnsNull(): void {
		/* Arrange. */
		$lesson_id  = $this->factory->lesson->create();
		$user_id    = $this->factory->user->create();
		$quiz_id    = $this->factory->quiz->create( [ 'post_parent' => $lesson_id ] );
		$repository = new Comments_Based_Quiz_Progress_Repository();

		/* Act. */
		$actual = $repository->get( $quiz_id, $user_id );

		/* Assert. */
		self::assertNull( $actual );
	}

	public function testHas_WhenStatusFound_ReturnsTrue(): void {
		/* Arrange. */
		$lesson_id  = $this->factory->lesson->create();
		$user_id    = $this->factory->user->create();
		$quiz_id    = $this->factory->quiz->create( [ 'post_parent' => $lesson_id ] );
		$repository = new \Sensei\Internal\Student_Progress\Quiz_Progress\Repositories\Comments_Based_Quiz_Progress_Repository();
		\Sensei_Utils::update_lesson_status( $user_id, $lesson_id, 'in-progress' );

		/* Act. */
		$actual = $repository->has( $quiz_id, $user_id );

		/* Assert. */
		self::assertTrue( $actual );
	}

	public function testHas_WhenStatusNotFound_ReturnsFalse(): void {
		/* Arrange. */
		$lesson_id  = $this->factory->lesson->create();
		$user_id    = $this->factory->user->create();
		$quiz_id    = $this->factory->quiz->create( [ 'post_parent' => $lesson_id ] );
		$repository = new Comments_Based_Quiz_Progress_Repository();

		/* Act. */
		$actual = $repository->has( $quiz_id, $user_id );

		/* Assert. */
		self::assertFalse( $actual );
	}

	public function testGet_WhenProgressUpdatedAndSaved_ReturnsMatchingProgress(): void {
		/* Arrange. */
		$lesson_id = $this->factory->lesson->create();
		$user_id   = $this->factory->user->create();
		$quiz_id   = $this->factory->quiz->create( [ 'post_parent' => $lesson_id ] );
		\Sensei_Utils::update_lesson_status( $user_id, $lesson_id, 'in-progress' );

		$repository = new Comments_Based_Quiz_Progress_Repository();

		$progress = $repository->get( $quiz_id, $user_id );
		$progress->pass();
		$repository->save( $progress );

		/* Act. */
		$actual = $repository->get( $quiz_id, $user_id );

		/* Assert. */
		$expected = [
			'user_id' => $user_id,
			'quiz_id' => $quiz_id,
			'status'  => 'passed',
		];
		self::assertSame( $expected, $this->export_progress( $actual ) );
	}

	public function testSave_NonCommentsBasedProgressGiven_ThrowsException(): void {
		/* Arrange. */
		$progress   = $this->createMock( Quiz_Progress_Interface::class );
		$repository = new Comments_Based_Quiz_Progress_Repository();

		/* Expect & Act. */
		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Expected Comments_Based_Quiz_Progress, got ' . get_class( $progress ) . '.' );
		$repository->save( $progress );
	}

	public function testDelete_ProgressGiven_DeletesAllUserAnswers(): void {
		/* Arrange. */
		$lesson_id   = $this->factory->lesson->create();
		$user_id     = $this->factory->user->create();
		$quiz_id     = $this->factory->quiz->create( [ 'post_parent' => $lesson_id ] );
		$question_id = $this->factory->question->create( [ 'quiz_id' => $quiz_id ] );
		\Sensei_Utils::update_lesson_status( $user_id, $lesson_id, 'in-progress' );

		$repository = new Comments_Based_Quiz_Progress_Repository();

		$progress = $repository->get( $quiz_id, $user_id );
		$progress->pass();
		$repository->save( $progress );

		update_comment_meta( $progress->get_id(), 'questions_asked', $question_id );
		update_comment_meta( $progress->get_id(), 'quiz_answers', [ $question_id => 'answer' ] );

		/* Act. */
		$repository->delete( $progress );

		/* Assert. */
		$actual = get_comment_meta( $progress->get_id(), 'quiz_answers', true );
		self::assertEmpty( $actual );
	}

	public function testDelete_ProgressGiven_DeletesGrade(): void {
		/* Arrange. */
		$lesson_id = $this->factory->lesson->create();
		$user_id   = $this->factory->user->create();
		$quiz_id   = $this->factory->quiz->create( [ 'post_parent' => $lesson_id ] );
		\Sensei_Utils::update_lesson_status( $user_id, $lesson_id, 'in-progress' );

		$repository = new Comments_Based_Quiz_Progress_Repository();

		$progress = $repository->get( $quiz_id, $user_id );
		$progress->pass();
		$repository->save( $progress );

		update_comment_meta( $progress->get_id(), 'questions_asked', '1,2' );
		update_comment_meta( $progress->get_id(), 'grade', 1 );

		/* Act. */
		$repository->delete( $progress );

		/* Assert. */
		$actual = get_comment_meta( $progress->get_id(), 'grade', true );
		self::assertEmpty( $actual );
	}

	public function testDeleteForQuiz_WhenQuizGiven_DeletesGradesForThisQuiz(): void {
		/* Arrange. */
		$lesson1_id = $this->factory->lesson->create();
		$lesson2_id = $this->factory->lesson->create();
		$user1_id   = $this->factory->user->create();
		$user2_id   = $this->factory->user->create();
		$quiz1_id   = $this->factory->quiz->create( [ 'post_parent' => $lesson1_id ] );
		$quiz2_id   = $this->factory->quiz->create( [ 'post_parent' => $lesson2_id ] );
		\Sensei_Utils::update_lesson_status( $user1_id, $lesson1_id, 'in-progress' );
		\Sensei_Utils::update_lesson_status( $user1_id, $lesson2_id, 'in-progress' );
		\Sensei_Utils::update_lesson_status( $user2_id, $lesson1_id, 'in-progress' );

		$repository = new Comments_Based_Quiz_Progress_Repository();

		$progress1 = $repository->get( $quiz1_id, $user1_id );
		$progress1->pass();
		$repository->save( $progress1 );
		update_comment_meta( $progress1->get_id(), 'grade', 1 );

		$progress2 = $repository->get( $quiz2_id, $user1_id );
		$progress2->pass();
		$repository->save( $progress2 );
		update_comment_meta( $progress2->get_id(), 'grade', 1 );

		$progress3 = $repository->get( $quiz1_id, $user2_id );
		$progress3->pass();
		$repository->save( $progress3 );
		update_comment_meta( $progress3->get_id(), 'grade', 1 );

		/* Act. */
		$repository->delete_for_quiz( $quiz1_id );

		/* Assert. */
		$actual   = [
			'progress1 grade' => get_comment_meta( $progress1->get_id(), 'grade', true ),
			'progress2 grade' => get_comment_meta( $progress2->get_id(), 'grade', true ),
			'progress3 grade' => get_comment_meta( $progress3->get_id(), 'grade', true ),
		];
		$expected = [
			'progress1 grade' => '',
			'progress2 grade' => '1',
			'progress3 grade' => '',
		];
		self::assertSame( $expected, $actual );
	}

	public function testDeleteForUser_WhenUserGiven_DeletesGradesForThisUser(): void {
		/* Arrange. */
		$lesson1_id = $this->factory->lesson->create();
		$lesson2_id = $this->factory->lesson->create();
		$user1_id   = $this->factory->user->create();
		$user2_id   = $this->factory->user->create();
		$quiz1_id   = $this->factory->quiz->create( [ 'post_parent' => $lesson1_id ] );
		$quiz2_id   = $this->factory->quiz->create( [ 'post_parent' => $lesson2_id ] );
		\Sensei_Utils::update_lesson_status( $user1_id, $lesson1_id, 'in-progress' );
		\Sensei_Utils::update_lesson_status( $user1_id, $lesson2_id, 'in-progress' );
		\Sensei_Utils::update_lesson_status( $user2_id, $lesson1_id, 'in-progress' );

		$repository = new Comments_Based_Quiz_Progress_Repository();

		$progress1 = $repository->get( $quiz1_id, $user1_id );
		$progress1->pass();
		$repository->save( $progress1 );
		update_comment_meta( $progress1->get_id(), 'grade', 1 );

		$progress2 = $repository->get( $quiz2_id, $user1_id );
		$progress2->pass();
		$repository->save( $progress2 );
		update_comment_meta( $progress2->get_id(), 'grade', 1 );

		$progress3 = $repository->get( $quiz1_id, $user2_id );
		$progress3->pass();
		$repository->save( $progress3 );
		update_comment_meta( $progress3->get_id(), 'grade', 1 );

		/* Act. */
		$repository->delete_for_user( $user1_id );

		/* Assert. */
		$actual = [
			'progress1 grade' => get_comment_meta( $progress1->get_id(), 'grade', true ),
			'progress2 grade' => get_comment_meta( $progress2->get_id(), 'grade', true ),
			'progress3 grade' => get_comment_meta( $progress3->get_id(), 'grade', true ),
		];

		$expected = [
			'progress1 grade' => '',
			'progress2 grade' => '',
			'progress3 grade' => '1',
		];
		self::assertSame( $expected, $actual );
	}

	private function export_progress( Quiz_Progress_Interface $progress ): array {
		return [
			'user_id' => $progress->get_user_id(),
			'quiz_id' => $progress->get_quiz_id(),
			'status'  => $progress->get_status(),
		];
	}
}
