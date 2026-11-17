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
 * @since $$next-version$$
 */
final class Progress_Start_Date {
	/**
	 * Check whether a stored value represents a recorded start date.
	 *
	 * @since $$next-version$$
	 * @param string|null $value Stored start date.
	 * @return bool
	 * @psalm-assert-if-true non-empty-string $value
	 */
	public static function is_recorded( ?string $value ): bool {
		return ! empty( $value )
			&& '0000-00-00' !== $value
			&& '0000-00-00 00:00:00' !== $value;
	}
}
