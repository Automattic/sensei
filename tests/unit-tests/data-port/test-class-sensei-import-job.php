<?php
/**
 * This file contains the Sensei_Import_Job_Test class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tests for Sensei_Import_Job class.
 *
 * @group data-port
 */
class Sensei_Import_Job_Test extends WP_UnitTestCase {

	/**
	 * Set up the tests.
	 */
	public function setUp() {
		// Make sure CSVs are allowed on WordPress multi-site.
		update_site_option( 'upload_filetypes', 'csv' );

		return parent::setUp();
	}

	/**
	 * Tear down after tests.
	 */
	public function tearDown() {
		parent::tearDown();

		delete_site_option( 'upload_filetypes' );
	}

	/**
	 * Test saving a valid file to the job.
	 */
	public function testSaveFileValid() {
		if ( ! version_compare( get_bloginfo( 'version' ), '5.0.0', '>=' ) ) {
			$this->markTestSkipped( 'Test fails with 4.9 due to text/csv getting interpreted as text/plain.' );
		}

		$test_file = SENSEI_TEST_FRAMEWORK_DIR . '/data-port/data-files/questions.csv';
		$test_file = $this->get_tmp_file( $test_file );
		$job       = new Sensei_Import_Job( 'test-job' );

		$result = $job->save_file( 'questions', $test_file, basename( $test_file ) );

		$this->assertTrue( $result, 'Valid file with a valid file key should be stored' );
		$this->assertTrue( isset( $job->get_files()['questions'] ) );

		$attachment = get_post( $job->get_files()['questions'] );
		$this->assertTrue( $attachment instanceof WP_Post );

		$this->assertEquals( basename( $test_file ), $attachment->post_title );
	}

	/**
	 * Test saving an invalid file to the job when it already has a valid file. Should immediately remove
	 * the valid file.
	 */
	public function testSaveFileInvalidAfterValid() {
		if ( ! version_compare( get_bloginfo( 'version' ), '5.0.0', '>=' ) ) {
			$this->markTestSkipped( 'Test fails with 4.9 due to text/csv getting interpreted as text/plain.' );
		}

		$test_file_valid   = SENSEI_TEST_FRAMEWORK_DIR . '/data-port/data-files/questions.csv';
		$test_file_valid   = $this->get_tmp_file( $test_file_valid );
		$test_file_invalid = SENSEI_TEST_FRAMEWORK_DIR . '/data-port/data-files/invalid_file_type.tsv';
		$test_file_invalid = $this->get_tmp_file( $test_file_invalid );

		$job = new Sensei_Import_Job( 'test-job' );

		$result_valid = $job->save_file( 'questions', $test_file_valid, basename( $test_file_valid ) );

		$this->assertTrue( $result_valid, 'Valid file with a valid file key should be stored' );
		$this->assertTrue( isset( $job->get_files()['questions'] ) );

		$result_invalid = $job->save_file( 'questions', $test_file_invalid, basename( $test_file_invalid ) );

		$this->assertWPError( $result_invalid, 'Invalid file should not be stored' );
		$this->assertFalse( isset( $job->get_files()['questions'] ), 'Old valid file should be removed on invalid attempt' );
	}

	/**
	 * Test saving a non CSV file to a job.
	 */
	public function testSaveFileBadFile() {
		$test_file = SENSEI_TEST_FRAMEWORK_DIR . '/data-port/data-files/invalid_file_type.tsv';
		$test_file = $this->get_tmp_file( $test_file );
		$job       = new Sensei_Import_Job( 'test-job' );

		$result = $job->save_file( 'questions', $test_file, basename( $test_file ) );

		$this->assertWPError( $result, 'Invalid file should result in a WP Error' );
		$this->assertEquals( 'sensei_data_port_unexpected_file_type', $result->get_error_code() );
	}

	/**
	 * Tests setting import ID in a job.
	 */
	public function testSetImportId() {
		$job = new Sensei_Import_Job( 'test-job' );
		$job->set_import_id( 'question', 100, 101 );

		$state = $job->jsonSerialize()['s'][ Sensei_Import_Job::MAPPED_ID_STATE_KEY ];

		$this->assertTrue( isset( $state['question'][100] ) );
		$this->assertEquals( 101, $state['question'][100] );
	}

	/**
	 * Tests getting import ID in a job.
	 */
	public function testGetImportId() {
		$job = new Sensei_Import_Job( 'test-job' );
		$job->set_import_id( 'question', 100, 101 );

		$this->assertEquals( 101, $job->get_import_id( 'question', 100 ) );
	}

	/**
	 * Get a temporary file from a source file.
	 *
	 * @param string $file_path File to copy.
	 *
	 * @return string
	 */
	private function get_tmp_file( $file_path ) {
		$tmp = wp_tempnam( basename( $file_path ) ) . '.' . pathinfo( $file_path, PATHINFO_EXTENSION );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_file_put_contents, WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		file_put_contents( $tmp, file_get_contents( $file_path ) );

		return $tmp;
	}
}
