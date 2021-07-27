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
	 * @var string[]
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
	 * Taxonomies to be deleted.
	 *
	 * @var string[]
	 */
	private static $taxonomies = array(
		'module',
		'course-category',
		'quiz-type',
		'question-type',
		'question-category',
		'lesson-tag',
		'sensei_learner',
	);

	/**
	 * Options to be deleted.
	 *
	 * @var string[]
	 */
	private static $options = array(
		'sensei_installed',
		'sensei_course_enrolment_site_salt',
		'sensei_course_order',
		'skip_install_sensei_pages', // @deprecated since 3.1.0.
		'sensei_suggest_setup_wizard',
		'sensei-data-port-jobs',
		'sensei_setup_wizard_data',
		'sensei_exit_survey_data',
		'sensei_flush_rewrite_rules',
		'sensei_needs_language_pack_install',
		'woothemes_sensei_language_pack_version',
		'sensei-version',
		'woothemes-sensei-version',
		'sensei_enrolment_legacy',
		'sensei_usage_tracking_opt_in_hide', // @deprecated since 3.1.0.
		'sensei-upgrades',
		'woothemes-sensei-upgrades',
		'woothemes-sensei-settings',
		'sensei-settings',
		'sensei_show_email_signup_form', // @deprecated 3.1.0
		'sensei_courses_page_id',
		'woothemes-sensei_courses_page_id',
		'woothemes-sensei_user_dashboard_page_id',
		'woothemes-sensei_course_completed_page_id',
		'sensei-legacy-flags',
		'sensei-scheduler-calculation-version',
		'widget_sensei_course_component',
		'widget_sensei_lesson_component',
		'widget_sensei_course_categories',
		'widget_sensei_category_courses',
		'sensei_dismiss_wcpc_prompt',
		'sensei-cancelled-wccom-connect-dismissed',
	);

	/**
	 * Role to be removed.
	 *
	 * @var string
	 */
	private static $role = 'teacher';

	/**
	 * Name of the role to be removed. This is used temporarily, and will never
	 * be displayed, and so it doesn't need to be translated.
	 *
	 * @var string
	 */
	private static $role_name = 'Teacher';

	/**
	 * Capabilities to be deleted.
	 *
	 * @var string[]
	 */
	private static $caps = array(
		// General.
		'manage_sensei_grades',
		'manage_sensei',

		// Lessons.
		'edit_lesson',
		'read_lesson',
		'delete_lesson',
		'create_lessons',
		'edit_lessons',
		'edit_others_lessons',
		'publish_lessons',
		'read_private_lessons',
		'delete_lessons',
		'delete_private_lessons',
		'delete_published_lessons',
		'delete_others_lessons',
		'edit_private_lessons',
		'edit_published_lessons',

		// Courses.
		'edit_course',
		'read_course',
		'delete_course',
		'create_courses',
		'edit_courses',
		'edit_others_courses',
		'publish_courses',
		'read_private_courses',
		'delete_courses',
		'delete_private_courses',
		'delete_published_courses',
		'delete_others_courses',
		'edit_private_courses',
		'edit_published_courses',

		// Quizzes.
		'edit_quiz',
		'read_quiz',
		'delete_quiz',
		'create_quizs',
		'edit_quizs',
		'edit_others_quizs',
		'publish_quizs',
		'read_private_quizs',
		'delete_quizs',
		'delete_private_quizs',
		'delete_published_quizs',
		'delete_others_quizs',
		'edit_private_quizs',
		'edit_published_quizs',

		// Questions.
		'edit_question',
		'read_question',
		'delete_question',
		'create_questions',
		'edit_questions',
		'edit_others_questions',
		'publish_questions',
		'read_private_questions',
		'delete_questions',
		'delete_private_questions',
		'delete_published_questions',
		'delete_others_questions',
		'edit_private_questions',
		'edit_published_questions',

		// Messages.
		'edit_messages',
		'read_messages',
		'delete_messages',
		'create_messagess',
		'edit_messagess',
		'edit_others_messagess',
		'publish_messagess',
		'read_private_messagess',
		'delete_messagess',
		'delete_private_messagess',
		'delete_published_messagess',
		'delete_others_messagess',
		'edit_private_messagess',
		'edit_published_messagess',

		// Teacher caps.
		'manage_lesson_categories',
		'manage_course_categories',
		'publish_quizzes',
		'edit_quizzes',
		'edit_published_quizzes',
		'edit_private_quizzes',
		'read_private_quizzes',
		'publish_sensei_messages',
		'edit_sensei_messages',
		'edit_published_sensei_messages',
		'edit_private_sensei_messages',
		'read_private_sensei_messages',
	);

	/**
	 * Transient names (as MySQL regexes) to be deleted. The prefixes
	 * "_transient_" and "_transient_timeout_" will be prepended.
	 *
	 * @var string[]
	 */
	private static $transients = array(
		'sensei_[0-9]+_none_module_lessons',
		'sensei_answers_[0-9]+_[0-9]+',
		'sensei_answers_feedback_[0-9]+_[0-9]+',
		'quiz_grades_[0-9]+_[0-9]+',
		'sensei_comment_counts_[0-9]+',
		'sensei_activation_redirect',
		'sensei_woocommerce_plugin_information',
		'sensei_extensions_.*',
		'sensei_background_job_.*',
	);

	/**
	 * User meta key names (as MySQL regexes) to be deleted.
	 *
	 * @var string[]
	 */
	private static $user_meta_keys = array(
		'^sensei_hide_menu_settings_notice$',
		'^_module_progress_[0-9]+_[0-9]+$',
		'^%BLOG_PREFIX%sensei_learner_calculated_version$',
		'^%BLOG_PREFIX%sensei_course_enrolment_[0-9]+$',
		'^%BLOG_PREFIX%sensei_enrolment_providers_state$',
		'^%BLOG_PREFIX%sensei_enrolment_providers_journal$',
	);

	/**
	 * Post meta to be deleted.
	 *
	 * @var string[]
	 */
	private static $post_meta = array(
		'sensei_payment_complete',
		'sensei_products_processed',
		'_sensei_attachment_source_key',
	);

	/**
	 * Cleanup all data.
	 *
	 * @access public
	 */
	public static function cleanup_all() {
		// Ensure module taxonomy is created before calling functions that rely on its existence.
		Sensei()->modules->setup_modules_taxonomy();

		self::cleanup_custom_post_types();
		self::cleanup_post_meta();
		self::cleanup_pages();
		self::cleanup_taxonomies();
		self::cleanup_roles_and_caps();
		self::cleanup_transients();
		self::cleanup_options();
		self::cleanup_user_meta();
	}

	/**
	 * Cleanup data for custom post types.
	 *
	 * @access private
	 */
	private static function cleanup_custom_post_types() {
		foreach ( self::$custom_post_types as $post_type ) {
			$items = get_posts(
				array(
					'post_type'   => $post_type,
					'post_status' => 'any',
					'numberposts' => -1,
					'fields'      => 'ids',
				)
			);

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
	 * Cleanup data for roles and caps.
	 *
	 * @access private
	 */
	private static function cleanup_roles_and_caps() {
		global $wp_roles;

		// Remove caps from roles.
		$role_names = array_keys( $wp_roles->roles );
		foreach ( $role_names as $role_name ) {
			$role = get_role( $role_name );
			self::remove_all_sensei_caps( $role );
		}

		// Remove caps and role from users.
		$users = get_users( array() );
		foreach ( $users as $user ) {
			self::remove_all_sensei_caps( $user );
			$user->remove_role( self::$role );
		}

		// Remove role.
		remove_role( self::$role );
	}

	/**
	 * Helper method to remove Sensei caps from a user or role object.
	 *
	 * @param (WP_User|WP_Role) $object the user or role object.
	 */
	private static function remove_all_sensei_caps( $object ) {
		foreach ( self::$caps as $cap ) {
			$object->remove_cap( $cap );
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

		foreach ( self::$taxonomies as $taxonomy ) {
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

	/**
	 * Cleanup transients from the database.
	 *
	 * @access private
	 */
	private static function cleanup_transients() {
		global $wpdb;

		foreach ( array( '_transient_', '_transient_timeout_' ) as $prefix ) {
			foreach ( self::$transients as $transient ) {
				$wpdb->query(
					$wpdb->prepare(
						"DELETE FROM $wpdb->options WHERE option_name RLIKE %s",
						$prefix . $transient
					)
				);
			}
		}
	}

	/**
	 * Cleanup Sensei user meta from the database.
	 *
	 * @access private
	 */
	private static function cleanup_user_meta() {
		global $wpdb;

		foreach ( self::$user_meta_keys as $meta_key ) {
			$meta_key = str_replace( '%BLOG_PREFIX%', preg_quote( $wpdb->get_blog_prefix(), null ), $meta_key );

			$wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$wpdb->usermeta} WHERE meta_key RLIKE %s",
					$meta_key
				)
			);
		}
	}

	/**
	 * Cleanup post meta that doesn't get deleted automatically.
	 *
	 * @access private
	 */
	private static function cleanup_post_meta() {
		global $wpdb;

		foreach ( self::$post_meta as $post_meta ) {
			$wpdb->delete( $wpdb->postmeta, array( 'meta_key' => $post_meta ) );
		}
	}
}
