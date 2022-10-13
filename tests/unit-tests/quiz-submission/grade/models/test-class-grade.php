<?php
/**
 * File containing the Grade_Test class.
 */

namespace SenseiTest\Quiz_Submission\Grade\Models;

use Sensei\Quiz_Submission\Grade\Models\Grade;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Grade_Test.
 *
 * @covers \Sensei\Quiz_Submission\Grade\Models\Grade
 */
class Grade_Test extends \WP_UnitTestCase {

	public function testGetId_ConstructedWithId_ReturnsSameId(): void {
		/* Arrange. */
		$grade = $this->createGrade();

		/* Act. */
		$actual = $grade->get_id();

		/* Assert. */
		self::assertSame( 1, $actual );
	}

	public function testGetAnswerId_ConstructedWithAnswerId_ReturnsSameAnswerId(): void {
		/* Arrange. */
		$grade = $this->createGrade();

		/* Act. */
		$actual = $grade->get_answer_id();

		/* Assert. */
		self::assertSame( 2, $actual );
	}

	public function testGetQuestionId_ConstructedWithQuestionId_ReturnsSameQuestionId(): void {
		/* Arrange. */
		$grade = $this->createGrade();

		/* Act. */
		$actual = $grade->get_question_id();

		/* Assert. */
		self::assertSame( 3, $actual );
	}

	public function testGetPoints_ConstructedWithPoints_ReturnsSamePoints(): void {
		/* Arrange. */
		$grade = $this->createGrade();

		/* Act. */
		$actual = $grade->get_points();

		/* Assert. */
		self::assertSame( 10, $actual );
	}

	public function testGetFeedback_ConstructedWithFeedback_ReturnsSameFeedback(): void {
		/* Arrange. */
		$grade = $this->createGrade();

		/* Act. */
		$actual = $grade->get_feedback();

		/* Assert. */
		self::assertSame( 'Good job!', $actual );
	}

	public function testGetFeedback_WhenFeedbackSet_ReturnsSameFeedback(): void {
		/* Arrange. */
		$grade = $this->createGrade();
		$grade->set_feedback( 'Correct!' );

		/* Act. */
		$actual = $grade->get_feedback();

		/* Assert. */
		self::assertSame( 'Correct!', $actual );
	}

	public function testGetCreatedAt_ConstructedWithCreatedAt_ReturnsSameCreatedAt(): void {
		/* Arrange. */
		$grade = $this->createGrade();

		/* Act. */
		$actual = $grade->get_created_at()->format( 'Y-m-d H:i:s' );

		/* Assert. */
		self::assertSame( '2020-01-01 00:00:01', $actual );
	}

	public function testGetUpdatedAt_ConstructedWithUpdatedAt_ReturnsSameUpdatedAt(): void {
		/* Arrange. */
		$grade = $this->createGrade();

		/* Act. */
		$actual = $grade->get_updated_at()->format( 'Y-m-d H:i:s' );

		/* Assert. */
		self::assertSame( '2020-01-01 00:00:02', $actual );
	}

	private function createGrade(): Grade {
		return new Grade(
			1,
			2,
			3,
			10,
			'Good job!',
			new \DateTime( '2020-01-01 00:00:01' ),
			new \DateTime( '2020-01-01 00:00:02' )
		);
	}
}
