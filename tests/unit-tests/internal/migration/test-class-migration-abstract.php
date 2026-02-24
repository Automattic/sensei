<?php

namespace SenseiTest\Internal\Migration;

use Sensei\Internal\Migration\Migration_Abstract;

/**
 * @covers \Sensei\Internal\Migration\Migration_Abstract
 */
class Migration_Abstract_Test extends \WP_UnitTestCase {

	public function testIsTimeExceeded_NoBudgetSet_ReturnsFalse(): void {
		/* Arrange. */
		$migration = $this->get_concrete_migration();

		/* Act & Assert. */
		$this->assertFalse( $migration->is_time_exceeded() );
	}

	public function testIsTimeExceeded_WithinBudget_ReturnsFalse(): void {
		/* Arrange. */
		$migration = $this->get_concrete_migration();
		$migration->set_time_budget( 30.0 );

		/* Act & Assert. */
		$this->assertFalse( $migration->is_time_exceeded() );
	}

	public function testIsTimeExceeded_BudgetExhausted_ReturnsTrue(): void {
		/* Arrange. */
		$migration = $this->get_concrete_migration();
		// Set a tiny budget that will be immediately exceeded.
		$migration->set_time_budget( 0.0 );

		/* Act & Assert. */
		$this->assertTrue( $migration->is_time_exceeded() );
	}

	private function get_concrete_migration(): Migration_Abstract {
		return new class() extends Migration_Abstract {
			public function run( bool $dry_run = true ) {
				return 0;
			}
		};
	}
}
