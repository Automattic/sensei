<?php
/**
 * Sensei Course Theme compatibility functions. Used for WordPress 5.7 and 5.8  support.
 *
 * @package sensei
 */

namespace Sensei\Themes\Sensei_Course_Theme\Compat;

use Sensei\Themes\Sensei_Course_Theme as Theme;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Initialize theme compatibility hooks.
 *
 * @return void
 */
function init() {
	add_filter( 'template_include', '\Sensei\Themes\Sensei_Course_Theme\Compat\get_wrapper_template' );
	add_filter( 'theme_mod_custom_logo', '\Sensei\Themes\Sensei_Course_Theme\Compat\theme_mod_custom_logo', 60 );

}

/**
 * Load the layout and render its blocks.
 *
 * @access private
 */
function the_course_theme_layout() {

	$content = load_block_template( Theme\get_layout_template() );

	//phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Theme function.
	echo $content;
}

/**
 * Load the wrapper template, unless the core block template canvas is already being used.
 *
 * @access private
 *
 * @param string $template Current template.
 *
 * @return string The wrapper template path.
 */
function get_wrapper_template( $template ) {

	if ( ! preg_match( '/template-canvas.php$/', $template ) ) {
		return locate_template( 'index.php' );
	}

	return $template;
}

/**
 * Load and render a block template file.
 *
 * @param string $template Template name.
 *
 * @return string
 */
function load_block_template( $template ) {

	$template_path = get_template_directory() . '/templates/' . $template . '.html';
	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local file usage.
	$template_content = file_get_contents( $template_path );

	return get_the_block_template_html( $template_content );
}

/**
 * Render a block template.
 * Replicates WordPress core get_the_block_template_html.
 *
 * @param string $template_content Block template content.
 *
 * @return string
 */
function get_the_block_template_html( $template_content ) {
	global $wp_embed;
	$content = $wp_embed->run_shortcode( $template_content );
	$content = $wp_embed->autoembed( $content );
	$content = do_blocks( $content );
	$content = wptexturize( $content );
	$content = wp_filter_content_tags( $content );
	$content = str_replace( ']]>', ']]&gt;', $content );

	// Wrap block template in .wp-site-blocks to allow for specific descendant styles
	// (e.g. `.wp-site-blocks > *`).
	return '<div class="wp-site-blocks">' . $content . '</div>';

}

/**
 * Get custom logo from the original theme's customize settings if it was not found already.
 *
 * @param string $custom_logo
 *
 * @return string
 */
function theme_mod_custom_logo( $custom_logo ) {

	if ( $custom_logo ) {
		return $custom_logo;
	}

	$theme_mods = get_option( 'theme_mods_' . \Sensei_Course_Theme::instance()->get_original_theme() );

	if ( ! empty( $theme_mods['custom_logo'] ) ) {
		return $theme_mods['custom_logo'];
	}

	return $custom_logo;

}


