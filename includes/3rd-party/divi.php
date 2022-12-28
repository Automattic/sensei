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
		remove_action( 'plugins_loaded', 'wpseo_init', 14 );
	}
}
add_action( 'plugins_loaded', 'sensei_fix_divi_yoast_conflict', 13 );
