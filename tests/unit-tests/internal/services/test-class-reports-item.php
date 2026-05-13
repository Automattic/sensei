<?php
/**
 * File containing tests for the Reports_Item class.
 *
 * @package sensei-tests
 */

namespace SenseiTest\Internal\Services;

use Sensei\Internal\Services\Reports_Item;

/**
 * Class Reports_Item_Test.
 *
 * @covers \Sensei\Internal\Services\Reports_Item
 */
class Reports_Item_Test extends \WP_UnitTestCase {

	/**
	 * Tests that accessing comment_approved returns the status property.
	 *
	 * @covers \Sensei\Internal\Services\Reports_Item::__get
	 */
	public function testGet_WithCommentApproved_ReturnsStatus(): void {
		/* Arrange. */
		$item = new Reports_Item( 1, 2, 'complete', null, '2024-01-01', null, null );
		$this->setExpectedDeprecated( 'Reports_Item::$comment_approved' );

		/* Act. */
		$result = $item->comment_approved;

		/* Assert. */
		$this->assertSame( 'complete', $result, 'comment_approved should map to status.' );
	}

	/**
	 * Tests that accessing comment_post_ID returns the post_id property.
	 *
	 * @covers \Sensei\Internal\Services\Reports_Item::__get
	 */
	public function testGet_WithCommentPostID_ReturnsPostId(): void {
		/* Arrange. */
		$item = new Reports_Item( 42, 2, 'complete', null, null, null, null );
		$this->setExpectedDeprecated( 'Reports_Item::$comment_post_ID' );

		/* Act. */
		$result = $item->comment_post_ID;

		/* Assert. */
		$this->assertSame( 42, $result, 'comment_post_ID should map to post_id.' );
	}

	/**
	 * Tests that accessing comment_date returns the completed_at property.
	 *
	 * @covers \Sensei\Internal\Services\Reports_Item::__get
	 */
	public function testGet_WithCommentDate_ReturnsCompletedAt(): void {
		/* Arrange. */
		$item = new Reports_Item( 1, 2, 'complete', null, '2024-01-15 10:30:00', null, null );
		$this->setExpectedDeprecated( 'Reports_Item::$comment_date' );

		/* Act. */
		$result = $item->comment_date;

		/* Assert. */
		$this->assertSame( '2024-01-15 10:30:00', $result, 'comment_date should map to completed_at.' );
	}

	/**
	 * Tests that accessing an unmapped property returns null.
	 *
	 * @covers \Sensei\Internal\Services\Reports_Item::__get
	 */
	public function testGet_WithUnmappedProperty_ReturnsNull(): void {
		/* Arrange. */
		$item = new Reports_Item( 1, 2, 'complete', null, null, null, null );
		$this->setExpectedIncorrectUsage( 'Reports_Item::$comment_ID' );

		/* Act. */
		$result = $item->comment_ID;

		/* Assert. */
		$this->assertNull( $result, 'Unmapped properties should return null.' );
	}
}
