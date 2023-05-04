<?php
/**
 * Adds additional compatibility with Automattic/course theme.
 *
 * @package 3rd-Party
 */


/**
 * Load learning mode is enabled and the current theme is the automattic/course.
 */

function sensei_disable_learning_mode_style_for_course_theme() {
	add_filter( 'course_learning_mode_load_styles', '__return_false'  );
}

function sensei_load_learning_mode_style_for_course_theme() {
	$course_id = Sensei_Utils::get_current_course();
	$is_course_theme =  'course' === wp_get_theme()->get_template();

	if( empty( $course_id ) || ! $is_course_theme ) {
		return false;
	}


	if ( Sensei_Course_Theme_Option::has_learning_mode_enabled( $course_id )  ) {
		Sensei()->assets->enqueue( 'course-learning-mode', 'css/3rd-party/themes/course/learning-mode.css');
	}
}

// add_action( 'wp', 'sensei_disable_learning_mode_style_for_course_theme' );
// add_action( 'wp_enqueue_scripts', 'sensei_load_learning_mode_style_for_course_theme' );
