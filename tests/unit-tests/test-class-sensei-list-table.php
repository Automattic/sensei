<?php

namespace SenseiTest;

use Sensei_List_Table;

/**
 * Tests for Sensei_List_Table.
 *
 * @covers Sensei_List_Table
 */
class Sensei_List_Table_Test extends \WP_UnitTestCase {
	public function testSingleRow_WithoutNativeRowActions_ReturnsMatchingMarkup() {
		/* Arrange. */
		$list_table                          = new List_Table_Implementation();
		$list_table::$has_native_row_actions = false;
		$item                                = new \stdClass();

		/* Act. */
		ob_start();
		$list_table->single_row( $item );
		$actual = ob_get_clean();

		/* Assert. */
		$expected = '<tr class="alternate"><td class=\'foo column-foo column-primary\' data-colname="Foo" >bar<button type="button" class="toggle-row"><span class="screen-reader-text">Show more details</span></button></td></tr>';
		self::assertSame( $expected, $actual );
	}

	public function testSingleRow_WithNativeRowActions_ReturnsMatchingMarkup() {
		/* Arrange. */
		$list_table                          = new List_Table_Implementation( true );
		$list_table::$has_native_row_actions = true;
		$item                                = new \stdClass();

		/* Act. */
		ob_start();
		$list_table->single_row( $item );
		$actual = ob_get_clean();

		/* Assert. */
		$expected = '<tr class="alternate"><td class=\'foo column-foo column-primary\' data-colname="Foo" >bar</td></tr>';

		self::assertSame( $expected, $actual );
	}
}

// phpcs:ignore Generic.Files.OneObjectStructurePerFile.MultipleFound -- This implementation needed only for testing here.
class List_Table_Implementation extends Sensei_List_Table {
	public static $has_native_row_actions;

	public function __construct() {
		parent::__construct( 'a' );
	}

	public function get_columns() {
		return [ 'foo' => 'Foo' ];
	}

	protected function get_row_data( $item ) {
		return [ 'foo' => 'bar' ];
	}

	protected function has_native_row_actions() {
		return self::$has_native_row_actions;
	}

	protected function get_row_class( $item ): string {
		return 'alternate';
	}
}
