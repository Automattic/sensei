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

	/**
	 * Set up the tests.
	 */
	public function setUp() {
		parent::setUp();

		Sensei_Test_Events::reset();
	}

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

		Sensei_Data_Port_Manager::instance()->run_scheduled_data_port_job( [ 'job_id' => 'test-job' ] );
	}

	public function testStartingJobStoresState() {
		$job = Sensei_Data_Port_Manager::instance()->create_import_job( 1234 );
		Sensei_Data_Port_Manager::instance()->start_job( $job );
		Sensei_Data_Port_Manager::instance()->persist();

		$json = get_option( Sensei_Data_Port_Manager::OPTION_NAME );
		$this->assertRegExp( '/.*user_id.*1234/', $json, 'User id should be stored in JSON.' );
		$this->assertRegExp( '/.*handler.*Sensei_Import_Job/', $json, 'Handler should equal to Sensei_Import_Job.' );
	}

	public function testCancelledJobsAreRemoved() {
		$job = Sensei_Data_Port_Manager::instance()->create_import_job( 1 );
		Sensei_Data_Port_Manager::instance()->start_job( $job );
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

	/**
	 * Make sure garbage collection removes old jobs.
	 */
	public function testOldJobAreRemoved() {
		$job = Sensei_Data_Port_Manager::instance()->create_import_job( 1 );
		Sensei_Data_Port_Manager::instance()->start_job( $job );
		$this->set_data_port_jobs(
			[
				[
					'user_id' => 1,
					'time'    => time() - ( Sensei_Data_Port_Manager::JOB_STALE_AGE_SECONDS ) - 1,
					'handler' => 'Sensei_Data_Port_Job_Mock',
					'id'      => 'first-job',
				],
				[
					'user_id' => 1,
					'time'    => time() - ( Sensei_Data_Port_Manager::JOB_STALE_AGE_SECONDS / 2 ),
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

		Sensei_Data_Port_Manager::instance()->clean_old_jobs();
		Sensei_Data_Port_Manager::instance()->persist();

		$port_jobs = json_decode( get_option( Sensei_Data_Port_Manager::OPTION_NAME ), true );
		$this->assertCount( 2, $port_jobs );
		$this->assertEquals( 'second-job', array_values( $port_jobs )[0]['id'] );
		$this->assertEquals( 'third-job', array_values( $port_jobs )[1]['id'] );
	}

	/**
	 * Tests to make sure import jobs are logged successfully.
	 */
	public function testLogCompleteImportJobs() {
		$job = $this->getMockBuilder( Sensei_Import_Job::class )
					->setConstructorArgs( [ 'test-job' ] )
					->setMethods( [ 'get_result_counts' ] )
					->getMock();

		$job->expects( $this->exactly( 1 ) )
			->method( 'get_result_counts' )
			->willReturn(
				[
					Sensei_Import_Course_Model::MODEL_KEY => [
						'success' => 3,
						'warning' => 1,
						'error'   => 2,
					],
					Sensei_Import_Lesson_Model::MODEL_KEY => [
						'success' => 1,
						'warning' => 3,
						'error'   => 2,
					],
					Sensei_Import_Question_Model::MODEL_KEY => [
						'success' => 2,
						'warning' => 3,
						'error'   => 1,
					],
				]
			);

		Sensei_Data_Port_Manager::instance()->log_complete_import_jobs( $job );

		$events = Sensei_Test_Events::get_logged_events( 'sensei_import_complete' );
		$this->assertCount( 1, $events );

		$expected_data = [
			'imported_courses'   => 4,
			'imported_lessons'   => 4,
			'imported_questions' => 5,
			'failed_courses'     => 2,
			'failed_lessons'     => 2,
			'failed_questions'   => 1,
		];

		foreach ( $expected_data as $key => $value ) {
			$this->assertEquals( $value, $events[0]['url_args'][ $key ], "'{$key}' does not match" );
		}
	}

	/**
	 * Tests to make sure non-import data port jobs are not logged.
	 */
	public function testLogCompleteImportJobsNonImportJobFail() {
		$job = $this->getMockBuilder( Sensei_Data_Port_Job_Mock::class )
					->setConstructorArgs( [ 'test-id' ] )
					->getMock();

		Sensei_Data_Port_Manager::instance()->log_complete_import_jobs( $job );

		$events = Sensei_Test_Events::get_logged_events( 'sensei_import_complete' );
		$this->assertCount( 0, $events );
	}

	private function set_data_port_jobs( $jobs ) {
		$property = new ReflectionProperty( 'Sensei_Data_Port_Manager', 'data_port_jobs' );
		$property->setAccessible( true );
		$property->setValue( Sensei_Data_Port_Manager::instance(), $jobs );
	}

	/**
	 * Tests to make sure export jobs are logged successfully.
	 */
	public function testLogCompleteExportJobs() {
		$job = $this->getMockBuilder( Sensei_Export_Job::class )
					->setConstructorArgs( [ 'test-job' ] )
					->setMethods( [ 'get_content_types' ] )
					->getMock();

		$job->expects( $this->exactly( 1 ) )
			->method( 'get_content_types' )
			->willReturn(
				[ 'lesson', 'course' ]
			);

		Sensei_Data_Port_Manager::instance()->log_complete_export_jobs( $job );

		$events = Sensei_Test_Events::get_logged_events( 'sensei_export_complete' );
		$this->assertCount( 1, $events );

		$expected_data = [
			'type' => 'courses,lessons',
		];

		foreach ( $expected_data as $key => $value ) {
			$this->assertEquals( $value, $events[0]['url_args'][ $key ], "'{$key}' does not match" );
		}
	}

	/**
	 * Tests redirect import sample.
	 *
	 * @dataProvider redirectImportSampleDataSources
	 */
	public function testRedirectImportSample( $nonce, $expects_call, $expect_die_exception ) {
		$import_id = 99;

		// Mock the redirect_edit_post_link method
		$job_manager_mock = $this->getMockBuilder( Sensei_Data_Port_Manager::class )
			->disableOriginalConstructor()
			->setMethods( [ 'redirect_edit_post_link' ] )
			->getMock();

		$job_manager_mock->expects( $expects_call )
			->method( 'redirect_edit_post_link' )
			->with(
				$this->equalTo( $import_id )
			);

		$instance_property = new ReflectionProperty( Sensei_Data_Port_Manager::class, 'instance' );
		$instance_property->setAccessible( true );
		$instance_property->setValue( $job_manager_mock );

		$job = Sensei_Data_Port_Manager::instance()->create_import_job( get_current_user_id() );

		$job->set_import_id( 'course', Sensei_Data_Port_Manager::SAMPLE_COURSE_ID, $import_id );
		$job->persist();

		$_GET['redirect_imported_sample'] = '1';
		$_GET['job_id']                   = $job->get_job_id();
		$_GET['nonce']                    = $nonce;

		if ( $expect_die_exception ) {
			$this->expectExceptionMessage( 'Invalid request' );
		}

		Sensei_Data_Port_Manager::instance()->redirect_imported_sample();
	}

	/**
	 * Data source redirect import samples test.
	 *
	 * @return array[]
	 */
	public function redirectImportSampleDataSources() {
		return [
			'valid'         => [
				wp_create_nonce( 'sensei-setup-wizard' ),
				$this->once(),
				false,
			],
			'invalid_nonce' => [
				'invalid',
				$this->never(),
				true,
			],
		];
	}

	private function mock_job_method( $job_id, $method ) {
		return $this->getMockBuilder( Sensei_Data_Port_Job_Mock::class )
			->setConstructorArgs( [ $job_id ] )
			->setMethods( [ $method ] )
			->getMock();
	}
}
