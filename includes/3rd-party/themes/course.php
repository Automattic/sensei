<?php
/**
 * Adds additional compatibility with Automattic/course theme.
 *
 * @package 3rd-Party
 */

/**
 * Don't load Learning Mode styles from Course theme.
 */
function sensei_disable_learning_mode_style_for_course_theme() {
	add_filter( 'course_learning_mode_load_styles', '__return_false' );
}

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

add_action( 'wp', 'sensei_disable_learning_mode_style_for_course_theme' );
add_action( 'wp_enqueue_scripts', 'sensei_load_learning_mode_style_for_course_theme' );
add_action( 'admin_enqueue_scripts', 'sensei_admin_load_learning_mode_style_for_course_theme' );
