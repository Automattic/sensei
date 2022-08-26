<?php

namespace SenseiTest\Student_Progress\Repositories;

use Sensei\Student_Progress\Models\Course_Progress_Interface;
use Sensei\Student_Progress\Repositories\Course_Progress_Comments_Repository;

/**
 * Tests for the Course_Progress_Comments_Repository class.
 *
 * @covers \Sensei\Student_Progress\Repositories\Course_Progress_Comments_Repository
 */
class Course_Progress_Comments_Repository_Test extends \WP_UnitTestCase {
	private $factory;

	public function setup() {
		parent::setup();
		$this->factory = new \Sensei_Factory();
	}

	public function tearDown() {
		parent::tearDown();
		$this->factory->tearDown();
	}

	public function testGet_WhenStatusFound_ReturnsCourseProgress(): void {
		/* Arrange. */
		$course_id  = $this->factory->course->create();
		$user_id    = $this->factory->user->create();
		$repository = new Course_Progress_Comments_Repository();
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

	public function testGet_WhenStatusNotFound_ReturnsNull(): void {
		/* Arrange. */
		$course_id  = $this->factory->course->create();
		$user_id    = $this->factory->user->create();
		$repository = new Course_Progress_Comments_Repository();

		/* Act. */
		$progress = $repository->get( $course_id, $user_id );

		/* Assert. */
		self::assertNull( $progress );
	}

	public function testGet_WhenProgressCreated_ReturnsSameProgress(): void {
		/* Arrange. */
		$course_id  = $this->factory->course->create();
		$user_id    = $this->factory->user->create();
		$repository = new Course_Progress_Comments_Repository();
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
		$repository = new Course_Progress_Comments_Repository();
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
		$repository = new Course_Progress_Comments_Repository();

		/* Act. */
		$actual = $repository->has( $course_id, $user_id );

		/* Assert. */
		self::assertFalse( $actual );
	}

	public function testGet_WhenProgressChangedAndSaved_ReturnsUpdatedProgress(): void {
		/* Arrange. */
		$course_id  = $this->factory->course->create();
		$user_id    = $this->factory->user->create();
		$repository = new Course_Progress_Comments_Repository();

		$created = $repository->create( $course_id, $user_id );
		$created->complete();
		$repository->save( $created );

		/* Act. */
		$actual = $repository->get( $course_id, $user_id );

		/* Assert. */
		self::assertSame( $this->export_progress( $created ), $this->export_progress( $actual ) );
	}

	private function export_progress( Course_Progress_Interface $progress ): array {
		return [
			'user_id'   => $progress->get_user_id(),
			'course_id' => $progress->get_course_id(),
			'status'    => $progress->get_status(),
		];
	}
}
