<?php
/**
 * File with class for testing events.
 *
 * @package sensei-tests
 */

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
	 *
	 * @var array
	 */
	private static $_logged_events = [];

	/**
	 * Intialize the event logging test class.
	 */
	public static function init() {
		// Set up filter to intercept event logging.
		add_filter(
			'pre_http_request',
			function( $preempt, $r, $url ) {
				$host = wp_parse_url( $url, PHP_URL_HOST );
				$path = wp_parse_url( $url, PHP_URL_PATH );
				if ( 'pixel.wp.com' === $host && '/t.gif' === $path ) {
					// Log event.
					$args = [];
					parse_str( wp_parse_url( $url, PHP_URL_QUERY ), $args );
					array_push(
						self::$_logged_events,
						[
							'event_name' => $args['_en'],
							'url_args'   => $args,
						]
					);
					return new WP_Error();
				}
				return $preempt;
			},
			10,
			3
		);

		// Ensure event logging is enabled.
		Sensei()->settings->set( Sensei_Usage_Tracking::SENSEI_SETTING_NAME, true );
		Sensei()->settings->get_settings();
	}

	/**
	 * Get the list of events that have been logged.
	 *
	 * @param string $event_name Optional event name to filter by.
	 */
	public static function get_logged_events( $event_name = null ) {
		if ( $event_name ) {
			return array_values(
				array_filter(
					self::$_logged_events,
					function( $element ) use ( $event_name ) {
						return $event_name === $element['event_name'];
					}
				)
			);
		} else {
			return self::$_logged_events;
		}
	}

	/**
	 * Clear the list of events that have been logged.
	 */
	public static function reset() {
		self::$_logged_events = [];
	}
}
