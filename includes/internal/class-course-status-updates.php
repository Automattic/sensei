<?php
/**
 * Course-status results for guarded progress actions.
 *
 * @package sensei
 */

namespace Sensei\Internal;

/**
 * Collects failures that WordPress action callback return values cannot convey.
 *
 * @internal
 * @since $$next-version$$
 */
final class Course_Status_Updates {
	/**
	 * Failure in the current dispatch, or null outside a guarded action.
	 *
	 * WordPress ignores action callback return values. This flag reports a failed
	 * course update without changing hook timing or interrupting other callbacks.
	 *
	 * @var bool|null
	 */
	private static $failed;

	/**
	 * Run an action and check whether its course-status update succeeded.
	 *
	 * @since $$next-version$$
	 *
	 * @param callable $operation Action to run.
	 * @return bool Whether the course-status callbacks completed successfully.
	 */
	public static function run( callable $operation ): bool {
		$previous     = self::$failed;
		self::$failed = false;

		try {
			$operation();
			return ! self::$failed;
		} finally {
			self::$failed = $previous;
		}
	}

	/**
	 * Record a failed course-status update without interrupting hook callbacks.
	 *
	 * @since $$next-version$$
	 *
	 * @return void
	 */
	public static function mark_failed(): void {
		if ( null !== self::$failed ) {
			self::$failed = true;
		}
	}
}
