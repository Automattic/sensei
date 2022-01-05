<?php
/**
 * File containing Sensei_Course_Theme_Option class.
 *
 * @package sensei-lms
 * @since   3.13.4
 */

use phpDocumentor\Reflection\Types\Boolean;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Handle using the Course Theme for a given course.
 *
 * @since 3.13.4
 */
class Sensei_Course_Theme_Option {
	/**
	 * Course post meta for theme preference.
	 */
	const THEME_POST_META_NAME = '_course_theme';

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
	 * Sensei_Course_Theme_Option constructor. Prevents other instances from being created outside of `self::instance()`.
	 */
	private function __construct() {}

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
	 * Initializes the Course Theme.
	 *
	 * @param Sensei_Main $sensei Sensei object.
	 */
	public function init( $sensei ) {
		add_action( 'admin_enqueue_scripts', [ $this, 'add_feature_flag_inline_script' ] );

		if ( ! $sensei->feature_flags->is_enabled( 'course_theme' ) ) {
			// As soon this feature flag check is removed, the `$sensei` argument can also be removed.
			return;
		}

		// Init blocks.
		new \Sensei\Blocks\Course_Theme();

		add_action( 'init', [ $this, 'register_post_meta' ] );
		add_action( 'template_redirect', [ $this, 'ensure_learning_mode_url_prefix' ] );
		add_action( 'template_redirect', [ Sensei_Course_Theme_Lesson::instance(), 'init' ] );
		add_action( 'template_redirect', [ Sensei_Course_Theme_Quiz::instance(), 'init' ] );
		add_filter( 'sensei_admin_notices', [ $this, 'add_course_theme_notice' ] );
	}

	/**
	 * Add feature flag inline script.
	 *
	 * @access private
	 */
	public function add_feature_flag_inline_script() {
		$screen  = get_current_screen();
		$enabled = Sensei()->feature_flags->is_enabled( 'course_theme' ) ? 'true' : 'false';

		if ( 'course' === $screen->id ) {
			wp_add_inline_script( 'sensei-admin-course-edit', 'window.senseiCourseThemeFeatureFlagEnabled = ' . $enabled, 'before' );
		}
	}

	/**
	 * Ensure the learning mode prefix is set if required or removed
	 * if not allowed.
	 *
	 * @access private
	 */
	public function ensure_learning_mode_url_prefix() {
		if (
			is_admin() ||
			( Sensei_Course_Theme::instance()->is_active() && $this->should_use_sensei_theme() ) ||
			( ! Sensei_Course_Theme::instance()->is_active() && ! $this->should_use_sensei_theme() )
		) {
			return;
		}

		$url = get_permalink();
		if ( $this->should_use_sensei_theme() ) {
			$url = str_replace( trailingslashit( home_url() ), '', $url );
			$url = Sensei_Course_Theme::instance()->get_theme_redirect_url( $url );
		}

		wp_safe_redirect( $url );

		/**
		 * Need to exit, otherwise unwanted hooks might run.
		 * See: https://developer.wordpress.org/reference/functions/wp_safe_redirect/#description
		 */
		exit;
	}

	/**
	 * Check if it should use Sensei Theme template.
	 *
	 * @return boolean
	 */
	public function should_use_sensei_theme() {

		$is_course_content = is_singular( 'lesson' ) || is_singular( 'quiz' ) || is_tax( 'module' );

		if ( ! $is_course_content ) {
			return false;
		}

		$course_id = \Sensei_Utils::get_current_course();

		if ( empty( $course_id ) ) {
			return false;
		}

		if ( self::has_sensei_theme_enabled( $course_id ) ) {
			return true;
		}

		if ( Sensei_Course_Theme::is_sensei_theme_preview_mode_allowed( $course_id ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Check if the given course has the Sensei Theme enabled or not.
	 *
	 * @param int $course_id Course ID.
	 *
	 * @return bool
	 */
	public static function has_sensei_theme_enabled( int $course_id ) {
		$theme              = get_post_meta( $course_id, self::THEME_POST_META_NAME, true );
		$enabled_for_course = self::SENSEI_THEME === $theme;
		$enabled_globally   = (bool) \Sensei()->settings->settings['sensei_learning_mode_all'];

		/**
		 * Filters if a course has learning mode enabled.
		 *
		 * @since 4.0.0
		 * @hook sensei_has_learning_mode_enabled
		 *
		 * @param {bool} $enabled_for_course True if the learning mode is enabled for the course.
		 * @param {bool} $enabled_globally   True if the learning mode is enabled globally.
		 * @param {int}  $course_id          The id of the course.
		 */
		$enabled_via_filter = (bool) apply_filters( 'sensei_has_learning_mode_enabled', $enabled_for_course, $enabled_globally, $course_id );

		return $enabled_for_course || $enabled_globally || $enabled_via_filter;
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
		$notices['sensei-course-theme'] = [
			'type'       => 'user',
			'icon'       => 'sensei',
			'heading'    => __( 'Sensei’s new learning mode is here', 'sensei-lms' ),
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
		register_post_meta(
			'course',
			self::THEME_POST_META_NAME,
			[
				'show_in_rest'  => true,
				'single'        => true,
				'type'          => 'string',
				'default'       => self::WORDPRESS_THEME,
				'auth_callback' => function( $allowed, $meta_key, $post_id ) {
					return current_user_can( 'edit_post', $post_id );
				},
			]
		);
	}
}
