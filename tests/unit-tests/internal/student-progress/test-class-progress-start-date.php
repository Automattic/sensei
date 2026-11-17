<?php // phpcs:disable WordPress.Files.FileName.InvalidClassFileName -- Follow the test suite's file naming convention.
/**
 * Tests for the Progress_Start_Date class.
 *
 * @package sensei-tests
 */

namespace SenseiTest\Internal\Student_Progress;

use PHPUnit\Framework\TestCase;
use Sensei\Internal\Student_Progress\Progress_Start_Date;

/**
 * Tests whether a stored start date is recorded.
 */
class Progress_Start_Date_Test extends TestCase {
	/**
	 * Identify whether the stored value represents a recorded start.
	 *
	 * @covers \Sensei\Internal\Student_Progress\Progress_Start_Date::is_recorded
	 * @dataProvider stored_start_dates
	 *
	 * @param string|null $value Stored start date.
	 * @param bool        $expected Whether the value represents a recorded start.
	 */
	public function testIsRecorded_StoredStartDateGiven_IdentifiesRecordedStart( ?string $value, bool $expected ): void {
		self::assertSame( $expected, Progress_Start_Date::is_recorded( $value ) );
	}

	/**
	 * Start dates as read from comment metadata and the progress table.
	 *
	 * @return array
	 */
	public static function stored_start_dates(): array {
		return array(
			'recorded date'            => array( '2024-05-01 15:45:00', true ),
			'missing comment metadata' => array( '', false ),
			'null table value'         => array( null, false ),
			'legacy table zero date'   => array( '0000-00-00 00:00:00', false ),
		);
	}
}
