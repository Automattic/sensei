<?php
require_once SENSEI_TEST_FRAMEWORK_DIR . '/trait-sensei-course-enrolment-test-helpers.php';

/**
 * Tests for Sensei_Enrolment_Provider_State_Store class.
 *
 * @group course-enrolment
 */
class Sensei_Enrolment_Provider_State_Store_Test extends WP_UnitTestCase {
	/**
	 * Tests to make sure valid json strings result in a valid state set.
	 *
	 * @covers \Sensei_Enrolment_Provider_State_Store::get_provider_state
	 * @covers \Sensei_Enrolment_Provider_State_Store::from_json
	 */
	public function testFromJsonString() {
		$always_provides_provider = new Sensei_Test_Enrolment_Provider_Always_Provides();
		$never_provides_provider  = new Sensei_Test_Enrolment_Provider_Never_Provides();

		$data = [
			$always_provides_provider->get_id() => [
				'd' => [
					'test' => true,
				],
				'l' => [],
			],
			$never_provides_provider->get_id()  => [
				'd' => [],
				'l' => [
					[
						time(),
						'Such a great log message.',
					],
				],
			],
		];

		$data_json   = \wp_json_encode( $data );
		$state_store = \Sensei_Enrolment_Provider_State_Store::from_json( $data_json );

		$this->assertTrue( $state_store instanceof Sensei_Enrolment_Provider_State_Store, 'JSON should have resulted in a valid state set' );

		$always_provides_state = $state_store->get_provider_state( $always_provides_provider );
		$this->assertTrue( $always_provides_state->get_stored_value( 'test' ), 'Provider state should have been initialized with a stored value test as true' );

		$never_provides_state = $state_store->get_provider_state( $never_provides_provider );
		$logs                 = $never_provides_state->get_logs();
		$this->assertEquals( $data['never-provides']['l'][0][1], $logs[0][1], 'Never provides provider should have a log entry' );
	}

	/**
	 * Tests to make sure valid json strings result in a valid state set.
	 *
	 * @covers \Sensei_Enrolment_Provider_State_Store::get_provider_state
	 * @covers \Sensei_Enrolment_Provider_State_Store::from_json
	 */
	public function testSerializedJsonValid() {
		$always_provides_provider = new Sensei_Test_Enrolment_Provider_Always_Provides();
		$never_provides_provider  = new Sensei_Test_Enrolment_Provider_Never_Provides();

		$data = [
			$always_provides_provider->get_id() => [
				'd' => [
					'test' => true,
				],
				'l' => [],
			],
			$never_provides_provider->get_id()  => [
				'd' => [],
				'l' => [
					[
						time(),
						'Such a great log message.',
					],
				],
			],
		];

		$data_json        = \wp_json_encode( $data );
		$state_store      = \Sensei_Enrolment_Provider_State_Store::from_json( $data_json );
		$state_store_json = \wp_json_encode( $state_store );

		$this->assertEquals( $data_json, $state_store_json, 'Serialized state set should equal the initial state' );
	}

	/**
	 * Test setting the has changed state when making changes to the provider states within the set.
	 *
	 * @covers \Sensei_Enrolment_Provider_State_Store::set_has_changed
	 * @covers \Sensei_Enrolment_Provider_State_Store::get_has_changed
	 * @covers \Sensei_Enrolment_Provider_State::set_stored_value
	 * @covers \Sensei_Enrolment_Provider_State::add_log_message
	 */
	public function testHasChangedStates() {
		$always_provides_provider = new Sensei_Test_Enrolment_Provider_Always_Provides();
		$state_store              = Sensei_Enrolment_Provider_State_Store::create();
		$provider_state           = $state_store->get_provider_state( $always_provides_provider );

		// Note that we only count new providers as a change after something changes within them.
		$this->assertFalse( $state_store->get_has_changed(), 'Nothing has changed in the provider state set yet' );

		$provider_state->set_stored_value( 'test', true );
		$this->assertTrue( $state_store->get_has_changed(), 'State set should be marked as having had changed after setting data value' );
		$state_store->set_has_changed( false );

		$this->assertFalse( $state_store->get_has_changed(), 'Has Changed status should have been set to false.' );
		$provider_state->add_log_message( 'Test log message' );
		$this->assertTrue( $state_store->get_has_changed(), 'State set should be marked as having had changed after adding log entry' );
	}
}
