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
	const MINIMUM_PHP_VERSION        = '7.2';
	const FUTURE_MINIMUM_PHP_VERSION = '7.2';

	/**
	 * Checks for our PHP version requirement.
	 *
	 * @return bool
	 */
	public static function check_php_requirement() {
		return self::verify_php( self::MINIMUM_PHP_VERSION );
	}

	/**
	 * Checks for our future PHP version requirement.
	 *
	 * @return bool
	 */
	public static function check_future_php_requirement() {
		return self::verify_php( self::FUTURE_MINIMUM_PHP_VERSION );
	}

	/**
	 * Checks for our PHP version requirement.
	 *
	 * @param string $version The PHP requirement to check against.
	 * @return bool
	 */
	private static function verify_php( $version ) {
		return version_compare( phpversion(), $version, '>=' );
	}

	/**
	 * Adds error in WP Admin that the current PHP version doesn't met the current minimum supported version of PHP.
	 *
	 * @access private
	 */
	public static function add_php_version_notice() {
		// translators: %1$s is version of PHP that Sensei requires; %2$s is the version of PHP WordPress is running on.
		$message = sprintf( __( '<strong>Sensei LMS</strong> requires a minimum PHP version of %1$s, but you are running %2$s.', 'sensei-lms' ), self::MINIMUM_PHP_VERSION, phpversion() );
		self::show_php_notice( $message );
	}

	/**
	 * Adds warning in WP Admin that the current PHP version doesn't met the future supported minimum version of PHP.
	 *
	 * @access private
	 */
	public static function add_future_php_version_notice() {
		// translators: %1$s is version of PHP that Sensei is going to require in the future; %2$s is the version of PHP WordPress is running on.
		$message = sprintf( __( '<strong>Sensei LMS</strong> will require, in the next release, a minimum PHP version of %1$s, but you are running %2$s.', 'sensei-lms' ), self::FUTURE_MINIMUM_PHP_VERSION, phpversion() );
		self::show_php_notice( $message );
	}

	/**
	 * Verify if the user can see a PHP compatibility error and then shows the message if appropriate.
	 *
	 * @param string $message The message to show.
	 */
	private static function show_php_notice( $message ) {
		$screen        = get_current_screen();
		$valid_screens = array( 'dashboard', 'plugins' );

		if ( ! current_user_can( 'activate_plugins' ) || ! in_array( $screen->id, $valid_screens, true ) ) {
			return;
		}

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

	/**
	 * Checks if assets/dist exists
	 *
	 * @return bool
	 */
	public static function check_assets() {
		$assets_dir = dirname( __DIR__ ) . '/assets/dist';

		return file_exists( $assets_dir );
	}

	/**
	 * Adds notice in WP Admin that assets/dist directory not exists
	 *
	 * @access private
	 */
	public static function add_assets_notice() {
		?>
		<div class="notice notice-error">
			<p>
				<?php
				printf(
					/* translators: 1: is a link to a support document. 2: closing link */
					esc_html__( 'Your installation of Sensei LMS is incomplete. If you installed Sensei LMS from GitHub, %1$splease refer to this document%2$s to set up your development environment.', 'sensei-lms' ),
					'<a href="' . esc_url( 'https://github.com/Automattic/sensei/wiki/Setting-Up-Your-Development-Environment' ) . '" target="_blank" rel="noopener noreferrer">',
					'</a>'
				);
				?>
			</p>
		</div>
		<?php
	}
}
