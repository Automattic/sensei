<?php
/**
 * File containing the class Sensei_Import.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * This class is responsible for displaying the import page in admin.
 */
class Sensei_Import {

	/**
	 * URL Slug for Import page
	 *
	 * @var string
	 */
	public $page_slug;

	/**
	 * Sensei_Import constructor.
	 */
	public function __construct() {

		$this->page_slug = 'sensei_import';

		add_action( 'admin_menu', [ $this, 'admin_menu' ], 40 );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Arguments used for comparison.
		if ( isset( $_GET['page'] ) && ( $_GET['page'] === $this->page_slug ) ) {

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
	}

	/**
	 * Register an import submenu.
	 */
	public function admin_menu() {
		if ( current_user_can( 'manage_sensei' ) ) {
			add_submenu_page(
				'sensei',
				__( 'Import Content', 'sensei-lms' ),
				__( 'Import', 'sensei-lms' ),
				'manage_sensei',
				$this->page_slug,
				[ $this, 'import_page' ]
			);
		}
	}

	/**
	 * Render app container for import page.
	 */
	public function import_page() {

		?>
		<div id="sensei-import-page-wrapper" class="wrap">
			<h1>
				<?php echo wp_kses_post( get_admin_page_title() ); ?>
			</h1>
			<div id="sensei-import-page" class="sensei-import">

			</div>
		</div>
		<?php
	}

}
