<?php
/**
 * File containing the Clock_Interface.
 *
 * @package sensei
 */

namespace Sensei\Clock;

use DateTimeZone;

/**
 * Interface Clock_Interface
 *
 * @since $$next-version$$
 */
interface Clock_Interface {
	/**
	 * Get the current time.
	 *
	 * @param DateTimeZone|null $timezone The timezone to use. Uses the default timezone if not provided.
	 * @return \DateTimeImmutable
	 */
	public function now( DateTimeZone $timezone = null );
}
