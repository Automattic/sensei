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
	public function setUp(): void {
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
	public function tearDown(): void {
		parent::tearDown();

		global $wp_rest_server;
		$wp_rest_server = null;
	}

	/**
	 * A request with a `selections` payload starts the job and the chosen
	 * types are reflected in the job's state.
	 */
	public function testStartJob_SelectionsPayload_StartsJobWithChosenTypes() {
		$job_id = $this->create_persisted_export_job();

		$response = $this->dispatch_start( $job_id, array( 'selections' => array( 'course' => array() ) ) );

		self::assertSame( 200, $response->get_status(), 'Endpoint should return 200 for a valid selections payload.' );

		$job = $this->reload_active_export_job();

		$expected_tasks = array( 'course' );
		if ( class_exists( 'ZipArchive' ) ) {
			$expected_tasks[] = 'package';
		}

		self::assertSame( array( 'course' ), $job->get_content_types(), 'Content types should derive from the selections keys.' );
		self::assertSame( $expected_tasks, array_keys( $job->get_tasks() ), 'Task list should match the chosen types plus the package task.' );

		$this->assertResultValidJob( $response->get_data() );
	}

	/**
	 * Per-type post IDs supplied in the payload are persisted on the job so
	 * the export can restrict its query later.
	 */
	public function testStartJob_SelectionsWithPostIds_PersistsSelectionOnJob() {
		$job_id = $this->create_persisted_export_job();

		$response = $this->dispatch_start(
			$job_id,
			array(
				'selections' => array(
					'course' => array( 42, 99 ),
				),
			)
		);

		self::assertSame( 200, $response->get_status(), 'Endpoint should return 200 for a valid selections payload.' );

		$job = $this->reload_active_export_job();
		self::assertSame( array( 42, 99 ), $job->get_selection( 'course' ), 'Course IDs from the payload should round-trip onto the job.' );
	}

	/**
	 * A request without a `selections` field is rejected.
	 */
	public function testStartJob_MissingSelections_Returns400() {
		$job_id = $this->create_persisted_export_job();

		$response = $this->dispatch_start( $job_id, array() );

		self::assertSame( 400, $response->get_status(), 'Missing selections should return a 400.' );
	}

	/**
	 * A `selections` payload whose keys are all unknown (e.g. typos) normalises
	 * to no enabled types and is rejected with 400 instead of starting a no-op
	 * export.
	 */
	public function testStartJob_SelectionsWithOnlyUnknownKeys_Returns400() {
		$job_id = $this->create_persisted_export_job();

		$response = $this->dispatch_start(
			$job_id,
			array(
				'selections' => array(
					'courses' => array( 1 ),
				),
			)
		);

		self::assertSame( 400, $response->get_status(), 'Unknown selection keys should be rejected with a 400.' );
	}

	/**
	 * Create and persist an export job for the current user, returning its id.
	 *
	 * @return string
	 */
	private function create_persisted_export_job() {
		$job = Sensei_Data_Port_Manager::instance()->create_export_job( get_current_user_id() );
		$job->persist();
		Sensei_Data_Port_Manager::instance()->persist();

		return $job->get_job_id();
	}

	/**
	 * Dispatch a JSON POST to the /export/{job_id}/start endpoint.
	 *
	 * @param string $job_id The job id.
	 * @param array  $body   The JSON payload.
	 *
	 * @return WP_REST_Response
	 */
	private function dispatch_start( $job_id, array $body ) {
		$request = new WP_REST_Request( 'POST', '/sensei-internal/v1/export/' . $job_id . '/start' );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body( wp_json_encode( $body ) );

		return $this->server->dispatch( $request );
	}

	/**
	 * Persist the data port manager and return the freshly-loaded active job.
	 *
	 * @return Sensei_Export_Job
	 */
	private function reload_active_export_job() {
		Sensei_Data_Port_Manager::instance()->persist();

		return Sensei_Data_Port_Manager::instance()->get_active_job( Sensei_Export_Job::class, get_current_user_id() );
	}
}
