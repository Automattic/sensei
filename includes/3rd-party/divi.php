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

/**
 * Unsets the layout for Divi Builder.
 * This code was provided by a Divi developer in https://github.com/Automattic/sensei/issues/6414#issuecomment-1406665650.
 *
 * @param array $layouts Divi's theme builder layouts.
 * @return array
 */
function sensei_disable_divi_theme_builder( $layouts ) {
	// Early return if layouts is empty, or we are not in the view of a singular lesson.
	if ( empty( $layouts ) || ! is_singular( 'lesson' ) ) {
		return $layouts;
	}

	// Disable Divi Theme Builder header layout.
	$layouts['et_header_layout']['id']       = 0;
	$layouts['et_header_layout']['enabled']  = false;
	$layouts['et_header_layout']['override'] = false;

	// Disable Divi Theme Builder body layout.
	$layouts['et_body_layout']['id']       = 0;
	$layouts['et_body_layout']['enabled']  = false;
	$layouts['et_body_layout']['override'] = false;

	// Disable Divi Theme Builder footer layout.
	$layouts['et_footer_layout']['id']       = 0;
	$layouts['et_footer_layout']['enabled']  = false;
	$layouts['et_footer_layout']['override'] = false;

	return $layouts;
}

/**
 * If learning mode is enabled for the current course we disable the Divi Theme Builder.
 */
function sensei_fix_divi_theme_builder_and_learning_mode_conflict() {
	$course_id = Sensei_Utils::get_current_course();
	if ( ! is_null( $course_id ) && Sensei_Course_Theme_Option::has_learning_mode_enabled( $course_id ) ) {
		add_filter( 'et_theme_builder_template_layouts', 'sensei_disable_divi_theme_builder', 11, 1 );
	}

}
add_action( 'wp', 'sensei_fix_divi_theme_builder_and_learning_mode_conflict' );
