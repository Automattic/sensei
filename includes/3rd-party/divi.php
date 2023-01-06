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
		&& 'lesson' === get_post_type()
		&& current_user_can( 'edit_post', get_the_id() )
	) {
		$course_id = Sensei_Utils::get_current_course();
		if ( Sensei_Course_Theme_Option::has_learning_mode_enabled( $course_id ) ) {
			remove_all_actions( 'wpseo_head' );
		}
	}
}
add_action( 'wp', 'sensei_fix_divi_yoast_conflict', 13 );
