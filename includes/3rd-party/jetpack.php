<?php
/**
 * Adds additional compatibility with Jetpack.
 *
 * @package 3rd-Party
 */

/**
 * Overrides the shortcodes that Jetpack loads.
 *
 * Removes Vimeo and Youtube from the Jetpack shortcodes module to avoid iframes being converted
 * to shortcodes.
 *
 * @since 2.0.0
 *
 * @param array $shortcodes Array of shortcodes to include.
 * @return array Revised array of shortcodes to include.
 */
function remove_jetpack_shortcodes( $shortcodes ) {
	$jetpack_shortcodes_dir = WP_CONTENT_DIR . '/plugins/jetpack/modules/shortcodes/';
	$shortcodes_to_unload   = array( 'vimeo.php', 'youtube.php' );

	foreach ( $shortcodes_to_unload as $shortcode ) {
		$key = array_search( $jetpack_shortcodes_dir . $shortcode, $shortcodes, true );

		if ( $key ) {
			unset( $shortcodes[ $key ] );
		}
	}

	return $shortcodes;
}

add_filter( 'jetpack_shortcodes_to_include', 'remove_jetpack_shortcodes' );
