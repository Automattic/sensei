<?php
/**
 * File containing Sensei_Tool_Interactive_Interface interface.
 *
 * @package sensei-lms
 * @since 3.7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Sensei_Tool_Interactive_Interface interface for tools that have an interactive element.
 *
 * @since 3.7.0
 */
interface Sensei_Tool_Interactive_Interface {
	/**
	 * Output tool view for interactive action methods.
	 */
	public function output();
}
