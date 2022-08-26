<?php
/**
 * File containing the Lesson_Progress_Comments_Test class.
 */

namespace SenseiTest\Student_Progress\Models;

use Sensei\Student_Progress\Models\Lesson_Progress_Comments;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Lesson_Progress_Comments_Test.
 *
 * @covers \Sensei\Student_Progress\Models\Lesson_Progress_Comments
 */
class Lesson_Progress_Comments_Test extends \WP_UnitTestCase {

	public function testGetId_ConstructedWithId_ReturnsSameId(): void {
		/* Arrange. */
		$course_progress = $this->createProgress();

		/* Act. */
		$actual = $course_progress->get_id();

		/* Assert. */
		self::assertSame( 1, $actual );
	}

	public function testGetLessonId_ConstructedWithLessonId_ReturnsSameLessonId(): void {
		/* Arrange. */
		$course_progress = $this->createProgress();

		/* Act. */
		$actual = $course_progress->get_lesson_id();

		/* Assert. */
		self::assertSame( 2, $actual );
	}

	public function testGetUserId_ConstructedWithUserId_ReturnsSameUserId(): void {
		/* Arrange. */
		$course_progress = $this->createProgress();

		/* Act. */
		$actual = $course_progress->get_user_id();

		/* Assert. */
		self::assertSame( 3, $actual );
	}

	public function testGetStatus_ConstructedWithStatus_ReturnsSameStatus(): void {
		/* Arrange. */
		$course_progress = $this->createProgress();

		/* Act. */
		$actual = $course_progress->get_status();

		/* Assert. */
		self::assertSame( 'in-progress', $actual );
	}

	public function testGetStartedAt_ConstructedWithStartedAt_ReturnsSameStartedAt(): void {
		/* Arrange. */
		$course_progress = $this->createProgress();

		/* Act. */
		$actual = $course_progress->get_started_at()->format( 'Y-m-d H:i:s' );

		/* Assert. */
		self::assertSame( '2020-01-01 00:00:00', $actual );
	}

	public function testGetCompletedAt_ConstructedWithCompletedAt_ReturnsSameCompletedAt(): void {
		/* Arrange. */
		$course_progress = $this->createProgress();

		/* Act. */
		$actual = $course_progress->get_completed_at()->format( 'Y-m-d H:i:s' );

		/* Assert. */
		self::assertSame( '2020-01-01 00:00:01', $actual );
	}

	public function testGetCreatedAt_ConstructedWithCreatedAt_ReturnsSameCreatedAt(): void {
		/* Arrange. */
		$course_progress = $this->createProgress();

		/* Act. */
		$actual = $course_progress->get_created_at()->format( 'Y-m-d H:i:s' );

		/* Assert. */
		self::assertSame( '2020-01-01 00:00:02', $actual );
	}

	public function testGetUpdatedAt_ConstructedWithUpdatedAt_ReturnsSameUpdatedAt(): void {
		/* Arrange. */
		$course_progress = $this->createProgress();

		/* Act. */
		$actual = $course_progress->get_updated_at()->format( 'Y-m-d H:i:s' );

		/* Assert. */
		self::assertSame( '2020-01-01 00:00:03', $actual );
	}

	public function testGetUpdatedAt_WhenUpdatedAtSet_ReturnsSameUpdatedAt(): void {
		/* Arrange. */
		$course_progress = $this->createProgress();
		$course_progress->set_updated_at( new \DateTime( '2020-01-01 00:00:04' ) );

		/* Act. */
		$actual = $course_progress->get_updated_at()->format( 'Y-m-d H:i:s' );

		/* Assert. */
		self::assertSame( '2020-01-01 00:00:04', $actual );
	}

	public function testGetStartedAt_WhenStartWithStartedAtCalled_ReturnsSameStartedAt(): void {
		/* Arrange. */
		$started_at = new \DateTimeImmutable();
		$progress   = $this->createProgress();

		/* Act. */
		$progress->start( $started_at );

		/* Assert. */
		self::assertSame( $started_at, $progress->get_started_at() );
	}

	public function testIsComplete_WhenConstructedInProgressAndCompleteCalled_ReturnsTrue(): void {
		/* Arrange. */
		$progress = $this->createProgress( 'in-progress' );
		$progress->complete();

		/* Act. */
		$actual = $progress->is_complete();

		/* Assert. */
		self::assertTrue( $actual );
	}

	public function testIsComplete_WhenConstructedCompleteAndStartCalled_ReturnsFalse(): void {
		/* Arrange. */
		$progress = $this->createProgress( 'complete' );
		$progress->start();

		/* Act. */
		$actual = $progress->is_complete();

		/* Assert. */
		self::assertFalse( $actual );
	}

	/**
	 * Test that the progress is complete when the completed_at is set.
	 *
	 * @dataProvider providerIsComplete_WhenConstructedWithStatus_ReturnsMatchingValue
	 * @param string $status The status to set.
	 * @param bool   $expected The expected value.
	 */
	public function testIsComplete_WhenConstructedWithStatus_ReturnsMatchingValue( string $status, bool $expected ): void {
		/* Arrange. */
		$progress = $this->createProgress( $status );

		/* Act. */
		$actual = $progress->is_complete();

		/* Assert. */
		self::assertSame( $expected, $actual );
	}

	public function providerIsComplete_WhenConstructedWithStatus_ReturnsMatchingValue(): array {
		return [
			[ 'in-progress', false ],
			[ 'complete', true ],
			// The following values come from quiz progress:
			[ 'passed', true ],
			[ 'failed', false ],
			[ 'graded', true ],
			[ 'ungraded', false ],
		];
	}

	public function testGetCompletedAt_WhenCompleteWithCompletedAtCalled_ReturnsSameCompletedAt(): void {
		/* Arrange. */
		$completed_at = new \DateTimeImmutable();
		$progress     = $this->createProgress();

		/* Act. */
		$progress->complete( $completed_at );

		/* Assert. */
		self::assertSame( $completed_at, $progress->get_completed_at() );
	}

	private function createProgress( string $status = null ): Lesson_Progress_Comments {
		return new Lesson_Progress_Comments(
			1,
			2,
			3,
			$status ?? 'in-progress',
			new \DateTime( '2020-01-01 00:00:00' ),
			new \DateTime( '2020-01-01 00:00:01' ),
			new \DateTime( '2020-01-01 00:00:02' ),
			new \DateTime( '2020-01-01 00:00:03' )
		);
	}
}
