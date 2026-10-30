<?php
/**
 * Tests course-status results from guarded hooks.
 *
 * @package sensei-tests
 */

namespace SenseiTest\Internal;

use Sensei\Internal\Course_Status_Updates;
use RuntimeException;

/**
 * Tests for Course_Status_Updates.
 */
class Course_Status_Updates_Test extends \PHPUnit\Framework\TestCase {
	/**
	 * A failure result must not interrupt the remaining action callbacks.
	 *
	 * @covers Sensei\Internal\Course_Status_Updates::run
	 * @covers Sensei\Internal\Course_Status_Updates::mark_failed
	 */
	public function testRun_CourseUpdateFailed_ReturnsFalseAfterCallbacksFinish(): void {
		$continued = false;

		$result = Course_Status_Updates::run(
			static function () use ( &$continued ) {
				Course_Status_Updates::mark_failed();
				$continued = true;
			}
		);

		self::assertFalse( $result, 'The enclosing action must receive the failed course-status result.' );
		self::assertTrue( $continued, 'The hook callbacks must still complete normally.' );
	}

	/**
	 * An interrupted nested operation must not clear an outer failure.
	 *
	 * @covers Sensei\Internal\Course_Status_Updates::run
	 */
	public function testRun_NestedOperationThrew_PreservesOuterFailure(): void {
		$handled = false;

		$result = Course_Status_Updates::run(
			static function () use ( &$handled ) {
				Course_Status_Updates::mark_failed();
				try {
					Course_Status_Updates::run(
						static function () {
							throw new RuntimeException( 'Nested operation failed.' );
						}
					);
				} catch ( RuntimeException $exception ) {
					$handled = true;
				}
			}
		);

		self::assertFalse( $result, 'A nested exception must not clear the outer course-status failure.' );
		self::assertTrue( $handled, 'The nested caller must still handle its own exception.' );
	}
}
