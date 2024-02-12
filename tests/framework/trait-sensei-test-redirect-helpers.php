<?php
/**
 * File with trait Sensei_Test_Redirect_Helpers.
 *
 * @package sensei-tests
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Helpers for handling wp_redirect().
 */
trait Sensei_Test_Redirect_Helpers {
	/**
	 * Prevent redirects so they can be tested.
	 * Throws a Sensei_WP_Redirect_Exception instead.
	 */
	private function prevent_wp_redirect(): void {
		$callback = function ( $location, $status ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			throw new Sensei_WP_Redirect_Exception( $location, $status );
		};

		add_filter( 'wp_redirect', $callback, 1, 2 );
	}
}
