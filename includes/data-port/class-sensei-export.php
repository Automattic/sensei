<?php
/**
 * File containing the class Sensei_Export.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * This class is responsible for displaying the export page in admin.
 */
class Sensei_Export {

	/**
	 * URL Slug for Export page
	 *
	 * @var string
	 */
	public $page_slug;

	/**
	 * Sensei_Export constructor.
	 */
	public function __construct() {

		$this->page_slug = 'sensei_export';

		add_action( 'admin_menu', [ $this, 'admin_menu' ], 40 );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Arguments used for comparison.
		if ( isset( $_GET['page'] ) && ( $_GET['page'] === $this->page_slug ) ) {

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
	}

	/**
	 * Register an export submenu.
	 */
	public function admin_menu() {
		if ( current_user_can( 'manage_sensei' ) ) {
			add_submenu_page(
				'sensei',
				__( 'Export Content', 'sensei-lms' ),
				__( 'Export', 'sensei-lms' ),
				'manage_sensei',
				$this->page_slug,
				[ $this, 'export_page' ]
			);
		}
	}

	/**
	 * Render app container for export page.
	 */
	public function export_page() {

		?>
		<div id="sensei-export-page-wrapper" class="wrap">
			<h1>
				<?php echo wp_kses_post( get_admin_page_title() ); ?>
			</h1>
			<div id="sensei-export-page" class="sensei-export">

			</div>
		</div>
		<?php
	}

}
