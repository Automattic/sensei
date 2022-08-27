<?php

namespace SenseiTest\Student_Progress\Repositories;

use Sensei\Student_Progress\Models\Quiz_Progress;
use Sensei\Student_Progress\Repositories\Quiz_Progress_Comments_Repository;

/**
 * Tests for the Quiz_Progress_Comments_Repository class.
 *
 * @covers \Sensei\Student_Progress\Repositories\Quiz_Progress_Comments_Repository
 */
class Quiz_Progress_Comments_Repository_Test extends \WP_UnitTestCase {
	/**
	 * Sensei factory.
	 *
	 * @var \Sensei_Factory
	 */
	private $factory;

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
		$repository = new Quiz_Progress_Comments_Repository();
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
		$repository = new Quiz_Progress_Comments_Repository();

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
		$repository = new Quiz_Progress_Comments_Repository();
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
		$repository = new Quiz_Progress_Comments_Repository();

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
		$repository = new Quiz_Progress_Comments_Repository();
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
		$repository = new Quiz_Progress_Comments_Repository();

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

		$repository = new Quiz_Progress_Comments_Repository();

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

	private function export_progress( Quiz_Progress $progress ): array {
		return [
			'user_id' => $progress->get_user_id(),
			'quiz_id' => $progress->get_quiz_id(),
			'status'  => $progress->get_status(),
		];
	}
}
