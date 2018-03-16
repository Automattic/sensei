<?php
/**
 * Defines a class with methods for cleaning up plugin data. To be used when
 * the plugin is deleted.
 *
 * @package Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	// Exit if accessed directly.
	exit;
}

/**
 * Methods for cleaning up all plugin data.
 *
 * @author Automattic
 * @since 1.10.0
 */
class Sensei_Data_Cleaner {

	/**
	 * Custom post types to be deleted.
	 *
	 * @var $custom_post_types
	 */
	private static $custom_post_types = array(
		'course',
		'lesson',
		'quiz',
		'question',
		'multiple_question',
		'sensei_message',
	);

	/**
	 * Options to be deleted.
	 *
	 * @var $options
	 */
	private static $options = array(
		'sensei_installed',
		'sensei_course_order',
		'skip_install_sensei_pages',
		'sensei_flush_rewrite_rules',
		'sensei_needs_language_pack_install',
		'woothemes_sensei_language_pack_version',
		'woothemes-sensei-version',
		'sensei_usage_tracking_opt_in_hide',
		'woothemes-sensei-upgrades',
		'woothemes-sensei-settings',
		'sensei_courses_page_id',
		'woothemes-sensei_courses_page_id',
		'woothemes-sensei_user_dashboard_page_id',
	);

	/**
	 * Cleanup all data.
	 *
	 * @access public
	 */
	public static function cleanup_all() {
		self::cleanup_custom_post_types();
		self::cleanup_options();
	}

	/**
	 * Cleanup data for custom post types.
	 *
	 * @access private
	 */
	private static function cleanup_custom_post_types() {
		foreach ( self::$custom_post_types as $post_type ) {
			$items = get_posts( array(
				'post_type'   => $post_type,
				'post_status' => 'any',
				'numberposts' => -1,
				'fields'      => 'ids',
			) );

			foreach ( $items as $item ) {
				wp_trash_post( $item );
			}
		}
	}

	/**
	 * Cleanup data for options.
	 *
	 * @access private
	 */
	private static function cleanup_options() {
		foreach ( self::$options as $option ) {
			delete_option( $option );
		}
	}
}
