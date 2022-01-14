<?php
/**
 * File containing Sensei_Tool_Export class.
 *
 * @package sensei-lms
 * @since 4.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Sensei_Tool_Export class.
 *
 * @package sensei-lms
 * @since 4.0.0
 */
class Sensei_Tool_Export implements Sensei_Tool_Interface, Sensei_Tool_Interactive_Interface {
	/**
	 * Output tool view for interactive action methods.
	 */
	public function output() {
		include __DIR__ . '/views/html-export.php';
	}

	/**
	 * Get the ID of the tool.
	 *
	 * @return string
	 */
	public function get_id() {
		return 'export-content';
	}

	/**
	 * Get the name of the tool.
	 *
	 * @return string
	 */
	public function get_name() {
		return __( 'Export Content', 'sensei-lms' );
	}

	/**
	 * Get the description of the tool.
	 *
	 * @return string
	 */
	public function get_description() {
		return __( 'Export courses, lessons, and questions to a CSV file.', 'sensei-lms' );
	}

	/**
	 * Run the tool.
	 */
	public function process() {
		add_action(
			'admin_print_scripts',
			function() {
				Sensei()->assets->enqueue( 'sensei-export', 'data-port/export.js', [], true );
				Sensei()->assets->preload_data( [ '/sensei-internal/v1/export/active' ] );
			}
		);

		add_action(
			'admin_print_styles',
			function() {
				Sensei()->assets->enqueue( 'sensei-export', 'data-port/style.css', [ 'sensei-wp-components' ] );
			}
		);
	}

	/**
	 * Is the tool currently available?
	 *
	 * @return bool True if tool is available.
	 */
	public function is_available() {
		return true;
	}
}
