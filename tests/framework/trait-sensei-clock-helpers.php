<?php
/**
 * File with trait Sensei_Clock_Helpers.
 *
 * @package sensei-tests
 */

// phpcs:disable WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid -- Using PHPUnit conventions.

use Sensei\Clock\Clock_Interface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Helpers related to the clock.
 *
 * @since 4.20.1
 */
trait Sensei_Clock_Helpers {

	/**
	* Set the clock to a specific time.
	*
	* @param int|int[] $timestamp The timestamp or an array of timestamp clock will return on consecutive calls.
	* @param string    $timezone  The timezone to use. UTC by default.
	*/
	private function set_clock_to( $timestamp, $timezone = 'UTC' ) {
		$return_values = array();

		$timestamp = is_array( $timestamp ) ? $timestamp : array( $timestamp );
		$timestamp = array_map( 'intval', $timestamp );

		foreach ( $timestamp as $ts ) {
			$return_values[] = new \DateTimeImmutable( "@$ts", new \DateTimeZone( $timezone ) );
		}

		$clock = $this->createMock( Clock_Interface::class );
		$clock->method( 'now' )
			->willReturnOnConsecutiveCalls(
				...$return_values
			);

		Sensei()->clock = $clock;
	}

	/**
	 * Reset the clock to the default.
	 */
	private function reset_clock() {
		Sensei()->clock = new Sensei_Clock_Stub();
	}
}
