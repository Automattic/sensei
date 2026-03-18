<?php

namespace SenseiTest\Internal\Services;

use Sensei\Internal\Services\Grading_Item;

/**
 * Class Grading_Item_Test.
 *
 * @covers \Sensei\Internal\Services\Grading_Item
 */
class Grading_Item_Test extends \WP_UnitTestCase {

	public function testCompletedStatuses_ContainsExpectedValues(): void {
		/* Assert. */
		$this->assertSame(
			[ 'complete', 'graded', 'passed', 'failed', 'ungraded' ],
			Grading_Item::COMPLETED_STATUSES
		);
	}

	public function testGet_WithCommentApproved_ReturnsStatus(): void {
		/* Arrange. */
		$item = new Grading_Item( 'passed', 1, 100, '2026-01-01', 85 );
		$this->setExpectedDeprecated( 'Grading_Item::$comment_approved' );

		/* Act. */
		$result = $item->comment_approved;

		/* Assert. */
		$this->assertSame( 'passed', $result );
	}

	public function testGet_WithCommentPostID_ReturnsLessonId(): void {
		/* Arrange. */
		$item = new Grading_Item( 'passed', 1, 100, '2026-01-01', 85 );
		$this->setExpectedDeprecated( 'Grading_Item::$comment_post_ID' );

		/* Act. */
		$result = $item->comment_post_ID;

		/* Assert. */
		$this->assertSame( 100, $result );
	}

	public function testGet_WithCommentDate_ReturnsUpdatedAt(): void {
		/* Arrange. */
		$item = new Grading_Item( 'passed', 1, 100, '2026-01-01', 85 );
		$this->setExpectedDeprecated( 'Grading_Item::$comment_date' );

		/* Act. */
		$result = $item->comment_date;

		/* Assert. */
		$this->assertSame( '2026-01-01', $result );
	}

	public function testGet_WithUnmappedProperty_ReturnsNull(): void {
		/* Arrange. */
		$item = new Grading_Item( 'passed', 1, 100, '2026-01-01', 85 );
		$this->setExpectedIncorrectUsage( 'Grading_Item::$nonexistent' );

		/* Act. */
		$result = $item->nonexistent;

		/* Assert. */
		$this->assertNull( $result );
	}
}
