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
		$this->factory = new Sensei_Factory();

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
		$job       = Sensei_Import_Job::create( 'test-job', 0 );

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

		$job = Sensei_Import_Job::create( 'test-job', 0 );

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
		$job       = Sensei_Import_Job::create( 'test-job', 0 );

		$result = $job->save_file( 'questions', $test_file, basename( $test_file ) );

		$this->assertWPError( $result, 'Invalid file should result in a WP Error' );
		$this->assertEquals( 'sensei_data_port_unexpected_file_type', $result->get_error_code() );
	}

	/**
	 * Tests setting import ID in a job.
	 */
	public function testSetImportId() {
		$job = Sensei_Import_Job::create( 'test-job', 0 );
		$job->set_import_id( 'question', 100, 101 );

		$state = $job->jsonSerialize()['s'][ Sensei_Import_Job::MAPPED_ID_STATE_KEY ];

		$this->assertTrue( isset( $state['question'][100] ) );
		$this->assertEquals( 101, $state['question'][100] );
	}

	/**
	 * Tests getting import ID in a job.
	 */
	public function testGetImportId() {
		$job = Sensei_Import_Job::create( 'test-job', 0 );
		$job->set_import_id( 'question', 100, 101 );

		$this->assertEquals( 101, $job->get_import_id( 'question', 100 ) );
	}

	/**
	 * Test set_line_result method.
	 */
	public function testSetLineResults() {
		$expected_results = $this->get_default_result_counts();
		$job              = Sensei_Import_Job::create( 'test-job', 0 );
		$this->assertEquals( $expected_results, $job->get_result_counts(), 'Should equal the default value' );

		// Try incrementing a known result.
		$job->set_line_result( Sensei_Import_Course_Model::MODEL_KEY, 1, Sensei_Import_Job::RESULT_SUCCESS );
		$job->set_line_result( Sensei_Import_Course_Model::MODEL_KEY, 1, Sensei_Import_Job::RESULT_SUCCESS );

		$expected_results[ Sensei_Import_Course_Model::MODEL_KEY ]['success']++;
		$this->assertEquals( $expected_results, $job->get_result_counts(), 'Should have the known result incremented by 1' );

		$job->set_line_result( Sensei_Import_Course_Model::MODEL_KEY, 2, Sensei_Import_Job::RESULT_SUCCESS );
		$expected_results[ Sensei_Import_Course_Model::MODEL_KEY ]['success']++;
		$this->assertEquals( $expected_results, $job->get_result_counts(), 'Should have the known result incremented by 1' );

		$job->set_line_result( Sensei_Import_Course_Model::MODEL_KEY, 2, Sensei_Import_Job::RESULT_WARNING );
		$job->set_line_result( Sensei_Import_Course_Model::MODEL_KEY, 3, Sensei_Import_Job::RESULT_ERROR );
		$expected_results[ Sensei_Import_Course_Model::MODEL_KEY ]['success']--;
		$expected_results[ Sensei_Import_Course_Model::MODEL_KEY ]['error']++;
		$expected_results[ Sensei_Import_Course_Model::MODEL_KEY ]['warning']++;

		$this->assertEquals( $expected_results, $job->get_result_counts(), 'Line 2 should have moved the success to warning and a new line error' );

		$job->set_line_result( Sensei_Import_Course_Model::MODEL_KEY, 3, Sensei_Import_Job::RESULT_SUCCESS );
		$this->assertEquals( $expected_results, $job->get_result_counts(), 'Result should not have changed. Once an error always an error.' );
	}

	/**
	 * Test `Sensei_Import_Job::add_line_warning` marks line as having a warning and adds log entries.
	 */
	public function testAddLineWarning() {
		$expected_results = $this->get_default_result_counts();
		$job              = Sensei_Import_Job::create( 'test-job', 0 );
		$this->assertEquals( $expected_results, $job->get_result_counts(), 'Should equal the default value' );

		$expected_logs = [
			[
				'message' => 'Test warning A',
				'level'   => Sensei_Import_Job::LOG_LEVEL_NOTICE,
				'data'    => [
					'line' => 1,
				],
			],
			[
				'message' => 'Test warning B',
				'level'   => Sensei_Import_Job::LOG_LEVEL_NOTICE,
				'data'    => [
					'line' => 2,
				],
			],
		];

		$job->add_line_warning( Sensei_Import_Course_Model::MODEL_KEY, 1, $expected_logs[0]['message'] );
		$job->add_line_warning( Sensei_Import_Course_Model::MODEL_KEY, 2, $expected_logs[1]['message'] );
		$expected_results[ Sensei_Import_Course_Model::MODEL_KEY ]['warning'] = 2;

		$this->assertEquals( $expected_results, $job->get_result_counts(), 'Should have 2 warnings' );
		$this->assertEquals( $expected_logs, $job->get_logs() );
	}

	/**
	 * Tests translating an import ID.
	 */
	public function testTranslateImportId() {
		$course_id = $this->factory->course->create(
			[
				'post_name' => 'a-course',
			]
		);
		$job       = Sensei_Import_Job::create( 'test-job', 0 );

		$this->assertEquals( $course_id, $job->translate_import_id( 'course', $course_id ) );
		$this->assertNull( $job->translate_import_id( 'lesson', $course_id ) );

		$this->assertEquals( $course_id, $job->translate_import_id( 'course', 'slug:a-course' ) );
		$this->assertNull( $job->translate_import_id( 'course', 'slug:another-course' ) );

		$job->set_import_id( 'course', 'source_id', $course_id );
		$this->assertEquals( $course_id, $job->translate_import_id( 'course', 'id:source_id' ) );
		$this->assertNull( $job->translate_import_id( 'course', 'id:another_source_id' ) );
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

	/**
	 * Get the default value for the result counts.
	 *
	 * @return array
	 */
	private function get_default_result_counts() {
		$results     = Sensei_Data_Port_Job::get_array_by_model();
		$result_keys = [
			'error',
			'warning',
			'success',
		];

		foreach ( $results as $model_key => $counts ) {
			foreach ( $result_keys as $result_key ) {
				$results[ $model_key ][ $result_key ] = 0;
			}
		}

		return $results;
	}
}
