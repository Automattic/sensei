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

require_once dirname( __FILE__ ) . '/class-sensei-settings-api.php';
require_once dirname( __FILE__ ) . '/class-sensei-settings.php';

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

	const TAXONOMIES = array(
		'module',
		'course-category',
		'quiz-type',
		'question-type',
		'question-category',
		'lesson-tag',
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
		self::cleanup_pages();
		self::cleanup_taxonomies();
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

	/**
	 * Cleanup data for pages.
	 *
	 * @access private
	 */
	private static function cleanup_pages() {
		$settings = new Sensei_Settings();

		// Trash the Course Archive page.
		$course_archive_page_id = $settings->get( 'course_page' );
		if ( $course_archive_page_id ) {
			wp_trash_post( $course_archive_page_id );
		}

		// Trash the My Courses page.
		$my_courses_page_id = $settings->get( 'my_course_page' );
		if ( $my_courses_page_id ) {
			wp_trash_post( $my_courses_page_id );
		}
	}

	/**
	 * Cleanup data for taxonomies.
	 *
	 * @access private
	 */
	private static function cleanup_taxonomies() {
		global $wpdb;

		foreach ( self::TAXONOMIES as $taxonomy ) {
			$terms = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT term_id, term_taxonomy_id FROM $wpdb->term_taxonomy WHERE taxonomy = %s",
					$taxonomy
				)
			);

			// Delete all data for each term.
			foreach ( $terms as $term ) {
				$wpdb->delete( $wpdb->term_relationships, array( 'term_taxonomy_id' => $term->term_taxonomy_id ) );
				$wpdb->delete( $wpdb->term_taxonomy, array( 'term_taxonomy_id' => $term->term_taxonomy_id ) );
				$wpdb->delete( $wpdb->terms, array( 'term_id' => $term->term_id ) );
				$wpdb->delete( $wpdb->termmeta, array( 'term_id' => $term->term_id ) );
			}
		}
	}
}
