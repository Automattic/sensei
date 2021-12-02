<?php
/**
 * Sensei Course Theme.
 *
 * @package sensei
 */

namespace Sensei\Themes\Sensei_Course_Theme;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Set up the theme.
 */
function setup_theme() {

	add_theme_support( 'title-tag' );

}
add_action( 'after_setup_theme', '\Sensei\Themes\Sensei_Course_Theme\setup_theme' );


/**
 * Load the layout and render its blocks.
 *
 * @access private
 */
function the_course_theme_layout() {

	ob_start();
	$template = locate_template( get_layout_template() );

	load_template( $template );
	$output = ob_get_clean();

	//phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Theme function.
	echo do_blocks( $output );
}

/**
 * Get the template used for the current page's layout.
 *
 * @return string
 */
function get_layout_template() {
	if ( use_quiz_template() ) {
		return 'single-quiz.php';
	}

	return 'single.php';
}

/**
 * Check whether to use the quiz layout.
 *
 * @return bool
 */
function use_quiz_template() {
	$post = get_post();

	if ( $post && 'quiz' === $post->post_type ) {
		$lesson_id = \Sensei_Utils::get_current_lesson();
		$status    = \Sensei_Utils::user_lesson_status( $lesson_id );
		if ( $status && 'in-progress' === $status->comment_approved ) {
			return true;
		}
	}

	return false;
}
