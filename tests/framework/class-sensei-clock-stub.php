<?php
/**
* Stub for Clock_Interface.
*
* @package sensei-tests
*/

use Sensei\Clock\Clock_Interface;

/**
 * Class Sensei_Clock_Stub.
 */
class Sensei_Clock_Stub implements Clock_Interface {
	/**
	 * Get the current time. This is a stub that always returns the beginning of the Unix epoch.
	 *
	 * @return \DateTimeImmutable
	 */
	public function now( \DateTimeZone $timezone = null ) {
		return ( new \DateTimeImmutable( '@0' ) )->setTimezone( $timezone ?? new \DateTimeZone( 'UTC' ) );
	}
}
