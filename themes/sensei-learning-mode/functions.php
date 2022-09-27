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

add_action( 'after_setup_theme', '\Sensei\Themes\Sensei_Course_Theme\setup_theme' );

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
	remove_filter( 'render_block', 'wp_render_layout_support_flag' );

}
