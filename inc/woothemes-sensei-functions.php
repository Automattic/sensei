<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Global Sensei functions
 */

function is_sensei() {
	global $post, $woothemes_sensei;

	$is_sensei = false;

	$post_types = array( 'lesson', 'course', 'quiz', 'question' );
	$taxonomies = array( 'course-category', 'quiz-type', 'question-type', 'lesson-tag' );

	if( is_post_type_archive( $post_types ) || is_singular( $post_types ) || is_tax( $taxonomies ) ) {
		$is_sensei = true;
	}

	if( is_object( $post ) && ! is_wp_error( $post ) ) {

		$course_page_id = intval( $woothemes_sensei->settings->settings[ 'course_page' ] );
		$my_courses_page_id = intval( $woothemes_sensei->settings->settings[ 'my_course_page' ] );

		if( in_array( $post->ID, array( $course_page_id, $my_courses_page_id ) ) ) {
			$is_sensei = true;
		}
	}

	return apply_filters( 'is_sensei', $is_sensei, $post );
}

function sensei_all_access() {
	$access = false;
	if( current_user_can( 'manage_sensei' ) ) {
		$access = true;
	}
	return apply_filters( 'sensei_all_access', $access );
} // End sensei_all_access()

?>