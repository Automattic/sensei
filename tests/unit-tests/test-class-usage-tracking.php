<?php

class Sensei_Usage_Tracking_Test extends WP_UnitTestCase {
	public function setup() {
		parent::setup();
		$this->factory = new Sensei_Factory();
		$this->usage_tracking = new Sensei_Usage_Tracking();
	}

	public function teardown() {
		parent::teardown();
	}

	/**
	 * Ensure cron job action is set up.
	 */
	public function test_cron_job_action_added() {
		$this->usage_tracking->hook();
		$this->assertTrue( !! has_action( 'sensei_core_jobs_usage_tracking_send_data', array( $this->usage_tracking, 'maybe_send_usage_data' ) ) );
	}

	/**
	 * Ensure scheduling function works properly.
	 */
	public function test_maybe_schedule_tracking_task() {
		// Make sure it's cleared initially
		wp_clear_scheduled_hook( 'sensei_core_jobs_usage_tracking_send_data' );

		// Record how many times the event is scheduled
		$event_count = 0;
		add_filter( 'schedule_event', function( $event ) use ( &$event_count ) {
			if ( $event->hook === 'sensei_core_jobs_usage_tracking_send_data' ) {
				$event_count++;
			}
			return $event;
		} );

		// Should successfully schedule the task
		$this->assertFalse( wp_get_schedule( 'sensei_core_jobs_usage_tracking_send_data' ) );
		$this->usage_tracking->maybe_schedule_tracking_task();
		$this->assertNotFalse( wp_get_schedule( 'sensei_core_jobs_usage_tracking_send_data' ) );
		$this->assertEquals( 1, $event_count );

		// Should not duplicate when called again
		$this->usage_tracking->maybe_schedule_tracking_task();
		$this->assertEquals( 1, $event_count );
	}

	/* Test ajax request cases */

	/**
	 * Ensure ajax hook is set up properly.
	 */
	public function test_ajax_request_setup() {
		$this->usage_tracking->hook();
		$this->assertTrue( !! has_action( 'wp_ajax_handle_tracking_opt_in', array( $this->usage_tracking, 'handle_tracking_opt_in' ) ) );
	}

	/**
	 * Ensure tracking is enabled through ajax request.
	 */
	public function test_ajax_request_enable_tracking() {
		$this->setup_ajax_request();
		$_POST['enable_tracking'] = '1';

		$this->assertFalse( !! Sensei()->settings->get( 'sensei_usage_tracking_enabled' ) );
		$this->assertFalse( !! get_option( 'sensei_usage_tracking_opt_in_hide' ) );

		try {
			$this->usage_tracking->handle_tracking_opt_in();
		} catch ( WP_Die_Exception $e ) {
			$wp_die_args = $e->get_wp_die_args();
			$this->assertEquals( array(), $wp_die_args['args'] );
		}

		// Refresh settings
		Sensei()->settings->get_settings();

		$this->assertTrue( Sensei()->settings->get( 'sensei_usage_tracking_enabled' ) );
		$this->assertTrue( get_option( 'sensei_usage_tracking_opt_in_hide' ) );
	}

	/**
	 * Ensure tracking is disabled through ajax request.
	 */
	public function test_ajax_request_disable_tracking() {
		$this->setup_ajax_request();
		$_POST['enable_tracking'] = '0';

		$this->assertFalse( !! Sensei()->settings->get( 'sensei_usage_tracking_enabled' ) );
		$this->assertFalse( !! get_option( 'sensei_usage_tracking_opt_in_hide' ) );

		try {
			$this->usage_tracking->handle_tracking_opt_in();
		} catch ( WP_Die_Exception $e ) {
			$wp_die_args = $e->get_wp_die_args();
			$this->assertEquals( array(), $wp_die_args['args'] );
		}

		// Refresh settings
		Sensei()->settings->get_settings();

		$this->assertFalse( !! Sensei()->settings->get( 'sensei_usage_tracking_enabled' ) );
		$this->assertTrue( get_option( 'sensei_usage_tracking_opt_in_hide' ) );
	}

	/**
	 * Ensure ajax request fails on nonce failure and does not update option.
	 */
	public function test_ajax_request_failed_nonce() {
		$this->setup_ajax_request();
		$_REQUEST['nonce'] = 'invalid_nonce_1234';

		$this->assertFalse( !! Sensei()->settings->get( 'sensei_usage_tracking_enabled' ) );
		$this->assertFalse( !! get_option( 'sensei_usage_tracking_opt_in_hide' ) );

		try {
			$this->usage_tracking->handle_tracking_opt_in();
		} catch ( WP_Die_Exception $e ) {
			$wp_die_args = $e->get_wp_die_args();
			$this->assertEquals( 403, $wp_die_args['args']['response'] );
		}

		// Refresh settings
		Sensei()->settings->get_settings();

		$this->assertFalse( !! Sensei()->settings->get( 'sensei_usage_tracking_enabled' ) );
		$this->assertFalse( !! get_option( 'sensei_usage_tracking_opt_in_hide' ) );
	}

	/**
	 * Ensure ajax request fails on authorization failure and does not update option.
	 */
	public function test_ajax_request_failed_auth() {
		$this->setup_ajax_request();

		$user = wp_get_current_user();
		$user->remove_cap( 'manage_sensei' );

		$this->assertFalse( !! Sensei()->settings->get( 'sensei_usage_tracking_enabled' ) );
		$this->assertFalse( !! get_option( 'sensei_usage_tracking_opt_in_hide' ) );

		try {
			$this->usage_tracking->handle_tracking_opt_in();
		} catch ( WP_Die_Exception $e ) {
			$wp_die_args = $e->get_wp_die_args();
			$this->assertEquals( 403, $wp_die_args['args']['response'] );
		}

		// Refresh settings
		Sensei()->settings->get_settings();

		$this->assertFalse( !! Sensei()->settings->get( 'sensei_usage_tracking_enabled' ) );
		$this->assertFalse( !! get_option( 'sensei_usage_tracking_opt_in_hide' ) );
	}

	/**
	 * Helpers for ajax request.
	 */
	private function setup_ajax_request() {
		// Simulate an ajax request
		add_filter( 'wp_doing_ajax', function() { return true; } );

		// Set up nonce
		$_REQUEST['nonce'] = wp_create_nonce( 'tracking-opt-in' );

		// Set manage_sensei cap on current user
		$user = wp_get_current_user();
		$user->add_cap( 'manage_sensei' );

		// Reset the in-memory settings
		Sensei()->settings->get_settings();

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

	/* END test ajax request cases */

	/**
	 * Ensure that a request is made to the correct URL with the given
	 * properties and the default properties.
	 */
	public function test_send_event() {
		$event      = 'my_event';
		$properties = array(
			'button_clicked' => 'my_button'
		);
		$timestamp  = '1234';

		// Capture the network request, save the request URL and arguments, and
		// simulate a WP_Error
		$request_params = null;
		$request_url    = null;
		add_filter( 'pre_http_request', function( $preempt, $r, $url ) use ( &$request_params, &$request_url ) {
			$request_params = $r;
			$request_url    = $url;
			return new WP_Error();
		}, 10, 3 );

		Sensei_Usage_Tracking::send_event( 'my_event', $properties, $timestamp );

		$parsed_url = parse_url( $request_url );

		$this->assertEquals( 'pixel.wp.com', $parsed_url['host'] );
		$this->assertEquals( '/t.gif', $parsed_url['path'] );

		$query = array();
		parse_str( $parsed_url['query'], $query );
		$this->assertArraySubset( array(
			'button_clicked' => 'my_button',
			'admin_email'    => 'admin@example.org',
			'_ut'            => 'sensei:site_url',
			'_ui'            => 'http://example.org',
			'_ul'            => '',
			'_en'            => 'sensei_my_event',
			'_ts'            => '1234000',
			'_'              => '_',
		), $query );
	}

	/**
	 * Ensure that the request is only sent when the setting is enabled.
	 */
	public function test_maybe_send_usage_data() {
		$count = 0;

		// Count the number of network requests
		add_filter( 'pre_http_request', function() use ( &$count ) {
			$count++;
			return new WP_Error();
		} );

		// Setting is not set, ensure the request is not sent.
		Sensei_Usage_Tracking::maybe_send_usage_data();
		$this->assertEquals( 0, $count );

		// Set the setting and ensure request is sent.
		Sensei()->settings->set( 'sensei_usage_tracking_enabled', true );
		Sensei()->settings->get_settings();

		Sensei_Usage_Tracking::maybe_send_usage_data();
		$this->assertEquals( 1, $count );
	}

	/* Tests for tracking opt in dialog */

	/**
	 * When setting is not set, dialog is not hidden, and user has capability,
	 * we should see the dialog and Enable Usage Tracking button.
	 */
	public function test_display_tracking_opt_in() {
		$this->setup_opt_in_dialog();

		$this->expectOutputRegex( '/Enable Usage Tracking/' );
		$this->usage_tracking->maybe_display_tracking_opt_in();
	}

	/**
	 * When setting is already set, dialog should not appear.
	 */
	public function test_do_not_display_tracking_opt_in_when_setting_enabled() {
		$this->setup_opt_in_dialog();
		Sensei()->settings->set( 'sensei_usage_tracking_enabled', true );
		Sensei()->settings->get_settings();

		$this->expectOutputString( '' );
		$this->usage_tracking->maybe_display_tracking_opt_in();
	}

	/**
	 * When option is set to hide the dialog, it should not appear.
	 */
	public function test_do_not_display_tracking_opt_in_when_dialog_hidden() {
		$this->setup_opt_in_dialog();
		update_option( 'sensei_usage_tracking_opt_in_hide', true );

		$this->expectOutputString( '' );
		$this->usage_tracking->maybe_display_tracking_opt_in();
	}

	/**
	 * When user does not have permission to manage usage tracking, dialog
	 * should not appear.
	 */
	public function test_do_not_display_tracking_opt_in_when_user_not_authorized() {
		$this->setup_opt_in_dialog();
		$user = wp_get_current_user();
		$user->remove_cap( 'manage_sensei' );

		$this->expectOutputString( '' );
		$this->usage_tracking->maybe_display_tracking_opt_in();
	}

	/**
	 * Helper method to set up tracking opt-in dialog.
	 */
	private function setup_opt_in_dialog() {
		// Set manage_sensei cap on current user
		$user = wp_get_current_user();
		$user->add_cap( 'manage_sensei' );

		// Ensure setting is not set
		Sensei()->settings->set( 'sensei_usage_tracking_enabled', false );

		// Reset the in-memory settings
		Sensei()->settings->get_settings();
	}

	/* END tests for tracking opt in dialog */
}
