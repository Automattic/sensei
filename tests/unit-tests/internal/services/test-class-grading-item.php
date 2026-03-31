<?php

namespace SenseiTest\Internal\Services;

use Sensei\Internal\Services\Grading_Item;
use Sensei\Internal\Services\Utils;

/**
 * Class Grading_Item_Test.
 *
 * @covers \Sensei\Internal\Services\Grading_Item
 */
class Grading_Item_Test extends \WP_UnitTestCase {

	/**
	 * Original gmt_offset value, saved before each test.
	 *
	 * @var mixed
	 */
	private $original_gmt_offset;

	public function setUp(): void {
		parent::setUp();
		$this->original_gmt_offset = get_option( 'gmt_offset' );
	}

	public function tearDown(): void {
		update_option( 'gmt_offset', $this->original_gmt_offset );
		parent::tearDown();
	}

	public function testCompletedStatuses_ContainsExpectedValues(): void {
		/* Assert. */
		$this->assertSame(
			[ 'complete', 'graded', 'passed', 'failed', 'ungraded' ],
			Grading_Item::COMPLETED_STATUSES
		);
	}

	public function testStatusesWithCompletionDate_ContainsExpectedValues(): void {
		/* Assert. */
		$this->assertSame(
			[ 'complete', 'graded', 'passed' ],
			Grading_Item::STATUSES_WITH_COMPLETION_DATE
		);
	}

	public function testGetUtcOffsetString_WithWholeHourPositive_ReturnsCorrectFormat(): void {
		/* Arrange. */
		update_option( 'gmt_offset', '5' );

		/* Act. */
		$result = Utils::get_utc_offset_string();

		/* Assert. */
		$this->assertSame( '+05:00', $result );
	}

	public function testGetUtcOffsetString_WithWholeHourNegative_ReturnsCorrectFormat(): void {
		/* Arrange. */
		update_option( 'gmt_offset', '-8' );

		/* Act. */
		$result = Utils::get_utc_offset_string();

		/* Assert. */
		$this->assertSame( '-08:00', $result );
	}

	public function testGetUtcOffsetString_WithHalfHourOffset_ReturnsCorrectFormat(): void {
		/* Arrange. */
		update_option( 'gmt_offset', '5.5' );

		/* Act. */
		$result = Utils::get_utc_offset_string();

		/* Assert. */
		$this->assertSame( '+05:30', $result );
	}

	public function testGetUtcOffsetString_WithQuarterHourOffset_ReturnsCorrectFormat(): void {
		/* Arrange. */
		update_option( 'gmt_offset', '5.75' );

		/* Act. */
		$result = Utils::get_utc_offset_string();

		/* Assert. */
		$this->assertSame( '+05:45', $result );
	}

	public function testGetUtcOffsetString_WithNegativeFractionalOffset_ReturnsCorrectFormat(): void {
		/* Arrange. */
		update_option( 'gmt_offset', '-9.5' );

		/* Act. */
		$result = Utils::get_utc_offset_string();

		/* Assert. */
		$this->assertSame( '-09:30', $result );
	}

	public function testGetUtcOffsetString_WithZeroOffset_ReturnsCorrectFormat(): void {
		/* Arrange. */
		update_option( 'gmt_offset', '0' );

		/* Act. */
		$result = Utils::get_utc_offset_string();

		/* Assert. */
		$this->assertSame( '+00:00', $result );
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
