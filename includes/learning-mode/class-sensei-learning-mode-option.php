<?php
/**
 * File containing Learning_Mode_Option class.
 *
 * @package sensei-lms
 * @since   3.13.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Handle using the Learning Mode for a given course.
 *
 * @since $$next-version$$
 */
class Sensei_Learning_Mode_Option {
	/**
	 * Old course post meta for theme preference.
	 */
	const OLD_THEME_POST_META_NAME = '_course_theme';

	/**
	 * Course post meta for theme preference.
	 */
	const THEME_POST_META_NAME = '_learning_mode';

	/**
	 * Default theme setting value.
	 */
	const WORDPRESS_THEME = 'wordpress-theme';

	/**
	 * Course theme setting value.
	 */
	const SENSEI_THEME = 'sensei-theme';

	/**
	 * Instance of class.
	 *
	 * @var self
	 */
	private static $instance;

	/**
	 * Learning_Mode_Option constructor. Prevents other instances from being created outside of `self::instance()`.
	 */
	private function __construct() {
	}

	/**
	 * Fetches an instance of the class.
	 *
	 * @return self
	 */
	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Initializes Learning Mode.
	 */
	public function init() {

		add_action( 'init', [ $this, 'register_post_meta' ] );
		add_action( 'template_redirect', [ $this, 'ensure_learning_mode_url_prefix' ] );
		add_filter( 'show_admin_bar', [ $this, 'show_admin_bar_only_for_editors' ] );
		add_filter( 'sensei_admin_notices', [ $this, 'add_course_theme_notice' ] );
	}

	/**
	 * Ensure the learning mode prefix is removed if the theme is not overridden.
	 *
	 * @access private
	 */
	public function ensure_learning_mode_url_prefix() {

		$is_theme_overridden   = Sensei_Learning_Mode::instance()::THEME_NAME === get_stylesheet();
		$should_override_theme = self::should_use_learning_mode() && self::should_override_theme();

		// Remove the prefix only if the theme should not be overridden.
		if ( is_admin() || ! $is_theme_overridden || $should_override_theme ) {
			return;
		}

		$url    = get_pagenum_link( 1, false );
		$prefix = Sensei_Learning_Mode::instance()->get_theme_redirect_url( '' );
		$url    = str_replace( $prefix, trailingslashit( home_url() ), $url );

		if ( ! empty( $_SERVER['QUERY_STRING'] ) ) {
			$url = esc_url_raw( wp_unslash( $url . '?' . $_SERVER['QUERY_STRING'] ) );
		}

		wp_safe_redirect( $url );

		/**
		 * Need to exit, otherwise unwanted hooks might run.
		 * See: https://developer.wordpress.org/reference/functions/wp_safe_redirect/#description
		 */
		exit;
	}

	/**
	 * Check if it should use Learning Mode template.
	 *
	 * @deprecated 4.0.2 Use Learning_Mode_Option::should_use_learning_mode
	 *
	 * @return boolean
	 */
	public function should_use_sensei_theme() {
		_deprecated_function( __METHOD__, '4.0.2', 'Learning_Mode_Option::should_use_learning_mode' );
		return self::should_use_learning_mode();
	}

	/**
	 * Check if it should use Learning Mode template.
	 *
	 * @return boolean
	 */
	public static function should_use_learning_mode() {

		$is_course_content = is_singular( 'lesson' ) || is_singular( 'quiz' ) || is_tax( 'module' );

		if ( ! $is_course_content ) {
			return false;
		}

		$course_id = \Sensei_Utils::get_current_course();

		if ( empty( $course_id ) ) {
			return false;
		}

		$course_id = absint( $course_id );

		if (
			self::has_learning_mode_enabled( $course_id ) ||
			Sensei_Learning_Mode::is_preview_mode( $course_id )
		) {
			return true;
		}

		return false;
	}

	/**
	 * Check if it should override the theme for Learning Mode with the course theme.
	 *
	 * @return boolean
	 */
	public static function should_override_theme() {
		/**
		 * Filters if the theme should be overriden for learning mode.
		 *
		 * @since 4.0.2
		 * @hook  sensei_course_learning_mode_theme_override_enabled
		 * @deprecated $$next-version$$
		 *
		 * @param {bool} $enabled True if the learning mode theme override is enabled.
		 *
		 * @return {bool} The modified learning mode theme override setting.
		 */
		return (bool) apply_filters( 'sensei_course_learning_mode_theme_override_enabled', false );
	}

	/**
	 * Check if the given course has Learning Mode enabled or not.
	 *
	 * @param int $course_id Course ID.
	 *
	 * @deprecated 4.0.2 Use Learning_Mode_Option::has_learning_mode_enabled
	 *
	 * @return bool
	 */
	public static function has_sensei_theme_enabled( $course_id ) {
		_deprecated_function( __METHOD__, '4.0.2', 'Learning_Mode_Option::has_learning_mode_enabled' );
		return self::has_learning_mode_enabled( $course_id );
	}

	/**
	 * Check if the given course has Learning Mode enabled or not.
	 *
	 * @param int $course_id Course ID.
	 *
	 * @return bool
	 */
	public static function has_learning_mode_enabled( $course_id ) {
		$theme              = get_post_meta( $course_id, self::THEME_POST_META_NAME, true );
		$enabled_for_course = self::SENSEI_THEME === $theme;
		$enabled_globally   = (bool) \Sensei()->settings->get( 'sensei_learning_mode_all' );

		$enabled = $enabled_for_course || $enabled_globally;

		/**
		 * Filters if a course has learning mode enabled.
		 *
		 * @since 4.0.0
		 * @hook  sensei_course_learning_mode_enabled
		 *
		 * @param {bool} $enabled    True if the learning mode is enabled for the course or globally.
		 * @param {int}  $course_id  The id of the course.
		 *
		 * @return {bool} The modified learning mode setting.
		 */
		return (bool) apply_filters( 'sensei_course_learning_mode_enabled', $enabled, $course_id );
	}

	/**
	 * Filter to show admin bar only for editor users.
	 *
	 * @access private
	 *
	 * @param bool $show_admin_bar Whether show admin bar.
	 *
	 * @return bool Whether show admin bar.
	 */
	public function show_admin_bar_only_for_editors( $show_admin_bar ) {
		$lesson_id = Sensei_Utils::get_current_lesson();
		$course_id = Sensei()->lesson->get_course_id( $lesson_id );

		if ( self::has_learning_mode_enabled( $course_id ) ) {
			return current_user_can( get_post_type_object( 'lesson' )->cap->edit_post, $lesson_id );
		}

		return $show_admin_bar;
	}

	/**
	 * Adds a course theme notice.
	 *
	 * @access private
	 *
	 * @param array $notices Notices list.
	 *
	 * @return array Notices including the course theme notice.
	 */
	public function add_course_theme_notice( array $notices ) {
		$notices['sensei-learning-mode'] = [
			'type'       => 'user',
			'icon'       => 'sensei',
			'heading'    => __( 'Sensei’s new Learning Mode is here!', 'sensei-lms' ),
			'message'    => __( 'Give your students an intuitive and distraction-free learning experience.', 'sensei-lms' ),
			'actions'    => [
				[
					'label'  => __( 'Learn more', 'sensei-lms' ),
					'url'    => 'https://senseilms.com/wordpress-course-theme',
					'target' => '_blank',
				],
			],
			'conditions' => [
				[
					'type'    => 'screens',
					'screens' => [ 'sensei*' ],
				],
			],
		];

		return $notices;
	}

	/**
	 * Register post meta.
	 *
	 * @access private
	 */
	public function register_post_meta() {
		// Migrate old meta value if exists, then unregister the old meta.
		$course_id      = \Sensei_Utils::get_current_course();
		$old_theme_meta = get_post_meta( $course_id, self::OLD_THEME_POST_META_NAME, true );

		if ( $old_theme_meta ) {
			unregister_post_meta( 'course', self::OLD_THEME_POST_META_NAME );
		}

		$default = self::WORDPRESS_THEME;

		if ( $old_theme_meta === self::THEME_POST_META_NAME ) {
			$default = self::THEME_POST_META_NAME;
		}

		register_post_meta(
			'course',
			self::THEME_POST_META_NAME,
			[
				'show_in_rest'  => true,
				'single'        => true,
				'type'          => 'string',
				'default'       => $default,
				'auth_callback' => function( $allowed, $meta_key, $post_id ) {
					return current_user_can( 'edit_post', $post_id );
				},
			]
		);
	}
}

/**
 * Class Sensei_Course_Theme_Option
 *
 * @ignore only for backward compatibility.
 * @since 3.13.4
 */
class Sensei_Course_Theme_Option extends Sensei_Learning_Mode_Option {
}
