<?php

/**
 * Tests for Sensei_Enrolment_Provider_Journal class.
 *
 * @group course-enrolment
 */
class Sensei_Enrolment_Provider_Journal_Test extends WP_UnitTestCase {


	/**
	 * Tests to make sure arrays of serialized data return an instantiated object.
	 *
	 * @dataProvider  serializedArrayProvider
	 */
	public function testFromSerializedArray( $input, $expected_history, $expected_log ) {

		$result = Sensei_Enrolment_Provider_Journal::from_serialized_array( $input );

		$this->assertInstanceOf( 'Sensei_Enrolment_Provider_Journal', $result, 'Serialized data should have returned a instantiated object' );
		$this->assertCount( $expected_history, $result->get_history() );
		$this->assertCount( $expected_log, $result->get_logs() );
	}

	public function serializedArrayProvider() {
		return [
			'history 2 elements, log empty' => [
				[
					'h' => [
						[
							't' => 1586350840,
							's' => false,
						],
						[
							't' => 1586350640,
							's' => true,
						],
					],
					'l' => [],
				],
				2,
				0,
			],
			'history empty, log 2 elements' => [
				[
					'h' => [],
					'l' => [
						[
							't' => 1586350840,
							'm' => 'A log message',
						],
						[
							't' => 1586350640,
							'm' => 'Another message',
						],
					],
				],
				0,
				2,
			],
			'history empty, log empty'      => [
				[
					'h' => [],
					'l' => [],
				],
				0,
				0,
			],
		];
	}

	/**
	 * Tests to make sure invalid JSON strings return false.
	 */
	public function testFromSerializedEmptyArrayFails() {
		$result = Sensei_Enrolment_Provider_Journal::from_serialized_array( [] );

		$this->assertFalse( $result, 'Invalid serialized array should have returned false' );
	}

	/**
	 * Tests to make sure a log message is added and the object is JSON serialized properly.
	 */
	public function testMessageAddedAndSerialized() {
		$journal = Sensei_Enrolment_Provider_Journal::create();

		$journal->add_log_message( 'Test message 1' );
		$journal->add_log_message( 'Test message 2' );

		$journal_array = json_decode( wp_json_encode( $journal ), true );

		$this->assertEquals( 'Test message 2', $journal_array['l'][0]['m'], 'Second message should be the first in the log' );
		$this->assertEquals( 'Test message 1', $journal_array['l'][1]['m'], 'First message should be the second in the log' );
	}

	/**
	 * Tests to make sure that status is updated and the object is JSON serialized properly.
	 */
	public function testStatusUpdatedAndSerialized() {
		$journal = Sensei_Enrolment_Provider_Journal::create();

		$journal->update_enrolment_status( false );
		$journal->update_enrolment_status( false );
		$journal->update_enrolment_status( true );

		$journal_array = json_decode( wp_json_encode( $journal ), true );

		$this->assertCount( 2, $journal_array['h'], 'There should be 2 status updates in history.' );
		$this->assertTrue( $journal_array['h'][0]['s'], 'The current status should be true.' );
		$this->assertFalse( $journal_array['h'][1]['s'], 'The previous status should be false.' );
	}

	/**
	 * Tests to make sure that status is deleted and the object is JSON serialized properly.
	 */
	public function testStatusDeletedAndSerialized() {
		$journal = Sensei_Enrolment_Provider_Journal::create();

		$journal->delete_enrolment_status();

		$journal_array = json_decode( wp_json_encode( $journal ), true );

		$this->assertArrayNotHasKey( 'h', $journal_array, 'Deletion on an empty history should have no effect.' );

		$journal->update_enrolment_status( false );
		$journal->delete_enrolment_status();
		$journal->delete_enrolment_status();

		$journal_array = json_decode( wp_json_encode( $journal ), true );

		$this->assertCount( 2, $journal_array['h'], 'Deleting an already deleted status should have no effect.' );
		$this->assertNull( $journal_array['h'][0]['s'], 'The current status should be deleted.' );
		$this->assertFalse( $journal_array['h'][1]['s'], 'The previous status should be false.' );
	}

	/**
	 * Tests to make sure that status is returned when requested by timestamp.
	 */
	public function testGetStatusWithTimestamp() {
		$journal = Sensei_Enrolment_Provider_Journal::create();

		$journal->update_enrolment_status( false );
		$journal->update_enrolment_status( true );

		$status = $journal->get_status_at( 1586361098 );

		$this->assertNull( $status['enrolment_status'], 'Status with a very old timestamp should be null.' );

		$status = $journal->get_status_at( microtime( true ) );

		$this->assertTrue( $status['enrolment_status'], 'Current status should be true.' );
	}

	/**
	 * Tests to make sure that a maximum number of entries are kept in history and message log.
	 */
	public function testHistoryAndLogIsLimited() {
		$journal = Sensei_Enrolment_Provider_Journal::create();

		tests_add_filter(
			'sensei_enrolment_history_size',
			function () {
				return 3;
			}
		);

		tests_add_filter(
			'sensei_enrolment_message_log_size',
			function () {
				return 2;
			}
		);

		$journal->update_enrolment_status( false );
		$journal->update_enrolment_status( true );
		$journal->update_enrolment_status( false );
		$journal->update_enrolment_status( true );

		$journal->add_log_message( 'Test message' );
		$journal->add_log_message( 'Test message' );
		$journal->add_log_message( 'Test message' );

		$this->assertCount( 3, $journal->get_history(), 'The history size should have a maximum size of 3.' );
		$this->assertCount( 2, $journal->get_logs(), 'The message log size should have a maximum size of 2.' );
	}
}
