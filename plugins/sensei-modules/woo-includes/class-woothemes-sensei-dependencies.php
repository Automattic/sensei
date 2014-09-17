<?php
/**
 * WooThemes Sensei Dependency Checker
 *
 * Checks if Sensei is enabled
 */
class WooThemes_Sensei_Dependencies {

	private static $active_plugins;

	public static function init() {

		self::$active_plugins = (array) get_option( 'active_plugins', array() );

		if ( is_multisite() )
			self::$active_plugins = array_merge( self::$active_plugins, get_site_option( 'active_sitewide_plugins', array() ) );
	}

	public static function sensei_active_check() {

		if ( ! self::$active_plugins ) self::init();

		return in_array( 'woothemes-sensei/woothemes-sensei.php', self::$active_plugins ) || array_key_exists( 'woothemes-sensei/woothemes-sensei.php', self::$active_plugins ) || in_array( 'sensei/woothemes-sensei.php', self::$active_plugins ) || array_key_exists( 'sensei/woothemes-sensei.php', self::$active_plugins );

	}

}


