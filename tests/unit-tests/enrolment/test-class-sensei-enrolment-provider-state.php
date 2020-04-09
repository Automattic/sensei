<?php

/**
 * Tests for Sensei_Enrolment_Provider_State class.
 *
 * @group course-enrolment
 */
class Sensei_Enrolment_Provider_State_Test extends WP_UnitTestCase {
	use Sensei_Course_Enrolment_Test_Helpers;

	/**
	 * Setup function.
	 */
	public function setUp() {
		parent::setUp();

		self::resetEnrolmentStateStores();
	}

	/**
	 * Clean up after all tests.
	 */
	public static function tearDownAfterClass() {
		parent::tearDownAfterClass();
		self::resetEnrolmentStateStores();
	}

	/**
	 * Tests to make sure arrays of serialized data return an instantiated object.
	 */
	public function testFromSerializedArray() {
		$state_store = Sensei_Enrolment_Provider_State_Store::get( 0 );
		$test_array  = [
			'test' => 'Dinosaurs!',
		];

		$result = Sensei_Enrolment_Provider_State::from_array( $state_store, $test_array );

		$this->assertTrue( $result instanceof Sensei_Enrolment_Provider_State, 'Serialized data should have returned a instantiated object' );
		$this->assertEquals( $test_array['test'], $result->get_stored_value( 'test' ) );
	}

	/**
	 * Tests to make sure the object is JSON serialized properly.
	 */
	public function testJsonSerializeValid() {
		$state_store = Sensei_Enrolment_Provider_State_Store::get( 0 );
		$test_array  = [
			'test' => 'Dinosaurs!',
		];

		$state = Sensei_Enrolment_Provider_State::from_array( $state_store, $test_array );

		$this->assertEquals( \wp_json_encode( $test_array ), \wp_json_encode( $state ) );
	}

	/**
	 * Test to make sure we can get a stored data value that has been set.
	 */
	public function testGetStoredValueThatHasBeenSet() {
		$state_store = Sensei_Enrolment_Provider_State_Store::get( 0 );
		$test_array  = [
			'test' => 'value',
		];

		$state = Sensei_Enrolment_Provider_State::from_array( $state_store, $test_array );

		$this->assertEquals( $test_array['test'], $state->get_stored_value( 'test' ) );
	}

	/**
	 * Test to make sure data values that have not been set return as null.
	 */
	public function testGetStoredValueThatHasNotBeenSet() {
		$state_store = Sensei_Enrolment_Provider_State_Store::get( 0 );
		$test_object = [];
		$state       = Sensei_Enrolment_Provider_State::from_array( $state_store, $test_object );

		$this->assertEquals( null, $state->get_stored_value( 'test' ) );
	}

	/**
	 * Tests to ensure the enrolment status can be set.
	 */
	public function testSetDataValue() {
		$state_store = Sensei_Enrolment_Provider_State_Store::get( 0 );
		$state       = Sensei_Enrolment_Provider_State::create( $state_store );

		$state->set_stored_value( 'test', true );

		$this->assertTrue( $state->get_stored_value( 'test' ) );

		$json_string = \wp_json_encode( $state );
		$this->assertEquals( '{"test":true}', $json_string, 'The set data value should persist when serializing the object' );
	}

	/**
	 * Tests to ensure the enrolment status can be cleared.
	 */
	public function testClearStoredValue() {
		$state_store   = Sensei_Enrolment_Provider_State_Store::get( 0 );
		$initial_state = [];

		$state = Sensei_Enrolment_Provider_State::from_array( $state_store, $initial_state );
		$state->set_stored_value( 'test', true );

		$this->assertTrue( $state->get_stored_value( 'test' ) );

		$state->set_stored_value( 'test', null );
		$this->assertEquals( null, $state->get_stored_value( 'test' ) );

		$json_string     = \wp_json_encode( $state );
		$expected_string = \wp_json_encode( $initial_state );

		$this->assertEquals( $expected_string, $json_string, 'Setting the value to null should persist with data not set when serializing the object' );
	}
}
