<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sensei utility class for unsupported theme handling.
 *
 * Provides common functions needed for unsupported theme handlers.
 *
 * @author Automattic
 *
 * @since 1.12.0
 */
class Sensei_Unsupported_Theme_Handler_Utils {

	/**
	 * Turn off pagination in the theme.
	 */
	public static function disable_theme_pagination() {
		add_filter( 'previous_post_link', '__return_false' );
		add_filter( 'next_post_link', '__return_false' );
	}

}
