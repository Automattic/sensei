<?php

namespace SenseiTest\Internal;

use Sensei\Internal\Cache_Prefix;

/**
 * Tests for the Cache_Prefix trait.
 *
 * @covers \Sensei\Internal\Cache_Prefix
 */
class Cache_Prefix_Test extends \WP_UnitTestCase {

	/**
	 * Helper class that exposes the trait's private methods for testing.
	 *
	 * @var object
	 */
	private $helper;

	public function setUp(): void {
		parent::setUp();
		wp_cache_flush();

		$this->helper = new class() {
			use Cache_Prefix {
				get_cache_prefix as public;
				invalidate_cache_group as public;
				get_prefixed_key as public;
			}
		};
	}

	public function tearDown(): void {
		wp_cache_flush();
		parent::tearDown();
	}

	public function testGetCachePrefix_CalledTwice_ReturnsSamePrefix(): void {
		/* Act. */
		$prefix1 = $this->helper::get_cache_prefix( 'test_group' );
		$prefix2 = $this->helper::get_cache_prefix( 'test_group' );

		/* Assert. */
		self::assertSame( $prefix1, $prefix2 );
	}

	public function testGetCachePrefix_DifferentGroups_ReturnsDifferentPrefixes(): void {
		/* Act. */
		$prefix1 = $this->helper::get_cache_prefix( 'group_a' );
		$prefix2 = $this->helper::get_cache_prefix( 'group_b' );

		/* Assert. */
		self::assertNotSame( $prefix1, $prefix2 );
	}

	public function testGetCachePrefix_Always_StartsWithSenseiCache(): void {
		/* Act. */
		$prefix = $this->helper::get_cache_prefix( 'test_group' );

		/* Assert. */
		self::assertStringStartsWith( 'sensei_cache_', $prefix );
		self::assertStringEndsWith( '_', $prefix );
	}

	public function testInvalidateCacheGroup_Called_ChangesPrefix(): void {
		/* Arrange. */
		$prefix_before = $this->helper::get_cache_prefix( 'test_group' );

		/* Act. */
		$this->helper::invalidate_cache_group( 'test_group' );
		$prefix_after = $this->helper::get_cache_prefix( 'test_group' );

		/* Assert. */
		self::assertNotSame( $prefix_before, $prefix_after );
	}

	public function testInvalidateCacheGroup_Called_ReturnsTrue(): void {
		/* Act. */
		$result = $this->helper::invalidate_cache_group( 'test_group' );

		/* Assert. */
		self::assertTrue( $result );
	}

	public function testInvalidateCacheGroup_Called_DoesNotAffectOtherGroups(): void {
		/* Arrange. */
		$prefix_other_before = $this->helper::get_cache_prefix( 'other_group' );

		/* Act. */
		$this->helper::invalidate_cache_group( 'test_group' );
		$prefix_other_after = $this->helper::get_cache_prefix( 'other_group' );

		/* Assert. */
		self::assertSame( $prefix_other_before, $prefix_other_after );
	}

	public function testGetPrefixedKey_KeyAndGroupGiven_ReturnsPrefixedKey(): void {
		/* Act. */
		$prefixed_key = $this->helper::get_prefixed_key( 'my_key', 'test_group' );

		/* Assert. */
		self::assertStringStartsWith( 'sensei_cache_', $prefixed_key );
		self::assertStringEndsWith( '_my_key', $prefixed_key );
	}

	public function testGetPrefixedKey_CalledTwice_ReturnsSameKey(): void {
		/* Act. */
		$key1 = $this->helper::get_prefixed_key( 'my_key', 'test_group' );
		$key2 = $this->helper::get_prefixed_key( 'my_key', 'test_group' );

		/* Assert. */
		self::assertSame( $key1, $key2 );
	}

	public function testGetPrefixedKey_AfterInvalidation_ReturnsDifferentKey(): void {
		/* Arrange. */
		$key_before = $this->helper::get_prefixed_key( 'my_key', 'test_group' );

		/* Act. */
		$this->helper::invalidate_cache_group( 'test_group' );
		$key_after = $this->helper::get_prefixed_key( 'my_key', 'test_group' );

		/* Assert. */
		self::assertNotSame( $key_before, $key_after );
	}

	public function testInvalidation_MakesCachedValueUnreachable(): void {
		/* Arrange. */
		$group = 'test_group';
		$key   = $this->helper::get_prefixed_key( 'data', $group );
		wp_cache_set( $key, 'cached_value', $group );

		/* Act. */
		$this->helper::invalidate_cache_group( $group );
		$new_key = $this->helper::get_prefixed_key( 'data', $group );
		$result  = wp_cache_get( $new_key, $group );

		/* Assert. */
		self::assertFalse( $result );
	}
}
