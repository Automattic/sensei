<?php
/**
 * File containing the Clock class.
 *
 * @package sensei
 */

namespace Sensei\Clock;

use DateTimeZone;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Clock
 *
 * @since 4.20.1
 */
class Clock implements Clock_Interface {
	/**
	 * The timezone to use.
	 *
	 * @var DateTimeZone
	 */
	private DateTimeZone $timezone;

	/**
	 * Clock constructor.
	 *
	 * @param DateTimeZone $timezone The timezone to use.
	 */
	public function __construct( DateTimeZone $timezone ) {
		$this->timezone = $timezone;
	}

	/**
	 * Get the current time.
	 *
	 * @param DateTimeZone|null $timezone The timezone to use.
	 * @return \DateTimeImmutable
	 */
	public function now( DateTimeZone $timezone = null ) {
		return new \DateTimeImmutable( 'now', $timezone ?? $this->timezone );
	}
}
