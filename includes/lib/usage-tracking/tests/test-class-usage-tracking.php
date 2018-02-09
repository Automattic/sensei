<?php

include dirname( __FILE__ ) . '/support/class-usage-tracking-test-subclass.php';
include dirname( __FILE__ ) . '/support/wp-die-exception.php';

// Ensure instance is set up before PHPUnit starts removing hooks.
Usage_Tracking_Test_Subclass::get_instance();

/**
 * Usage Tracking tests. Please update the prefix to something unique to your
 * plugin.
 */
class Sensei_Usage_Tracking_Test extends WP_UnitTestCase {

	public function setUp() {
		parent::setUp();
		// Update the class name here to match the Usage Tracking class.
		$this->usage_tracking = Usage_Tracking_Test_Subclass::get_instance();
		$this->usage_tracking->set_callback( function() {
			return array( 'testing' => true );
		} );
	}

	/**
	 * Ensure cron job action is set up.
	 *
	 * @covers {Prefix}_Usage_Tracking::hook
	 */
	public function testCronJobActionAdded() {
		$this->assertTrue( !! has_action( $this->usage_tracking->get_prefix() . '_usage_tracking_send_usage_data', array( $this->usage_tracking, 'send_usage_data' ) ) );
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
		$prefix = $this->usage_tracking->get_prefix();
		$event_count = 0;
		add_filter( 'schedule_event', function( $event ) use ( &$event_count, $prefix ) {
			if ( $event->hook === $prefix . '_usage_tracking_send_usage_data' ) {
				$event_count++;
			}
			return $event;
		} );

		// Should successfully schedule the task
		$this->assertFalse( wp_get_schedule( $this->usage_tracking->get_prefix() . '_usage_tracking_send_usage_data' ), 'Not scheduled initial' );
		$this->usage_tracking->schedule_tracking_task();
		$this->assertNotFalse( wp_get_schedule( $this->usage_tracking->get_prefix() . '_usage_tracking_send_usage_data' ), 'Schedules a job' );
		$this->assertEquals( 1, $event_count, 'Schedules only one job' );

		// Should not duplicate when called again
		$this->usage_tracking->schedule_tracking_task();
		$this->assertEquals( 1, $event_count, 'Does not schedule an additional job' );
	}

	/* Test ajax request cases */

	/**
	 * Ensure ajax hook is set up properly.
	 *
	 * @covers {Prefix}_Usage_Tracking::hook
	 */
	public function testAjaxRequestSetup() {
		$this->assertTrue( !! has_action( 'wp_ajax_' . $this->usage_tracking->get_prefix() . '_handle_tracking_opt_in', array( $this->usage_tracking, 'handle_tracking_opt_in' ) ) );
	}

	/**
	 * Ensure tracking is enabled through ajax request.
	 *
	 * @covers {Prefix}_Usage_Tracking::_handle_tracking_opt_in
	 */
	public function testAjaxRequestEnableTracking() {
		$this->setupAjaxRequest();
		$_POST['enable_tracking'] = '1';

		$this->assertFalse( !! $this->usage_tracking->is_tracking_enabled(), 'Usage tracking initially disabled' );
		$this->assertFalse( !! get_option( $this->usage_tracking->get_prefix() . '_usage_tracking_opt_in_hide' ), 'Dialog initially shown' );

		try {
			$this->usage_tracking->handle_tracking_opt_in();
		} catch ( WP_Die_Exception $e ) {
			$wp_die_args = $e->get_wp_die_args();
			$this->assertEquals( array(), $wp_die_args['args'], 'wp_die call has no non-success status' );
		}

		$this->assertTrue( $this->usage_tracking->is_tracking_enabled(), 'Usage tracking enabled' );
		$this->assertTrue( get_option( $this->usage_tracking->get_prefix() . '_usage_tracking_opt_in_hide' ), 'Dialog hidden' );
	}

	/**
	 * Ensure tracking is disabled through ajax request.
	 *
	 * @covers {Prefix}_Usage_Tracking::_handle_tracking_opt_in
	 */
	public function testAjaxRequestDisableTracking() {
		$this->setupAjaxRequest();
		$_POST['enable_tracking'] = '0';

		$this->assertFalse( !! $this->usage_tracking->is_tracking_enabled(), 'Usage tracking initially disabled' );
		$this->assertFalse( !! get_option( $this->usage_tracking->get_prefix() . '_usage_tracking_opt_in_hide' ), 'Dialog initially shown' );

		try {
			$this->usage_tracking->handle_tracking_opt_in();
		} catch ( WP_Die_Exception $e ) {
			$wp_die_args = $e->get_wp_die_args();
			$this->assertEquals( array(), $wp_die_args['args'], 'wp_die call has no non-success status' );
		}

		$this->assertFalse( !! $this->usage_tracking->is_tracking_enabled(), 'Usage tracking disabled' );
		$this->assertTrue( get_option( $this->usage_tracking->get_prefix() . '_usage_tracking_opt_in_hide' ), 'Dialog hidden' );
	}

	/**
	 * Ensure ajax request fails on nonce failure and does not update option.
	 *
	 * @covers {Prefix}_Usage_Tracking::_handle_tracking_opt_in
	 */
	public function testAjaxRequestFailedNonce() {
		$this->setupAjaxRequest();
		$_REQUEST['nonce'] = 'invalid_nonce_1234';

		$this->assertFalse( !! $this->usage_tracking->is_tracking_enabled(), 'Usage tracking initially disabled' );
		$this->assertFalse( !! get_option( $this->usage_tracking->get_prefix() . '_usage_tracking_opt_in_hide' ), 'Dialog initially shown' );

		try {
			$this->usage_tracking->handle_tracking_opt_in();
		} catch ( WP_Die_Exception $e ) {
			$wp_die_args = $e->get_wp_die_args();
			$this->assertEquals( 403, $wp_die_args['args']['response'], 'wp_die called has "Forbidden" status' );
		}

		$this->assertFalse( !! $this->usage_tracking->is_tracking_enabled(), 'Usage tracking disabled' );
		$this->assertFalse( !! get_option( $this->usage_tracking->get_prefix() . '_usage_tracking_opt_in_hide' ), 'Dialog not hidden' );
	}

	/**
	 * Ensure ajax request fails on authorization failure and does not update option.
	 *
	 * @covers {Prefix}_Usage_Tracking::_handle_tracking_opt_in
	 */
	public function testAjaxRequestFailedAuth() {
		$this->setupAjaxRequest();

		// Current user cannot enable tracking
		$this->allowCurrentUserToEnableTracking( false );

		$this->assertFalse( !! $this->usage_tracking->is_tracking_enabled(), 'Usage tracking initially disabled' );
		$this->assertFalse( !! get_option( $this->usage_tracking->get_prefix() . '_usage_tracking_opt_in_hide' ), 'Dialog initially shown' );

		try {
			$this->usage_tracking->handle_tracking_opt_in();
		} catch ( WP_Die_Exception $e ) {
			$wp_die_args = $e->get_wp_die_args();
			$this->assertEquals( 403, $wp_die_args['args']['response'], 'wp_die called has "Forbidden" status' );
		}

		$this->assertFalse( !! $this->usage_tracking->is_tracking_enabled(), 'Usage tracking disabled' );
		$this->assertFalse( !! get_option( $this->usage_tracking->get_prefix() . '_usage_tracking_opt_in_hide' ), 'Dialog not hidden' );
	}

	/* END test ajax request cases */

	/**
	 * Ensure that a request is made to the correct URL with the given
	 * properties and the default properties.
	 *
	 * @covers {Prefix}_Usage_Tracking::send_event
	 */
	public function testSendEvent() {
		$event      = 'my_event';
		$properties = array(
			'button_clicked' => 'my_button'
		);
		$timestamp  = '1234';

		// Enable tracking
		$this->usage_tracking->set_tracking_enabled( true );

		// Capture the network request, save the request URL and arguments, and
		// simulate a WP_Error
		$request_params = null;
		$request_url    = null;
		add_filter( 'pre_http_request', function( $preempt, $r, $url ) use ( &$request_params, &$request_url ) {
			$request_params = $r;
			$request_url    = $url;
			return new WP_Error();
		}, 10, 3 );

		$this->usage_tracking->send_event( 'my_event', $properties, $timestamp );

		$parsed_url = parse_url( $request_url );

		$this->assertEquals( 'pixel.wp.com', $parsed_url['host'], 'Host' );
		$this->assertEquals( '/t.gif', $parsed_url['path'], 'Path' );

		$query = array();
		parse_str( $parsed_url['query'], $query );
		$this->assertArraySubset( array(
			'button_clicked' => 'my_button',
			'admin_email'    => 'admin@example.org',
			'_ut'            => $this->usage_tracking->get_prefix() . ':site_url',
			'_ui'            => 'http://example.org',
			'_ul'            => '',
			'_en'            => $this->usage_tracking->get_prefix() . '_my_event',
			'_ts'            => '1234000',
			'_'              => '_',
		), $query, 'Query parameters' );
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
			'button_clicked' => 'my_button'
		);
		$timestamp  = '1234';

		// Disable tracking
		$this->usage_tracking->set_tracking_enabled( false );

		// Count network requests
		$count = 0;
		add_filter( 'pre_http_request', function() use ( &$count ) {
			$count++;
			return new WP_Error();
		} );

		$this->usage_tracking->send_event( 'my_event', $properties, $timestamp );
		$this->assertEquals( 0, $count, 'No request when disabled' );
	}

	/**
	 * Ensure that the request is only sent when the setting is enabled.
	 *
	 * @covers {Prefix}_Usage_Tracking::maybe_send_usage_data
	 */
	public function testSendUsageData() {
		$count = 0;

		// Count the number of network requests
		add_filter( 'pre_http_request', function() use ( &$count ) {
			$count++;
			return new WP_Error();
		} );

		// Setting is not set, ensure the request is not sent.
		$this->usage_tracking->send_usage_data();
		$this->assertEquals( 0, $count, 'Request not sent when Usage Tracking disabled' );

		// Set the setting and ensure request is sent.
		$this->usage_tracking->set_tracking_enabled( true );

		$this->usage_tracking->send_usage_data();
		$this->assertEquals( 1, $count, 'Request sent when Usage Tracking enabled' );
	}

	/* Tests for tracking opt in dialog */

	/**
	 * When setting is not set, dialog is not hidden, and user has capability,
	 * we should see the dialog and Enable Usage Tracking button.
	 *
	 * @covers {Prefix}_Usage_Tracking::_maybe_display_tracking_opt_in
	 */
	public function testDisplayTrackingOptIn() {
		$this->setupOptInDialog();

		$this->expectOutputRegex( '/Enable Usage Tracking/' );
		$this->usage_tracking->maybe_display_tracking_opt_in();
	}

	/**
	 * When setting is already set, dialog should not appear.
	 *
	 * @covers {Prefix}_Usage_Tracking::_maybe_display_tracking_opt_in
	 */
	public function testDoNotDisplayTrackingOptInWhenSettingEnabled() {
		$this->setupOptInDialog();
		$this->usage_tracking->set_tracking_enabled( true );

		$this->expectOutputString( '' );
		$this->usage_tracking->maybe_display_tracking_opt_in();
	}

	/**
	 * When option is set to hide the dialog, it should not appear.
	 *
	 * @covers {Prefix}_Usage_Tracking::_maybe_display_tracking_opt_in
	 */
	public function testDoNotDisplayTrackingOptInWhenDialogHidden() {
		$this->setupOptInDialog();
		update_option( $this->usage_tracking->get_prefix() . '_usage_tracking_opt_in_hide', true );

		$this->expectOutputString( '' );
		$this->usage_tracking->maybe_display_tracking_opt_in();
	}

	/**
	 * When user does not have permission to manage usage tracking, dialog
	 * should not appear.
	 *
	 * @covers {Prefix}_Usage_Tracking::_maybe_display_tracking_opt_in
	 */
	public function testDoNotDisplayTrackingOptInWhenUserNotAuthorized() {
		$this->setupOptInDialog();
		$this->allowCurrentUserToEnableTracking( false );

		$this->expectOutputString( '' );
		$this->usage_tracking->maybe_display_tracking_opt_in();
	}

	/* END tests for tracking opt in dialog */


	/****** Helper methods ******/

	/**
	 * Helper method for ajax request.
	 */
	private function setupAjaxRequest() {
		// Simulate an ajax request
		add_filter( 'wp_doing_ajax', function() { return true; } );

		// Set up nonce
		$_REQUEST['nonce'] = wp_create_nonce( 'tracking-opt-in' );

		// Ensure current user can enable tracking
		$this->allowCurrentUserToEnableTracking();

		// When wp_die is called, save the args and throw an exception to stop
		// execution.
		add_filter( 'wp_die_ajax_handler', function() {
			return function( $message, $title, $args ) {
				$e = new WP_Die_Exception( 'wp_die called' );
				$e->set_wp_die_args( $message, $title, $args );
				throw $e;
			};
		} );
	}

	/**
	 * Helper method to set up tracking opt-in dialog.
	 */
	private function setupOptInDialog() {
		// Ensure current user can enable tracking
		$this->allowCurrentUserToEnableTracking();

		// Ensure setting is not set
		$this->usage_tracking->set_tracking_enabled( false );
	}

	/**
	 * Update the capaility for the current user to be able to enable or
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
}
