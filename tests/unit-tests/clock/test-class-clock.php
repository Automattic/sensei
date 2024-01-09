<?php

namespace SenseiTest\Clock;

use Sensei\Clock\Clock;

/**
 * Test the Clock class.
 *
 * @covers Sensei\Clock\Clock
 */
class Clock_Test extends \WP_UnitTestCase {
	public function testNow_Always_ReturnsImmutableDateTime() {
		// Arrange.
		$clock = new Clock( new \DateTimeZone( 'UTC' ) );

		// Act.
		$now = $clock->now();

		// Assert.
		$this->assertInstanceOf( \DateTimeImmutable::class, $now );
	}

	public function testNow_DateTimeZoneGiven_ReturnsDateTimeInGivenTimeZone() {
		// Arrange.
		$clock = new Clock( new \DateTimeZone( 'America/New_York' ) );

		// Act.
		$now = $clock->now();

		// Assert.
		$this->assertEquals( 'America/New_York', $now->getTimezone()->getName() );
	}
}
