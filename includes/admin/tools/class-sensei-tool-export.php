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

	public function output() {
		include __DIR__ . '/views/html-export.php';
	}

	public function get_id() {
		return 'export-content';
	}

	public function get_name() {
		return __( 'Export Content', 'sensei-lms' );
	}

	public function get_description() {
		return __( 'Export courses, lessons, and questions to a CSV file.', 'sensei-lms' );
	}

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

	public function is_available() {
		return true;
	}
}
