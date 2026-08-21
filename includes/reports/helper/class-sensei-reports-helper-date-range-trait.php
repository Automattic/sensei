<?php
/**
 * File containing the Sensei_Reports_Helper_Date_Range_Trait trait.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * This trait contains shared methods related to date range params handling.
 */
trait Sensei_Reports_Helper_Date_Range_Trait {
	/**
	 * Get the start date filter value.
	 *
	 * @return string The start date.
	 */
	protected function get_start_date_filter_value(): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only report filter.
		$start_date = isset( $_GET['start_date'] ) ? sanitize_text_field( wp_unslash( is_array( $_GET['start_date'] ) ? '' : $_GET['start_date'] ) ) : '';

		return DateTime::createFromFormat( 'Y-m-d', $start_date ) ? $start_date : '';
	}

	/**
	 * Get the start date filter value including the time in UTC.
	 *
	 * @return string The start date including the time or empty string if none.
	 */
	protected function get_start_date_and_time(): string {
		$start_date = DateTime::createFromFormat(
			'Y-m-d',
			$this->get_start_date_filter_value(),
			new DateTimeZone( $this->get_timezone() )
		);

		if ( ! $start_date ) {
			return '';
		}

		$start_date->setTime( 0, 0, 0 );
		$start_date->setTimezone( new DateTimeZone( 'UTC' ) );

		return $start_date->format( 'Y-m-d H:i:s' );
	}

	/**
	 * Get the end date filter value.
	 *
	 * @return string The end date or empty string if none.
	 */
	protected function get_end_date_filter_value(): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only report filter.
		$end_date = isset( $_GET['end_date'] ) ? sanitize_text_field( wp_unslash( is_array( $_GET['end_date'] ) ? '' : $_GET['end_date'] ) ) : '';

		return DateTime::createFromFormat( 'Y-m-d', $end_date ) ? $end_date : '';
	}

	/**
	 * Get the end date filter value including the time in UTC.
	 *
	 * @return string The end date including the time or empty string if none.
	 */
	protected function get_end_date_and_time(): string {
		$end_date = DateTime::createFromFormat(
			'Y-m-d',
			$this->get_end_date_filter_value(),
			new DateTimeZone( $this->get_timezone() )
		);

		if ( ! $end_date ) {
			return '';
		}

		$end_date->setTime( 23, 59, 59 );
		$end_date->setTimezone( new DateTimeZone( 'UTC' ) );

		return $end_date->format( 'Y-m-d H:i:s' );
	}

	/**
	 * Get the user's timezone. If not available, returns the site's timezone.
	 *
	 * @return string The timezone string.
	 */
	protected function get_timezone(): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only report filter.
		$user_timezone = isset( $_GET['timezone'] ) ? sanitize_text_field( wp_unslash( is_array( $_GET['timezone'] ) ? '' : $_GET['timezone'] ) ) : '';

		if ( $user_timezone ) {
			try {
				// Validate the requested timezone; accepts IANA identifiers and UTC offsets, rejects anything else.
				new DateTimeZone( $user_timezone );

				return $user_timezone;
			} catch ( Exception $e ) {
				// Invalid timezone requested; fall back to the site's timezone.
				return wp_timezone_string();
			}
		}

		return wp_timezone_string();
	}
}
