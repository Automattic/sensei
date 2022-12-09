<?php

/**
 * Sensei Reports Overview List Table Factory Test Class
 *
 * @covers Sensei_Reports_Overview_List_Table_Factory
 */
class Sensei_Reports_Overview_List_Table_Factory_Test extends WP_UnitTestCase {
	private static $initial_hook_suffix;

	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		self::$initial_hook_suffix = $GLOBALS['hook_suffix'] ?? null;
		$GLOBALS['hook_suffix']    = null;
	}

	public static function tearDownAfterClass(): void {
		parent::tearDownAfterClass();
		$GLOBALS['hook_suffix'] = self::$initial_hook_suffix;
	}

	/**
	 * Test create method success path
	 *
	 * @param string $type
	 * @param string $expected_class
	 *
	 * @dataProvider providerCreate_TypeGiven_ReturnsExpectedInstance
	 */
	public function testCreate_TypeGiven_ReturnsExpectedInstance( string $type, string $expected_class ) {
		/* Arrange. */
		$factory = new Sensei_Reports_Overview_List_Table_Factory();

		/* Act. */
		$actual_instance = $factory->create( $type );

		/* Assert. */
		$this->assertInstanceOf( $expected_class, $actual_instance );
	}

	public function providerCreate_TypeGiven_ReturnsExpectedInstance(): array {
		return [
			'courses' => [
				'courses',
				'Sensei_Reports_Overview_List_Table_Courses',
			],
		];
	}

	public function testCreate_UnknownTypeGiven_ThrowsException() {
		/* Arrange. */
		$factory = new Sensei_Reports_Overview_List_Table_Factory();

		/* Expect & Act. */
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Unknown list table type' );
		$factory->create( 'unknown' );
	}
}
