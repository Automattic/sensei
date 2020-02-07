<?php
/**
 * Tests for Sensei_Course_Enrolment_Provider_State class.
 *
 * @group course-enrolment
 */
class Sensei_Course_Enrolment_Provider_State_Test extends WP_UnitTestCase {
	/**
	 * Tests to make sure arrays of serialized data return an instantiated object.
	 */
	public function testFromSerializedArray() {
		$state_set  = Sensei_Course_Enrolment_Provider_State_Set::create();
		$test_array = [
			'd' => [
				'test' => 'Dinosaurs!',
			],
			'l' => [
				[
					microtime( true ),
					'This is a test.',
				],
			],
		];

		$result = Sensei_Course_Enrolment_Provider_State::from_serialized_array( $state_set, $test_array );

		$this->assertTrue( $result instanceof Sensei_Course_Enrolment_Provider_State, 'Serialized data should have returned a instantiated object' );
		$this->assertEquals( $test_array['l'], $result->get_logs() );
		$this->assertEquals( $test_array['d']['test'], $result->get_stored_value( 'test' ) );
	}

	/**
	 * Tests to make sure invalid JSON strings return false.
	 */
	public function testFromSerializedEmptyArrayFails() {
		$state_set = Sensei_Course_Enrolment_Provider_State_Set::create();
		$result    = Sensei_Course_Enrolment_Provider_State::from_serialized_array( $state_set, [] );

		$this->assertFalse( $result, 'Invalid serialized array should have returned false' );
	}

	/**
	 * Tests to make sure the object is JSON serialized properly.
	 */
	public function testJsonSerializeValid() {
		$state_set  = Sensei_Course_Enrolment_Provider_State_Set::create();
		$test_array = [
			'd' => [
				'test' => 'Dinosaurs!',
			],
			'l' => [
				[
					microtime( true ),
					'This is a test.',
				],
			],
		];

		$state = Sensei_Course_Enrolment_Provider_State::from_serialized_array( $state_set, $test_array );

		$this->assertEquals( \wp_json_encode( $test_array ), \wp_json_encode( $state ) );
	}

	/**
	 * Test to make sure we can get a stored data value that has been set.
	 */
	public function testGetStoredValueThatHasBeenSet() {
		$state_set  = Sensei_Course_Enrolment_Provider_State_Set::create();
		$test_array = [
			'd' => [
				'test' => 'value',
			],
		];

		$state = Sensei_Course_Enrolment_Provider_State::from_serialized_array( $state_set, $test_array );

		$this->assertEquals( $test_array['d']['test'], $state->get_stored_value( 'test' ) );
	}

	/**
	 * Test to make sure data values that have not been set return as null.
	 */
	public function testGetStoredValueThatHasNotBeenSet() {
		$state_set   = Sensei_Course_Enrolment_Provider_State_Set::create();
		$test_object = [
			'd' => [],
		];
		$test_string = \wp_json_encode( $test_object );
		$state       = Sensei_Course_Enrolment_Provider_State::from_serialized_array( $state_set, $test_string );

		$this->assertEquals( null, $state->get_stored_value( 'test' ) );
	}

	/**
	 * Tests to ensure the enrolment status can be set.
	 */
	public function testSetDataValue() {
		$state_set = Sensei_Course_Enrolment_Provider_State_Set::create();
		$state     = Sensei_Course_Enrolment_Provider_State::create( $state_set );

		$state->set_stored_value( 'test', true );

		$this->assertTrue( $state->get_stored_value( 'test' ) );

		$json_string = \wp_json_encode( $state );
		$this->assertEquals( '{"d":{"test":true},"l":[]}', $json_string, 'The set data value should persist when serializing the object' );
	}

	/**
	 * Tests to ensure the enrolment status can be cleared.
	 */
	public function testClearStoredValue() {
		$state_set     = Sensei_Course_Enrolment_Provider_State_Set::create();
		$initial_state = [
			'd' => [],
			'l' => [],
		];

		$state = Sensei_Course_Enrolment_Provider_State::from_serialized_array( $state_set, $initial_state );
		$state->set_stored_value( 'test', true );

		$this->assertTrue( $state->get_stored_value( 'test' ) );

		$state->set_stored_value( 'test', null );
		$this->assertEquals( null, $state->get_stored_value( 'test' ) );

		$json_string     = \wp_json_encode( $state );
		$expected_string = \wp_json_encode( $initial_state );

		$this->assertEquals( $expected_string, $json_string, 'Setting the value to null  should persist with data not set when serializing the object' );
	}
}
