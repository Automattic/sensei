<?php
/**
 * This file contains the Sensei_Import_CSV_Reader_Test class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tests for Sensei_Import_CSV_Reader class.
 *
 * @group data-port
 */
class Sensei_Import_CSV_Reader_Test extends WP_UnitTestCase {

	/**
	 * Test that a CSV file with no data lines is rejected.
	 */
	public function testEmptyFileDoesNotPassValidation() {
		$empty_file = SENSEI_TEST_FRAMEWORK_DIR . '/data-port/data-files/test_csv_reader_empty.csv';

		$result = Sensei_Import_CSV_Reader::validate_csv_file( $empty_file, [], [ 'first column', 'second column', 'third column' ] );

		$this->assertInstanceOf( WP_Error::class, $result, 'An empty file should not pass validation' );
		$this->assertEquals( 'sensei_data_port_job_empty_file', $result->get_error_code() );
	}

	/**
	 * Tests for column related validations.
	 */
	public function testColumnValidations() {
		$empty_file = SENSEI_TEST_FRAMEWORK_DIR . '/data-port/data-files/test_csv_reader.csv';

		$result = Sensei_Import_CSV_Reader::validate_csv_file( $empty_file, [ 'required column' ], [ 'first column', 'second column', 'third column' ] );

		$this->assertInstanceOf( WP_Error::class, $result, 'A file with missing required columns should not pass validation' );
		$this->assertEquals( 'sensei_data_port_job_missing_columns', $result->get_error_code() );

		$this->assertTrue( Sensei_Import_CSV_Reader::validate_csv_file( $empty_file, [ 'first column', 'second column', 'third column' ], [] ) );

		$result = Sensei_Import_CSV_Reader::validate_csv_file( $empty_file, [ 'first column' ], [ 'third column' ] );

		$this->assertInstanceOf( WP_Error::class, $result, 'A file with an unknown column should not pass validation' );
		$this->assertEquals( 'sensei_data_port_job_unknown_columns', $result->get_error_code() );
	}

	/**
	 * Tests that validation fails when the file cannot be accessed.
	 */
	public function testErrorIsReturnedWhenFileNotExists() {
		$empty_file = SENSEI_TEST_FRAMEWORK_DIR . '/data-port/data-files/non_existant_file.csv';

		$result = Sensei_Import_CSV_Reader::validate_csv_file( $empty_file, [], [ 'first column', 'second column', 'third column' ] );

		$this->assertInstanceOf( WP_Error::class, $result, 'An unreadable file should not pass validation' );
		$this->assertEquals( 'sensei_data_port_job_unreadable_file', $result->get_error_code() );
	}

	/**
	 * Tests that a correct file passes validation.
	 */
	public function testNonEmptyFilePassValidation() {
		$file = SENSEI_TEST_FRAMEWORK_DIR . '/data-port/data-files/test_csv_reader.csv';

		$this->assertTrue( Sensei_Import_CSV_Reader::validate_csv_file( $file, [], [ 'first column', 'second column', 'third column' ] ) );
	}

	/**
	 * Tests that lines that are empty are returned by read_lines() as empty arrays.
	 */
	public function testEmptyLinesAreReturnedAsEmpty() {
		$file = SENSEI_TEST_FRAMEWORK_DIR . '/data-port/data-files/test_csv_reader.csv';

		$reader = new Sensei_Import_CSV_Reader( $file, 0, 3 );

		foreach ( $reader->read_lines() as $line ) {
			$this->assertEmpty( $line );
		}
	}

	/**
	 * Test that lines_per_batch affects which lines are returned by read_lines
	 */
	public function testBatchSizeIsRespected() {
		$file = SENSEI_TEST_FRAMEWORK_DIR . '/data-port/data-files/test_csv_reader.csv';

		$reader = new Sensei_Import_CSV_Reader( $file, 0, 3 );

		foreach ( $reader->read_lines() as $line ) {
			$this->assertEmpty( $line );
		}

		$second_batch = $reader->read_lines();

		$this->assertEmpty( $second_batch[0] );
		$this->assertEquals(
			[
				'first column'  => 'first data 1',
				'second column' => 'second data 1',
				'third column'  => 'third data 1',
			],
			$second_batch[1]
		);

		$this->assertEquals(
			[
				'first column'  => 'first data 2',
				'second column' => 'second data 2',
				'third column'  => 'third data 2',
			],
			$second_batch[2]
		);

		$reader->read_lines();
		$reader->read_lines();
		$last_batch = $reader->read_lines();

		$this->assertEmpty( $last_batch[0] );
		$this->assertCount( 1, $last_batch );
	}

	/**
	 * Test the values of various data lines.
	 */
	public function testValuesReturnedAreCorrect() {
		$file = SENSEI_TEST_FRAMEWORK_DIR . '/data-port/data-files/test_csv_reader.csv';

		$reader = new Sensei_Import_CSV_Reader( $file, 7, 3 );

		$lines = $reader->read_lines();

		$this->assertEquals(
			[
				'first column'  => 'first data 3',
				'second column' => 'second data 3',
				'third column'  => null,
			],
			$lines[0]
		);

		$this->assertEquals(
			[
				'first column'  => null,
				'second column' => null,
				'third column'  => 'third data 4',
			],
			$lines[1]
		);

		$this->assertInstanceOf( WP_Error::class, $lines[2], 'A line with a wrong number of columns should contain WP_Error.' );
		$this->assertEquals( 'sensei_data_port_job_wrong_number_of_columns', $lines[2]->get_error_code() );

		$this->assertEquals( 10, $reader->get_completed_lines() );
		$this->assertEquals( 13, $reader->get_total_lines() );
	}
}
