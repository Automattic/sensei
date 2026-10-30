<?php
/**
 * Scoped propagation of progress persistence failures.
 *
 * @package sensei
 */

namespace Sensei\Internal;

/**
 * Lets guarded operations receive failures without changing public hook contracts.
 *
 * @internal
 * @since $$next-version$$
 */
final class Persistence_Exception_Boundary {

	/**
	 * Whether the enclosing operation handles persistence exceptions.
	 *
	 * @var bool
	 */
	private static $active = false;

	/**
	 * Run an operation whose caller catches persistence exceptions.
	 *
	 * @since $$next-version$$
	 *
	 * @param callable $operation Operation to run.
	 * @return mixed The operation's result.
	 */
	public static function run( callable $operation ) {
		$previous     = self::$active;
		self::$active = true;

		try {
			return $operation();
		} finally {
			self::$active = $previous;
		}
	}

	/**
	 * Check whether a persistence failure can reach an enclosing handler.
	 *
	 * @since $$next-version$$
	 *
	 * @return bool Whether to propagate the failure.
	 */
	public static function is_active(): bool {
		return self::$active;
	}
}
