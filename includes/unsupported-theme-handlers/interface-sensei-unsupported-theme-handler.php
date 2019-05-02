<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sensei interface for Unsupported Theme Handlers.
 *
 * @author Automattic
 *
 * @since 1.12.0
 */
interface Sensei_Unsupported_Theme_Handler_Interface {

	/**
	 * Return a boolean specifying whether this handler can handle the current
	 * request.
	 *
	 * @return bool
	 */
	public function can_handle_request();

	/**
	 * Handle the current request.
	 */
	public function handle_request();

}
