<?php
/**
 * File containing the Tables_Based_Grade_Test class.
 */

namespace SenseiTest\Internal\Quiz_Submission\Grade\Models;

use Sensei\Internal\Quiz_Submission\Grade\Models\Comments_Based_Grade;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Comments_Based_Grade_Test.
 *
 * @covers \Sensei\Internal\Quiz_Submission\Grade\Models\Comments_Based_Grade
 */
class Comments_Based_Grade_Test extends \WP_UnitTestCase {

	public function testGetId_WhenCalled_ThrowsException(): void {
		/* Arrange. */
		$grade = $this->create_grade();

		/* Assert. */
		$this->expectException( \BadMethodCallException::class );

		/* Act. */
		$grade->get_id();
	}

	public function testGetAnswerId_WhenCalled_ThrowsException(): void {
		/* Arrange. */
		$grade = $this->create_grade();

		/* Assert. */
		$this->expectException( \BadMethodCallException::class );

		/* Act. */
		$grade->get_answer_id();
	}

	public function testGetQuestionId_ConstructedWithQuestionId_ReturnsSameQuestionId(): void {
		/* Arrange. */
		$grade = $this->create_grade();

		/* Act. */
		$actual = $grade->get_question_id();

		/* Assert. */
		self::assertSame( 3, $actual );
	}

	public function testGetPoints_ConstructedWithPoints_ReturnsSamePoints(): void {
		/* Arrange. */
		$grade = $this->create_grade();

		/* Act. */
		$actual = $grade->get_points();

		/* Assert. */
		self::assertSame( 10, $actual );
	}

	public function testGetFeedback_ConstructedWithFeedback_ReturnsSameFeedback(): void {
		/* Arrange. */
		$grade = $this->create_grade();

		/* Act. */
		$actual = $grade->get_feedback();

		/* Assert. */
		self::assertSame( 'Good job!', $actual );
	}

	public function testGetFeedback_WhenFeedbackSet_ReturnsSameFeedback(): void {
		/* Arrange. */
		$grade = $this->create_grade();
		$grade->set_feedback( 'Correct!' );

		/* Act. */
		$actual = $grade->get_feedback();

		/* Assert. */
		self::assertSame( 'Correct!', $actual );
	}

	public function testGetCreatedAt_ConstructedWithCreatedAt_ReturnsSameCreatedAt(): void {
		/* Arrange. */
		$grade = $this->create_grade();

		/* Act. */
		$actual = $grade->get_created_at()->format( 'Y-m-d H:i:s' );

		/* Assert. */
		self::assertSame( '2020-01-01 00:00:01', $actual );
	}

	public function testGetUpdatedAt_ConstructedWithUpdatedAt_ReturnsSameUpdatedAt(): void {
		/* Arrange. */
		$grade = $this->create_grade();

		/* Act. */
		$actual = $grade->get_updated_at()->format( 'Y-m-d H:i:s' );

		/* Assert. */
		self::assertSame( '2020-01-01 00:00:02', $actual );
	}

	private function create_grade(): Comments_Based_Grade {
		return new Comments_Based_Grade(
			3,
			10,
			'Good job!',
			new \DateTime( '2020-01-01 00:00:01' ),
			new \DateTime( '2020-01-01 00:00:02' )
		);
	}
}
