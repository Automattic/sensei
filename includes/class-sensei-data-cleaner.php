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

	const CUSTOM_POST_TYPES = array(
		'course',
		'lesson',
		'quiz',
		'question',
		'multiple_question',
		'sensei_message',
	);

	/**
	 * Cleanup all data.
	 *
	 * @access public
	 */
	public static function cleanup_all() {
		self::cleanup_custom_post_types();
	}

	/**
	 * Cleanup data for custom post types.
	 *
	 * @access private
	 */
	private static function cleanup_custom_post_types() {
		foreach ( self::CUSTOM_POST_TYPES as $post_type ) {
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
}
