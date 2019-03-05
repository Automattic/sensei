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
	 * Checks if all dependencies are met.
	 *
	 * @return bool
	 */
	public static function are_dependencies_met() {
		$are_met = true;
		if ( ! self::check_php() ) {
			add_action( 'admin_notices', array( __CLASS__, 'add_php_notice' ) );
			$are_met = false;
		}
		return $are_met;
	}

	/**
	 * Checks for our PHP version requirement.
	 *
	 * @return bool
	 */
	private static function check_php() {
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
		$message = sprintf( __( '<strong>Sensei</strong> requires PHP version %1$s but you are running %2$s.', 'sensei' ), self::MINIMUM_PHP_VERSION, phpversion() );
		echo '<div class="error"><p>';
		echo wp_kses( $message, array( 'strong' => array() ) );
		echo '<p><a class="button button-primary" href="https://wordpress.org/support/update-php/" rel="noopener">' . esc_html__( 'Learn more about updating PHP', 'sensei' ) . '</a></p>';
		echo '</p></div>';
	}
}
