<?php
/**
 * Adds additional compatibility with Automattic/course theme.
 *
 * @package 3rd-Party
 */

/**
 * Enqueue Course theme-specific Learning Mode styles.
 */
function sensei_load_learning_mode_style_for_course_theme() {
	$course_id       = Sensei_Utils::get_current_course();
	$is_course_theme = 'course' === wp_get_theme()->get_template();

	if ( empty( $course_id ) || ! $is_course_theme ) {
		return false;
	}

	if ( Sensei_Course_Theme_Option::has_learning_mode_enabled( $course_id ) ) {
		Sensei()->assets->enqueue( 'course-learning-mode', 'css/3rd-party/themes/course/learning-mode.css' );
	}
}

/**
 * Enqueue Course theme-specific Learning Mode styles in the admin for the Site Editor and Lesson Editor.
 */
function sensei_admin_load_learning_mode_style_for_course_theme() {
	$is_course_theme = 'course' === wp_get_theme()->get_template();

	if ( ! is_admin() || ! function_exists( 'get_current_screen' ) || ! $is_course_theme ) {
		return;
	}

	$screen           = get_current_screen();
	$is_lesson_editor = 'lesson' === $screen->post_type && 'post' === $screen->base;
	$is_site_editor   = 'site-editor' === $screen->id;

	if ( $is_lesson_editor || $is_site_editor ) {
		Sensei()->assets->enqueue(
			'course-learning-mode',
			'css/3rd-party/themes/course/learning-mode.css',
			[],
			'screen'
		);
	}
}

add_action( 'course_theme_variation_loaded', 'sensei_load_styles_for_course_theme_variation' );

/**
 * Enqueue the specific stylesheet for current course theme variation.
 *
 * @param string $variation_name The current theme variation.
 *
 * @since 4.19.2
 */
function sensei_load_styles_for_course_theme_variation( $variation_name ) {
	if ( empty( $variation_name ) ) {
		return;
	}

	// Styles that are loaded all across when on course theme.
	Sensei()->assets->enqueue( 'course-theme-styles', 'css/3rd-party/themes/course/style.css' );

	// Variation specific styles, can override the above styles.
	Sensei()->assets->enqueue( 'course-theme-style-variations', 'css/3rd-party/themes/course/' . $variation_name . '.css', [ 'course-theme-styles' ] );
}

add_action( 'wp_enqueue_scripts', 'sensei_load_learning_mode_style_for_course_theme', 11 );
add_action( 'admin_enqueue_scripts', 'sensei_admin_load_learning_mode_style_for_course_theme', 11 );
