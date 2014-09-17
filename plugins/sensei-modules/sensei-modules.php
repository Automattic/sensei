<?php
/*
 * Plugin Name: Sensei Modules
 * Version: 1.0.6
 * Plugin URI: http://www.woothemes.com/
 * Description: Give your Sensei lessons more structure by grouping them into modules inside their courses.
 * Author: WooThemes
 * Author URI: http://www.woothemes.com/
 * Requires at least: 3.8
 * Tested up to: 3.9.1
 *
 * @package WordPress
 * @author WooThemes
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Required functions
 */
if ( ! function_exists( 'woothemes_queue_update' ) ) {
	require_once( 'woo-includes/woo-functions.php' );
}

/**
 * Plugin updates
 */
woothemes_queue_update( plugin_basename( __FILE__ ), 'c47899ef9bc4bdfa01565157723fa16d', '290545' );

/**
 * Functions used by plugins
 */
if ( ! class_exists( 'WooThemes_Sensei_Dependencies' ) ) {
	require_once 'woo-includes/class-woothemes-sensei-dependencies.php';
}

/**
 * Sensei Detection
 */
if ( ! function_exists( 'is_sensei_active' ) ) {
  function is_sensei_active() {
    return WooThemes_Sensei_Dependencies::sensei_active_check();
  }
}

if( is_sensei_active() ) {
	// Include plugin class files
	require_once( 'classes/class-sensei-modules.php' );

	// Instantiate necessary classes
	global $sensei_modules;
	$sensei_modules = new Sensei_Modules( __FILE__ );
}