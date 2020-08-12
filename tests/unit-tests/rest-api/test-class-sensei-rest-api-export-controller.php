<?php
/**
 * Sensei REST API: Sensei_REST_API_Export_Controller_Tests tests
 *
 * @package sensei-lms
 * @since 2.3.0
 */

/**
 * Class Sensei_REST_API_Export_Controller tests.
 */
class Sensei_REST_API_Export_Controller_Tests extends WP_Test_REST_TestCase {
	use Sensei_Data_Port_Test_Helpers;

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

		Sensei_Test_Events::reset();

		$admin_id = $this->factory->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $admin_id );
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
	 * Tests job starts for the given content types.
	 */
	public function testStartJob() {

		$job = Sensei_Data_Port_Manager::instance()->create_export_job( get_current_user_id() );
		$job->persist();
		$job_id = $job->get_job_id();
		Sensei_Data_Port_Manager::instance()->persist();

		$request = new WP_REST_Request( 'POST', '/sensei-internal/v1/export/' . $job_id . '/start' );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body( wp_json_encode( [ 'content_types' => [ 'course' ] ] ) );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( $response->get_status(), 200 );

		Sensei_Data_Port_Manager::instance()->persist();

		$job = Sensei_Data_Port_Manager::instance()->get_active_job( Sensei_Export_Job::class, get_current_user_id() );

		$this->assertEquals( $job->get_state( 'content_types' ), [ 'course' ] );
		$this->assertEquals( array_keys( $job->get_tasks() ), [ 'course', 'package' ] );

		$data = $response->get_data();
		$this->assertResultValidJob( $data );

	}
}
