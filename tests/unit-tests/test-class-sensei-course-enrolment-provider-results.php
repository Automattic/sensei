<?php

class Sensei_Class_Course_Enrolment_Provider_Results_Test extends WP_UnitTestCase {

	/**
	 * Setup function.
	 */
	public function setup() {
		parent::setup();

		$this->factory = new Sensei_Factory();
	}

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
		$this->assertEquals( $base['t'], $access_log->get_time() );
		$this->assertEquals( $base['v'], $access_log->get_version() );
		$this->assertEquals( $base['r'], $access_log->get_provider_results() );
	}
}
