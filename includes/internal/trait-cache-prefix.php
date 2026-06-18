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
 * @since 4.26.0
 *
 * @see https://github.com/memcached/memcached/wiki/ProgrammingTricks#namespacing
 */
trait Cache_Prefix {

	/**
	 * Sentinel value stored in cache to represent a confirmed "not found" result,
	 * distinguishing it from a cache miss (which returns false).
	 *
	 * @since 4.26.0
	 *
	 * @var string
	 */
	private static string $cache_not_found = '__not_found__';

	/**
	 * Get the cache key that stores the prefix for a group.
	 *
	 * @since 4.26.0
	 *
	 * @param string $group Group of cache.
	 * @return string Prefix meta-key.
	 */
	private static function get_prefix_key( string $group ): string {
		return $group . '_cache_prefix';
	}

	/**
	 * Get prefix for use with wp_cache_set. Allows all cache in a group to be invalidated at once.
	 *
	 * @since 4.26.0
	 *
	 * @param string $group Group of cache to get.
	 * @return string Prefix.
	 */
	private static function get_cache_prefix( string $group ): string {
		$prefix_key = self::get_prefix_key( $group );
		$prefix     = wp_cache_get( $prefix_key, $group );

		if ( false === $prefix ) {
			$prefix = str_replace( ' ', '', microtime() );
			wp_cache_add( $prefix_key, $prefix, $group );
			// Re-read in case another process won the race.
			$re_read = wp_cache_get( $prefix_key, $group );
			if ( false !== $re_read ) {
				$prefix = $re_read;
			}
		}

		return 'sensei_cache_' . $prefix . '_';
	}

	/**
	 * Invalidate cache group by rotating the prefix.
	 *
	 * @since 4.26.0
	 *
	 * @param string $group Group of cache to clear.
	 * @return bool True on success, false on failure.
	 */
	private static function invalidate_cache_group( string $group ): bool {
		return wp_cache_set( self::get_prefix_key( $group ), str_replace( ' ', '', microtime() ), $group );
	}

	/**
	 * Get a prefixed cache key.
	 *
	 * @since 4.26.0
	 *
	 * @param string $key   Key to prefix.
	 * @param string $group Group of cache to get.
	 * @return string Prefixed key.
	 */
	private static function get_prefixed_key( string $key, string $group ): string {
		return self::get_cache_prefix( $group ) . $key;
	}
}
