<?php
/**
 * Tests for Sensei_Admin::ajax_log_event().
 *
 * @package sensei-tests
 *
 * @group ajax-calls
 */
class Sensei_Admin_Log_Event_AJAX_Test extends WP_Ajax_UnitTestCase {

	/**
	 * Names of the events the handler logged.
	 *
	 * @var string[]
	 */
	private $logged_events = array();

	public function setUp(): void {
		parent::setUp();

		// The handler is only hooked on admin requests, so hook it here.
		new Sensei_Admin();

		add_filter( 'pre_http_request', '__return_empty_array' );
		add_filter( 'sensei_log_event', array( $this, 'record_event' ), 10, 2 );
	}

	public function tearDown(): void {
		remove_filter( 'pre_http_request', '__return_empty_array' );
		remove_filter( 'sensei_log_event', array( $this, 'record_event' ), 10 );

		parent::tearDown();
	}

	public function testAjaxLogEvent_NoNonceGiven_DoesNotLogEvent() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'teacher' ) ) );
		$_GET = array( 'event_name' => 'spoofed_event' );

		$this->dispatch_log_event();

		$this->assertEmpty( $this->logged_events );
	}

	public function testAjaxLogEvent_SubscriberGiven_DoesNotLogEvent() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );
		$_GET = array(
			'event_name' => 'spoofed_event',
			'nonce'      => wp_create_nonce( 'sensei_log_event' ),
		);

		$this->dispatch_log_event();

		$this->assertEmpty( $this->logged_events );
	}

	public function testAjaxLogEvent_TeacherGiven_LogsEvent() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'teacher' ) ) );
		$_GET = array(
			'event_name' => 'courses_view',
			'nonce'      => wp_create_nonce( 'sensei_log_event' ),
		);

		$this->dispatch_log_event();

		$this->assertSame( array( 'courses_view' ), $this->logged_events );
	}

	/**
	 * Record the event instead of sending it.
	 *
	 * @param bool   $log_event  Whether to log the event.
	 * @param string $event_name The event name.
	 *
	 * @return false Never send the event.
	 */
	public function record_event( $log_event, $event_name ) {
		$this->logged_events[] = $event_name;

		return false;
	}

	/**
	 * Dispatch the AJAX action with the request in $_GET, as the JS fallback sends it.
	 */
	private function dispatch_log_event(): void {
		try {
			$this->_handleAjax( 'sensei_log_event' );
		} catch ( WPAjaxDieContinueException | WPAjaxDieStopException $e ) {
			unset( $e );
		}
	}
}
