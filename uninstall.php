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
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit();
}

require dirname( __FILE__ ) . '/sensei.php';

if ( ! function_exists( 'Sensei' ) ) {
	// We still want people to be able to delete Sensei if they don't meet dependencies.
	return;
}

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

	$blog_ids         = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
	$original_blog_id = get_current_blog_id();

	foreach ( $blog_ids as $blog_id ) {
		switch_to_blog( $blog_id );

		// Only do deletion if the setting is true.
		Sensei()->settings->get_settings();
		$do_deletion = Sensei()->settings->get( 'sensei_delete_data_on_uninstall' );
		if ( $do_deletion ) {
			Sensei_Data_Cleaner::cleanup_all();
		}
	}

	switch_to_blog( $original_blog_id );
}
