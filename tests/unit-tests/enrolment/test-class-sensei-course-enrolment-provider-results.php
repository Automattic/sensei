<?php
/**
 * Tests for Sensei_Course_Enrolment_Provider_Results class.
 *
 * @group course-enrolment
 */
class Sensei_Course_Enrolment_Provider_Results_Test extends WP_UnitTestCase {

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
			't' => microtime( true ) - 4,
			'v' => '###',
			'r' => [
				'testA' => true,
				'testB' => false,
			],
		];

		$provider_results = Sensei_Course_Enrolment_Provider_Results::from_json( wp_json_encode( $base ) );
		$this->assertTrue( $provider_results instanceof Sensei_Course_Enrolment_Provider_Results );
		$this->assertEquals( round( $base['t'], 3 ), round( $provider_results->get_time(), 3 ), 'Time (`t`) should match what it was initially set to', 0.1 );
		$this->assertEquals( $base['v'], $provider_results->get_version_hash(), 'Version (`v`) should match what it was initially set to' );
		$this->assertEquals( $base['r'], $provider_results->get_provider_results(), 'Results (`r`) should match what it was initially set to' );
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

		$this->assertTrue( $results->is_enrolment_provided(), 'If one provider provides enrolment, they are enrolled' );
	}

	/**
	 * Tests for positive enrolment by all providers.
	 */
	public function testIsEnrolmentProvidedMultiplePositive() {
		$results = new Sensei_Course_Enrolment_Provider_Results(
			[
				'test-provider-a' => true,
				'test-provider-b' => true,
			],
			'#version#'
		);

		$this->assertTrue( $results->is_enrolment_provided(), 'If at least one provider provides enrolment, they are enrolled' );
	}

	/**
	 * Tests to make sure they aren't enrolled if all providers do not provide enrolment.
	 */
	public function testIsEnrolmentProvidedAllNegative() {
		$results = new Sensei_Course_Enrolment_Provider_Results(
			[
				'test-provider-a' => false,
				'test-provider-b' => false,
			],
			'#version#'
		);

		$this->assertFalse( $results->is_enrolment_provided(), 'No providers are providing enrolment so they should not be enrolled' );
	}

	/**
	 * Tests for negative enrolment when there are no providers handling the course.
	 */
	public function testIsEnrolmentProvidedNoProviders() {
		$results = new Sensei_Course_Enrolment_Provider_Results(
			[],
			'#version#'
		);

		$this->assertFalse( $results->is_enrolment_provided(), 'When there are no providers, this should return false' );
	}
}
