<?php
/**
 * Sensei Uninstall
 *
 * Uninstalls the plugin and associated data.
 *
 * @package WordPress
 * @subpackage Sensei
 * @category Core
 * @author Automattic
 * @since 1.0.0
 *
 * @var string $plugin Plugin name being passed to `uninstall_plugin()`.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit();
}

if ( class_exists( 'Sensei_Main' ) ) {
	// Another instance of Sensei is installed and activated on the current site or network.
	return;
}

require dirname( __FILE__ ) . '/sensei-lms.php';

if ( ! function_exists( 'Sensei' ) ) {
	// We still want people to be able to delete Sensei if they don't meet dependencies.
	return;
}

// We don't want any jobs to be scheduled during uninstall.
add_filter( 'sensei_is_enrolment_background_job_enabled', '__return_false' );

require dirname( __FILE__ ) . '/includes/class-sensei-data-cleaner.php';

// Cleanup all data.
if ( ! is_multisite() ) {

	// Only do deletion if the setting is true.
	$do_deletion = Sensei()->settings->get( 'sensei_delete_data_on_uninstall' );
	if ( $do_deletion ) {
		Sensei_Data_Cleaner::cleanup_all();
	}
} else {
	global $wpdb;

	if ( ! function_exists( 'is_another_sensei_activated' ) ) {
		/**
		 * Checks if another Sensei is activated on the specific site in the network.
		 *
		 * @param string $current_plugin Current plugin that is being deleted.
		 * @return bool True if another Sensei is activated.
		 */
		function is_another_sensei_activated( $current_plugin ) {
			$current_plugin_basename = plugin_basename( $current_plugin );
			$active_plugins          = (array) get_option( 'active_plugins', array() );
			$other_sensei_basenames  = array(
				'sensei/sensei.php',
				'sensei/sensei-lms.php',
				'sensei-lms/sensei-lms.php',
				'sensei/woothemes-sensei.php',
				'woothemes-sensei/woothemes-sensei.php',
				'woothemes-sensei/sensei.php',
			);
			foreach ( $other_sensei_basenames as $basename ) {
				if ( $basename === $current_plugin_basename ) {
					// Plugins can be deleted on the network level even when activated on the site level.
					// We don't want the current plugin to count in the search.
					continue;
				}
				if ( in_array( $basename, $active_plugins, true ) || array_key_exists( $basename, $active_plugins ) ) {
					return true;
				}
			}
			return false;
		}
	}

	$blog_ids         = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
	$original_blog_id = get_current_blog_id();

	foreach ( $blog_ids as $sensei_lms_current_blog_id ) {
		switch_to_blog( $sensei_lms_current_blog_id );

		// phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UndefinedVariable -- $plugin is passed to `uninstall_plugin`
		if ( is_another_sensei_activated( $plugin ) ) {
			continue;
		}

		// Only do deletion if the setting is true.
		Sensei()->settings->get_settings();
		$do_deletion = Sensei()->settings->get( 'sensei_delete_data_on_uninstall' );
		if ( $do_deletion ) {
			Sensei_Data_Cleaner::cleanup_all();
		}
	}

	switch_to_blog( $original_blog_id );
}
