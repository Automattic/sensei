<?php
/**
 * This file contains the Sensei_Import_Questions class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tests for Sensei_Import_Questions class.
 *
 * @group data-port
 */
class Sensei_Import_Questions_Tests extends WP_UnitTestCase {
	/**
	 * Placeholder test.
	 */
	public function testClassExists() {
		$this->assertTrue( class_exists( 'Sensei_Import_Questions' ) );
	}
	/**
	 * Validate source file with missing required columns.
	 */
	public function testValidateSourceFileMissingColumns() {
		$csv = <<< EOL
ID,Slug,Description
1,"do-you-like-dogs","This is a great question"
EOL;

		$tmp_file = wp_tempnam();
		$file     = new SplFileObject( $tmp_file, 'w+' );
		$file->fwrite( $csv );

		$result = Sensei_Import_Questions::validate_source_file( $tmp_file );

		$this->assertWPError( $result );
		$this->assertEquals( 'sensei_data_port_job_missing_columns', $result->get_error_code() );
	}

	/**
	 * Validate source file with no missing required columns.
	 */
	public function testValidateSourceFileAllRequiredColumns() {
		$csv = <<< EOL
Question,Answer,Description
"Do you like dogs?","Right:Yes, Wrong:No","This is a great question"
EOL;

		$tmp_file = wp_tempnam();
		$file     = new SplFileObject( $tmp_file, 'w+' );
		$file->fwrite( $csv );

		$result = Sensei_Import_Questions::validate_source_file( $tmp_file );

		$this->assertTrue( $result );
	}

	/**
	 * Validate source file with an unknown column
	 */
	public function testValidateSourceFileUnknownColumn() {
		$csv = <<< EOL
Question,Answer,Bad
"Do you like dogs?","Right:Yes, Wrong:No","This is a great question"
EOL;

		$tmp_file = wp_tempnam();
		$file     = new SplFileObject( $tmp_file, 'w+' );
		$file->fwrite( $csv );

		$result = Sensei_Import_Questions::validate_source_file( $tmp_file );

		$this->assertWPError( $result );
		$this->assertEquals( 'sensei_data_port_job_unknown_columns', $result->get_error_code() );
	}
}
