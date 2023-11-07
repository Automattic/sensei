<?php
/**
 * File containing the Comments_Based_Lesson_Progress_Test class.
 */

namespace SenseiTest\Internal\Student_Progress\Lesson_Progress\Models;

use Sensei\Internal\Student_Progress\Lesson_Progress\Models\Comments_Based_Lesson_Progress;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Comments_Based_Lesson_Progress_Test.
 *
 * @covers \Sensei\Internal\Student_Progress\Lesson_Progress\Models\Comments_Based_Lesson_Progress
 */
class Comments_Based_Lesson_Progress_Test extends \WP_UnitTestCase {
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

	public function testGetId_ConstructedWithId_ReturnsSameId(): void {
		/* Arrange. */
		$lesson_progress = $this->create_progress();

		/* Act. */
		$actual = $lesson_progress->get_id();

		/* Assert. */
		self::assertSame( 1, $actual );
	}

	public function testGetLessonId_ConstructedWithLessonId_ReturnsSameLessonId(): void {
		/* Arrange. */
		$lesson_progress = $this->create_progress();

		/* Act. */
		$actual = $lesson_progress->get_lesson_id();

		/* Assert. */
		self::assertSame( 2, $actual );
	}

	public function testGetUserId_ConstructedWithUserId_ReturnsSameUserId(): void {
		/* Arrange. */
		$lesson_progress = $this->create_progress();

		/* Act. */
		$actual = $lesson_progress->get_user_id();

		/* Assert. */
		self::assertSame( 3, $actual );
	}

	/**
	 * Test that get status returns the expected value.
	 *
	 * @dataProvider providerGetStatus_ConstructedWithNonFaildedStatus_ReturnsMatchingStatus
	 */
	public function testGetStatus_ConstructedWithNonFaildedStatus_ReturnsMatchingStatus( $internal_status, $expected_status ): void {
		/* Arrange. */
		$lesson_progress = $this->create_progress( $internal_status );

		/* Act. */
		$actual = $lesson_progress->get_status();

		/* Assert. */
		self::assertSame( $expected_status, $actual );
	}

	public function providerGetStatus_ConstructedWithNonFaildedStatus_ReturnsMatchingStatus(): array {
		return array(
			'internal in-progress' => array( 'in-progress', 'in-progress' ),
			'internal complete'    => array( 'complete', 'complete' ),
			// The following values come from quiz progress:
			'internal passed'      => array( 'passed', 'complete' ),
			'internal graded'      => array( 'graded', 'complete' ),
			'internal ungraded'    => array( 'ungraded', 'in-progress' ),
		);
	}

	public function testGetStatus_ConstructedWithFaildedStatusAndPassNotRequired_ReturnsComplete(): void {
		/* Arrange. */
		$lesson_id = $this->factory->lesson->create();
		$quiz_id   = $this->factory->quiz->create(
			array(
				'post_parent' => $lesson_id,
				'meta_input'  => array(
					'_pass_required' => 0,
				),
			)
		);

		$progress = new Comments_Based_Lesson_Progress(
			1,
			$lesson_id,
			3,
			'failed',
			new \DateTime( '2020-01-01 00:00:00' ),
			new \DateTime( '2020-01-01 00:00:01' ),
			new \DateTime( '2020-01-01 00:00:02' ),
			new \DateTime( '2020-01-01 00:00:03' )
		);

		/* Act. */
		$actual = $progress->get_status();

		/* Assert. */
		self::assertSame( 'complete', $actual );
	}

	public function testGetStatus_ConstructedWithFaildedStatusAndPassRequired_ReturnsInProgress(): void {
		/* Arrange. */
		$lesson_id = $this->factory->lesson->create();
		$quiz_id   = $this->factory->quiz->create(
			array(
				'post_parent' => $lesson_id,
				'meta_input'  => array(
					'_pass_required' => 1,
				),
			)
		);

		$progress = new Comments_Based_Lesson_Progress(
			1,
			$lesson_id,
			3,
			'failed',
			new \DateTime( '2020-01-01 00:00:00' ),
			new \DateTime( '2020-01-01 00:00:01' ),
			new \DateTime( '2020-01-01 00:00:02' ),
			new \DateTime( '2020-01-01 00:00:03' )
		);

		/* Act. */
		$actual = $progress->get_status();

		/* Assert. */
		self::assertSame( 'in-progress', $actual );
	}

	public function testGetStartedAt_ConstructedWithStartedAt_ReturnsSameStartedAt(): void {
		/* Arrange. */
		$lesson_progress = $this->create_progress();

		/* Act. */
		$actual = $lesson_progress->get_started_at()->format( 'Y-m-d H:i:s' );

		/* Assert. */
		self::assertSame( '2020-01-01 00:00:00', $actual );
	}

	public function testGetCompletedAt_ConstructedWithCompletedAt_ReturnsSameCompletedAt(): void {
		/* Arrange. */
		$lesson_progress = $this->create_progress();

		/* Act. */
		$actual = $lesson_progress->get_completed_at()->format( 'Y-m-d H:i:s' );

		/* Assert. */
		self::assertSame( '2020-01-01 00:00:01', $actual );
	}

	public function testGetCreatedAt_ConstructedWithCreatedAt_ReturnsSameCreatedAt(): void {
		/* Arrange. */
		$lesson_progress = $this->create_progress();

		/* Act. */
		$actual = $lesson_progress->get_created_at()->format( 'Y-m-d H:i:s' );

		/* Assert. */
		self::assertSame( '2020-01-01 00:00:02', $actual );
	}

	public function testGetUpdatedAt_ConstructedWithUpdatedAt_ReturnsSameUpdatedAt(): void {
		/* Arrange. */
		$lesson_progress = $this->create_progress();

		/* Act. */
		$actual = $lesson_progress->get_updated_at()->format( 'Y-m-d H:i:s' );

		/* Assert. */
		self::assertSame( '2020-01-01 00:00:03', $actual );
	}

	public function testGetUpdatedAt_WhenUpdatedAtSet_ReturnsSameUpdatedAt(): void {
		/* Arrange. */
		$lesson_progress = $this->create_progress();
		$lesson_progress->set_updated_at( new \DateTime( '2020-01-01 00:00:04' ) );

		/* Act. */
		$actual = $lesson_progress->get_updated_at()->format( 'Y-m-d H:i:s' );

		/* Assert. */
		self::assertSame( '2020-01-01 00:00:04', $actual );
	}

	public function testGetStartedAt_WhenStartWithStartedAtCalled_ReturnsSameStartedAt(): void {
		/* Arrange. */
		$started_at = new \DateTimeImmutable();
		$progress   = $this->create_progress();

		/* Act. */
		$progress->start( $started_at );

		/* Assert. */
		self::assertSame( $started_at, $progress->get_started_at() );
	}

	public function testIsComplete_WhenConstructedInProgressAndCompleteCalled_ReturnsTrue(): void {
		/* Arrange. */
		$progress = $this->create_progress( 'in-progress' );
		$progress->complete();

		/* Act. */
		$actual = $progress->is_complete();

		/* Assert. */
		self::assertTrue( $actual );
	}

	public function testIsComplete_WhenConstructedCompleteAndStartCalled_ReturnsFalse(): void {
		/* Arrange. */
		$progress = $this->create_progress( 'complete' );
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
		$progress = $this->create_progress( $status );

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

	public function testIsComplete_ConstructedWithFaildedStatusAndPassNotRequired_ReturnsTrue(): void {
		/* Arrange. */
		$lesson_id = $this->factory->lesson->create();
		$quiz_id   = $this->factory->quiz->create(
			array(
				'post_parent' => $lesson_id,
				'meta_input'  => array(
					'_pass_required' => 0,
				),
			)
		);

		$progress = new Comments_Based_Lesson_Progress(
			1,
			$lesson_id,
			3,
			'failed',
			new \DateTime( '2020-01-01 00:00:00' ),
			new \DateTime( '2020-01-01 00:00:01' ),
			new \DateTime( '2020-01-01 00:00:02' ),
			new \DateTime( '2020-01-01 00:00:03' )
		);

		/* Act. */
		$actual = $progress->is_complete();

		/* Assert. */
		self::assertTrue( $actual );
	}

	public function testIsComplete_ConstructedWithFaildedStatusAndPassRequired_ReturnsFalse(): void {
		/* Arrange. */
		$lesson_id = $this->factory->lesson->create();
		$quiz_id   = $this->factory->quiz->create(
			array(
				'post_parent' => $lesson_id,
				'meta_input'  => array(
					'_pass_required' => 1,
				),
			)
		);

		$progress = new Comments_Based_Lesson_Progress(
			1,
			$lesson_id,
			3,
			'failed',
			new \DateTime( '2020-01-01 00:00:00' ),
			new \DateTime( '2020-01-01 00:00:01' ),
			new \DateTime( '2020-01-01 00:00:02' ),
			new \DateTime( '2020-01-01 00:00:03' )
		);

		/* Act. */
		$actual = $progress->is_complete();

		/* Assert. */
		self::assertFalse( $actual );
	}

	public function testGetCompletedAt_WhenCompleteWithCompletedAtCalled_ReturnsSameCompletedAt(): void {
		/* Arrange. */
		$completed_at = new \DateTimeImmutable();
		$progress     = $this->create_progress();

		/* Act. */
		$progress->complete( $completed_at );

		/* Assert. */
		self::assertSame( $completed_at, $progress->get_completed_at() );
	}

	private function create_progress( string $status = null ): Comments_Based_Lesson_Progress {
		return new Comments_Based_Lesson_Progress(
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
