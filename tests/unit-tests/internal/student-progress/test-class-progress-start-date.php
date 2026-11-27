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
 * Tests recorded start dates and comment timezone conversion.
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
	 * Interpret a legacy start with the recorded offset or matching zone rules.
	 *
	 * @covers \Sensei\Internal\Student_Progress\Progress_Start_Date::from_comment
	 * @dataProvider comment_start_dates
	 */
	public function testFromComment_RecordedDatesGiven_ReturnsAbsoluteStart( $start, $local, $gmt, $zone, $expected ): void {
		$actual = Progress_Start_Date::from_comment( $start, $local, $gmt, new \DateTimeZone( $zone ) );

		self::assertSame( $expected, $actual ? $actual->setTimezone( new \DateTimeZone( 'UTC' ) )->format( 'Y-m-d H:i:s' ) : null );
	}

	/**
	 * Each case protects a distinct timezone interpretation or unknown-date rule.
	 *
	 * @return array
	 */
	public static function comment_start_dates(): array {
		return array(
			'changed timezone'           => array( '2022-05-17 01:00:00', '2022-05-17 01:36:12', '2022-05-16 19:36:12', 'America/Chicago', '2022-05-16 19:00:00' ),
			'normal DST crossing'        => array( '2022-01-17 01:00:00', '2022-05-17 01:36:12', '2022-05-17 06:36:12', 'America/Chicago', '2022-01-17 07:00:00' ),
			'fractional recorded offset' => array( '2022-05-17 01:00:00', '2022-05-17 01:36:12', '2022-05-16 19:51:12', 'UTC', '2022-05-16 19:15:00' ),
			'unusable timestamp pair'    => array( '2022-05-17 01:00:00', '2022-05-17 01:36:12', '0000-00-00 00:00:00', 'UTC', null ),
			'invalid calendar date'      => array( '2022-05-17 01:00:00', '2022-02-30 01:36:12', '2022-02-28 01:36:12', 'UTC', null ),
			'unrecorded start'           => array( '0000-00-00 00:00:00', '2022-05-17 01:36:12', '2022-05-17 01:36:12', 'UTC', null ),
		);
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
