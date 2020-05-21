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
	 * Tests `GET /import` when the job hasn't been started.
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

		$request  = new WP_REST_Request( 'GET', '/sensei-internal/v1/import' );
		$response = $this->server->dispatch( $request );
		$this->assertTrue( in_array( $response->get_status(), $expected_status_codes, true ), "{$user_description} requests should produce status of " . implode( ', ', $expected_status_codes ) );

		if ( $is_authorized ) {
			$this->assertTrue( isset( $response->get_data()['code'] ) );
			$this->assertEquals( 'rest_no_active_job', $response->get_data()['code'] );
		}
	}

	/**
	 * Tests `GET /import` when the job has been started.
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

		$expected_status_codes = [ 401, 403 ];
		if ( $is_authorized ) {
			$expected_status_codes = [ 200 ];

			$job = Sensei_Data_Port_Manager::instance()->create_import_job( get_current_user_id() );
			$job->persist();
			Sensei_Data_Port_Manager::instance()->persist();
		}

		$request  = new WP_REST_Request( 'GET', '/sensei-internal/v1/import' );
		$response = $this->server->dispatch( $request );
		$this->assertTrue( in_array( $response->get_status(), $expected_status_codes, true ), "{$user_description} requests should produce status of " . implode( ', ', $expected_status_codes ) );

		if ( $is_authorized ) {
			$expected_parts = [
				'status' => [
					'status'     => 'setup',
					'percentage' => 0,
				],
			];

			$this->assertResultValidJob( $response->get_data(), $expected_parts );
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
				'status' => [
					'status'     => 'setup',
					'percentage' => 0,
				],
			];

			$this->assertResultValidJob( $response->get_data(), $expected_parts );
		}
	}

	/**
	 * Tests `DELETE /import` when job has not been started.
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

		$request  = new WP_REST_Request( 'DELETE', '/sensei-internal/v1/import' );
		$response = $this->server->dispatch( $request );
		$this->assertTrue( in_array( $response->get_status(), $expected_status_codes, true ), "{$user_description} requests should produce status of " . implode( ', ', $expected_status_codes ) );

		if ( $is_authorized ) {
			$this->assertTrue( isset( $response->get_data()['code'] ) );
			$this->assertEquals( 'rest_no_active_job', $response->get_data()['code'] );
		}
	}

	/**
	 * Tests `DELETE /import` when job has been started.
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
		if ( $is_authorized ) {
			$expected_status_codes = [ 200 ];

			$job = Sensei_Data_Port_Manager::instance()->create_import_job( get_current_user_id() );
			$job->persist();
			Sensei_Data_Port_Manager::instance()->persist();
		}

		$request  = new WP_REST_Request( 'DELETE', '/sensei-internal/v1/import' );
		$response = $this->server->dispatch( $request );
		$this->assertTrue( in_array( $response->get_status(), $expected_status_codes, true ), "{$user_description} requests should produce status of " . implode( ', ', $expected_status_codes ) );

		if ( $is_authorized ) {
			$expected_parts = [
				'status' => [
					'status'     => 'setup',
					'percentage' => 0,
				],
			];

			$this->assertTrue( isset( $response->get_data()['deleted'] ) );
			$this->assertTrue( $response->get_data()['deleted'] );
			$this->assertTrue( isset( $response->get_data()['previous'] ) );
			$this->assertResultValidJob( $response->get_data()['previous'], $expected_parts );
		}
	}

	/**
	 * Assert that a REST API response is valid.
	 *
	 * @param $result
	 */
	protected function assertResultValidJob( $result, $expected = [] ) {
		$this->assertTrue( isset( $result['id'], $result['status'] ) );
		$this->assertTrue( is_string( $result['id'] ) );
		$this->assertTrue( is_array( $result['status'] ) );
		$this->assertNotEmpty( $result['id'] );
		$this->assertNotEmpty( $result['status'] );
		$this->assertTrue( isset( $result['status']['status'], $result['status']['percentage'] ) );

		foreach ( $expected as $key => $value ) {
			$this->assertEquals( $result[ $key ], $value );
		}
	}
}
