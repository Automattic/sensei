<?php
/**
 * Class for testing with wp_die. Instead of actually dying, add a hook for the
 * appropriate handler and return a function that throws this exception
 * instead, with the args set if desired. E.g.:
 *
 * ```
 * add_filter( 'wp_die_ajax_handler', function() {
 *     return function( $message, $title, $args ) {
 *         $e = new WP_Die_Exception( 'wp_die called' );
 *         $e->set_wp_die_args( $message, $title, $args );
 *         throw $e;
 *     };
 * } );
 * ```
 *
 * Then write tests like so:
 *
 * ```
 * try {
 *     function_calling_wp_die();
 * } catch ( WP_Die_Exception $e ) {
 *     $this->assertEquals( 403, $wp_die_args['args']['response'] );
 * }
 * ```
 */
class WP_Die_Exception extends Exception {
	private $wp_die_args = null;

	public function set_wp_die_args( $message, $title, $args ) {
		$this->wp_die_args = array(
			'message' => $message,
			'title'   => $title,
			'args'    => $args,
		);
	}

	public function get_wp_die_args() {
		return $this->wp_die_args;
	}
}
