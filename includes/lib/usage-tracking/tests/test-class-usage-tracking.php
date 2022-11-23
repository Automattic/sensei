<?php

require_once dirname( __FILE__ ) . '/support/class-usage-tracking-test-subclass.php';
require_once dirname( __FILE__ ) . '/support/wp-die-exception.php';

// Ensure instance is set up before PHPUnit starts removing hooks.
Usage_Tracking_Test_Subclass::get_instance();

/**
 * Usage Tracking tests. Please update the prefix to something unique to your
 * plugin.
 *
 * @group usage-tracking
 */
class Sensei_Base_Usage_Tracking_Test extends WP_UnitTestCase {
	private $event_counts       = array();
	private $track_http_request = array();

	public function setUp() {
		parent::setUp();
		// Update the class name here to match the Usage Tracking class.
		$this->usage_tracking = Usage_Tracking_Test_Subclass::get_instance();
		$this->usage_tracking->set_callback( array( $this, 'basicDataCallback' ) );
	}

	/**
	 * Ensure cron job action is set up.
	 *
	 * @covers {Prefix}_Usage_Tracking::hook
	 */
	public function testCronJobActionAdded() {
		$this->assertTrue( ! ! has_action( $this->usage_tracking->get_prefix() . '_usage_tracking_send_usage_data', array( $this->usage_tracking, 'send_usage_data' ) ) );
	}

	/**
	 * Ensure scheduling function works properly.
	 *
	 * @covers {Prefix}_Usage_Tracking::schedule_tracking_task
	 */
	public function testScheduleTrackingTask() {
		// Make sure it's cleared initially
		wp_clear_scheduled_hook( $this->usage_tracking->get_prefix() . '_usage_tracking_send_usage_data' );

		// Record how many times the event is scheduled
		$this->event_counts['schedule_event'] = 0;
		add_filter( 'schedule_event', array( $this, 'countScheduleEvent' ) );

		// Should successfully schedule the task
		$this->assertFalse( wp_get_schedule( $this->usage_tracking->get_prefix() . '_usage_tracking_send_usage_data' ), 'Not scheduled initial' );
		$this->usage_tracking->schedule_tracking_task();
		$this->assertNotFalse( wp_get_schedule( $this->usage_tracking->get_prefix() . '_usage_tracking_send_usage_data' ), 'Schedules a job' );
		$this->assertEquals( 1, $this->event_counts['schedule_event'], 'Schedules only one job' );

		// Should not duplicate when called again
		$this->usage_tracking->schedule_tracking_task();
		$this->assertEquals( 1, $this->event_counts['schedule_event'], 'Does not schedule an additional job' );
	}


	/**
	 * Ensure that a request is made to the correct URL with the given
	 * properties and the default properties.
	 *
	 * @covers {Prefix}_Usage_Tracking::send_event
	 */
	public function testSendEvent() {
		$event      = 'my_event';
		$properties = array(
			'button_clicked' => 'my_button',
		);
		$timestamp  = '1234';

		// Enable tracking
		$this->usage_tracking->set_tracking_enabled( true );

		// Capture the network request, save the request URL and arguments, and
		// simulate a WP_Error
		$this->track_http_request['request_params'] = null;
		$this->track_http_request['request_url']    = null;
		add_filter( 'pre_http_request', array( $this, 'trackHttpRequest' ), 10, 3 );

		$this->usage_tracking->send_event( 'my_event', $properties, $timestamp );

		$parsed_url = wp_parse_url( $this->track_http_request['request_url'] );

		$this->assertEquals( 'pixel.wp.com', $parsed_url['host'], 'Host' );
		$this->assertEquals( '/t.gif', $parsed_url['path'], 'Path' );

		$query = array();
		parse_str( $parsed_url['query'], $query );

		// Older versions (for PHP 5.2) of PHPUnit do not have this method
		if ( method_exists( $this, 'assertArraySubset' ) ) {
			$this->assertArraySubset(
				array(
					'button_clicked' => 'my_button',
					'admin_email'    => 'admin@example.org',
					'_ut'            => $this->usage_tracking->get_prefix() . ':site_url',
					'_ui'            => 'example.org',
					'_ul'            => '',
					'_en'            => $this->usage_tracking->get_prefix() . '_my_event',
					'_ts'            => '1234000',
					'_'              => '_',
				),
				$query,
				'Query parameters'
			);
		}
	}

	/**
	 * Ensure that the request is not made if tracking is not enabled, unless
	 * $force is true.
	 *
	 * @covers {Prefix}_Usage_Tracking::send_event
	 */
	public function testSendEventWithTrackingDisabled() {
		$event      = 'my_event';
		$properties = array(
			'button_clicked' => 'my_button',
		);
		$timestamp  = '1234';

		// Disable tracking
		$this->usage_tracking->set_tracking_enabled( false );

		// Count network requests
		$this->event_counts['http_request'] = 0;
		add_filter( 'pre_http_request', array( $this, 'countHttpRequest' ) );

		$this->usage_tracking->send_event( 'my_event', $properties, $timestamp );
		$this->assertEquals( 0, $this->event_counts['http_request'], 'No request when disabled' );
	}

	/**
	 * Ensure that the request is only sent when the setting is enabled.
	 *
	 * @covers {Prefix}_Usage_Tracking::maybe_send_usage_data
	 */
	public function testSendUsageData() {
		// Count the number of network requests
		$this->event_counts['http_request'] = 0;
		add_filter( 'pre_http_request', array( $this, 'countHttpRequest' ) );

		// Setting is not set, ensure the request is not sent.
		$this->usage_tracking->send_usage_data();
		$this->assertEquals( 0, $this->event_counts['http_request'], 'Request not sent when Usage Tracking disabled' );

		// Set the setting and ensure request is sent.
		$this->usage_tracking->set_tracking_enabled( true );

		$this->usage_tracking->send_usage_data();
		$this->assertEquals( 4, $this->event_counts['http_request'], 'Requests sent when Usage Tracking enabled' );
	}

	/* Tests for system data */

	/**
	 * Tests the basic structure for collected system data.
	 *
	 * @covers {Prefix}_Usage_Tracking::get_system_data
	 * @group track-system-data
	 */
	public function testSystemDataStructure() {
		global $wp_version;

		$system_data = $this->usage_tracking->get_system_data();

		$this->assertInternalType( 'array', $system_data, 'System data must be returned as an array' );

		$this->assertArrayHasKey( 'wp_version', $system_data, '`wp_version` key must exist in system data' );
		$this->assertEquals( $wp_version, $system_data['wp_version'], '`wp_version` does not match expected value' );

		$this->assertArrayHasKey( 'php_version', $system_data, '`php_version` key must exist in system data' );
		$this->assertEquals( PHP_VERSION, $system_data['php_version'], '`php_version` does not match expected value' );

		$this->assertArrayHasKey( 'locale', $system_data, '`locale` key must exist in system data' );
		$this->assertEquals( get_locale(), $system_data['locale'], '`locale` does not match expected value' );

		$this->assertArrayHasKey( 'multisite', $system_data, '`multisite` key must exist in system data' );
		$this->assertEquals( is_multisite(), $system_data['multisite'], '`multisite` does not match expected value' );

		/**
		 * Current active theme.
		 *
		 * @var WP_Theme $theme
		 */
		$theme = wp_get_theme();

		$this->assertArrayHasKey( 'active_theme', $system_data, '`active_theme` key must exist in system data' );
		$this->assertEquals( $theme['Name'], $system_data['active_theme'], '`active_theme` does not match expected value' );

		$this->assertArrayHasKey( 'active_theme_version', $system_data, '`active_theme_version` key must exist in system data' );
		$this->assertEquals( $theme['Version'], $system_data['active_theme_version'], '`active_theme_version` does not match expected value' );

		$this->assertArrayHasKey( 'plugin_my_favorite_plugin', $system_data, '`plugin_my_favorite_plugin` key must exist in system data' );
		$this->assertEquals( '1.0.0', $system_data['plugin_my_favorite_plugin'], '`plugin_my_favorite_plugin` does not match expected value' );

		$this->assertArrayHasKey( 'plugin_hello', $system_data, '`plugin_hello` key must exist in system data' );
		$this->assertEquals( '1.0.0', $system_data['plugin_my_favorite_plugin'], '`plugin_hello` does not match expected value' );

		$this->assertArrayHasKey( 'plugin_test', $system_data, '`plugin_test` key must exist in system data' );
		$this->assertEquals( '1.0.0', $system_data['plugin_test'], '`plugin_test` does not match expected value' );

		$this->assertArrayNotHasKey( 'plugin_jetpack', $system_data, '`plugin_jetpack` key must NOT exist in system data' );
		$this->assertArrayNotHasKey( 'plugin_test_dev', $system_data, '`plugin_test_dev` key must NOT exist in system data' );

		$plugin_prefix_count = 0;
		foreach ( $system_data as $key => $value ) {
			if ( 1 === preg_match( '/^plugin_/', $key ) ) {
				$plugin_prefix_count++;
			}
		}

		$this->assertEquals( 3, $plugin_prefix_count );
	}

	/* END tests for system data */

	/****** Helper methods ******/

	/**
	 * Update the capability for the current user to be able to enable or
	 * disable tracking.
	 *
	 * @param bool $allow true if the current user should be allowed to update
	 * the tracking setting, false otherwise. Default: true
	 **/
	private function allowCurrentUserToEnableTracking( $allow = true ) {
		$user = wp_get_current_user();

		if ( $allow ) {
			$user->add_cap( 'manage_usage_tracking' );
		} else {
			$user->remove_cap( 'manage_usage_tracking' );
		}
	}

	/**
	 * Callback helpers.
	 */

	/**
	 * Basic callback for usage data.
	 *
	 * @return array
	 */
	public function basicDataCallback() {
		return array( 'testing' => true );
	}

	/**
	 * Sets the die handler for ajax request.
	 *
	 * @return array
	 */
	public function ajaxDieHandler() {
		return array( $this, 'ajaxDieHandlerCallback' );
	}

	/**
	 * Error handler for ajax requests.
	 *
	 * @param string $message
	 * @param string $title
	 * @param array  $args
	 *
	 * @throws WP_Die_Exception
	 */
	public function ajaxDieHandlerCallback( $message, $title, $args ) {
		$e = new WP_Die_Exception( 'wp_die called' );
		$e->set_wp_die_args( $message, $title, $args );
		throw $e;
	}

	/**
	 * Count the number of times an event is scheduled.
	 *
	 * @param object $event
	 *
	 * @return object
	 */
	public function countScheduleEvent( $event ) {
		$prefix = $this->usage_tracking->get_prefix();
		if ( $event->hook === $prefix . '_usage_tracking_send_usage_data' ) {
			$this->event_counts['schedule_event']++;
		}
		return $event;
	}

	/**
	 * Count the number of HTTP requests.
	 *
	 * @return WP_Error
	 */
	public function countHttpRequest() {
		$this->event_counts['http_request']++;
		return new WP_Error();
	}

	/**
	 * Track HTTP request params and URL.
	 *
	 * @param string $preempt
	 * @param array  $r
	 * @param string $url
	 *
	 * @return WP_Error
	 */
	public function trackHttpRequest( $preempt, $r, $url ) {
		$this->track_http_request['request_params'] = $r;
		$this->track_http_request['request_url']    = $url;
		return new WP_Error();
	}
}
