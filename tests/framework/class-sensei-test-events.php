<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Test class for event logging.
 *
 * @author Automattic
 *
 * @since 2.1.0
 */
class Sensei_Test_Events {
	/**
	 * The internal list of logged events.
	 */
	private static $_logged_events = [];

	/**
	 * Intialize the event logging test class.
	 */
	public static function init() {
		// Set up filter to intercept event logging.
		add_filter( 'pre_http_request', function( $preempt, $r, $url ) {
			$host = parse_url( $url, PHP_URL_HOST );
			$path = parse_url( $url, PHP_URL_PATH );
			if ( 'pixel.wp.com' === $host && '/t.gif' === $path ) {
				// Log event.
				$args = [];
				parse_str( parse_url( $url, PHP_URL_QUERY ), $args );
				array_push( Sensei_Test_Events::$_logged_events, [
					'event_name' => $args['_en'],
					'url_args'   => $args,
				] );
				return new WP_Error();
			}
			return $preempt;
		}, 10, 3 );

		// Ensure event logging is enabled.
		Sensei()->settings->set( Sensei_Usage_Tracking::SENSEI_SETTING_NAME, true );
		Sensei()->settings->get_settings();
	}

	/**
	 * Get the list of events that have been logged.
	 */
	public static function get_logged_events() {
		return Sensei_Test_Events::$_logged_events;
	}

	/**
	 * Clear the list of events that have been logged.
	 */
	public static function reset() {
		Sensei_Test_Events::$_logged_events = [];
	}
}
