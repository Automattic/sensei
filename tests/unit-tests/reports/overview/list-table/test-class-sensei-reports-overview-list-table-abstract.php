<?php

class Sensei_Reports_Overview_List_Table_Abstract_Test extends WP_UnitTestCase {
	private static $initial_hook_suffix;

	public static function setUpBeforeClass() {
		parent::setUpBeforeClass();
		self::$initial_hook_suffix = $GLOBALS['hook_suffix'] ?? null;
		$GLOBALS['hook_suffix']    = null;
	}

	public static function tearDownAfterClass() {
		parent::tearDownAfterClass();
		$GLOBALS['hook_suffix'] = self::$initial_hook_suffix;
	}

	public function testPrepareItems_WhenCalled_GetsItemsFromDataProvider() {
		$data_provider = $this->createMock( Sensei_Reports_Overview_Data_Provider_Interface::class );

		$list_table = $this->getMockBuilder( Sensei_Reports_Overview_List_Table_Abstract::class )
			->setConstructorArgs( [ 'a', $data_provider ] )
			->getMockForAbstractClass();
		$list_table->method( 'get_additional_filters' )->willReturn( [ 'a' => 1 ] );

		$data_provider
			->expects( self::once() )
			->method( 'get_items' )
			->with(
				[
					'number'  => 20,
					'offset'  => 0,
					'orderby' => '',
					'order'   => 'ASC',
					'a'       => 1,
				]
			)
			->willReturn( [] );
		$list_table->prepare_items();
	}

	public function testGenerateReport_WhenCalled_ReturnsMatchingArray() {
		$post1 = new WP_Post( new stdClass() );
		$post2 = new WP_Post( new stdClass() );

		$data_provider = $this->createMock( Sensei_Reports_Overview_Data_Provider_Interface::class );
		$data_provider
			->method( 'get_items' )
			->with(
				[
					'number'  => -1,
					'offset'  => 0,
					'orderby' => '',
					'order'   => 'ASC',
					'a'       => 1,
				]
			)
			->willReturn( [ $post1, $post2 ] );

		$list_table = $this->getMockBuilder( Sensei_Reports_Overview_List_Table_Abstract::class )
			->setMethods( [ 'get_additional_filters', 'get_row_data' ] )
			->setConstructorArgs( [ 'a', $data_provider ] )
			->getMockForAbstractClass();
		$list_table
			->method( 'get_additional_filters' )
			->willReturn( [ 'a' => 1 ] );
		$list_table
			->method( 'get_row_data' )
			->willReturnMap(
				[
					[ $post1, [ 'b' => 2 ] ],
					[ $post2, [ 'c' => 3 ] ],
				]
			);
		$list_table->columns = [ 'd', 'e', 'f' ];

		$actual = $list_table->generate_report();

		$expected = [
			[ 'd', 'e', 'f' ],
			[ 'b' => 2 ],
			[ 'c' => 3 ],
		];
		self::assertSame( $expected, $actual );
	}
}
