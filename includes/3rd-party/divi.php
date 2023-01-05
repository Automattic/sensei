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
	if (
		isset( $_GET['et_block_layout_preview'] ) && '1' === $_GET['et_block_layout_preview'] // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		&& current_user_can( 'edit_post', get_the_id() )
	) {
		remove_all_actions( 'wpseo_head' );
	}
}
add_action( 'wp', 'sensei_fix_divi_yoast_conflict', 13 );
