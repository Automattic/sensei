<?php

namespace SenseiTest\Internal\Student_Progress\Repositories;

use Sensei\Internal\Student_Progress\Quiz_Progress\Models\Quiz_Progress;
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

	public function setup() {
		parent::setup();
		$this->factory = new \Sensei_Factory();
	}

	public function tearDown() {
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
		$repository = new \Sensei\Internal\Student_Progress\Quiz_Progress\Repositories\Comments_Based_Quiz_Progress_Repository();

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

		update_comment_meta( $quiz_id, 'quiz_answers', [ $question_id => 'answer' ] );

		/* Act. */
		$repository->delete( $progress );

		/* Assert. */
		$actual = get_comment_meta( $quiz_id, 'quiz_answers', true );
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

		update_comment_meta( $progress->get_id(), 'grade', 1 );

		/* Act. */
		$repository->delete( $progress );

		/* Assert. */
		$actual = get_comment_meta( $progress->get_id(), 'grade', true );
		self::assertEmpty( $actual );
	}

	private function export_progress( Quiz_Progress $progress ): array {
		return [
			'user_id' => $progress->get_user_id(),
			'quiz_id' => $progress->get_quiz_id(),
			'status'  => $progress->get_status(),
		];
	}
}
