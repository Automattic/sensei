<?php

namespace SenseiTest\Internal\Student_Progress\Quiz_Progress\Models;

use Sensei\Internal\Student_Progress\Quiz_Progress\Models\Tables_Based_Quiz_Progress;

/**
 * Class Tables_Based_Quiz_Progress_Test.
 *
 * @covers \Sensei\Internal\Student_Progress\Quiz_Progress\Models\Tables_Based_Quiz_Progress
 */
class Tables_Based_Quiz_Progress_Test extends \WP_UnitTestCase {

	public function testGetId_ConstructedWithId_ReturnsSameId(): void {
		/* Arrange. */
		$course_progress = $this->create_progress();

		/* Act. */
		$actual = $course_progress->get_id();

		/* Assert. */
		self::assertSame( 1, $actual );
	}

	public function testGetQuizId_ConstructedWithQuizId_ReturnsSameQuizId(): void {
		/* Arrange. */
		$course_progress = $this->create_progress();

		/* Act. */
		$actual = $course_progress->get_quiz_id();

		/* Assert. */
		self::assertSame( 2, $actual );
	}

	public function testGetUserId_ConstructedWithUserId_ReturnsSameUserId(): void {
		/* Arrange. */
		$course_progress = $this->create_progress();

		/* Act. */
		$actual = $course_progress->get_user_id();

		/* Assert. */
		self::assertSame( 3, $actual );
	}

	public function testGetStatus_ConstructedWithStatus_ReturnsSameStatus(): void {
		/* Arrange. */
		$course_progress = $this->create_progress();

		/* Act. */
		$actual = $course_progress->get_status();

		/* Assert. */
		self::assertSame( 'in-progress', $actual );
	}

	public function testGetStartedAt_ConstructedWithStartedAt_ReturnsSameStartedAt(): void {
		/* Arrange. */
		$course_progress = $this->create_progress();

		/* Act. */
		$actual = $course_progress->get_started_at()->format( 'Y-m-d H:i:s' );

		/* Assert. */
		self::assertSame( '2020-01-01 00:00:00', $actual );
	}

	public function testGetCompletedAt_ConstructedWithCompletedAt_ReturnsSameCompletedAt(): void {
		/* Arrange. */
		$course_progress = $this->create_progress();

		/* Act. */
		$actual = $course_progress->get_completed_at()->format( 'Y-m-d H:i:s' );

		/* Assert. */
		self::assertSame( '2020-01-01 00:00:01', $actual );
	}

	public function testGetCreatedAt_ConstructedWithCreatedAt_ReturnsSameCreatedAt(): void {
		/* Arrange. */
		$course_progress = $this->create_progress();

		/* Act. */
		$actual = $course_progress->get_created_at()->format( 'Y-m-d H:i:s' );

		/* Assert. */
		self::assertSame( '2020-01-01 00:00:02', $actual );
	}

	public function testGetUpdatedAt_ConstructedWithUpdatedAt_ReturnsSameUpdatedAt(): void {
		/* Arrange. */
		$course_progress = $this->create_progress();

		/* Act. */
		$actual = $course_progress->get_updated_at()->format( 'Y-m-d H:i:s' );

		/* Assert. */
		self::assertSame( '2020-01-01 00:00:03', $actual );
	}

	public function testGetUpdatedAt_WhenUpdatedAtSet_ReturnsSameUpdatedAt(): void {
		/* Arrange. */
		$course_progress = $this->create_progress();
		$course_progress->set_updated_at( new \DateTime( '2020-01-01 00:00:04' ) );

		/* Act. */
		$actual = $course_progress->get_updated_at()->format( 'Y-m-d H:i:s' );

		/* Assert. */
		self::assertSame( '2020-01-01 00:00:04', $actual );
	}

	public function testGetStatus_WhenGradeCalled_ReturnsGradedStatus(): void {
		/* Arrange. */
		$course_progress = $this->create_progress();
		$course_progress->grade();

		/* Act. */
		$actual = $course_progress->get_status();

		/* Assert. */
		self::assertSame( 'graded', $actual );
	}

	public function testGetStatus_WhenFailCalled_ReturnsFailedStatus(): void {
		/* Arrange. */
		$course_progress = $this->create_progress();
		$course_progress->fail();

		/* Act. */
		$actual = $course_progress->get_status();

		/* Assert. */
		self::assertSame( 'failed', $actual );
	}

	public function testGetStatus_WhenPassCalled_ReturnsPassedStatus(): void {
		/* Arrange. */
		$course_progress = $this->create_progress();
		$course_progress->pass();

		/* Act. */
		$actual = $course_progress->get_status();

		/* Assert. */
		self::assertSame( 'passed', $actual );
	}

	public function testGetStatus_WhenUngradeCalled_ReturnsUngradedStatus(): void {
		/* Arrange. */
		$course_progress = $this->create_progress();
		$course_progress->ungrade();

		/* Act. */
		$actual = $course_progress->get_status();

		/* Assert. */
		self::assertSame( 'ungraded', $actual );
	}

	private function create_progress( string $status = null ): Tables_Based_Quiz_Progress {
		return new Tables_Based_Quiz_Progress(
			1,
			2,
			3,
			$status ?? 'in-progress',
			new \DateTime( '2020-01-01 00:00:00' ),
			new \DateTime( '2020-01-01 00:00:01' ),
			new \DateTime( '2020-01-01 00:00:02' ),
			new \DateTime( '2020-01-01 00:00:03' )
		);
	}}
