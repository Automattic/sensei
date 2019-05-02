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
function sensei_jetpack_remove_shortcodes( $shortcodes ) {
	unset( $shortcodes['vimeo'] );
	unset( $shortcodes['youtube'] );

	return $shortcodes;
}

add_filter( 'jetpack_shortcodes_to_include', 'sensei_jetpack_remove_shortcodes' );
