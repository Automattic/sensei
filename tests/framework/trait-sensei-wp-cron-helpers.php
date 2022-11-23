<?php
/**
 * File with trait Sensei_WP_Cron_Helpers.
 *
 * @package sensei-tests
 */

// phpcs:disable WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid -- Using PHPUnit conventions.

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Helpers for simulating WP Cron.
 *
 * @since 3.9.0
 */
trait Sensei_WP_Cron_Helpers {
	/**
	 * Run all scheduled instances of an event.
	 *
	 * @param string $event Event that should run.
	 */
	private function runAllScheduledEvents( $event ) {
		$cron = _get_cron_array();
		foreach ( $cron as $time => $events ) {
			if ( isset( $events[ $event ] ) ) {
				foreach ( $events[ $event ] as $config ) {
					// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound -- Special test helper.
					do_action_ref_array( $event, $config['args'] );
				}
			}
		}
	}
}
