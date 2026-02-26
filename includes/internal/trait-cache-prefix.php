<?php
/**
 * File containing the Cache_Prefix trait.
 *
 * @package sensei
 */

namespace Sensei\Internal;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Trait Cache_Prefix
 *
 * Implements a namespacing algorithm for wp_cache to simulate group invalidation.
 * Mirrors WooCommerce's CacheNameSpaceTrait approach: a rotating prefix per cache group
 * makes all old keys unreachable when the prefix changes.
 *
 * @internal
 *
 * @since 4.24.0
 *
 * @see https://github.com/memcached/memcached/wiki/ProgrammingTricks#namespacing
 */
trait Cache_Prefix {

	/**
	 * Get prefix for use with wp_cache_set. Allows all cache in a group to be invalidated at once.
	 *
	 * @param string $group Group of cache to get.
	 * @return string Prefix.
	 */
	private static function get_cache_prefix( string $group ): string {
		$prefix = wp_cache_get( 'sensei_' . $group . '_cache_prefix', $group );

		if ( false === $prefix ) {
			$prefix = microtime();
			wp_cache_set( 'sensei_' . $group . '_cache_prefix', $prefix, $group );
		}

		return 'sensei_cache_' . $prefix . '_';
	}

	/**
	 * Invalidate cache group by rotating the prefix.
	 *
	 * @param string $group Group of cache to clear.
	 * @return bool True on success, false on failure.
	 */
	private static function invalidate_cache_group( string $group ): bool {
		return wp_cache_set( 'sensei_' . $group . '_cache_prefix', microtime(), $group );
	}

	/**
	 * Get a prefixed cache key.
	 *
	 * @param string $key   Key to prefix.
	 * @param string $group Group of cache to get.
	 * @return string Prefixed key.
	 */
	private static function get_prefixed_key( string $key, string $group ): string {
		return self::get_cache_prefix( $group ) . $key;
	}
}
