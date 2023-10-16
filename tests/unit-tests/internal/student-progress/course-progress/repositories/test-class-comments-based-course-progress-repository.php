<?php

namespace SenseiTest\Internal\Student_Progress\Course_Progress\Repositories;

use Sensei\Internal\Student_Progress\Course_Progress\Models\Course_Progress_Interface;
use Sensei\Internal\Student_Progress\Course_Progress\Repositories\Comments_Based_Course_Progress_Repository;

/**
 * Tests for the Comments_Based_Course_Progress_Repository_Test class.
 *
 * @covers \Sensei\Internal\Student_Progress\Course_Progress\Repositories\Comments_Based_Course_Progress_Repository
 */
class Comments_Based_Course_Progress_Repository_Test extends \WP_UnitTestCase {
	protected $factory;

	public function setUp(): void {
		parent::setUp();
		$this->factory = new \Sensei_Factory();
	}

	public function tearDown(): void {
		parent::tearDown();
		$this->factory->tearDown();
	}

	public function testGet_WhenStatusFound_ReturnsCourseProgress(): void {
		/* Arrange. */
		$course_id  = $this->factory->course->create();
		$user_id    = $this->factory->user->create();
		$repository = new Comments_Based_Course_Progress_Repository();
		\Sensei_Utils::update_course_status( $user_id, $course_id, 'in-progress' );

		/* Act. */
		$progress = $repository->get( $course_id, $user_id );

		/* Assert. */
		$expected = [
			'user_id'   => $user_id,
			'course_id' => $course_id,
			'status'    => 'in-progress',
		];
		self::assertSame( $expected, $this->export_progress( $progress ) );
	}

	public function testGet_WhenSeveralStatusesFound_ReturnsCourseProgress(): void {
		/* Arrange. */
		$course_id  = $this->factory->course->create();
		$user_id    = $this->factory->user->create();
		$repository = new Comments_Based_Course_Progress_Repository();
		$this->create_status_comment( $user_id, $course_id, 'complete' );
		$this->create_status_comment( $user_id, $course_id, 'in-progress' );

		/* Act. */
		$progress = $repository->get( $course_id, $user_id );

		/* Assert. */
		$expected = [
			'user_id'   => $user_id,
			'course_id' => $course_id,
			'status'    => 'in-progress',
		];
		self::assertSame( $expected, $this->export_progress( $progress ) );
	}

	private function create_status_comment( $user_id, $course_id, $status ) {
		$comment_id = wp_insert_comment(
			[
				'comment_post_ID'  => $course_id,
				'user_id'          => $user_id,
				'comment_type'     => 'sensei_course_status',
				'comment_approved' => $status,
			]
		);
		return $comment_id;
	}

	public function testGet_WhenStatusNotFound_ReturnsNull(): void {
		/* Arrange. */
		$course_id  = $this->factory->course->create();
		$user_id    = $this->factory->user->create();
		$repository = new Comments_Based_Course_Progress_Repository();

		/* Act. */
		$progress = $repository->get( $course_id, $user_id );

		/* Assert. */
		self::assertNull( $progress );
	}

	public function testGet_WhenProgressCreated_ReturnsSameProgress(): void {
		/* Arrange. */
		$course_id  = $this->factory->course->create();
		$user_id    = $this->factory->user->create();
		$repository = new Comments_Based_Course_Progress_Repository();
		\Sensei_Utils::update_course_status( $user_id, $course_id, 'in-progress' );
		$created = $repository->create( $course_id, $user_id );

		/* Act. */
		$actual = $repository->get( $course_id, $user_id );

		/* Assert. */
		self::assertSame( $this->export_progress( $created ), $this->export_progress( $actual ) );
	}

	public function testHas_WhenStatusFound_ReturnsTrue(): void {
		/* Arrange. */
		$course_id  = $this->factory->course->create();
		$user_id    = $this->factory->user->create();
		$repository = new Comments_Based_Course_Progress_Repository();
		\Sensei_Utils::update_course_status( $user_id, $course_id, 'in-progress' );
		$repository->create( $course_id, $user_id );

		/* Act. */
		$actual = $repository->has( $course_id, $user_id );

		/* Assert. */
		self::assertTrue( $actual );
	}

	public function testHas_WhenStatusNotFound_ReturnsFalse(): void {
		/* Arrange. */
		$course_id  = $this->factory->course->create();
		$user_id    = $this->factory->user->create();
		$repository = new Comments_Based_Course_Progress_Repository();

		/* Act. */
		$actual = $repository->has( $course_id, $user_id );

		/* Assert. */
		self::assertFalse( $actual );
	}

	public function testGet_WhenProgressChangedAndSaved_ReturnsUpdatedProgress(): void {
		/* Arrange. */
		$course_id  = $this->factory->course->create();
		$user_id    = $this->factory->user->create();
		$repository = new Comments_Based_Course_Progress_Repository();

		$created = $repository->create( $course_id, $user_id );
		$created->complete();
		$repository->save( $created );

		/* Act. */
		$actual = $repository->get( $course_id, $user_id );

		/* Assert. */
		self::assertSame( $this->export_progress( $created ), $this->export_progress( $actual ) );
	}

	public function testDelete_WhenProgressGiven_DeletesProgress(): void {
		/* Arrange. */
		$course_id  = $this->factory->course->create();
		$user_id    = $this->factory->user->create();
		$repository = new Comments_Based_Course_Progress_Repository();

		$created = $repository->create( $course_id, $user_id );
		$repository->save( $created );

		/* Act. */
		$repository->delete( $created );

		/* Assert. */
		self::assertFalse( $repository->has( $course_id, $user_id ) );
	}

	public function testDeleteForCourse_WhenCourseGiven_DeletesProgressForCourse(): void {
		/* Arrange. */
		$course_id              = $this->factory->course->create();
		$second_course_id       = $this->factory->course->create();
		$user_id                = $this->factory->user->create();
		$repository             = new Comments_Based_Course_Progress_Repository();
		$progress_to_be_deleted = $repository->create( $course_id, $user_id );
		$progress_to_be_kept    = $repository->create( $second_course_id, $user_id );

		/* Act. */
		$repository->delete_for_course( $course_id );

		/* Assert. */
		self::assertFalse( $repository->has( $course_id, $user_id ) );
		self::assertTrue( $repository->has( $second_course_id, $user_id ) );
	}

	public function testDeleteForUser_WhenUserGiven_DeletesProgressForUser(): void {
		/* Arrange. */
		$course_id              = $this->factory->course->create();
		$user_id                = $this->factory->user->create();
		$deleted_user_id        = $this->factory->user->create();
		$repository             = new Comments_Based_Course_Progress_Repository();
		$progress_to_be_deleted = $repository->create( $course_id, $user_id );
		$progress_to_be_kept    = $repository->create( $course_id, $deleted_user_id );

		/* Act. */
		$repository->delete_for_user( $deleted_user_id );

		/* Assert. */
		self::assertFalse( $repository->has( $course_id, $deleted_user_id ) );
		self::assertTrue( $repository->has( $course_id, $user_id ) );
	}

	public function testSave_CommentsBasedProgressGiven_ThrowsException(): void {
		/* Arrange. */
		$progress   = $this->createMock( Course_Progress_Interface::class );
		$repository = new Comments_Based_Course_Progress_Repository();

		/* Expect & Act. */
		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Expected Comments_Based_Course_Progress, got ' . get_class( $progress ) . '.' );
		$repository->save( $progress );
	}

	public function testFind_ArgumentsGiven_ReturnsMatchingProgress(): void {
		/* Arrange. */
		$course_ids = $this->factory->course->create_many( 5 );
		$user_id    = $this->factory->user->create();

		$repository       = new Comments_Based_Course_Progress_Repository();
		$created_progress = [];
		foreach ( $course_ids as $course_id ) {
			$created_progress[] = $repository->create( $course_id, $user_id );
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

	private function export_progress( Course_Progress_Interface $progress ): array {
		return [
			'user_id'   => $progress->get_user_id(),
			'course_id' => $progress->get_course_id(),
			'status'    => $progress->get_status(),
		];
	}
}
