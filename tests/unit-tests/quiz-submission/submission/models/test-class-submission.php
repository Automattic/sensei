<?php
/**
 * File containing the Grade_Test class.
 */

namespace SenseiTest\Quiz_Submission\Submission\Models;

use Sensei\Quiz_Submission\Submission\Models\Submission;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Submission_Test.
 *
 * @covers \Sensei\Quiz_Submission\Submission\Models\Submission
 */
class Submission_Test extends \WP_UnitTestCase {

	public function testGetId_ConstructedWithId_ReturnsSameId(): void {
		/* Arrange. */
		$submission = $this->createSubmission();

		/* Act. */
		$actual = $submission->get_id();

		/* Assert. */
		self::assertSame( 1, $actual );
	}

	public function testGetQuizId_ConstructedWithQuizId_ReturnsSameQuizId(): void {
		/* Arrange. */
		$submission = $this->createSubmission();

		/* Act. */
		$actual = $submission->get_quiz_id();

		/* Assert. */
		self::assertSame( 2, $actual );
	}

	public function testGetUserId_ConstructedWithUserId_ReturnsSameUserId(): void {
		/* Arrange. */
		$submission = $this->createSubmission();

		/* Act. */
		$actual = $submission->get_user_id();

		/* Assert. */
		self::assertSame( 3, $actual );
	}

	public function testGetFinalGrade_ConstructedWithFinalGrade_ReturnsSameFinalGrade(): void {
		/* Arrange. */
		$submission = $this->createSubmission();

		/* Act. */
		$actual = $submission->get_final_grade();

		/* Assert. */
		self::assertSame( 12.34, $actual );
	}

	public function testGetFinalGrade_WhenFinalGradeSet_ReturnsSameFinalGrade(): void {
		/* Arrange. */
		$submission = $this->createSubmission();
		$submission->set_final_grade( 34.21 );

		/* Act. */
		$actual = $submission->get_final_grade();

		/* Assert. */
		self::assertSame( 34.21, $actual );
	}

	public function testGetCreatedAt_ConstructedWithCreatedAt_ReturnsSameCreatedAt(): void {
		/* Arrange. */
		$submission = $this->createSubmission();

		/* Act. */
		$actual = $submission->get_created_at()->format( 'Y-m-d H:i:s' );

		/* Assert. */
		self::assertSame( '2020-01-01 00:00:01', $actual );
	}

	public function testGetUpdatedAt_ConstructedWithUpdatedAt_ReturnsSameUpdatedAt(): void {
		/* Arrange. */
		$submission = $this->createSubmission();

		/* Act. */
		$actual = $submission->get_updated_at()->format( 'Y-m-d H:i:s' );

		/* Assert. */
		self::assertSame( '2020-01-01 00:00:02', $actual );
	}

	private function createSubmission(): Submission {
		return new Submission(
			1,
			2,
			3,
			12.34,
			new \DateTime( '2020-01-01 00:00:01' ),
			new \DateTime( '2020-01-01 00:00:02' )
		);
	}
}
