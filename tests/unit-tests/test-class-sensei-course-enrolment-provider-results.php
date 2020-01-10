<?php

class Sensei_Class_Course_Enrolment_Provider_Results_Test extends WP_UnitTestCase {

	/**
	 * Setup function.
	 */
	public function setup() {
		parent::setup();

		$this->factory = new Sensei_Factory();
	}

	/**
	 * Tests restoring from a stored JSON string.
	 */
	public function testFromJson() {
		$base = [
			't' => microtime( true ),
			'v' => '###',
			'r' => [
				'testA' => true,
				'testB' => false,
			],
		];

		$access_log = Sensei_Course_Enrolment_Provider_Results::from_json( wp_json_encode( $base ) );
		$this->assertTrue( $access_log instanceof Sensei_Course_Enrolment_Provider_Results );
		$this->assertEquals( round( $base['t'], 3 ), round( $access_log->get_time(), 3 ) );
		$this->assertEquals( $base['v'], $access_log->get_version() );
		$this->assertEquals( $base['r'], $access_log->get_provider_results() );
	}

	/**
	 * Tests for positive enrolment by at least one provider.
	 */
	public function testIsEnrolmentProvidedSinglePositive() {
		$results = new Sensei_Course_Enrolment_Provider_Results(
			[
				'test-provider-a' => true,
				'test-provider-b' => false,
			],
			'#version#'
		);

		$this->assertTrue( $results->is_enrolment_provided(), 'If one provider provides enrolment, they are enroled' );
	}

	/**
	 * Tests for positive enrolment by at least one provider.
	 */
	public function testIsEnrolmentProvidedMultiplePositive() {
		$results = new Sensei_Course_Enrolment_Provider_Results(
			[
				'test-provider-a' => true,
				'test-provider-b' => true,
			],
			'#version#'
		);

		$this->assertTrue( $results->is_enrolment_provided(), 'If at least one provider provides enrolment, they are enroled' );
	}

	/**
	 * Tests for negative enrolment by at least one provider.
	 */
	public function testIsEnrolmentProvidedAllNegative() {
		$results = new Sensei_Course_Enrolment_Provider_Results(
			[
				'test-provider-a' => false,
				'test-provider-b' => false,
			],
			'#version#'
		);

		$this->assertFalse( $results->is_enrolment_provided(), 'If at least one provider provides enrolment, they are enroled' );
	}

	/**
	 * Tests for negative enrolment when there are no providers handling the course.
	 */
	public function testIsEnrolmentProvidedNoProviders() {
		$this->markTestSkipped( 'Until we implement manual enrolment provider, this should be skipped to allow for the temporary null behavior' );

		$results = new Sensei_Course_Enrolment_Provider_Results(
			[],
			'#version#'
		);

		$this->assertFalse( $results->is_enrolment_provided(), 'When there are no providers, this should return false' );
	}
}
