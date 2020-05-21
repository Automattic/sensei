<?php
/**
 * This file contains the Sensei_Data_Port_Manager_Test class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once SENSEI_TEST_FRAMEWORK_DIR . '/data-port/class-sensei-data-port-job-mock.php';

/**
 * Tests for Sensei_Data_Port_Manager class.
 *
 * @group data-port
 */
class Sensei_Data_Port_Manager_Test extends WP_UnitTestCase {

	public function tearDown() {
		parent::tearDown();

		$this->set_data_port_jobs( [] );
	}

	public function testJobIsResumed() {
		$job = $this->mock_job_method( 'test-job', 'run' );
		Sensei_Data_Port_Job_Mock::set_restore_mock( $job );

		$this->set_data_port_jobs(
			[
				[
					'user_id' => 1,
					'time'    => time(),
					'handler' => 'Sensei_Data_Port_Job_Mock',
					'id'      => 'test-job',
				],
			]
		);

		$job->expects( $this->once() )->method( 'run' );

		Sensei_Data_Port_Manager::instance()->run_data_port_job( [ 'job_id' => 'test-job' ] );
	}

	public function testStartingJobStoresState() {
		Sensei_Data_Port_Manager::instance()->start_import_job( 1234 );
		Sensei_Data_Port_Manager::instance()->persist();

		$json = get_option( Sensei_Data_Port_Manager::OPTION_NAME );
		$this->assertRegExp( '/.*user_id.*1234/', $json, 'User id should be stored in JSON.' );
		$this->assertRegExp( '/.*handler.*Sensei_Import_Job/', $json, 'Handler should equal to Sensei_Import_Job.' );
	}

	public function testCancelledJobsAreRemoved() {
		Sensei_Data_Port_Manager::instance()->start_import_job( 1 );
		$this->set_data_port_jobs(
			[
				[
					'user_id' => 1,
					'time'    => time(),
					'handler' => 'Sensei_Data_Port_Job_Mock',
					'id'      => 'first-job',
				],
				[
					'user_id' => 1,
					'time'    => time(),
					'handler' => 'Sensei_Data_Port_Job_Mock',
					'id'      => 'second-job',
				],
				[
					'user_id' => 1,
					'time'    => time(),
					'handler' => 'Sensei_Data_Port_Job_Mock',
					'id'      => 'third-job',
				],
			]
		);

		Sensei_Data_Port_Manager::instance()->persist();
		$port_jobs = json_decode( get_option( Sensei_Data_Port_Manager::OPTION_NAME ), true );
		$this->assertCount( 3, $port_jobs );

		Sensei_Data_Port_Manager::instance()->cancel_job( 'first-job' );
		Sensei_Data_Port_Manager::instance()->persist();
		$port_jobs = json_decode( get_option( Sensei_Data_Port_Manager::OPTION_NAME ), true );
		$this->assertCount( 2, $port_jobs );
		$this->assertEquals( 'second-job', array_values( $port_jobs )[0]['id'] );
		$this->assertEquals( 'third-job', array_values( $port_jobs )[1]['id'] );

		Sensei_Data_Port_Manager::instance()->cancel_all_jobs();
		Sensei_Data_Port_Manager::instance()->persist();
		$port_jobs = json_decode( get_option( Sensei_Data_Port_Manager::OPTION_NAME ), true );
		$this->assertCount( 0, $port_jobs );
	}

	private function set_data_port_jobs( $jobs ) {
		$property = new ReflectionProperty( 'Sensei_Data_Port_Manager', 'data_port_jobs' );
		$property->setAccessible( true );
		$property->setValue( Sensei_Data_Port_Manager::instance(), $jobs );
	}

	private function mock_job_method( $job_id, $method ) {
		return $this->getMockBuilder( Sensei_Data_Port_Job_Mock::class )
			->setConstructorArgs( [ $job_id ] )
			->setMethods( [ $method ] )
			->getMock();
	}
}
