<?php
/**
 * Sensei REST API: Sensei_REST_API_Import_Controller_Tests tests
 *
 * @package sensei-lms
 * @since 2.3.0
 */

/**
 * Class Sensei_REST_API_Import_Controller tests.
 */
class Sensei_REST_API_Import_Controller_Tests extends WP_Test_REST_TestCase {

	/**
	 * A server instance that we use in tests to dispatch requests.
	 *
	 * @var WP_REST_Server $server
	 */
	protected $server;

	/**
	 * Test specific setup.
	 */
	public function setUp() {
		parent::setUp();

		global $wp_rest_server;
		$wp_rest_server = new WP_REST_Server();
		$this->server   = $wp_rest_server;

		do_action( 'rest_api_init' );

		// Make sure CSVs are allowed on WordPress multi-site.
		update_site_option( 'upload_filetypes', 'csv' );

		// We need to re-instansiate the controller on each tests to register any hooks.
		new Sensei_REST_API_Messages_Controller( 'sensei_message' );
	}

	/**
	 * Test specific teardown.
	 */
	public function tearDown() {
		parent::tearDown();

		global $wp_rest_server;
		$wp_rest_server = null;

		delete_site_option( 'upload_filetypes' );
	}

	/**
	 * Data source for tests.
	 *
	 * @return array[]
	 */
	public function userDataSources() {
		return [
			'guest'         => [
				null,
				false,
			],
			'teacher'       => [
				'teacher',
				false,
			],
			'administrator' => [
				'administrator',
				true,
			],
		];
	}

	/**
	 * Tests `GET /import/active` when the job hasn't been started.
	 *
	 * @dataProvider userDataSources
	 *
	 * @param string $user_role     User role to run the request as.
	 * @param bool   $is_authorized Is the user authenticated and authorized.
	 */
	public function testGetImportNotStarted( $user_role, $is_authorized ) {
		wp_logout();

		$user_description = 'Guest';
		if ( $user_role ) {
			$user_id          = $this->factory->user->create( [ 'role' => $user_role ] );
			$user_description = ucfirst( $user_role );
			wp_set_current_user( $user_id );
		}

		$expected_status_codes = [ 401, 403 ];
		if ( $is_authorized ) {
			$expected_status_codes = [ 404 ];
		}

		$request  = new WP_REST_Request( 'GET', '/sensei-internal/v1/import/active' );
		$response = $this->server->dispatch( $request );
		$this->assertTrue( in_array( $response->get_status(), $expected_status_codes, true ), "{$user_description} requests should produce status of " . implode( ', ', $expected_status_codes ) );

		if ( $is_authorized ) {
			$this->assertTrue( isset( $response->get_data()['code'] ) );
			$this->assertEquals( 'sensei_data_port_job_not_found', $response->get_data()['code'] );
		}
	}

	/**
	 * Tests `GET /import/active` when the job has been started.
	 *
	 * @dataProvider userDataSources
	 *
	 * @param string $user_role     User role to run the request as.
	 * @param bool   $is_authorized Is the user authenticated and authorized.
	 */
	public function testGetImportAlreadySetup( $user_role, $is_authorized ) {
		wp_logout();

		$user_description = 'Guest';
		if ( $user_role ) {
			$user_id          = $this->factory->user->create( [ 'role' => $user_role ] );
			$user_description = ucfirst( $user_role );
			wp_set_current_user( $user_id );
		}

		$job_results = Sensei_Import_Job::get_default_results();

		$expected_status_codes = [ 401, 403 ];
		if ( $is_authorized ) {
			$expected_status_codes = [ 200 ];

			$job = Sensei_Data_Port_Manager::instance()->create_import_job( get_current_user_id() );
			$job->increment_result( Sensei_Import_Course_Model::MODEL_KEY, Sensei_Import_Job::RESULT_SUCCESS );
			$job->increment_result( Sensei_Import_Course_Model::MODEL_KEY, Sensei_Import_Job::RESULT_SUCCESS );
			$job->increment_result( Sensei_Import_Course_Model::MODEL_KEY, Sensei_Import_Job::RESULT_ERROR );

			$job_results[ Sensei_Import_Course_Model::MODEL_KEY ][ Sensei_Import_Job::RESULT_SUCCESS ] = 2;
			$job_results[ Sensei_Import_Course_Model::MODEL_KEY ][ Sensei_Import_Job::RESULT_ERROR ]   = 1;

			$job->persist();

			Sensei_Data_Port_Manager::instance()->persist();
		}

		$request  = new WP_REST_Request( 'GET', '/sensei-internal/v1/import/active' );
		$response = $this->server->dispatch( $request );
		$this->assertTrue( in_array( $response->get_status(), $expected_status_codes, true ), "{$user_description} requests should produce status code of " . implode( ', ', $expected_status_codes ) );

		if ( $is_authorized ) {
			$expected_parts = [
				'id'      => $job->get_job_id(),
				'status'  => [
					'status'     => 'setup',
					'percentage' => 0,
				],
				'results' => $job_results,
			];

			$this->assertResultValidJob( $response->get_data(), $expected_parts );
		}
	}

	/**
	 * Tests `GET /import/{job_id}/logs`.
	 *
	 * @dataProvider userDataSources
	 *
	 * @param string $user_role     User role to run the request as.
	 * @param bool   $is_authorized Is the user authenticated and authorized.
	 */
	public function testGetImportLogs( $user_role, $is_authorized ) {
		wp_logout();

		$user_description = 'Guest';
		if ( $user_role ) {
			$user_id          = $this->factory->user->create( [ 'role' => $user_role ] );
			$user_description = ucfirst( $user_role );
			wp_set_current_user( $user_id );
		}

		$expected_status_codes = [ 401, 403 ];
		$job_id                = 'doesnotexit';

		$entries = [
			[
				'input'    => [
					'message' => 'Test message A',
					'level'   => Sensei_Data_Port_Job::LOG_LEVEL_NOTICE,
					'data'    => [
						'type'        => 'course',
						'line'        => 100,
						'entry_title' => 'Test Course A',
						'entry_id'    => '123',
					],
				],
				'expected' => [
					'type'       => 'course',
					'line'       => 100,
					'severity'   => 'notice',
					'descriptor' => 'Test Course A; ID: 123',
					'message'    => 'Test message A',
				],
			],
			[
				'input'    => [
					'message' => 'Test message B',
					'level'   => Sensei_Data_Port_Job::LOG_LEVEL_NOTICE,
					'data'    => [
						'type'        => 'course',
						'line'        => 101,
						'entry_title' => 'Test Course B',
						'entry_id'    => null,
					],
				],
				'expected' => [
					'type'       => 'course',
					'line'       => 101,
					'severity'   => 'notice',
					'descriptor' => 'Test Course B',
					'message'    => 'Test message B',
				],
			],
			[
				'input'    => [
					'message' => 'Test message C',
					'level'   => Sensei_Data_Port_Job::LOG_LEVEL_ERROR,
					'data'    => [
						'type'        => 'course',
						'line'        => 102,
						'entry_title' => null,
						'entry_id'    => null,
					],
				],
				'expected' => [
					'type'       => 'course',
					'line'       => 102,
					'severity'   => 'error',
					'descriptor' => null,
					'message'    => 'Test message C',
				],
			],
		];

		if ( $is_authorized ) {
			$expected_status_codes = [ 200 ];

			$job = Sensei_Data_Port_Manager::instance()->create_import_job( get_current_user_id() );
			foreach ( $entries as $entry ) {
				$job->add_log_entry( $entry['input']['message'], $entry['input']['level'], $entry['input']['data'] );
			}

			$job->persist();
			Sensei_Data_Port_Manager::instance()->persist();
			$job_id = $job->get_job_id();
		}

		$request  = new WP_REST_Request( 'GET', '/sensei-internal/v1/import/' . $job_id . '/logs' );
		$response = $this->server->dispatch( $request );
		$this->assertTrue( in_array( $response->get_status(), $expected_status_codes, true ), "{$user_description} requests should produce status code of " . implode( ', ', $expected_status_codes ) );

		$result = $response->get_data();
		if ( $is_authorized ) {
			$this->assertEquals( 3, $result['total'] );
			foreach ( $entries as $index => $entry ) {
				$this->assertTrue( isset( $result['items'][ $index ] ) );
				$this->assertEquals( $entry['expected'], $result['items'][ $index ] );
			}
		}
	}

	/**
	 * Tests `POST /import`.
	 *
	 * @dataProvider userDataSources
	 *
	 * @param string $user_role     User role to run the request as.
	 * @param bool   $is_authorized Is the user authenticated and authorized.
	 */
	public function testPostImport( $user_role, $is_authorized ) {
		wp_logout();

		$user_description = 'Guest';
		if ( $user_role ) {
			$user_id          = $this->factory->user->create( [ 'role' => $user_role ] );
			$user_description = ucfirst( $user_role );
			wp_set_current_user( $user_id );
		}

		$expected_status_codes = [ 401, 403 ];
		if ( $is_authorized ) {
			$expected_status_codes = [ 201 ];
		}

		$request  = new WP_REST_Request( 'POST', '/sensei-internal/v1/import' );
		$response = $this->server->dispatch( $request );
		$this->assertTrue( in_array( $response->get_status(), $expected_status_codes, true ), "{$user_description} requests should produce status of " . implode( ', ', $expected_status_codes ) );

		if ( $is_authorized ) {
			$expected_parts = [
				'status'  => [
					'status'     => 'setup',
					'percentage' => 0,
				],
				'results' => Sensei_Import_Job::get_default_results(),
			];

			$this->assertResultValidJob( $response->get_data(), $expected_parts );
		}
	}

	/**
	 * Tests `DELETE /import/{job_id}` when job has not been created.
	 *
	 * @dataProvider userDataSources
	 *
	 * @param string $user_role     User role to run the request as.
	 * @param bool   $is_authorized Is the user authenticated and authorized.
	 */
	public function testDeleteImportNotStarted( $user_role, $is_authorized ) {
		wp_logout();

		$user_description = 'Guest';
		if ( $user_role ) {
			$user_id          = $this->factory->user->create( [ 'role' => $user_role ] );
			$user_description = ucfirst( $user_role );
			wp_set_current_user( $user_id );
		}

		$expected_status_codes = [ 401, 403 ];
		if ( $is_authorized ) {
			$expected_status_codes = [ 404 ];
		}

		$request  = new WP_REST_Request( 'DELETE', '/sensei-internal/v1/import/doesnotexist' );
		$response = $this->server->dispatch( $request );
		$this->assertTrue( in_array( $response->get_status(), $expected_status_codes, true ), "{$user_description} requests should produce status of " . implode( ', ', $expected_status_codes ) );

		if ( $is_authorized ) {
			$this->assertTrue( isset( $response->get_data()['code'] ) );
			$this->assertEquals( 'sensei_data_port_job_not_found', $response->get_data()['code'] );
		}
	}

	/**
	 * Tests `DELETE /import/{job_id}` when job has been started.
	 *
	 * @dataProvider userDataSources
	 *
	 * @param string $user_role     User role to run the request as.
	 * @param bool   $is_authorized Is the user authenticated and authorized.
	 */
	public function testDeleteImportStarted( $user_role, $is_authorized ) {
		wp_logout();

		$user_description = 'Guest';
		if ( $user_role ) {
			$user_id          = $this->factory->user->create( [ 'role' => $user_role ] );
			$user_description = ucfirst( $user_role );
			wp_set_current_user( $user_id );
		}

		$expected_status_codes = [ 401, 403 ];
		$job_id                = 'current';
		if ( $is_authorized ) {
			$expected_status_codes = [ 200 ];

			$job = Sensei_Data_Port_Manager::instance()->create_import_job( get_current_user_id() );
			$job->persist();
			Sensei_Data_Port_Manager::instance()->persist();
			$job_id = $job->get_job_id();
		}

		$request  = new WP_REST_Request( 'DELETE', '/sensei-internal/v1/import/' . $job_id );
		$response = $this->server->dispatch( $request );
		$this->assertTrue( in_array( $response->get_status(), $expected_status_codes, true ), "{$user_description} requests should produce status of " . implode( ', ', $expected_status_codes ) );

		if ( $is_authorized ) {
			$expected_parts = [
				'status'  => [
					'status'     => 'setup',
					'percentage' => 0,
				],
				'results' => Sensei_Import_Job::get_default_results(),
			];

			$this->assertTrue( isset( $response->get_data()['deleted'] ) );
			$this->assertTrue( $response->get_data()['deleted'] );
			$this->assertTrue( isset( $response->get_data()['previous'] ) );
			$this->assertResultValidJob( $response->get_data()['previous'], $expected_parts );
		}
	}

	/**
	 * Tests `POST /import/active/file/{file_key}` and create a new job.
	 *
	 * @dataProvider userDataSources
	 *
	 * @param string $user_role     User role to run the request as.
	 * @param bool   $is_authorized Is the user authenticated and authorized.
	 */
	public function testPostFileValidFileNewJob( $user_role, $is_authorized ) {
		if ( ! version_compare( get_bloginfo( 'version' ), '5.0.0', '>=' ) ) {
			$this->markTestSkipped( 'Test fails with 4.9 due to text/csv getting interpreted as text/plain.' );
		}

		wp_logout();

		$user_description = 'Guest';
		if ( $user_role ) {
			$user_id          = $this->factory->user->create( [ 'role' => $user_role ] );
			$user_description = ucfirst( $user_role );
			wp_set_current_user( $user_id );
		}

		$expected_status_codes = [ 401, 403 ];
		if ( $is_authorized ) {
			$expected_status_codes = [ 200 ];
		}

		$test_file = SENSEI_TEST_FRAMEWORK_DIR . '/data-port/data-files/questions.csv';
		$test_file = $this->get_tmp_file( $test_file );

		$request = new WP_REST_Request( 'POST', '/sensei-internal/v1/import/active/file/questions' );
		$request->set_file_params(
			[
				'file' => [
					'name'     => basename( $test_file ),
					'size'     => filesize( $test_file ),
					'tmp_name' => $test_file,
					'type'     => 'text/csv',
					'error'    => UPLOAD_ERR_OK,
				],
			]
		);

		$response = $this->server->dispatch( $request );

		$this->assertTrue( in_array( $response->get_status(), $expected_status_codes, true ), "{$user_description} requests should produce status of " . implode( ', ', $expected_status_codes ) );

		if ( $is_authorized ) {
			$data = $response->get_data();
			$this->assertResultValidJob( $data );

			$this->assertTrue( isset( $data['files']['questions']['name'] ) );
			$this->assertEquals( basename( $test_file ), $data['files']['questions']['name'] );
		}
	}

	/**
	 * Tests `POST /import/{job_id}/file/{file_key}` for existing job ID.
	 *
	 * @dataProvider userDataSources
	 *
	 * @param string $user_role     User role to run the request as.
	 * @param bool   $is_authorized Is the user authenticated and authorized.
	 */
	public function testPostFileValidFileExistingJob( $user_role, $is_authorized ) {
		if ( ! version_compare( get_bloginfo( 'version' ), '5.0.0', '>=' ) ) {
			$this->markTestSkipped( 'Test fails with 4.9 due to text/csv getting interpreted as text/plain.' );
		}

		wp_logout();

		$user_description = 'Guest';
		if ( $user_role ) {
			$user_id          = $this->factory->user->create( [ 'role' => $user_role ] );
			$user_description = ucfirst( $user_role );
			wp_set_current_user( $user_id );
		}

		$expected_status_codes = [ 401, 403 ];
		$job_id                = 'current';
		if ( $is_authorized ) {
			$expected_status_codes = [ 200 ];

			$job = Sensei_Data_Port_Manager::instance()->create_import_job( get_current_user_id() );
			$job->persist();
			Sensei_Data_Port_Manager::instance()->persist();

			$job_id = $job->get_job_id();
		}

		$test_file = SENSEI_TEST_FRAMEWORK_DIR . '/data-port/data-files/questions.csv';
		$test_file = $this->get_tmp_file( $test_file );

		$request = new WP_REST_Request( 'POST', '/sensei-internal/v1/import/' . $job_id . '/file/questions' );
		$request->set_file_params(
			[
				'file' => [
					'name'     => basename( $test_file ),
					'size'     => filesize( $test_file ),
					'tmp_name' => $test_file,
					'type'     => 'text/csv',
					'error'    => UPLOAD_ERR_OK,
				],
			]
		);

		$response = $this->server->dispatch( $request );

		$this->assertTrue( in_array( $response->get_status(), $expected_status_codes, true ), "{$user_description} requests should produce status of " . implode( ', ', $expected_status_codes ) );

		if ( $is_authorized ) {
			$data = $response->get_data();
			$this->assertResultValidJob( $data );

			$this->assertTrue( isset( $data['files']['questions']['name'] ) );
			$this->assertEquals( basename( $test_file ), $data['files']['questions']['name'] );
		}
	}

	/**
	 * Tests `POST /import/{job_id}/file/{file_key}` with an invalid file type.
	 */
	public function testPostFileInvalidFileType() {
		wp_logout();

		$user_role = 'administrator';
		$user_id   = $this->factory->user->create( [ 'role' => $user_role ] );
		wp_set_current_user( $user_id );

		$job = Sensei_Data_Port_Manager::instance()->create_import_job( get_current_user_id() );
		$job->persist();
		Sensei_Data_Port_Manager::instance()->persist();

		$test_file = SENSEI_TEST_FRAMEWORK_DIR . '/data-port/data-files/invalid_file_type.tsv';
		$test_file = $this->get_tmp_file( $test_file );

		$request = new WP_REST_Request( 'POST', '/sensei-internal/v1/import/' . $job->get_job_id() . '/file/questions' );
		$request->set_file_params(
			[
				'file' => [
					'name'     => basename( $test_file ),
					'size'     => filesize( $test_file ),
					'tmp_name' => $test_file,
					'type'     => 'text/tsv',
					'error'    => UPLOAD_ERR_OK,
				],
			]
		);

		$response = $this->server->dispatch( $request );

		$this->assertEquals( 400, $response->get_status(), 'Invalid upload file types should result in a 400 status code.' );

		$data = $response->get_data();

		$this->assertTrue( isset( $data['code'], $data['message'] ) );
		$this->assertEquals( 'sensei_data_port_unexpected_file_type', $data['code'] );
	}

	/**
	 * Tests `POST /import/{job_id}/file/{file_key}` with an invalid file key.
	 */
	public function testPostFileInvalidFileKey() {
		wp_logout();

		$user_role = 'administrator';
		$user_id   = $this->factory->user->create( [ 'role' => $user_role ] );
		wp_set_current_user( $user_id );

		$job = Sensei_Data_Port_Manager::instance()->create_import_job( get_current_user_id() );
		$job->persist();
		Sensei_Data_Port_Manager::instance()->persist();

		$test_file = SENSEI_TEST_FRAMEWORK_DIR . '/data-port/data-files/questions.csv';
		$test_file = $this->get_tmp_file( $test_file );

		$request = new WP_REST_Request( 'POST', '/sensei-internal/v1/import/' . $job->get_job_id() . '/file/dinosaurs' );
		$request->set_file_params(
			[
				'file' => [
					'name'     => basename( $test_file ),
					'size'     => filesize( $test_file ),
					'tmp_name' => $test_file,
					'type'     => 'text/tsv',
					'error'    => UPLOAD_ERR_OK,
				],
			]
		);

		$response = $this->server->dispatch( $request );

		$this->assertEquals( 500, $response->get_status(), 'Invalid file key should result in a 500 status code.' );

		$data = $response->get_data();

		$this->assertTrue( isset( $data['code'], $data['message'] ) );
		$this->assertEquals( 'sensei_data_port_unknown_file_key', $data['code'] );
	}

	/**
	 * Tests `DELETE /import/{job_id}/file/{file_key}` for a file that exists.
	 *
	 * @dataProvider userDataSources
	 *
	 * @param string $user_role     User role to run the request as.
	 * @param bool   $is_authorized Is the user authenticated and authorized.
	 */
	public function testDeleteFileExists( $user_role, $is_authorized ) {
		if ( ! version_compare( get_bloginfo( 'version' ), '5.0.0', '>=' ) ) {
			$this->markTestSkipped( 'Test fails with 4.9 due to text/csv getting interpreted as text/plain.' );
		}

		wp_logout();

		$user_description = 'Guest';
		if ( $user_role ) {
			$user_id          = $this->factory->user->create( [ 'role' => $user_role ] );
			$user_description = ucfirst( $user_role );
			wp_set_current_user( $user_id );
		}

		$test_file             = SENSEI_TEST_FRAMEWORK_DIR . '/data-port/data-files/questions.csv';
		$test_file             = $this->get_tmp_file( $test_file );
		$job_id                = 'current';
		$expected_status_codes = [ 401, 403 ];
		if ( $is_authorized ) {
			$expected_status_codes = [ 200 ];

			$job = Sensei_Data_Port_Manager::instance()->create_import_job( get_current_user_id() );
			$job->save_file( 'questions', $test_file, basename( $test_file ) );
			$job->persist();
			Sensei_Data_Port_Manager::instance()->persist();
			$job_id = $job->get_job_id();
		}

		$request  = new WP_REST_Request( 'DELETE', '/sensei-internal/v1/import/' . $job_id . '/file/questions' );
		$response = $this->server->dispatch( $request );

		$this->assertTrue( in_array( $response->get_status(), $expected_status_codes, true ), "{$user_description} requests should produce status of " . implode( ', ', $expected_status_codes ) );

		if ( $is_authorized ) {
			$data = $response->get_data();
			$this->assertResultValidJob( $data );

			$this->assertFalse( isset( $data['files']['questions']['name'] ) );
		}
	}

	/**
	 * Tests `DELETE /import/{job_id}/file/{file_key}` when file does not exist.
	 *
	 * @dataProvider userDataSources
	 *
	 * @param string $user_role     User role to run the request as.
	 * @param bool   $is_authorized Is the user authenticated and authorized.
	 */
	public function testDeleteFileNotExists( $user_role, $is_authorized ) {
		wp_logout();

		$user_description = 'Guest';
		if ( $user_role ) {
			$user_id          = $this->factory->user->create( [ 'role' => $user_role ] );
			$user_description = ucfirst( $user_role );
			wp_set_current_user( $user_id );
		}

		$job_id                = 'current';
		$expected_status_codes = [ 401, 403 ];
		if ( $is_authorized ) {
			$expected_status_codes = [ 404 ];

			$job = Sensei_Data_Port_Manager::instance()->create_import_job( get_current_user_id() );
			$job->persist();
			Sensei_Data_Port_Manager::instance()->persist();
			$job_id = $job->get_job_id();
		}

		$request  = new WP_REST_Request( 'DELETE', '/sensei-internal/v1/import/' . $job_id . '/file/questions' );
		$response = $this->server->dispatch( $request );

		$this->assertTrue( in_array( $response->get_status(), $expected_status_codes, true ), "{$user_description} requests should produce status of " . implode( ', ', $expected_status_codes ) );

		if ( $is_authorized ) {
			$data = $response->get_data();
			$this->assertTrue( isset( $data['code'], $data['message'] ) );
			$this->assertEquals( 'sensei_data_port_job_file_not_found', $data['code'] );
		}
	}

	/**
	 * Tests `POST /import/{job_id}/start` when the job is ready.
	 *
	 * @dataProvider userDataSources
	 *
	 * @param string $user_role     User role to run the request as.
	 * @param bool   $is_authorized Is the user authenticated and authorized.
	 */
	public function testStartJobIsReady( $user_role, $is_authorized ) {
		if ( ! version_compare( get_bloginfo( 'version' ), '5.0.0', '>=' ) ) {
			$this->markTestSkipped( 'Test fails with 4.9 due to text/csv getting interpreted as text/plain.' );
		}

		wp_logout();

		$user_description = 'Guest';
		if ( $user_role ) {
			$user_id          = $this->factory->user->create( [ 'role' => $user_role ] );
			$user_description = ucfirst( $user_role );
			wp_set_current_user( $user_id );
		}

		$expected_status_codes = [ 401, 403 ];
		$job_id                = 'current';
		if ( $is_authorized ) {
			$expected_status_codes = [ 200 ];

			$test_file = SENSEI_TEST_FRAMEWORK_DIR . '/data-port/data-files/questions.csv';
			$test_file = $this->get_tmp_file( $test_file );

			$job = Sensei_Data_Port_Manager::instance()->create_import_job( get_current_user_id() );
			$job->save_file( 'questions', $test_file, basename( $test_file ) );
			$job->persist();
			$job_id = $job->get_job_id();
			Sensei_Data_Port_Manager::instance()->persist();
		}

		$request  = new WP_REST_Request( 'POST', '/sensei-internal/v1/import/' . $job_id . '/start' );
		$response = $this->server->dispatch( $request );

		$this->assertTrue( in_array( $response->get_status(), $expected_status_codes, true ), "{$user_description} requests should produce status of " . implode( ', ', $expected_status_codes ) );

		if ( $is_authorized ) {
			$data = $response->get_data();
			$this->assertResultValidJob( $data );
		}
	}

	/**
	 * Tests `POST /import/{job_id}/start` when the job is already running.
	 *
	 * @dataProvider userDataSources
	 *
	 * @param string $user_role     User role to run the request as.
	 * @param bool   $is_authorized Is the user authenticated and authorized.
	 */
	public function testStartJobIsStarted( $user_role, $is_authorized ) {
		if ( ! version_compare( get_bloginfo( 'version' ), '5.0.0', '>=' ) ) {
			$this->markTestSkipped( 'Test fails with 4.9 due to text/csv getting interpreted as text/plain.' );
		}

		wp_logout();

		$user_description = 'Guest';
		if ( $user_role ) {
			$user_id          = $this->factory->user->create( [ 'role' => $user_role ] );
			$user_description = ucfirst( $user_role );
			wp_set_current_user( $user_id );
		}

		$expected_status_codes = [ 401, 403 ];
		$job_id                = 'current';
		if ( $is_authorized ) {
			$expected_status_codes = [ 400 ];

			$test_file = SENSEI_TEST_FRAMEWORK_DIR . '/data-port/data-files/questions.csv';
			$test_file = $this->get_tmp_file( $test_file );

			$job = Sensei_Data_Port_Manager::instance()->create_import_job( get_current_user_id() );
			$job->save_file( 'questions', $test_file, basename( $test_file ) );
			$job->start();
			$job->persist();
			Sensei_Data_Port_Manager::instance()->persist();
			$job_id = $job->get_job_id();
		}

		$request  = new WP_REST_Request( 'POST', '/sensei-internal/v1/import/' . $job_id . '/start' );
		$response = $this->server->dispatch( $request );

		$this->assertTrue( in_array( $response->get_status(), $expected_status_codes, true ), "{$user_description} requests should produce status of " . implode( ', ', $expected_status_codes ) );

		if ( $is_authorized ) {
			$data = $response->get_data();
			$this->assertResultError( $data, 'sensei_data_port_job_already_started' );
		}
	}

	/**
	 * Tests `POST /import/{job_id}/start` when the job is not ready.
	 *
	 * @dataProvider userDataSources
	 *
	 * @param string $user_role     User role to run the request as.
	 * @param bool   $is_authorized Is the user authenticated and authorized.
	 */
	public function testStartJobNotReady( $user_role, $is_authorized ) {
		wp_logout();

		$user_description = 'Guest';
		if ( $user_role ) {
			$user_id          = $this->factory->user->create( [ 'role' => $user_role ] );
			$user_description = ucfirst( $user_role );
			wp_set_current_user( $user_id );
		}

		$expected_status_codes = [ 401, 403 ];
		$job_id                = 'current';
		if ( $is_authorized ) {
			$data_port_manager = Sensei_Data_Port_Manager::instance();
			$job               = $data_port_manager->create_import_job( get_current_user_id() );
			$job->persist();
			$data_port_manager->persist();

			$expected_status_codes = [ 400 ];
			$job_id                = $job->get_job_id();
		}

		$request  = new WP_REST_Request( 'POST', '/sensei-internal/v1/import/' . $job_id . '/start' );
		$response = $this->server->dispatch( $request );

		$this->assertTrue( in_array( $response->get_status(), $expected_status_codes, true ), "{$user_description} requests should produce status of " . implode( ', ', $expected_status_codes ) );

		if ( $is_authorized ) {
			$data = $response->get_data();
			$this->assertResultError( $data, 'sensei_data_port_job_not_ready' );
		}
	}


	/**
	 * Tests `POST /import/{job_id}/start` when the job hasn't been created.
	 *
	 * @dataProvider userDataSources
	 *
	 * @param string $user_role     User role to run the request as.
	 * @param bool   $is_authorized Is the user authenticated and authorized.
	 */
	public function testStartJobNotCreated( $user_role, $is_authorized ) {
		wp_logout();

		$user_description = 'Guest';
		if ( $user_role ) {
			$user_id          = $this->factory->user->create( [ 'role' => $user_role ] );
			$user_description = ucfirst( $user_role );
			wp_set_current_user( $user_id );
		}

		$expected_status_codes = [ 401, 403 ];
		if ( $is_authorized ) {
			$expected_status_codes = [ 404 ];
		}

		$request  = new WP_REST_Request( 'POST', '/sensei-internal/v1/import/doesnotexist/start' );
		$response = $this->server->dispatch( $request );

		$this->assertTrue( in_array( $response->get_status(), $expected_status_codes, true ), "{$user_description} requests should produce status of " . implode( ', ', $expected_status_codes ) );

		if ( $is_authorized ) {
			$data = $response->get_data();
			$this->assertResultError( $data, 'sensei_data_port_job_not_found' );
		}
	}

	/**
	 * Assert that a REST API response is an WP error with a specific code
	 *
	 * @param array  $result        REST API result.
	 * @param string $expected_code Expected status code.
	 */
	protected function assertResultError( $result, $expected_code ) {
		$this->assertTrue( isset( $result['message'], $result['code'] ) );
		$this->assertEquals( $expected_code, $result['code'] );
	}

	/**
	 * Assert that a REST API response is valid.
	 *
	 * @param $result
	 */
	protected function assertResultValidJob( $result, $expected = [] ) {
		$this->assertTrue( isset( $result['id'], $result['status'], $result['files'] ) );
		$this->assertTrue( is_string( $result['id'] ) );
		$this->assertTrue( is_array( $result['status'] ) );
		$this->assertTrue( is_array( $result['files'] ) );
		$this->assertNotEmpty( $result['id'] );
		$this->assertNotEmpty( $result['status'] );
		$this->assertTrue( isset( $result['status']['status'], $result['status']['percentage'] ) );

		foreach ( $expected as $key => $value ) {
			$this->assertEquals( $result[ $key ], $value );
		}
	}

	/**
	 * Get a temporary file from a source file.
	 *
	 * @param string $file_path File to copy.
	 *
	 * @return string
	 */
	private function get_tmp_file( $file_path ) {
		$tmp = wp_tempnam( basename( $file_path ) ) . '.' . pathinfo( $file_path, PATHINFO_EXTENSION );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_file_put_contents, WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		file_put_contents( $tmp, file_get_contents( $file_path ) );

		register_shutdown_function(
			function() use ( $tmp ) {
				if ( file_exists( $tmp ) ) {
					unlink( $tmp );
				}
			}
		);

		return $tmp;
	}
}
