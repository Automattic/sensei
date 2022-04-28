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
		$default = gmdate( 'Y-m-d', strtotime( '-30 days' ) );

		// phpcs:ignore WordPress.Security -- The date is sanitized by DateTime.
		$start_date = $_GET['start_date'] ?? $default;

		return DateTime::createFromFormat( 'Y-m-d', $start_date ) ? $start_date : '';
	}

	/**
	 * Get the start date filter value including the time.
	 *
	 * @return string The start date including the time or empty string if none.
	 */
	protected function get_start_date_and_time(): string {
		$start_date = DateTime::createFromFormat( 'Y-m-d', $this->get_start_date_filter_value() );

		if ( ! $start_date ) {
			return '';
		}

		$start_date->setTime( 0, 0, 0 );

		return $start_date->format( 'Y-m-d H:i:s' );
	}

	/**
	 * Get the end date filter value.
	 *
	 * @return string The end date or empty string if none.
	 */
	protected function get_end_date_filter_value(): string {
		// phpcs:ignore WordPress.Security -- The date is sanitized by DateTime.
		$end_date = $_GET['end_date'] ?? '';

		return DateTime::createFromFormat( 'Y-m-d', $end_date ) ? $end_date : '';
	}

	/**
	 * Get the end date filter value including the time.
	 *
	 * @return string The end date including the time or empty string if none.
	 */
	protected function get_end_date_and_time(): string {
		$end_date = DateTime::createFromFormat( 'Y-m-d', $this->get_end_date_filter_value() );

		if ( ! $end_date ) {
			return '';
		}

		$end_date->setTime( 23, 59, 59 );

		return $end_date->format( 'Y-m-d H:i:s' );
	}
}
