<?php
/**
 * WooThemes Sensei Uninstall
 *
 * Uninstalls the plugin and associated data.
 *
 * @package WordPress
 * @subpackage Sensei
 * @category Core
 * @author WooThemes
 * @since 1.0.0
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit();
}

$token = 'woothemes-sensei';
delete_option( 'skip_install_sensei_pages' );
delete_option( 'sensei_installed' );

// Cleanup all data.
require 'woothemes-sensei.php';
require 'includes/class-sensei-data-cleaner.php';

if ( ! is_multisite() ) {
	Sensei_Data_Cleaner::cleanup_all();
} else {
	global $wpdb;

	$blog_ids         = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
	$original_blog_id = get_current_blog_id();

	foreach ( $blog_ids as $blog_id ) {
		switch_to_blog( $blog_id );
		Sensei_Data_Cleaner::cleanup_all();
	}

	switch_to_blog( $original_blog_id );
}
