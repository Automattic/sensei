<?php
/**
 * Defines a class with methods for checking if Sensei's dependencies are met.
 *
 * NOTICE: This class should be PHP 5.2 compatible.
 *
 * @package Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sensei Dependencies Check
 *
 * @since 2.0.0
 */
class Sensei_Dependency_Checker {
	const MINIMUM_PHP_VERSION = '5.6';

	/**
	 * Checks for our PHP version requirement.
	 *
	 * @return bool
	 */
	public static function check_php() {
		return version_compare( phpversion(), self::MINIMUM_PHP_VERSION, '>=' );
	}

	/**
	 * Adds notice in WP Admin that minimum version of PHP is not met.
	 *
	 * @access private
	 */
	public static function add_php_notice() {
		$screen        = get_current_screen();
		$valid_screens = array( 'dashboard', 'plugins' );

		if ( ! current_user_can( 'activate_plugins' ) || ! in_array( $screen->id, $valid_screens, true ) ) {
			return;
		}

		// translators: %1$s is version of PHP that Sensei requires; %2$s is the version of PHP WordPress is running on.
		$message = sprintf( __( '<strong>Sensei</strong> requires a minimum PHP version of %1$s, but you are running %2$s.', 'sensei-lms' ), self::MINIMUM_PHP_VERSION, phpversion() );

		echo '<div class="error"><p>';
		echo wp_kses( $message, array( 'strong' => array() ) );
		$php_update_url = 'https://wordpress.org/support/update-php/';
		if ( function_exists( 'wp_get_update_php_url' ) ) {
			$php_update_url = wp_get_update_php_url();
		}
		printf(
			'<p><a class="button button-primary" href="%1$s" target="_blank" rel="noopener noreferrer">%2$s <span class="screen-reader-text">%3$s</span><span aria-hidden="true" class="dashicons dashicons-external"></span></a></p>',
			esc_url( $php_update_url ),
			esc_html__( 'Learn more about updating PHP', 'sensei-lms' ),
			/* translators: accessibility text */
			esc_html__( '(opens in a new tab)', 'sensei-lms' )
		);
		echo '</p></div>';
	}
}
