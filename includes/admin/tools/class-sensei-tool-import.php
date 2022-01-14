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

	public function output() {
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
		?>
		<div id="sensei-import-page-wrapper" class="wrap">
			<h1>
				<?php echo wp_kses_post( get_admin_page_title() ); ?>
			</h1>
			<div id="sensei-import-page" class="sensei-import">

			</div>
		</div>
	<?php	}

	public function get_id() {
		return 'import-content';
	}

	public function get_name() {
		return __( 'Import Content', 'sensei-lms' );
	}

	public function get_description() {
		return __( 'Import courses, lessons, and questions from a CSV file.', 'sensei-lms' );
	}

	public function process() {
		// TODO: Implement process() method.
	}

	public function is_available() {
		return true;
	}
}
