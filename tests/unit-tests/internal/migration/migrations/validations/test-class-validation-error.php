<?php

namespace SenseiTest\Internal\Migration\Validations;

use Sensei\Internal\Migration\Validations\Validation_Error;

/**
 * Class Validation_Error_Test
 *
 * @covers \Sensei\Internal\Migration\Validations\Validation_Error
 */
class Validation_Error_Test extends \WP_UnitTestCase {
	public function testGetMessage_HasMessage_ReturnsMessage(): void {
		/* Arrange. */
		$error = new Validation_Error( 'foo' );

		/* Act. */
		$actual = $error->get_message();

		/* Assert. */
		$this->assertSame( 'foo', $actual );
	}

	public function testGetData_HasData_ReturnsData(): void {
		/* Arrange. */
		$error = new Validation_Error( 'foo', [ 'bar' => 'baz' ] );

		/* Act. */
		$actual = $error->get_data();

		/* Assert. */
		$this->assertSame( [ 'bar' => 'baz' ], $actual );
	}

	public function testGetData_HasNoData_ReturnsEmptyArray(): void {
		/* Arrange. */
		$error = new Validation_Error( 'foo' );

		/* Act. */
		$actual = $error->get_data();

		/* Assert. */
		$this->assertSame( [], $actual );
	}

	public function testHasData_HasData_ReturnsTrue(): void {
		/* Arrange. */
		$error = new Validation_Error( 'foo', [ 'bar' => 'baz' ] );

		/* Act. */
		$actual = $error->has_data();

		/* Assert. */
		$this->assertTrue( $actual );
	}

	public function testHasData_HasNoData_ReturnsTrue(): void {
		/* Arrange. */
		$error = new Validation_Error( 'foo' );

		/* Act. */
		$actual = $error->has_data();

		/* Assert. */
		$this->assertFalse( $actual );
	}
}
