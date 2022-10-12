<?php
/**
 * File containing the Comments_Based_Course_Progress_Test class.
 *
 * @package sensei
 */

namespace SenseiTest\Student_Progress\Course_Progress\Models;

use Sensei\Student_Progress\Course_Progress\Models\Course_Progress;

/**
 * Class Comments_Based_Course_Progress_Test.
 *
 * @covers \Sensei\Student_Progress\Course_Progress\Models\Course_Progress
 */
class Comments_Based_Course_Progress_Test extends \WP_UnitTestCase {
	public function testGetStartedAt_WhenStartWithStartedAtCalled_ReturnsSameStartedAt(): void {
		/* Arrange. */
		$started_at = new \DateTimeImmutable();
		$progress   = $this->createProgress();

		/* Act. */
		$progress->start( $started_at );

		/* Assert. */
		self::assertSame( $started_at, $progress->get_started_at() );
	}

	public function testGetStatus_WhenStartCalled_ReturnsMatchingStatus(): void {
		/* Arrange. */
		$progress = $this->createProgress();

		/* Act. */
		$progress->start();

		/* Assert. */
		self::assertSame( 'in-progress', $progress->get_status() );
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

	public function testGetStatus_WhenCompleteCalled_ReturnsMatchingStatus(): void {
		/* Arrange. */
		$progress = $this->createProgress();

		/* Act. */
		$progress->complete();

		/* Assert. */
		self::assertSame( 'complete', $progress->get_status() );
	}

	private function createProgress(): Course_Progress {
		return new Course_Progress(
			1,
			2,
			3,
			'complete',
			new \DateTime( '2020-01-01 00:00:01' ),
			new \DateTime( '2020-01-01 00:00:02' ),
			new \DateTime( '2020-01-01 00:00:00' ),
			new \DateTime( '2020-01-01 00:00:03' ),
		);
	}
}
