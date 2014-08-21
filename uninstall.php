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
delete_option( 'skip_install_sensei_pages' );
delete_option( 'sensei_installed' );