<?php
/**
 * File containing class Courses_Csv_Tests.
 *
 * @package sensei-lms
 * @since 3.5.0
 */

/**
 * Tests integrity of sample data.
 */
class Courses_Csv_Tests extends WP_UnitTestCase {
	const SAMPLE_FILE = 'courses.csv';

	/**
	 * Ensure the teacher username and email columns are empty.
	 */
	public function testTeacherColumnsEmpty() {
		$data = $this->read_sample_file();

		$this->assertNotEmpty( $data, 'The courses file must contain data' );
		foreach ( $data as $item ) {
			$this->assertTrue( empty( $item['teacher username'] ), 'Teacher Username column must be empty or not set' );
			$this->assertTrue( empty( $item['teacher email'] ), 'Teacher Email column must be empty or not set' );
		}
	}

	/**
	 * Ensure the data contains expected course ID.
	 */
	public function testCourseID() {
		$expected_id = Sensei_Data_Port_Manager::SAMPLE_COURSE_ID;
		$data        = $this->read_sample_file();

		$this->assertNotEmpty( $data, 'The courses file must contain data' );

		$found_id = array_filter(
			$data,
			function( $item ) use ( $expected_id ) {
				return intval( $item['id'] ) === $expected_id;
			}
		);

		$this->assertNotEmpty(
			$found_id,
			sprintf( 'It should contains the %s ID', Sensei_Data_Port_Manager::SAMPLE_COURSE_ID )
		);
	}

	/**
	 * Read the sample file data.
	 *
	 * @return array
	 */
	private function read_sample_file() {
		$file_path = Sensei_Unit_Tests_Bootstrap::instance()->plugin_dir . '/sample-data/' . self::SAMPLE_FILE;

		$this->assertTrue( file_exists( $file_path ), 'Sample file ' . self::SAMPLE_FILE . ' does not exist.' );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fopen -- Just a test.
		$file    = fopen( $file_path, 'r' );
		$data    = [];
		$headers = array_map( 'strtolower', fgetcsv( $file ) );

		while ( ! feof( $file ) ) {
			$row = fgetcsv( $file );
			if ( empty( $row ) ) {
				continue;
			}

			$data[] = array_combine( $headers, $row );
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fclose -- Just a test.
		fclose( $file );

		return $data;
	}
}
