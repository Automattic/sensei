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

	public function testCreate_NoConstructorArgumentsGiven_ReturnsExpectedInstance(): void {
		/* Act. */
		$list_table = ( new Sensei_Reports_Overview_List_Table_Factory() )->create( 'courses' );

		/* Assert. */
		$this->assertInstanceOf( Sensei_Reports_Overview_List_Table_Courses::class, $list_table );
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
		$factory = $this->create_factory();

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
		$factory = $this->create_factory();

		/* Expect & Act. */
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Unknown list table type' );
		$factory->create( 'unknown' );
	}

	/**
	 * Create a factory with injected query services.
	 *
	 * @return Sensei_Reports_Overview_List_Table_Factory
	 */
	private function create_factory(): Sensei_Reports_Overview_List_Table_Factory {
		return new Sensei_Reports_Overview_List_Table_Factory(
			Sensei()->course,
			$this->createMock( \Sensei\Internal\Services\Progress_Clauses_Service_Interface::class ),
			$this->createMock( \Sensei\Internal\Services\Progress_Aggregation_Service_Interface::class ),
			$this->createMock( \Sensei\Internal\Services\Grading_Stats_Service_Interface::class )
		);
	}
}
