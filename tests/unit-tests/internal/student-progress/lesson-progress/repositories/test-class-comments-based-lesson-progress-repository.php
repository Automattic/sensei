<?php

namespace SenseiTest\Internal\Student_Progress\Lesson_Progress\Repositories;

use Sensei\Internal\Student_Progress\Lesson_Progress\Models\Lesson_Progress_Interface;
use Sensei\Internal\Student_Progress\Lesson_Progress\Repositories\Comments_Based_Lesson_Progress_Repository;

/**
 * Tests for the Comments_Based_Lesson_Progress_Repository_Test class.
 *
 * @covers \Sensei\Internal\Student_Progress\Lesson_Progress\Repositories\Comments_Based_Lesson_Progress_Repository
 */
class Comments_Based_Lesson_Progress_Repository_Test extends \WP_UnitTestCase {
	protected $factory;

	public function setUp(): void {
		parent::setUp();
		$this->factory = new \Sensei_Factory();
	}

	public function tearDown(): void {
		parent::tearDown();
		$this->factory->tearDown();
	}

	public function testGet_WhenStatusFound_ReturnsLessonProgress(): void {
		/* Arrange. */
		$lesson_id  = $this->factory->lesson->create();
		$user_id    = $this->factory->user->create();
		$repository = new Comments_Based_Lesson_Progress_Repository();
		\Sensei_Utils::update_lesson_status( $user_id, $lesson_id, 'in-progress' );

		/* Act. */
		$progress = $repository->get( $lesson_id, $user_id );

		/* Assert. */
		$expected = [
			'user_id'   => $user_id,
			'lesson_id' => $lesson_id,
			'status'    => 'in-progress',
		];
		self::assertSame( $expected, $this->export_progress( $progress ) );
	}

	public function testGet_WhenStatusNotFound_ReturnsNull(): void {
		/* Arrange. */
		$lesson_id  = $this->factory->lesson->create();
		$user_id    = $this->factory->user->create();
		$repository = new Comments_Based_Lesson_Progress_Repository();

		/* Act. */
		$progress = $repository->get( $lesson_id, $user_id );

		/* Assert. */
		self::assertNull( $progress );
	}

	public function testGet_WhenCreated_ReturnsSameProgress(): void {
		/* Arrange. */
		$lesson_id  = $this->factory->lesson->create();
		$user_id    = $this->factory->user->create();
		$repository = new Comments_Based_Lesson_Progress_Repository();
		$created    = $repository->create( $lesson_id, $user_id );

		/* Act. */
		$progress = $repository->get( $lesson_id, $user_id );

		/* Assert. */
		self::assertSame( $this->export_progress( $created ), $this->export_progress( $progress ) );
	}

	public function testHas_WhenStatusFound_ReturnsTrue(): void {
		/* Arrange. */
		$lesson_id  = $this->factory->lesson->create();
		$user_id    = $this->factory->user->create();
		$repository = new Comments_Based_Lesson_Progress_Repository();
		\Sensei_Utils::update_lesson_status( $user_id, $lesson_id, 'in-progress' );

		/* Act. */
		$has = $repository->has( $lesson_id, $user_id );

		/* Assert. */
		self::assertTrue( $has );
	}

	public function testHas_WhenStatusNotFound_ReturnsFalse(): void {
		/* Arrange. */
		$lesson_id  = $this->factory->lesson->create();
		$user_id    = $this->factory->user->create();
		$repository = new Comments_Based_Lesson_Progress_Repository();

		/* Act. */
		$has = $repository->has( $lesson_id, $user_id );

		/* Assert. */
		self::assertFalse( $has );
	}

	public function testGet_WhenProgressChangedAndSaved_ReturnsUpdatedProgress(): void {
		/* Arrange. */
		$lesson_id  = $this->factory->lesson->create();
		$user_id    = $this->factory->user->create();
		$repository = new Comments_Based_Lesson_Progress_Repository();
		$progress   = $repository->create( $lesson_id, $user_id );
		$progress->complete();
		$repository->save( $progress );

		/* Act. */
		$actual = $repository->get( $lesson_id, $user_id );

		/* Assert. */
		self::assertSame( $this->export_progress( $progress ), $this->export_progress( $actual ) );
	}

	public function testSave_WhenNonCommentsBasedProgressGiven_ThrowsException(): void {
		/* Arrange. */
		$progress   = $this->createMock( Lesson_Progress_Interface::class );
		$repository = new Comments_Based_Lesson_Progress_Repository();

		/* Expect&Act. */
		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Expected Comments_Based_Lesson_Progress, got ' . get_class( $progress ) . '.' );
		$repository->save( $progress );
	}

	public function testCount_WhenNoProgress_ReturnsZero(): void {
		/* Arrange. */
		$course_id  = $this->factory->course->create();
		$user_id    = $this->factory->user->create();
		$repository = new Comments_Based_Lesson_Progress_Repository();

		/* Act. */
		$count = $repository->count( $course_id, $user_id );

		/* Assert. */
		self::assertSame( 0, $count );
	}

	public function testCount_WhenProgressCreated_ReturnsOne(): void {
		/* Arrange. */
		$course_id  = $this->factory->course->create();
		$lesson_id  = $this->factory->lesson->create( [ 'meta_input' => [ '_lesson_course' => $course_id ] ] );
		$user_id    = $this->factory->user->create();
		$repository = new Comments_Based_Lesson_Progress_Repository();
		$repository->create( $lesson_id, $user_id );

		/* Act. */
		$count = $repository->count( $course_id, $user_id );

		/* Assert. */
		self::assertSame( 1, $count );
	}

	public function testDelete_WhenProgressGiven_DeletesProgress(): void {
		/* Arrange. */
		$lesson_id  = $this->factory->lesson->create();
		$user_id    = $this->factory->user->create();
		$repository = new Comments_Based_Lesson_Progress_Repository();
		$progress   = $repository->create( $lesson_id, $user_id );

		/* Act. */
		$repository->delete( $progress );

		/* Assert. */
		self::assertFalse( $repository->has( $lesson_id, $user_id ) );
	}

	public function testDeleteForLesson_WhenLessonGiven_DeletesProgressForLesson(): void {
		/* Arrange. */
		$lesson_id              = $this->factory->lesson->create();
		$second_lesson_id       = $this->factory->lesson->create();
		$user_id                = $this->factory->user->create();
		$repository             = new Comments_Based_Lesson_Progress_Repository();
		$progress_to_be_deleted = $repository->create( $lesson_id, $user_id );
		$progress_to_be_kept    = $repository->create( $second_lesson_id, $user_id );

		/* Act. */
		$repository->delete_for_lesson( $lesson_id );

		/* Assert. */
		self::assertFalse( $repository->has( $lesson_id, $user_id ) );
		self::assertTrue( $repository->has( $second_lesson_id, $user_id ) );
	}

	public function testDeleteForUser_WhenUserGiven_DeletesProgressForUser(): void {
		/* Arrange. */
		$lesson_id              = $this->factory->lesson->create();
		$user_id                = $this->factory->user->create();
		$deleted_user_id        = $this->factory->user->create();
		$repository             = new Comments_Based_Lesson_Progress_Repository();
		$progress_to_be_deleted = $repository->create( $lesson_id, $user_id );
		$progress_to_be_kept    = $repository->create( $lesson_id, $deleted_user_id );

		/* Act. */
		$repository->delete_for_user( $deleted_user_id );

		/* Assert. */
		self::assertFalse( $repository->has( $lesson_id, $deleted_user_id ) );
		self::assertTrue( $repository->has( $lesson_id, $user_id ) );
	}

	public function testFind_ArgumentsGiven_ReturnsMatchingProgress(): void {
		/* Arrange. */
		$lesson_ids = $this->factory->lesson->create_many( 5 );
		$user_id    = $this->factory->user->create();

		$repository       = new Comments_Based_Lesson_Progress_Repository();
		$created_progress = [];
		foreach ( $lesson_ids as $lesson_id ) {
			$created_progress[] = $repository->create( $lesson_id, $user_id );
		}

		$expected = array();
		for ( $i = 0; $i < 3; $i++ ) {
			$progress = $created_progress[ $i ];
			$progress->complete();
			$repository->save( $progress );
			$expected[] = $this->export_progress( $progress );
		}

		/* Act. */
		$found_progress = $repository->find(
			array(
				'user_id' => $user_id,
				'status'  => 'complete',
			)
		);
		$actual         = array_map( array( $this, 'export_progress' ), $found_progress );

		/* Assert. */
		self::assertSame( $expected, $actual );
	}

	private function export_progress( Lesson_Progress_Interface $progress ): array {
		return [
			'user_id'   => $progress->get_user_id(),
			'lesson_id' => $progress->get_lesson_id(),
			'status'    => $progress->get_status(),
		];
	}
}
