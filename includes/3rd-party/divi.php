<?php
/**
 * Adds additional compatibility with Divi.
 *
 * @package 3rd-Party
 */

/**
 * It disables Yoast initialization when running the Divi preview in the editor.
 * It avoids an existing conflict that prevents the preview from loading.
 */
function sensei_fix_divi_yoast_conflict() {
	if ( isset( $_GET['et_block_layout_preview'] ) && '1' === $_GET['et_block_layout_preview'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$admin_url        = get_admin_url();
		$admin_url_length = strlen( $admin_url );

		// Check if the request is coming from the admin.
		if ( isset( $_SERVER['HTTP_REFERER'] ) && substr( esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) ), 0, $admin_url_length ) === $admin_url ) {
			remove_action( 'plugins_loaded', 'wpseo_init', 14 );
		}
	}
}
add_action( 'plugins_loaded', 'sensei_fix_divi_yoast_conflict', 13 );
