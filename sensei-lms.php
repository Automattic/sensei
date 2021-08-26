<?php
/**
 * Plugin Name: Sensei LMS
 * Plugin URI: https://woocommerce.com/products/sensei/
 * Description: Share your knowledge, grow your network, and strengthen your brand by launching an online course.
 * Version: 3.13.1
 * Author: Automattic
 * Author URI: https://automattic.com
 * License: GPL version 2 or later - http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Requires at least: 5.6
 * Tested up to: 5.8
 * Requires PHP: 7.0
 * Text Domain: sensei-lms
 * Domain path: /lang/
 */

/**
 * Copyright 2013-2020 Automattic
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2, as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( class_exists( 'Sensei_Main' ) ) {
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

require_once dirname( __FILE__ ) . '/includes/class-sensei-dependency-checker.php';
if ( ! Sensei_Dependency_Checker::check_php() ) {
	add_action( 'admin_notices', array( 'Sensei_Dependency_Checker', 'add_php_notice' ) );
	return;
}

if ( ! Sensei_Dependency_Checker::check_assets() ) {
	add_action( 'admin_notices', array( 'Sensei_Dependency_Checker', 'add_assets_notice' ) );
}

require_once dirname( __FILE__ ) . '/includes/class-sensei-bootstrap.php';

Sensei_Bootstrap::get_instance()->bootstrap();

if ( ! function_exists( 'Sensei' ) ) {
	/**
	 * Returns the global Sensei Instance.
	 * phpcs:disable WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid
	 *
	 * @since 1.8.0
	 */
	function Sensei() {
		// phpcs:enable
		return Sensei_Main::instance( array( 'version' => '3.13.1' ) );
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
register_activation_hook( __FILE__, 'activate_sensei' );

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
