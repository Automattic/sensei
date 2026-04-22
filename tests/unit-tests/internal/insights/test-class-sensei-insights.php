<?php

namespace SenseiTest\Internal\Insights;

use Sensei\Internal\Insights\Sensei_Insights;

/**
 * Class Sensei_Insights_Test
 *
 * @covers \Sensei\Internal\Insights\Sensei_Insights
 */
class Sensei_Insights_Test extends \WP_UnitTestCase {

	public function testIsEnabled_WhenConstructedWithTrue_ReturnsTrue(): void {
		$insights = new Sensei_Insights( true );

		self::assertTrue( $insights->is_enabled() );
	}

	public function testIsEnabled_WhenConstructedWithFalse_ReturnsFalse(): void {
		$insights = new Sensei_Insights( false );

		self::assertFalse( $insights->is_enabled() );
	}

	public function testInit_WhenDisabled_DoesNotRegisterHooks(): void {
		/* Arrange. */
		$insights = new Sensei_Insights( false );
		$before   = $this->snapshot_hook_counts();

		/* Act. */
		$insights->init();

		/* Assert. */
		self::assertSame( $before, $this->snapshot_hook_counts() );
	}

	/**
	 * Capture a snapshot of the WordPress hook registry so we can assert that
	 * init() did not attach anything.
	 *
	 * @return array
	 */
	private function snapshot_hook_counts(): array {
		global $wp_filter;

		$snapshot = [];
		foreach ( (array) $wp_filter as $hook => $callbacks ) {
			$snapshot[ $hook ] = count( $callbacks->callbacks );
		}

		return $snapshot;
	}
}
