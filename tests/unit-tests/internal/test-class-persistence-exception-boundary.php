<?php
/**
 * Tests scoped persistence exception propagation.
 *
 * @package sensei-tests
 */

namespace SenseiTest\Internal;

use PHPUnit\Framework\TestCase;
use Sensei\Internal\Persistence_Exception;
use Sensei\Internal\Persistence_Exception_Boundary;

/**
 * Tests for Persistence_Exception_Boundary.
 */
class Persistence_Exception_Boundary_Test extends TestCase {

	/**
	 * Nested failures must preserve the outer handler until its operation ends.
	 *
	 * @covers Sensei\Internal\Persistence_Exception_Boundary::run
	 * @covers Sensei\Internal\Persistence_Exception_Boundary::is_active
	 */
	public function testRun_NestedOperationThrew_RestoresOuterBoundary(): void {
		$result = Persistence_Exception_Boundary::run(
			static function () {
				try {
					Persistence_Exception_Boundary::run(
						static function () {
							throw new Persistence_Exception( 'course', 'comments', 1, 2 );
						}
					);
				} catch ( Persistence_Exception $exception ) {
					return Persistence_Exception_Boundary::is_active();
				}
			}
		);

		self::assertTrue( $result, 'The outer boundary must remain active after a nested failure.' );
		self::assertFalse( Persistence_Exception_Boundary::is_active(), 'Ordinary callers must regain default handling after the outer operation.' );
	}

	/**
	 * An exception must not leave propagation enabled for later callers.
	 *
	 * @covers Sensei\Internal\Persistence_Exception_Boundary::run
	 * @covers Sensei\Internal\Persistence_Exception_Boundary::is_active
	 */
	public function testRun_OperationThrew_RestoresDefaultHandling(): void {
		$this->expectException( Persistence_Exception::class );

		try {
			Persistence_Exception_Boundary::run(
				static function () {
					throw new Persistence_Exception( 'lesson', 'tables', 1, 2 );
				}
			);
		} finally {
			self::assertFalse( Persistence_Exception_Boundary::is_active() );
		}
	}
}
