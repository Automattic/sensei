<?php

/**
 * Tests for Sensei_Enrolment_Provider_Journal_Store class.
 *
 * @group course-enrolment
 *
 * @property Sensei_Factory $factory
 */
class Sensei_Enrolment_Provider_Journal_Store_Test extends WP_UnitTestCase {
	use Sensei_Course_Enrolment_Test_Helpers;


	/**
	 * Setup function.
	 */
	public function setUp() {
		parent::setUp();

		$this->factory = new Sensei_Factory();

		self::resetEnrolmentJournalStores();
		self::resetEnrolmentProviders();
	}

	/**
	 * Clean up after all tests.
	 */
	public static function tearDownAfterClass() {
		parent::tearDownAfterClass();

		self::resetEnrolmentJournalStores();
		self::resetEnrolmentProviders();
	}

	/**
	 * Tests that nothing is stored when the journal is disabled.
	 */
	public function testEnableJournalFilter() {
		$course   = $this->factory->course->create();
		$user     = $this->factory->user->create();
		$provider = new Sensei_Test_Enrolment_Provider_Always_Provides();

		Sensei_Enrolment_Provider_Journal_Store::add_provider_log_message( $provider, $user, $course, 'Test message' );
		Sensei_Enrolment_Provider_Journal_Store::persist_all();

		$user_meta = get_user_meta( $user, Sensei_Enrolment_Provider_Journal_Store::get_provider_journal_store_meta_key() );
		$this->assertEmpty( $user_meta, 'Nothing should be stored with the filter returning false.' );

		$this->enableJournal();
		Sensei_Enrolment_Provider_Journal_Store::persist_all();

		$user_meta = get_user_meta( $user, Sensei_Enrolment_Provider_Journal_Store::get_provider_journal_store_meta_key() );
		$this->assertNotEmpty( $user_meta, 'Meta should be stored with the filter returning true.' );
	}

	/**
	 * Tests to make sure that messages are stored in the db correctly.
	 */
	public function testMessagesAreStored() {
		$course   = $this->factory->course->create();
		$user     = $this->factory->user->create();
		$provider = new Sensei_Test_Enrolment_Provider_Always_Provides();
		$this->enableJournal();

		Sensei_Enrolment_Provider_Journal_Store::add_provider_log_message( $provider, $user, $course, 'First message' );
		Sensei_Enrolment_Provider_Journal_Store::add_provider_log_message( $provider, $user, $course, 'Second message' );
		Sensei_Enrolment_Provider_Journal_Store::persist_all();

		$user_meta = get_user_meta( $user, Sensei_Enrolment_Provider_Journal_Store::get_provider_journal_store_meta_key(), true );
		$this->assertRegExp( '/.*always-provides.*Second message.*First message/', $user_meta, 'A meta with the provider id and the two messages should be stored.' );

		$logs = Sensei_Enrolment_Provider_Journal_Store::get_provider_logs( $provider, $user, $course );
		$this->assertCount( 2, $logs, 'There should be exactly 2 messages in the logs' );
		$this->assertEquals( 'Second message', $logs[0]['message'], 'The second message should be in the beginning of the log.' );
		$this->assertEquals( 'First message', $logs[1]['message'], 'The first message should be in the end of the log.' );
	}

	/**
	 * Tests that the provider enrolment status is stored correctly.
	 */
	public function testEnrolmentHistoryIsStored() {
		$course = $this->factory->course->create();
		$user   = $this->factory->user->create();
		$this->enableJournal();

		$provider_results = [
			'manual' => true,
			'simple' => false,
		];

		Sensei_Enrolment_Provider_Journal_Store::register_possible_enrolment_change(
			new Sensei_Course_Enrolment_Provider_Results( $provider_results, 'thehash' ),
			$user,
			$course
		);
		Sensei_Enrolment_Provider_Journal_Store::persist_all();

		$user_meta = get_user_meta( $user, Sensei_Enrolment_Provider_Journal_Store::get_provider_journal_store_meta_key(), true );
		$this->assertRegExp( '/.*manual.*s.*true/', $user_meta, 'Manual provider status should be true' );
		$this->assertRegExp( '/.*simple.*s.*false/', $user_meta, 'Simple provider status should be true' );

		$provider_results = [
			'manual' => false,
			'simple' => true,
		];

		Sensei_Enrolment_Provider_Journal_Store::register_possible_enrolment_change(
			new Sensei_Course_Enrolment_Provider_Results( $provider_results, 'thehash' ),
			$user,
			$course
		);
		Sensei_Enrolment_Provider_Journal_Store::persist_all();

		$user_meta = get_user_meta( $user, Sensei_Enrolment_Provider_Journal_Store::get_provider_journal_store_meta_key(), true );
		$this->assertRegExp( '/.*manual.*s.*false.*s.*true/', $user_meta, 'Manual provider status should be initially true then false' );
		$this->assertRegExp( '/.*simple.*s.*true.*s.*false/', $user_meta, 'Simple provider status should be initially false then true' );
	}

	/**
	 * Tests that the user meta is stored only after the user becomes enrolled to a course.
	 */
	public function testEnrolmentHistoryIsStoredAfterEnrolment() {
		$course = $this->factory->course->create();
		$user   = $this->factory->user->create();
		$this->enableJournal();

		// Test that when the user is not enrolled, no meta is stored.
		$provider_results = [
			'manual' => false,
			'simple' => false,
		];

		Sensei_Enrolment_Provider_Journal_Store::register_possible_enrolment_change(
			new Sensei_Course_Enrolment_Provider_Results( $provider_results, 'thehash' ),
			$user,
			$course
		);
		Sensei_Enrolment_Provider_Journal_Store::persist_all();

		$user_meta = get_user_meta( $user, Sensei_Enrolment_Provider_Journal_Store::get_provider_journal_store_meta_key(), true );
		$this->assertEmpty( $user_meta, 'Meta should not be stored if the user is not enrolled to any courses.' );

		// Test that after the user gets enrolled, the meta is stored.
		$provider_results = [
			'manual' => false,
			'simple' => true,
		];

		Sensei_Enrolment_Provider_Journal_Store::register_possible_enrolment_change(
			new Sensei_Course_Enrolment_Provider_Results( $provider_results, 'thehash' ),
			$user,
			$course
		);
		Sensei_Enrolment_Provider_Journal_Store::persist_all();

		$user_meta = get_user_meta( $user, Sensei_Enrolment_Provider_Journal_Store::get_provider_journal_store_meta_key(), true );
		$this->assertNotEmpty( $user_meta, 'Meta should be stored after the user gets enrolled to the courses.' );

		// Test that changes are stored after the user gets enrolled.
		$provider_results = [
			'manual' => false,
			'simple' => false,
		];

		Sensei_Enrolment_Provider_Journal_Store::register_possible_enrolment_change(
			new Sensei_Course_Enrolment_Provider_Results( $provider_results, 'thehash' ),
			$user,
			$course
		);
		Sensei_Enrolment_Provider_Journal_Store::persist_all();

		$user_meta = get_user_meta( $user, Sensei_Enrolment_Provider_Journal_Store::get_provider_journal_store_meta_key(), true );
		$this->assertRegExp( '/.*manual.*s.*false.*s.*false/', $user_meta, 'Manual provider status should be false in both entries.' );
		$this->assertRegExp( '/.*simple.*s.*false.*s.*true/', $user_meta, 'Simple provider status should be initially true then false.' );

		// Test that if a user is enrolled to a course, nothing is stored for courses that is unenrolled.
		$second_course    = $this->factory->course->create();
		$provider_results = [
			'manual' => false,
			'simple' => false,
		];

		Sensei_Enrolment_Provider_Journal_Store::register_possible_enrolment_change(
			new Sensei_Course_Enrolment_Provider_Results( $provider_results, 'thehash' ),
			$user,
			$second_course
		);
		Sensei_Enrolment_Provider_Journal_Store::persist_all();

		$user_meta     = get_user_meta( $user, Sensei_Enrolment_Provider_Journal_Store::get_provider_journal_store_meta_key(), true );
		$journal_array = json_decode( $user_meta, true );
		$this->assertArrayHasKey( $course, $journal_array );
		$this->assertArrayNotHasKey( $second_course, $journal_array );
	}

	/**
	 * Tests that the history is stored after a message was added to the log.
	 */
	public function testEnrolmentHistoryIsStoredAfterMessageIsAdded() {
		$course   = $this->factory->course->create();
		$user     = $this->factory->user->create();
		$provider = new Sensei_Test_Enrolment_Provider_Always_Provides();
		$this->enableJournal();

		Sensei_Enrolment_Provider_Journal_Store::add_provider_log_message( $provider, $user, $course, 'First message' );

		$provider_results = [
			'manual' => false,
			'simple' => false,
		];

		Sensei_Enrolment_Provider_Journal_Store::register_possible_enrolment_change(
			new Sensei_Course_Enrolment_Provider_Results( $provider_results, 'thehash' ),
			$user,
			$course
		);
		Sensei_Enrolment_Provider_Journal_Store::persist_all();

		$user_meta = get_user_meta( $user, Sensei_Enrolment_Provider_Journal_Store::get_provider_journal_store_meta_key(), true );
		$this->assertRegExp( '/.*manual.*s.*false/', $user_meta, 'Manual provider status should be stored.' );
		$this->assertRegExp( '/.*simple.*s.*false/', $user_meta, 'Simple provider status should be stored.' );
	}

	/**
	 * Tests that only the changes are stored in enrolment history.
	 */
	public function testOnlyChangesAreStoredInHistory() {
		$course = $this->factory->course->create();
		$user   = $this->factory->user->create();
		$this->enableJournal();
		$manual_provider = Sensei_Course_Manual_Enrolment_Provider::instance();
		$never_provides  = new Sensei_Test_Enrolment_Provider_Never_Provides();
		$denies_crooks   = new Sensei_Test_Enrolment_Provider_Denies_Crooks();

		$provider_results = [
			'manual'         => true,
			'never-provides' => false,
			'denies-crooks'  => false,
		];

		Sensei_Enrolment_Provider_Journal_Store::register_possible_enrolment_change(
			new Sensei_Course_Enrolment_Provider_Results( $provider_results, 'thehash' ),
			$user,
			$course
		);

		Sensei_Enrolment_Provider_Journal_Store::register_possible_enrolment_change(
			new Sensei_Course_Enrolment_Provider_Results( $provider_results, 'thehash' ),
			$user,
			$course
		);

		$provider_results = [
			'manual'         => false,
			'never-provides' => false,
		];

		Sensei_Enrolment_Provider_Journal_Store::register_possible_enrolment_change(
			new Sensei_Course_Enrolment_Provider_Results( $provider_results, 'thehash' ),
			$user,
			$course
		);

		$manual_history = Sensei_Enrolment_Provider_Journal_Store::get_provider_history( $manual_provider, $user, $course );
		$this->assertCount( 2, $manual_history, 'There should be 2 changes in manual provider status.' );
		$this->assertFalse( $manual_history[0]['enrolment_status'], 'The current status should be false.' );
		$this->assertTrue( $manual_history[1]['enrolment_status'], 'The previous status should be true.' );

		$simple_history = Sensei_Enrolment_Provider_Journal_Store::get_provider_history( $never_provides, $user, $course );
		$this->assertCount( 1, $simple_history, 'There should be 1 change in simple provider status.' );
		$this->assertFalse( $simple_history[0]['enrolment_status'], 'The current status should be false.' );

		$memberships_history = Sensei_Enrolment_Provider_Journal_Store::get_provider_history( $denies_crooks, $user, $course );
		$this->assertCount( 2, $memberships_history, 'There should be 2 changes in memberships provider status.' );
		$this->assertNull( $memberships_history[0]['enrolment_status'], 'The current status should be deleted.' );
		$this->assertFalse( $memberships_history[1]['enrolment_status'], 'The previous status should be false.' );
	}

	/**
	 * Tests that Sensei_Enrolment_Provider_Journal_Store::get_enrolment_snanpshot returns correct snapshots.
	 */
	public function testHistorySnapshotsWithDeletions() {
		$course = $this->factory->course->create();
		$user   = $this->factory->user->create();
		$this->enableJournal();

		$provider_results = [
			'manual' => true,
			'simple' => false,
		];

		Sensei_Enrolment_Provider_Journal_Store::register_possible_enrolment_change(
			new Sensei_Course_Enrolment_Provider_Results( $provider_results, 'thehash' ),
			$user,
			$course
		);
		$after_first_change = microtime( true );
		usleep( 10000 );

		$provider_results = [
			'memberships' => false,
		];

		Sensei_Enrolment_Provider_Journal_Store::register_possible_enrolment_change(
			new Sensei_Course_Enrolment_Provider_Results( $provider_results, 'thehash' ),
			$user,
			$course
		);
		$after_second_change = microtime( true );
		usleep( 10000 );

		$provider_results = [
			'manual'      => false,
			'memberships' => true,
		];

		Sensei_Enrolment_Provider_Journal_Store::register_possible_enrolment_change(
			new Sensei_Course_Enrolment_Provider_Results( $provider_results, 'thehash' ),
			$user,
			$course
		);

		$first_change_snapshot = Sensei_Enrolment_Provider_Journal_Store::get_enrolment_snanpshot( $user, $course, $after_first_change );
		$this->assertCount( 2, $first_change_snapshot, 'There should be 2 providers in the snapshot.' );
		$this->assertTrue( $first_change_snapshot['manual'], 'The manual provider status should be true.' );
		$this->assertFalse( $first_change_snapshot['simple'], 'The simple provider status should be false.' );

		$second_change_snapshot = Sensei_Enrolment_Provider_Journal_Store::get_enrolment_snanpshot( $user, $course, $after_second_change );
		$this->assertCount( 1, $second_change_snapshot, 'There should be 1 provider in the snapshot.' );
		$this->assertFalse( $second_change_snapshot['memberships'], 'The memberships provider status should be false.' );

		$current_snapshot = Sensei_Enrolment_Provider_Journal_Store::get_enrolment_snanpshot( $user, $course );
		$this->assertCount( 2, $current_snapshot, 'There should be 2 providers in the snapshot.' );
		$this->assertTrue( $current_snapshot['memberships'], 'The memberships provider status should be true.' );
		$this->assertFalse( $current_snapshot['manual'], 'The manual provider status should be false.' );
	}

	/**
	 * Tests that Sensei_Enrolment_Provider_Journal_Store::get_enrolment_snanpshot returns correct snapshots after the
	 * timestamps are rounded.
	 */
	public function testHistorySnapshotsRoundings() {
		$course = $this->factory->course->create();
		$user   = $this->factory->user->create();
		$this->enableJournal();

		$method = new ReflectionMethod( Sensei_Enrolment_Provider_Journal_Store::class, 'get' );
		$method->setAccessible( true );
		$journal_store = $method->invoke( null, $user, $course );

		$journal_json = <<<EOT
			{
				"$course": {
					"manual": {
						"h": [
								{
									"t": 1586530073.434888,
									"s": false
								},
								{
									"t": 1586530073.432448,
									"s": true
								}
							],
						"l": []
					},
					"memberships": {
						"h": [
								{
									"t": 1586530073.434444,
									"s": null
								},
								{
									"t": 1586530073.431507,
									"s": false
								}
							],
						"l": []
					}
				}
			}
EOT;

		$method = new ReflectionMethod( $journal_store, 'restore_from_json' );
		$method->setAccessible( true );
		$method->invoke( $journal_store, $journal_json );

		$state_store_instances = new ReflectionProperty( Sensei_Enrolment_Provider_Journal_Store::class, 'instances' );
		$state_store_instances->setAccessible( true );
		$state_store_instances->setValue( [ $user => $journal_store ] );

		$current_snapshot = Sensei_Enrolment_Provider_Journal_Store::get_enrolment_snanpshot( $user, $course, 1586530073.434586 );
		$this->assertCount( 1, $current_snapshot, 'There should be 1 provider in the snapshot.' );
		$this->assertFalse( $current_snapshot['manual'], 'The manual provider status should be false.' );

		$previous_snapshot = Sensei_Enrolment_Provider_Journal_Store::get_enrolment_snanpshot( $user, $course, 1586530073.432801 );
		$this->assertCount( 2, $previous_snapshot, 'There should be 2 providers in the snapshot.' );
		$this->assertTrue( $previous_snapshot['manual'], 'The manual provider status should be true.' );
		$this->assertFalse( $previous_snapshot['memberships'], 'The memberships provider status should be false.' );
	}

	/**
	 * Tests that the JSON is parsed correctly when there are 2 courses.
	 */
	public function testJournalWithManyCourses() {
		$courses = $this->factory->course->create_many( 2 );
		$user    = $this->factory->user->create();
		$this->enableJournal();

		$method = new ReflectionMethod( Sensei_Enrolment_Provider_Journal_Store::class, 'get' );
		$method->setAccessible( true );
		$journal_store = $method->invoke( null, $user );

		$journal_json = <<<EOT
			{
				"$courses[0]": {
					"manual": {
						"h": [
								{
									"t": 1586530073.432448,
									"s": true
								}
							],
						"l": []
					}
				},
				"$courses[1]": {
					"denies-crooks": {
						"h": [],
						"l": [
							{
								"t": 1586530073,
								"m": "Meaningful message"
							}
						]
					}
				}
			}
EOT;

		$method = new ReflectionMethod( $journal_store, 'restore_from_json' );
		$method->setAccessible( true );
		$method->invoke( $journal_store, $journal_json );

		$state_store_instances = new ReflectionProperty( Sensei_Enrolment_Provider_Journal_Store::class, 'instances' );
		$state_store_instances->setAccessible( true );
		$state_store_instances->setValue( [ $user => $journal_store ] );

		$current_snapshot = Sensei_Enrolment_Provider_Journal_Store::get_enrolment_snanpshot( $user, $courses[0] );
		$this->assertCount( 1, $current_snapshot, 'There should be 1 provider in the snapshot.' );
		$this->assertTrue( $current_snapshot['manual'], 'The manual provider status should be true.' );

		$logs = Sensei_Enrolment_Provider_Journal_Store::get_provider_logs( Sensei_Course_Manual_Enrolment_Provider::instance(), $user, $courses[0] );
		$this->assertCount( 0, $logs, 'There should no messages in the logs' );

		$current_snapshot = Sensei_Enrolment_Provider_Journal_Store::get_enrolment_snanpshot( $user, $courses[1] );
		$this->assertCount( 0, $current_snapshot, 'There should be no providers in the snapshot.' );

		$logs = Sensei_Enrolment_Provider_Journal_Store::get_provider_logs( new Sensei_Test_Enrolment_Provider_Denies_Crooks(), $user, $courses[1] );
		$this->assertCount( 1, $logs, 'There should be exactly 1 message in the logs' );
		$this->assertEquals( 'Meaningful message', $logs[0]['message'] );
	}

	private function enableJournal() {
		tests_add_filter(
			'sensei_enable_enrolment_provider_journal',
			function () {
				return true;
			}
		);
	}
}
