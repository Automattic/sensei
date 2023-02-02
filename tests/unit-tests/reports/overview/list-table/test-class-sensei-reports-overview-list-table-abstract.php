<?php

class Sensei_Reports_Overview_List_Table_Abstract_Test extends WP_UnitTestCase {
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

	public function testPrepareItems_WhenCalled_GetsItemsFromDataProvider() {
		/* Arrange. */
		$data_provider = $this->createMock( Sensei_Reports_Overview_Data_Provider_Interface::class );

		$list_table = $this->getMockBuilder( Sensei_Reports_Overview_List_Table_Abstract::class )
			->setConstructorArgs( [ 'a', $data_provider ] )
			->getMockForAbstractClass();
		$list_table->method( 'get_additional_filters' )->willReturn( [ 'a' => 1 ] );

		/* Expect & Act. */
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
		/* Arrange. */
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
		$list_table->columns       = array_combine( [ 'd', 'e', 'f' ], [ 'd', 'e', 'f' ] );
		$columns_with_empty_values = array_fill_keys( array_keys( $list_table->columns ), '' );

		/* Act. */
		$actual = $list_table->generate_report();

		/* Assert. */
		$expected = [
			[ 'd', 'e', 'f' ],
			array_merge( $columns_with_empty_values, [ 'b' => 2 ] ),
			array_merge( $columns_with_empty_values, [ 'c' => 3 ] ),
		];
		self::assertSame( $expected, $actual );
	}

	public function testTableFooter_WhenCalled_DisplayTheExportButton() {
		/* Arrange. */
		$nonce = wp_create_nonce( 'sensei_csv_download' );
		$_GET  = [
			's'             => 'course 5',
			'order'         => 'asc  ',
			'orderby'       => 'id  ',
			'start_date'    => '2022-03-01',
			'end_date'      => '2022-03-01',
			'timezone'      => 'UTC',
			'course_filter' => 1,
			'_wpnonce'      => $nonce,

		];
		$data_provider = $this->createMock( Sensei_Reports_Overview_Data_Provider_Interface::class );
		$list_table    = $this->getMockBuilder( Sensei_Reports_Overview_List_Table_Abstract::class )
			->setConstructorArgs( [ 'a', $data_provider ] )
			->getMockForAbstractClass();

		$data_provider
			->method( 'get_last_total_items' )
			->willReturn( 1 );

		/* Act. */
		ob_start();
		$list_table->prepare_items();
		$list_table->data_table_footer();
		$actual = ob_get_clean();

		/* Assert. */
		$expected = '<a class="button button-primary" href="http://example.org/wp-admin/admin.php?page=sensei_reports&#038;view=a&#038;sensei_report_download=user-overview&#038;orderby=id&#038;order=asc&#038;course_filter=1&#038;start_date=2022-03-01&#038;end_date=2022-03-01&#038;timezone=UTC&#038;s=course+5&#038;_sdl_nonce=' . $nonce . '">Export all rows (CSV)</a>';
		self::assertSame( $expected, $actual, 'The export button should be displayed' );
	}

	public function testTableFooter_WhenCalledWithNoData_NotDisplayTheExportButton() {
		/* Arrange. */
		$data_provider = $this->createMock( Sensei_Reports_Overview_Data_Provider_Interface::class );
		$list_table    = $this->getMockBuilder( Sensei_Reports_Overview_List_Table_Abstract::class )
			->setConstructorArgs( [ 'a', $data_provider ] )
			->getMockForAbstractClass();

		$data_provider
			->method( 'get_last_total_items' )
			->willReturn( 0 );

		/* Act. */
		ob_start();
		$list_table->data_table_footer();
		$actual = ob_get_clean();

		/* Assert. */
		$expected = '';
		self::assertSame( $expected, $actual, 'The export button should not be displayed' );
	}
}
