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

/**
 * Enqueue Astra theme-specific Learning Mode styles in the admin for the Site Editor and Lesson Editor.
 */
function sensei_admin_load_learning_mode_style_for_astra_theme() {
	$is_astra_theme = 'astra' === strtolower( wp_get_theme()->get_template() );

	if ( ! is_admin() || ! function_exists( 'get_current_screen' ) || ! $is_astra_theme ) {
		return;
	}

	$screen = get_current_screen();

	if ( ! $screen ) {
		return;
	}

	$is_lesson_editor = 'lesson' === $screen->post_type && 'post' === $screen->base;
	$is_site_editor   = 'site-editor' === $screen->id;

	if ( $is_lesson_editor || $is_site_editor ) {
		Sensei()->assets->enqueue(
			'astra-learning-mode',
			'css/3rd-party/themes/astra/learning-mode.css',
			[],
			'screen'
		);
	}
}

add_action( 'wp_enqueue_scripts', 'sensei_load_learning_mode_styles_for_astra_theme', 11 );
add_action( 'admin_enqueue_scripts', 'sensei_admin_load_learning_mode_style_for_astra_theme', 11 );
