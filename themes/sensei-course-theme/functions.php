<?php
/**
 * Sensei Course Theme.
 *
 * @package sensei
 */

namespace Sensei\Themes\Sensei_Course_Theme;

require_once __DIR__ . '/compat.php';

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'after_setup_theme', '\Sensei\Themes\Sensei_Course_Theme\setup_theme' );
add_filter( 'single_template_hierarchy', '\Sensei\Themes\Sensei_Course_Theme\set_single_template_hierarchy' );

/**
 * Set up the theme.
 */
function setup_theme() {

	add_theme_support( 'title-tag' );
	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'align-wide' );
	add_theme_support( 'editor-styles' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'block-nav-menus' );
	add_theme_support( 'custom-logo' );
	add_theme_support( 'html5', array( 'comment-list', 'comment-form', 'search-form', 'gallery', 'caption', 'style', 'script' ) );

	Compat\init();

}

/**
 * Get the template used for the current page's layout.
 *
 * @return string
 */
function get_layout_template() {
	if ( should_use_quiz_template() ) {
		return 'quiz';
	}

	return 'lesson';
}


/**
 * Add the template used for the current page's layout to the template hierarchy.
 *
 * @param array $templates Template hierarchy.
 *
 * @return array
 */
function set_single_template_hierarchy( $templates ) {

	array_unshift( $templates, get_layout_template() );

	return $templates;
}

/**
 * Check whether to use the quiz layout.
 *
 * @return bool
 */
function should_use_quiz_template() {
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

