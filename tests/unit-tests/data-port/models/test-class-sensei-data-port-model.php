<?php
/**
 * This file contains the Sensei_Data_Port_Model_Test class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once SENSEI_TEST_FRAMEWORK_DIR . '/data-port/class-sensei-data-port-model-mock.php';

/**
 * Tests for Sensei_Data_Port_Model class.
 *
 * @group data-port
 */
class Sensei_Data_Port_Model_Test extends WP_UnitTestCase {
	/**
	 * Test getting optional field from schema.
	 */
	public function testGetOptionalFields() {
		$optional_mock_fields = [
			'test-string-no-html',
			'favorite_int',
			'favorite_float',
			'slug',
		];

		$this->assertEquals(
			$optional_mock_fields,
			Sensei_Data_Port_Model_Mock::get_optional_fields()
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
			Sensei_Data_Port_Model_Mock::get_required_fields()
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

		$model = Sensei_Data_Port_Model_Mock::from_source_array( $data );

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

		$model = Sensei_Data_Port_Model_Mock::from_source_array( $data );

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

		$model = Sensei_Data_Port_Model_Mock::from_source_array( $data );

		$this->assertEquals( $expected, $model->get_data() );
		$this->assertFalse( $model->is_valid(), 'Type did not match a valid field so should invalidate the entry' );
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

		$model = Sensei_Data_Port_Model_Mock::from_source_array( $data );

		$this->assertEquals( 0, $model->get_value( 'favorite_int', true ), 'Default should be provided when not included in data' );
		$this->assertEquals( null, $model->get_value( 'favorite_int', false ), 'Null should be provided when not included in data' );

		$this->assertEquals( 'neat-slug', $model->get_value( 'slug', true ), 'Default should be provided when not included in data' );
		$this->assertEquals( null, $model->get_value( 'slug', false ), 'Null should be provided when not included in data' );

		$this->assertEquals( $data['email'], $model->get_value( 'email', true ), 'Actual value should be provided' );
	}
}
