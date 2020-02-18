<?php
/**
 * Tests for Sensei_Enrolment_Provider_State class.
 *
 * @group course-enrolment
 */
class Sensei_Enrolment_Provider_State_Test extends WP_UnitTestCase {
	/**
	 * Tests to make sure arrays of serialized data return an instantiated object.
	 */
	public function testFromSerializedArray() {
		$state_set  = Sensei_Enrolment_Provider_State_Store::create();
		$test_array = [
			'd' => [
				'test' => 'Dinosaurs!',
			],
			'l' => [
				[
					time(),
					'This is a test.',
				],
			],
		];

		$result = Sensei_Enrolment_Provider_State::from_serialized_array( $state_set, $test_array );

		$this->assertTrue( $result instanceof Sensei_Enrolment_Provider_State, 'Serialized data should have returned a instantiated object' );
		$this->assertEquals( $test_array['l'], $result->get_logs() );
		$this->assertEquals( $test_array['d']['test'], $result->get_stored_value( 'test' ) );
	}

	/**
	 * Tests to make sure invalid JSON strings return false.
	 */
	public function testFromSerializedEmptyArrayFails() {
		$state_set = Sensei_Enrolment_Provider_State_Store::create();
		$result    = Sensei_Enrolment_Provider_State::from_serialized_array( $state_set, [] );

		$this->assertFalse( $result, 'Invalid serialized array should have returned false' );
	}

	/**
	 * Tests to make sure the object is JSON serialized properly.
	 */
	public function testJsonSerializeValid() {
		$state_set  = Sensei_Enrolment_Provider_State_Store::create();
		$test_array = [
			'd' => [
				'test' => 'Dinosaurs!',
			],
			'l' => [
				[
					time(),
					'This is a test.',
				],
			],
		];

		$state = Sensei_Enrolment_Provider_State::from_serialized_array( $state_set, $test_array );

		$this->assertEquals( \wp_json_encode( $test_array ), \wp_json_encode( $state ) );
	}

	/**
	 * Test to make sure we can get a stored data value that has been set.
	 */
	public function testGetStoredValueThatHasBeenSet() {
		$state_set  = Sensei_Enrolment_Provider_State_Store::create();
		$test_array = [
			'd' => [
				'test' => 'value',
			],
		];

		$state = Sensei_Enrolment_Provider_State::from_serialized_array( $state_set, $test_array );

		$this->assertEquals( $test_array['d']['test'], $state->get_stored_value( 'test' ) );
	}

	/**
	 * Test to make sure data values that have not been set return as null.
	 */
	public function testGetStoredValueThatHasNotBeenSet() {
		$state_set   = Sensei_Enrolment_Provider_State_Store::create();
		$test_object = [
			'd' => [],
		];
		$test_string = \wp_json_encode( $test_object );
		$state       = Sensei_Enrolment_Provider_State::from_serialized_array( $state_set, $test_string );

		$this->assertEquals( null, $state->get_stored_value( 'test' ) );
	}

	/**
	 * Tests to ensure the enrolment status can be set.
	 */
	public function testSetDataValue() {
		$state_set = Sensei_Enrolment_Provider_State_Store::create();
		$state     = Sensei_Enrolment_Provider_State::create( $state_set );

		$state->set_stored_value( 'test', true );

		$this->assertTrue( $state->get_stored_value( 'test' ) );

		$json_string = \wp_json_encode( $state );
		$this->assertEquals( '{"d":{"test":true},"l":[]}', $json_string, 'The set data value should persist when serializing the object' );
	}

	/**
	 * Tests to ensure the enrolment status can be cleared.
	 */
	public function testClearStoredValue() {
		$state_set     = Sensei_Enrolment_Provider_State_Store::create();
		$initial_state = [
			'd' => [],
			'l' => [],
		];

		$state = Sensei_Enrolment_Provider_State::from_serialized_array( $state_set, $initial_state );
		$state->set_stored_value( 'test', true );

		$this->assertTrue( $state->get_stored_value( 'test' ) );

		$state->set_stored_value( 'test', null );
		$this->assertEquals( null, $state->get_stored_value( 'test' ) );

		$json_string     = \wp_json_encode( $state );
		$expected_string = \wp_json_encode( $initial_state );

		$this->assertEquals( $expected_string, $json_string, 'Setting the value to null should persist with data not set when serializing the object' );
	}

	/**
	 * Tests logging a simple message.
	 */
	public function testAddLogMessage() {
		$state_set = Sensei_Enrolment_Provider_State_Store::create();
		$state     = Sensei_Enrolment_Provider_State::create( $state_set );

		$test_message = 'I really hope this works';
		$state->add_log_message( $test_message );
		$logs = $state->get_logs();

		$this->assertEquals( $logs[0][1], $test_message, 'The message should match what was added' );
	}

	/**
	 * Tests pruning the most recent 30 messages on log add.
	 */
	public function testPruneOnAddLog() {
		$time         = time();
		$initial_data = [
			'l' => [
				[
					$time,
					'This is the newest message',
				],
				[
					$time - 1000,
					'This is the oldest message',
				],
				[
					$time - 999,
					'This is the second oldest message',
				],
			],
		];
		$logs_entries = array_fill( 0, 27, 'Dinosaur in the middle!' );
		foreach( $logs_entries as $l ) {
			$initial_data['l'][] = [
				wp_rand( time() - 997, time() - 1 ),
				$l,
			];
		}


		$state_set = Sensei_Enrolment_Provider_State_Store::create();
		$state     = Sensei_Enrolment_Provider_State::from_serialized_array( $state_set, $initial_data );

		$state->add_log_message( 'This should cause a pruning' );
		$logs = $state->get_logs();

		$this->assertEquals( 30, count( $logs ), 'The log should have been pruned to just 30 entries.' );
		$this->assertEquals( $initial_data['l'][2], $logs[0], 'The first log entry should be the original second oldest' );
		$this->assertEquals( $initial_data['l'][0], $logs[28], 'The second to last log entry should be the newest original' );
		$this->assertEquals( 'This should cause a pruning', $logs[29][1], 'The last log entry should be the entry we just added' );
	}

	/**
	 * Tests getting log message with oldest at the top.
	 */
	public function testGetLogs() {
		$time         = time();
		$initial_data = [
			'l' => [
				[
					$time,
					'This is the newest message',
				],
				[
					$time - 10,
					'This is the oldest message',
				],
				[
					$time - 5,
					'This is the third oldest message',
				],
				[
					$time - 7,
					'This is the second oldest message',
				],
			],
		];

		$state_set = Sensei_Enrolment_Provider_State_Store::create();
		$state     = Sensei_Enrolment_Provider_State::from_serialized_array( $state_set, $initial_data );

		$logs = $state->get_logs();

		$this->assertEquals( $initial_data['l'][1], $logs[0], 'The first log entry should be the oldest' );
		$this->assertEquals( $initial_data['l'][3], $logs[1], 'The second log entry should be the second oldest' );
		$this->assertEquals( $initial_data['l'][2], $logs[2], 'The third log entry should be the third oldest' );
		$this->assertEquals( $initial_data['l'][0], $logs[3], 'The last log entry should be the newest' );
	}
}
