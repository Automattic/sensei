<?php

namespace SenseiTest\Installer;

use Sensei\Installer\Schema;

/**
 * Test for \Sensei\Installer\Schema.
 *
 * @covers \Sensei\Installer\Schema
 */
class Schema_Test extends \WP_UnitTestCase {
	/**
	 * The Schema instance.
	 *
	 * @var Schema
	 */
	protected $schema;

	/**
	 * Test specific setup.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->schema = new Schema();

		$this->reset_schema();
	}

	public function testCreateTables_WhenCalled_ShouldCreateTheTablesDefinedInGetTables(): void {
		/* Arrange. */
		global $wpdb;

		$expected_tables = $this->schema->get_tables();

		/* Act. */
		$this->schema->create_tables();

		/* Assert. */
		$created_tables = $wpdb->get_col( "SHOW TABLES LIKE '{$wpdb->prefix}sensei_lms%'" );
		foreach ( $expected_tables as $table ) {
			$this->assertContains( $table, $created_tables );
		}
	}

	protected function reset_schema(): void {
		global $wpdb;

		// Remove the Test Suite’s use of temporary tables.
		// @see https://wordpress.stackexchange.com/a/220308
		remove_filter( 'query', array( $this, '_create_temporary_tables' ) );
		remove_filter( 'query', array( $this, '_drop_temporary_tables' ) );

		// Remove any existing tables in the environment.
		$query = 'DROP TABLE IF EXISTS ' . implode( ',', $this->schema->get_tables() );
		$wpdb->query( $query );
	}
}
