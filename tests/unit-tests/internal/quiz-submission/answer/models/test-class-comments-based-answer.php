<?php
/**
 * File containing the Comments_Based_Answer_Test class.
 */

namespace SenseiTest\Internal\Quiz_Submission\Answer\Models;

use Sensei\Internal\Quiz_Submission\Answer\Models\Comments_Based_Answer;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Comments_Based_Answer_Test.
 *
 * @covers \Sensei\Internal\Quiz_Submission\Answer\Models\Comments_Based_Answer
 */
class Comments_Based_Answer_Test extends \WP_UnitTestCase {

	public function testGetId_WhenCalled_ThrowsException(): void {
		/* Arrange. */
		$answer = $this->create_answer();

		/* Assert. */
		self::expectException( \BadMethodCallException::class );

		/* Act. */
		$answer->get_id();
	}

	public function testGetSubmissionId_ConstructedWithSubmissionId_ReturnsSameSubmissionId(): void {
		/* Arrange. */
		$answer = $this->create_answer();

		/* Act. */
		$actual = $answer->get_submission_id();

		/* Assert. */
		self::assertSame( 2, $actual );
	}

	public function testGetQuestionId_ConstructedWithQuestionId_ReturnsSameQuestionId(): void {
		/* Arrange. */
		$answer = $this->create_answer();

		/* Act. */
		$actual = $answer->get_question_id();

		/* Assert. */
		self::assertSame( 3, $actual );
	}

	public function testGetValue_ConstructedWithValue_ReturnsSameValue(): void {
		/* Arrange. */
		$answer = $this->create_answer();

		/* Act. */
		$actual = $answer->get_value();

		/* Assert. */
		self::assertSame( 'yes', $actual );
	}

	public function testGetCreatedAt_ConstructedWithCreatedAt_ReturnsSameCreatedAt(): void {
		/* Arrange. */
		$answer = $this->create_answer();

		/* Act. */
		$actual = $answer->get_created_at()->format( 'Y-m-d H:i:s' );

		/* Assert. */
		self::assertSame( '2020-01-01 00:00:01', $actual );
	}

	public function testGetUpdatedAt_ConstructedWithUpdatedAt_ReturnsSameUpdatedAt(): void {
		/* Arrange. */
		$answer = $this->create_answer();

		/* Act. */
		$actual = $answer->get_updated_at()->format( 'Y-m-d H:i:s' );

		/* Assert. */
		self::assertSame( '2020-01-01 00:00:02', $actual );
	}

	private function create_answer(): Comments_Based_Answer {
		return new Comments_Based_Answer(
			2,
			3,
			'yes',
			new \DateTime( '2020-01-01 00:00:01' ),
			new \DateTime( '2020-01-01 00:00:02' )
		);
	}
}
