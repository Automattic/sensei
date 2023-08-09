<?php
/**
 * Adds additional compatibility with the Astra theme.
 *
 * @package 3rd-Party
 */

/**
 * Enqueue Astra theme-specific Learning Mode styles.
 */
function sensei_load_learning_mode_styles_for_astra_theme() {
	$course_id       = Sensei_Utils::get_current_course();
	$is_target_theme = 'astra' === strtolower( wp_get_theme()->get_template() );

	if ( empty( $course_id ) || ! $is_target_theme ) {
		return false;
	}

	if ( Sensei_Course_Theme_Option::has_learning_mode_enabled( $course_id ) ) {
		Sensei()->assets->enqueue( 'astra-learning-mode', 'css/3rd-party/themes/astra/learning-mode.css' );
	}
}

add_action( 'wp_enqueue_scripts', 'sensei_load_learning_mode_styles_for_astra_theme' );
