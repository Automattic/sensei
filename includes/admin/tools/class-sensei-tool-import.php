<?php
/**
 * File containing Sensei_Tool_Import class.
 *
 * @package sensei-lms
 * @since 4.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Sensei_Tool_Import class.
 *
 * @package sensei-lms
 * @since 4.0.0
 */
class Sensei_Tool_Import implements Sensei_Tool_Interface, Sensei_Tool_Interactive_Interface {
	/**
	 * Output tool view for interactive action methods.
	 */
	public function output() {
		include __DIR__ . '/views/html-import.php';
	}

	/**
	 * Get the ID of the tool.
	 *
	 * @return string
	 */
	public function get_id() {
		return 'import-content';
	}

	/**
	 * Get the name of the tool.
	 *
	 * @return string
	 */
	public function get_name() {
		return __( 'Import Content', 'sensei-lms' );
	}

	/**
	 * Get the description of the tool.
	 *
	 * @return string
	 */
	public function get_description() {
		return __( 'Import courses, lessons, and questions from a CSV file.', 'sensei-lms' );
	}

	/**
	 * Run the tool.
	 */
	public function process() {
		add_action(
			'admin_print_scripts',
			function() {
				Sensei()->assets->enqueue( 'sensei-import', 'data-port/import.js', [], true );
				Sensei()->assets->preload_data( [ '/sensei-internal/v1/import/active' ] );
			}
		);

		add_action(
			'admin_print_styles',
			function() {
				Sensei()->assets->enqueue( 'sensei-import', 'data-port/style.css', [ 'sensei-wp-components' ] );
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
