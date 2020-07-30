<?php
/**
 * This file contains the Sensei_Data_Port_Model_Test class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once SENSEI_TEST_FRAMEWORK_DIR . '/data-port/class-sensei-import-model-mock.php';
require_once SENSEI_TEST_FRAMEWORK_DIR . '/data-port/class-sensei-data-port-schema-mock.php';
require_once SENSEI_TEST_FRAMEWORK_DIR . '/data-port/class-sensei-data-port-job-mock.php';

/**
 * Tests for Sensei_Data_Port_Model class.
 *
 * @group data-port
 */
class Sensei_Data_Port_Model_Test extends WP_UnitTestCase {
	use Sensei_Data_Port_Test_Helpers;

	/**
	 * Test getting optional field from schema.
	 */
	public function testGetOptionalFields() {
		$optional_mock_fields = [
			'test-string-no-html',
			'favorite_int',
			'other_int',
			'favorite_float',
			'favorite_bool',
			'slug',
		];

		$this->assertEquals(
			$optional_mock_fields,
			( new Sensei_Data_Port_Schema_Mock() )->get_optional_fields()
		);
	}

	/**
	 * Test getting required field from schema.
	 */
	public function testGetRequiredFields() {
		$optional_mock_fields = [
			'test-string-allow-html',
			'email',
			'type',
		];

		$this->assertEquals(
			$optional_mock_fields,
			( new Sensei_Data_Port_Schema_Mock() )->get_required_fields()
		);
	}

	/**
	 * Check to make sure a valid data set comes back the same.
	 */
	public function testSanitizeAllValid() {
		$data = [
			'test-string-allow-html' => '<em>This is great HTML</em>',
			'test-string-no-html'    => 'Cool',
			'favorite_int'           => 1000,
			'favorite_float'         => 1.0003,
			'email'                  => 'dinosaur@example.com',
			'slug'                   => 'my-favorite-animal',
			'type'                   => 'cool',
		];

		$model = Sensei_Import_Model_Mock::from_source_array( 1, $data, new Sensei_Data_Port_Schema_Mock() );

		$this->assertEquals( $data, $model->get_data() );
		$this->assertTrue( $model->is_valid() );
	}

	/**
	 * Check to make sure a valid data set comes back the same.
	 */
	public function testSanitizeHasUnknownColumn() {
		$data = [
			'test-string-allow-html' => '<em>This is great HTML</em>',
			'test-string-no-html'    => 'Cool',
			'favorite_int'           => 1000,
			'favorite_float'         => 1.0003,
			'email'                  => 'dinosaur@example.com',
			'slug'                   => 'my-favorite-animal',
			'type'                   => 'cool',
		];

		$expected = $data;

		$data['unknown_column'] = 1;

		$model = Sensei_Import_Model_Mock::from_source_array( 1, $data, new Sensei_Data_Port_Schema_Mock() );

		$this->assertEquals( $expected, $model->get_data() );
		$this->assertTrue( $model->is_valid() );
	}

	/**
	 * Check to make sure bad data is sanitized.
	 */
	public function testSanitizeChangesUnclean() {
		$data = [
			'test-string-allow-html' => '<em>This is great HTML <script>alert("Bad!");</script></em>',
			'test-string-no-html'    => '<em>Cool</em>',
			'favorite_int'           => '1000',
			'favorite_float'         => 1.0003,
			'email'                  => 'dinosaur()@example.com',
			'slug'                   => 'my favorite ANIMAL',
			'type'                   => 'wise',
		];

		$expected = [
			'test-string-allow-html' => '<em>This is great HTML alert("Bad!");</em>',
			'test-string-no-html'    => 'Cool',
			'favorite_int'           => 1000,
			'favorite_float'         => 1.0003,
			'email'                  => 'dinosaur@example.com',
			'slug'                   => 'my-favorite-animal',
			'type'                   => null,
		];

		$data['unknown_column'] = 1;

		$model = Sensei_Import_Model_Mock::from_source_array( 1, $data, new Sensei_Data_Port_Schema_Mock() );

		$this->assertEquals( $expected, $model->get_data() );
		$this->assertFalse( $model->is_valid(), 'Type did not match a valid field so should invalidate the entry' );
	}

	/**
	 * Check that int is sanitized correctly and adds the warnings.
	 */
	public function testSanitizeInt() {
		$data = [
			'favorite_int' => '10.22',
			'other_int'    => 'abc',
		];

		$expected = [
			'favorite_int' => 10,
			'other_int'    => 0,
		];

		$job   = Sensei_Import_Job::create( 'test', 0 );
		$task  = new Sensei_Import_Courses( $job );
		$model = Sensei_Import_Model_Mock::from_source_array( 1, $data, new Sensei_Data_Port_Schema_Mock(), $task );

		$this->assertEquals( $expected, $model->get_data() );

		$model->add_warnings_to_job();
		$this->assertJobHasLogEntry( $job, 'Error in column "favorite_int": It must be a whole number.' );
		$this->assertJobHasLogEntry( $job, 'Error in column "other_int": It must be a whole number.' );
	}

	/**
	 * Check that float is sanitized correctly and adds the warnings.
	 */
	public function testSanitizeFloat() {
		$data = [
			'favorite_float' => 'abc',
		];

		$expected = [
			'favorite_float' => 0,
		];

		$job   = Sensei_Import_Job::create( 'test', 0 );
		$task  = new Sensei_Import_Courses( $job );
		$model = Sensei_Import_Model_Mock::from_source_array( 1, $data, new Sensei_Data_Port_Schema_Mock(), $task );

		$this->assertEquals( $expected, $model->get_data() );

		$model->add_warnings_to_job();
		$this->assertJobHasLogEntry( $job, 'Error in column "favorite_float": It must be a number.' );
	}

	/**
	 * Check that bool is sanitized correctly and adds the warnings.
	 */
	public function testSanitizeBool() {
		$data = [
			'favorite_bool' => 'abc',
		];

		$expected = [
			'favorite_bool' => null,
		];

		$job   = Sensei_Import_Job::create( 'test', 0 );
		$task  = new Sensei_Import_Courses( $job );
		$model = Sensei_Import_Model_Mock::from_source_array( 1, $data, new Sensei_Data_Port_Schema_Mock(), $task );

		$this->assertEquals( $expected, $model->get_data() );

		$model->add_warnings_to_job();
		$this->assertJobHasLogEntry( $job, 'Error in column "favorite_bool": It must be one of the following: 0, 1, true, false.' );
	}

	/**
	 * Check that slug is sanitized correctly and adds the warnings.
	 */
	public function testSanitizeSlug() {
		$data = [
			'slug' => 'slúg-@',
		];

		$expected = [
			'slug' => 'slug',
		];

		$job   = Sensei_Import_Job::create( 'test', 0 );
		$task  = new Sensei_Import_Courses( $job );
		$model = Sensei_Import_Model_Mock::from_source_array( 1, $data, new Sensei_Data_Port_Schema_Mock(), $task );

		$this->assertEquals( $expected, $model->get_data() );

		$model->add_warnings_to_job();
		$this->assertJobHasLogEntry( $job, 'Error in column "slug": It contains invalid characters.' );
	}

	/**
	 * Tests various scenarios with get value.
	 */
	public function testGetValue() {
		$data = [
			'test-string-allow-html' => '<em>This is great HTML</em>',
			'test-string-no-html'    => 'Cool',
			'email'                  => 'dinosaur@example.com',
			'type'                   => 'cool',
		];

		$model = Sensei_Import_Model_Mock::from_source_array( 1, $data, new Sensei_Data_Port_Schema_Mock() );

		$property = new ReflectionProperty( 'Sensei_Import_Model', 'is_new' );
		$property->setAccessible( true );
		$property->setValue( $model, false );

		$this->assertEquals( null, $model->get_value( 'favorite_int' ), 'Null should be provided when not included in data' );
		$this->assertEquals( null, $model->get_value( 'slug' ), 'Null should be provided when not included in data' );

		$property->setValue( $model, true );

		$this->assertEquals( 0, $model->get_value( 'favorite_int' ), 'Default should be provided when not included in data' );
		$this->assertEquals( 'neat-slug', $model->get_value( 'slug' ), 'Default should be provided when not included in data' );
		$this->assertEquals( $data['email'], $model->get_value( 'email' ), 'Actual value should be provided' );
	}
}
