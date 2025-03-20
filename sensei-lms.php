<?php
/**
 * Plugin Name: Sensei LMS
 * Plugin URI: https://senseilms.com/
 * Description: Share your knowledge, grow your network, and strengthen your brand by launching an online course.
 * Version: 4.24.5
 * Author: Automattic
 * Author URI: https://automattic.com
 * License: GPLv2 or later
 * Requires at least: 6.5
 * Tested up to: 6.7
 * Requires PHP: 7.4
 * Text Domain: sensei-lms
 * Domain path: /lang/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! defined( 'SENSEI_LMS_VERSION' ) ) {
	define( 'SENSEI_LMS_VERSION', '4.24.5' ); // WRCS: DEFINED_VERSION.
}

if ( ! defined( 'SENSEI_LMS_PLUGIN_FILE' ) ) {
	define( 'SENSEI_LMS_PLUGIN_FILE', __FILE__ );
}

if ( ! defined( 'SENSEI_LMS_PLUGIN_PATH' ) ) {
	define( 'SENSEI_LMS_PLUGIN_PATH', plugin_dir_path( SENSEI_LMS_PLUGIN_FILE ) );
}

if ( class_exists( 'Sensei_Main', false ) ) {
	if ( ! function_exists( 'is_sensei_activating' ) ) {
		/**
		 * Checks if Sensei is being activated.
		 *
		 * @since 2.0.0
		 * @access private
		 *
		 * @param string|bool $activating_plugin Plugin that may be getting activated. False if none.
		 * @param string      $plugin            This plugin basename from loading function.
		 * @return bool
		 */
		function is_sensei_activating( $activating_plugin, $plugin ) {
			return ! empty( $activating_plugin ) && $activating_plugin === $plugin;
		}
	}

	// phpcs:disable VariableAnalysis.CodeAnalysis.VariableAnalysis.UndefinedVariable -- false positive
	if ( ! isset( $_wp_plugin_file ) ) {
		$_wp_plugin_file = false;
	}

	if ( ! isset( $plugin ) ) {
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- This shouldn't have any effect. Just ensuring variable is set.
		$plugin = null;
	}
	// phpcs:enable

	if ( is_sensei_activating( $_wp_plugin_file, $plugin ) && defined( 'SENSEI_IGNORE_ACTIVATION_CONFLICT' ) && true === SENSEI_IGNORE_ACTIVATION_CONFLICT ) {
		// Hope that this will just be a conflict that happens during activation.
		return;
	} else {
		die( esc_html__( 'Deactivate other instances of Sensei LMS before activating this plugin.', 'sensei-lms' ) );
	}
}

/**
 * Autoload the vendor dependencies. This includes the prefixed vendor dependencies as well.
 */
require SENSEI_LMS_PLUGIN_PATH . 'vendor/autoload.php';

/**
 * Load packages and libraries.
 */
if (
	! (
		// Check for Automattic private site in WPCOM (Like Learnomattic).
		( defined( 'A8C__IS_A8C_PRIVATE_BLOG' ) && A8C__IS_A8C_PRIVATE_BLOG )
		// Check for Automattic public site in WPCOM (learn.wordpress.com).
		|| ( defined( 'A8C__IS_A8C_BLOG' ) && A8C__IS_A8C_BLOG )
		|| ( defined( 'SENSEI_DO_NOT_LOAD_ACTION_SCHEDULER' ) && SENSEI_DO_NOT_LOAD_ACTION_SCHEDULER )
	)
) {
	require SENSEI_LMS_PLUGIN_PATH . 'vendor/woocommerce/action-scheduler/action-scheduler.php';
}

require_once dirname( __FILE__ ) . '/includes/class-sensei-dependency-checker.php';
if ( ! Sensei_Dependency_Checker::check_php_requirement() ) {
	add_action( 'admin_notices', array( 'Sensei_Dependency_Checker', 'add_php_version_notice' ) );
	return;
}


if ( ! Sensei_Dependency_Checker::check_future_php_requirement() ) {
	add_action( 'admin_notices', array( 'Sensei_Dependency_Checker', 'add_future_php_version_notice' ) );
}

if ( ! Sensei_Dependency_Checker::check_assets() ) {
	add_action( 'admin_notices', array( 'Sensei_Dependency_Checker', 'add_assets_notice' ) );
}

if ( ! function_exists( 'Sensei' ) ) {
	/**
	 * Returns the global Sensei Instance.
	 * phpcs:disable WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid
	 *
	 * @since 1.8.0
	 *
	 * @return Sensei_Main
	 */
	function Sensei() {
		// phpcs:enable
		return Sensei_Main::instance( array( 'version' => SENSEI_LMS_VERSION ) );
	}
}

// For backwards compatibility, put plugin into the global variable.
global $woothemes_sensei;
$woothemes_sensei = Sensei();

/**
 * Sensei Activation Hook registration
 *
 * @since 1.8.0
 */
register_activation_hook( SENSEI_LMS_PLUGIN_FILE, 'activate_sensei' );

if ( ! function_exists( 'activate_sensei' ) ) {
	/**
	 * Activate_sensei
	 * phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
	 *
	 * All the activation checks needed to ensure Sensei is ready for use
	 *
	 * @since 1.8.0
	 */
	function activate_sensei() {
		// phpcs:enable
		Sensei()->activate();
	}
}

\Sensei\Internal\Installer\Installer::instance( SENSEI_LMS_VERSION )->init();
