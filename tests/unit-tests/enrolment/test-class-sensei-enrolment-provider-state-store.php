<?php

/**
 * Tests for Sensei_Enrolment_Provider_State_Store class.
 *
 * @group course-enrolment
 */
class Sensei_Enrolment_Provider_State_Store_Test extends WP_UnitTestCase {
	use Sensei_Course_Enrolment_Test_Helpers;

	/**
	 * Setup function.
	 */
	public function setUp() {
		parent::setUp();

		$this->factory = new Sensei_Factory();

		self::resetEnrolmentStateStores();
		self::resetEnrolmentProviders();
	}

	/**
	 * Clean up after all tests.
	 */
	public static function tearDownAfterClass() {
		parent::tearDownAfterClass();

		self::resetEnrolmentStateStores();
		self::resetEnrolmentProviders();
	}

	/**
	 * Tests to make sure instances are preserved.
	 */
	public function testGetCachesCorrectly() {
		$instance_a_1 = Sensei_Enrolment_Provider_State_Store::get( 1, 1 );
		$instance_a_2 = Sensei_Enrolment_Provider_State_Store::get( 1, 1 );
		$instance_b_1 = Sensei_Enrolment_Provider_State_Store::get( 1, 2 );
		$instance_c_1 = Sensei_Enrolment_Provider_State_Store::get( 2, 1 );

		$this->assertEquals( $instance_a_1, $instance_a_2, 'Instances with same user and course IDs should be exact match' );
		$this->assertNotEquals( $instance_a_1, $instance_b_1, 'Instances with different user IDs should be different' );
		$this->assertNotEquals( $instance_a_1, $instance_c_1, 'Instances with different course IDs should be different' );
		$this->assertNotEquals( $instance_b_1, $instance_c_1, 'Instances with different course and user IDs should be different' );
	}

	/**
	 * Tests to make sure valid json strings result in a valid state store.
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
		$state_store = $this->getStateStoreFromJSON( $data_json );

		$this->assertTrue( $state_store instanceof Sensei_Enrolment_Provider_State_Store, 'JSON should have resulted in a valid state store' );

		$always_provides_state = $state_store->get_provider_state( $always_provides_provider );
		$this->assertTrue( $always_provides_state->get_stored_value( 'test' ), 'Provider state should have been initialized with a stored value test as true' );

		$never_provides_state = $state_store->get_provider_state( $never_provides_provider );
		$logs                 = $never_provides_state->get_logs();
		$this->assertEquals( $data['never-provides']['l'][0][1], $logs[0][1], 'Never provides provider should have a log entry' );
	}

	/**
	 * Tests to make sure valid json strings result in a valid state store.
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
		$state_store      = $this->getStateStoreFromJSON( $data_json );
		$state_store_json = \wp_json_encode( $state_store );

		$this->assertEquals( $data_json, $state_store_json, 'Serialized state store should equal the initial state' );
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
		$state_store              = Sensei_Enrolment_Provider_State_Store::get( 0, 0 );
		$provider_state           = $state_store->get_provider_state( $always_provides_provider );

		// Note that we only count new providers as a change after something changes within them.
		$this->assertFalse( $state_store->get_has_changed(), 'Nothing has changed in the provider state store yet' );

		$provider_state->set_stored_value( 'test', true );
		$this->assertTrue( $state_store->get_has_changed(), 'State store should be marked as having had changed after setting data value' );
		$state_store->set_has_changed( false );

		$this->assertFalse( $state_store->get_has_changed(), 'Has Changed status should have been set to false.' );
		$provider_state->add_log_message( 'Test log message' );
		$this->assertTrue( $state_store->get_has_changed(), 'State store should be marked as having had changed after adding log entry' );
	}

	/**
	 * Get state store from JSON.
	 *
	 * @param string $json_str
	 * @param int    $user_id
	 * @param int    $course_id
	 *
	 * @return Sensei_Enrolment_Provider_State_Store
	 */
	private function getStateStoreFromJSON( $json_str, $user_id = 0, $course_id = 0 ) {
		$store  = Sensei_Enrolment_Provider_State_Store::get( $user_id, $course_id );
		$method = new ReflectionMethod( Sensei_Enrolment_Provider_State_Store::class, 'restore_from_json' );
		$method->setAccessible( true );
		$method->invoke( $store, $json_str );

		return $store;
	}

	/**
	 * Tests persisting of state stores when there is a change.
	 */
	public function testPersistStateSetsWhenChange() {
		$course_id     = $this->getSimpleCourse();
		$student_id    = $this->createStandardStudent();
		$persisted_set = '{"always-provides":{"d":{"test":1234},"l":[[1581098440,"This is a log message"]]}}';
		update_user_meta( $student_id, Sensei_Enrolment_Provider_State_Store::META_PREFIX_ENROLMENT_PROVIDERS_STATE . $course_id, $persisted_set );

		$provider_class = Sensei_Test_Enrolment_Provider_Always_Provides::class;
		$this->addEnrolmentProvider( $provider_class );
		$this->prepareEnrolmentManager();

		$enrolment_manager = Sensei_Course_Enrolment_Manager::instance();
		$provider          = $enrolment_manager->get_enrolment_provider_by_id( $provider_class::ID );
		$course_enrolment  = Sensei_Course_Enrolment::get_course_instance( $course_id );
		$provider_state    = $course_enrolment->get_provider_state( $provider, $student_id );
		$provider_state->set_stored_value( 'test', 54321 );
		$provider_state->save();

		$expected_persisted_set = '{"always-provides":{"d":{"test":54321},"l":[[1581098440,"This is a log message"]]}}';
		$persisted_set          = get_user_meta( $student_id, Sensei_Enrolment_Provider_State_Store::META_PREFIX_ENROLMENT_PROVIDERS_STATE . $course_id, true );
		$this->assertEquals( $expected_persisted_set, $persisted_set, 'The changed stored value should have been persisted' );
	}

	/**
	 * Tests not persisting of state stores when there isn't a change.
	 */
	public function testMpPersistStateSetsWhenMpChange() {
		$course_id     = $this->getSimpleCourse();
		$student_id    = $this->createStandardStudent();
		$persisted_set = '{"always-provides":{"d":{"test":1234},"l":[[1581098440,"This is a log message"]]}}';
		update_user_meta( $student_id, Sensei_Enrolment_Provider_State_Store::META_PREFIX_ENROLMENT_PROVIDERS_STATE . $course_id, $persisted_set );

		$provider_class = Sensei_Test_Enrolment_Provider_Always_Provides::class;
		$this->addEnrolmentProvider( $provider_class );
		$this->prepareEnrolmentManager();

		$enrolment_manager = Sensei_Course_Enrolment_Manager::instance();
		$provider          = $enrolment_manager->get_enrolment_provider_by_id( $provider_class::ID );
		$course_enrolment  = Sensei_Course_Enrolment::get_course_instance( $course_id );
		$provider_state    = $course_enrolment->get_provider_state( $provider, $student_id );

		// Background remove user meta.
		delete_user_meta( $student_id, Sensei_Enrolment_Provider_State_Store::META_PREFIX_ENROLMENT_PROVIDERS_STATE . $course_id );

		// This isn't a change in the stored value.
		$provider_state->set_stored_value( 'test', 1234 );
		$provider_state->get_logs();
		$provider_state->get_stored_value( 'new-key' );
		$provider_state->save();

		$expected_persisted_set = null;
		$persisted_set          = get_user_meta( $student_id, Sensei_Enrolment_Provider_State_Store::META_PREFIX_ENROLMENT_PROVIDERS_STATE . $course_id, true );
		$this->assertEquals( $expected_persisted_set, $persisted_set, 'The state stores should NOT have been persisted without a change' );
	}

}
