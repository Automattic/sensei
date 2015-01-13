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
if( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) exit();

$token = 'woothemes-sensei';
delete_option( $token . '-settings' );
delete_option( $token . '-version' );
delete_option( $token . '_courses_page_id' );
delete_option( $token . '_user_dashboard_page_id' );
delete_option( 'skip_install_sensei_pages' );
delete_option( 'sensei_installed' );