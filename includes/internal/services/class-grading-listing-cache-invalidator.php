<?php
/**
 * File containing the Grading_Listing_Cache_Invalidator class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Grading_Listing_Cache_Invalidator.
 *
 * Bumps the cache version used by the tables-based Grading listing tab-count
 * cache so stale per-status counts disappear after a quiz grade or lesson
 * status write.
 *
 * @internal
 *
 * @since $$next-version$$
 */
class Grading_Listing_Cache_Invalidator {

	public const VERSION_OPTION = 'sensei_grading_listing_count_version';

	/**
	 * Register the invalidation hooks.
	 *
	 * @since $$next-version$$
	 */
	public function init(): void {
		add_action( 'sensei_user_quiz_grade', array( $this, 'bump_version' ) );
		add_action( 'sensei_lesson_status_updated', array( $this, 'bump_version' ) );
	}

	/**
	 * Bump the cache version, which invalidates every cached per-status count.
	 *
	 * @since $$next-version$$
	 */
	public function bump_version(): void {
		update_option( self::VERSION_OPTION, microtime( true ), false );
	}

	/**
	 * Get the current cache version.
	 *
	 * @since $$next-version$$
	 *
	 * @return string Cache version token.
	 */
	public static function get_version(): string {
		$version = get_option( self::VERSION_OPTION );

		if ( ! $version ) {
			$version = (string) microtime( true );
			update_option( self::VERSION_OPTION, $version, false );
		}

		return (string) $version;
	}
}
