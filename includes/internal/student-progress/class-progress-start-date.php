<?php
/**
 * File containing the Progress_Start_Date class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Student_Progress;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Identifies recorded start dates across progress stores.
 *
 * @internal
 * @since 4.28.0
 */
final class Progress_Start_Date {
	/**
	 * Whether an unusable legacy date has been reported during this request.
	 *
	 * @var bool
	 */
	private static $conversion_issue_reported = false;

	/**
	 * Check whether a stored value represents a recorded start date.
	 *
	 * @since 4.28.0
	 * @param string|null $value Stored start date.
	 * @return bool
	 * @psalm-assert-if-true non-empty-string $value
	 */
	public static function is_recorded( ?string $value ): bool {
		return ! empty( $value )
			&& '0000-00-00' !== $value
			&& '0000-00-00 00:00:00' !== $value;
	}

	/**
	 * Interpret a legacy local start using its comment's recorded timezone offset.
	 *
	 * A matching offset does not identify the original timezone. Assume the
	 * configured timezone to preserve ordinary daylight saving transitions;
	 * otherwise use the recorded fixed offset.
	 *
	 * @since $$next-version$$
	 *
	 * @param string|null   $start Stored local start.
	 * @param string        $comment_date Local comment timestamp.
	 * @param string        $comment_date_gmt UTC comment timestamp.
	 * @param \DateTimeZone $site_timezone Configured site timezone.
	 * @return \DateTime|null Absolute start instant, or null when unknown.
	 */
	public static function from_comment( ?string $start, string $comment_date, string $comment_date_gmt, \DateTimeZone $site_timezone ): ?\DateTime {
		if ( ! self::is_recorded( $start ) ) {
			return null;
		}

		$utc   = new \DateTimeZone( 'UTC' );
		$local = self::parse_clock( $comment_date, $utc );
		$gmt   = self::parse_clock( $comment_date_gmt, $utc );
		if ( ! $local || ! $gmt ) {
			self::report_conversion_issue();
			return null;
		}

		// UTC here only makes subtraction compare clock values without DST adjustments.
		$recorded_offset = $local->getTimestamp() - $gmt->getTimestamp();
		if ( abs( $recorded_offset ) > 14 * 3600 ) {
			self::report_conversion_issue();
			return null;
		}

		$uses_site_timezone = $recorded_offset === $site_timezone->getOffset( $gmt );
		$started_at         = self::parse_clock( $start, $uses_site_timezone ? $site_timezone : $utc );
		if ( ! $started_at ) {
			self::report_conversion_issue();
			return null;
		}

		if ( ! $uses_site_timezone ) {
			$started_at->setTimestamp( $started_at->getTimestamp() - $recorded_offset );
		}

		return $started_at->setTimezone( $site_timezone );
	}

	/**
	 * Parse a stored clock without accepting normalized invalid dates.
	 *
	 * @param string        $value Stored timestamp.
	 * @param \DateTimeZone $timezone Timezone used for interpretation.
	 * @return \DateTime|null
	 */
	private static function parse_clock( string $value, \DateTimeZone $timezone ): ?\DateTime {
		$date = \DateTime::createFromFormat( '!Y-m-d H:i:s', $value, $timezone );

		return $date && $date->format( 'Y-m-d H:i:s' ) === $value ? $date : null;
	}

	/**
	 * Report unusable dates once per request without changing retained metadata.
	 */
	private static function report_conversion_issue(): void {
		if ( self::$conversion_issue_reported ) {
			return;
		}

		self::$conversion_issue_reported = true;
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Bounded diagnostic for legacy data that cannot be interpreted.
		error_log( 'Sensei: Legacy progress start date could not be interpreted from its comment timestamps. Diagnostic code: invalid_progress_start_timezone.' );
	}
}
