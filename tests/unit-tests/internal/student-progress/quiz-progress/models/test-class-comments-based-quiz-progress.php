<?php

namespace SenseiTest\Internal\Student_Progress\Quiz_Progress\Models;

use Sensei\Internal\Student_Progress\Quiz_Progress\Models\Comments_Based_Quiz_Progress;

/**
 * Class Comments_Based_Quiz_Progress_Test.
 *
 * @covers \Sensei\Internal\Student_Progress\Quiz_Progress\Models\Comments_Based_Quiz_Progress
 */
class Comments_Based_Quiz_Progress_Test extends \WP_UnitTestCase {

	public function testGetId_ConstructedWithId_ReturnsSameId(): void {
		/* Arrange. */
		$quiz_progress = $this->create_progress();

		/* Act. */
		$actual = $quiz_progress->get_id();

		/* Assert. */
		self::assertSame( 1, $actual );
	}

	public function testGetQuizId_ConstructedWithQuizId_ReturnsSameQuizId(): void {
		/* Arrange. */
		$quiz_progress = $this->create_progress();

		/* Act. */
		$actual = $quiz_progress->get_quiz_id();

		/* Assert. */
		self::assertSame( 2, $actual );
	}

	public function testGetUserId_ConstructedWithUserId_ReturnsSameUserId(): void {
		/* Arrange. */
		$quiz_progress = $this->create_progress();

		/* Act. */
		$actual = $quiz_progress->get_user_id();

		/* Assert. */
		self::assertSame( 3, $actual );
	}

	public function testGetStatus_ConstructedWithStatus_ReturnsSameStatus(): void {
		/* Arrange. */
		$quiz_progress = $this->create_progress();

		/* Act. */
		$actual = $quiz_progress->get_status();

		/* Assert. */
		self::assertSame( 'in-progress', $actual );
	}

	/**
	 * Test that the quiz is considered submitted based on the quiz progress status.
	 *
	 * @dataProvider providerIsQuizSubmitted_ConstructedWithStatus_ReturnsMatchingValue
	 */
	public function testIsQuizSubmitted_ConstructedWithStatus_ReturnsMatchingValue( string $status, bool $expected ): void {
		/* Arrange. */
		$quiz_progress = $this->create_progress( $status );

		/* Act. */
		$actual = $quiz_progress->is_quiz_submitted();

		/* Assert. */
		self::assertSame( $expected, $actual );
	}

	public function providerIsQuizSubmitted_ConstructedWithStatus_ReturnsMatchingValue(): array {
		return array(
			'quiz in progress'                => array( 'in-progress', false ),
			'quiz graded'                     => array( 'graded', true ),
			'quiz failed'                     => array( 'failed', true ),
			'quiz passed'                     => array( 'passed', true ),
			'quiz ungraded'                   => array( 'ungraded', true ),
			'lesson complete (legacy status)' => array( 'complete', false ),
		);
	}

	/**
	 * Test that the quiz is considered completed based on the quiz progress status.
	 *
	 * @dataProvider providerIsQuizCompleted_ConstructedWithStatus_ReturnsMatchingValue
	 */
	public function testIsQuizCompleted_ConstructedWithStatus_ReturnsMatchingValue( string $status, bool $expected ): void {
		/* Arrange. */
		$quiz_progress = $this->create_progress( $status );

		/* Act. */
		$actual = $quiz_progress->is_quiz_completed();

		/* Assert. */
		self::assertSame( $expected, $actual );
	}

	public function providerIsQuizCompleted_ConstructedWithStatus_ReturnsMatchingValue(): array {
		return array(
			'quiz in progress'                => array( 'in-progress', false ),
			'quiz graded'                     => array( 'graded', true ),
			'quiz failed'                     => array( 'failed', true ),
			'quiz passed'                     => array( 'passed', true ),
			'quiz ungraded'                   => array( 'ungraded', false ),
			'lesson complete (legacy status)' => array( 'complete', false ),
		);
	}

	public function testGetStartedAt_ConstructedWithStartedAt_ReturnsSameStartedAt(): void {
		/* Arrange. */
		$quiz_progress = $this->create_progress();

		/* Act. */
		$actual = $quiz_progress->get_started_at()->format( 'Y-m-d H:i:s' );

		/* Assert. */
		self::assertSame( '2020-01-01 00:00:00', $actual );
	}

	public function testGetCompletedAt_ConstructedWithCompletedAt_ReturnsSameCompletedAt(): void {
		/* Arrange. */
		$quiz_progress = $this->create_progress();

		/* Act. */
		$actual = $quiz_progress->get_completed_at()->format( 'Y-m-d H:i:s' );

		/* Assert. */
		self::assertSame( '2020-01-01 00:00:01', $actual );
	}

	public function testGetCreatedAt_ConstructedWithCreatedAt_ReturnsSameCreatedAt(): void {
		/* Arrange. */
		$quiz_progress = $this->create_progress();

		/* Act. */
		$actual = $quiz_progress->get_created_at()->format( 'Y-m-d H:i:s' );

		/* Assert. */
		self::assertSame( '2020-01-01 00:00:02', $actual );
	}

	public function testGetUpdatedAt_ConstructedWithUpdatedAt_ReturnsSameUpdatedAt(): void {
		/* Arrange. */
		$quiz_progress = $this->create_progress();

		/* Act. */
		$actual = $quiz_progress->get_updated_at()->format( 'Y-m-d H:i:s' );

		/* Assert. */
		self::assertSame( '2020-01-01 00:00:03', $actual );
	}

	public function testGetUpdatedAt_WhenUpdatedAtSet_ReturnsSameUpdatedAt(): void {
		/* Arrange. */
		$quiz_progress = $this->create_progress();
		$quiz_progress->set_updated_at( new \DateTime( '2020-01-01 00:00:04' ) );

		/* Act. */
		$actual = $quiz_progress->get_updated_at()->format( 'Y-m-d H:i:s' );

		/* Assert. */
		self::assertSame( '2020-01-01 00:00:04', $actual );
	}

	public function testGetStatus_WhenGradeCalled_ReturnsGradedStatus(): void {
		/* Arrange. */
		$quiz_progress = $this->create_progress();
		$quiz_progress->grade();

		/* Act. */
		$actual = $quiz_progress->get_status();

		/* Assert. */
		self::assertSame( 'graded', $actual );
	}

	public function testGetStatus_WhenFailCalled_ReturnsFailedStatus(): void {
		/* Arrange. */
		$quiz_progress = $this->create_progress();
		$quiz_progress->fail();

		/* Act. */
		$actual = $quiz_progress->get_status();

		/* Assert. */
		self::assertSame( 'failed', $actual );
	}

	public function testGetStatus_WhenPassCalled_ReturnsPassedStatus(): void {
		/* Arrange. */
		$quiz_progress = $this->create_progress();
		$quiz_progress->pass();

		/* Act. */
		$actual = $quiz_progress->get_status();

		/* Assert. */
		self::assertSame( 'passed', $actual );
	}

	public function testGetStatus_WhenUngradeCalled_ReturnsUngradedStatus(): void {
		/* Arrange. */
		$quiz_progress = $this->create_progress();
		$quiz_progress->ungrade();

		/* Act. */
		$actual = $quiz_progress->get_status();

		/* Assert. */
		self::assertSame( 'ungraded', $actual );
	}

	private function create_progress( string $status = null ): Comments_Based_Quiz_Progress {
		return new Comments_Based_Quiz_Progress(
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
