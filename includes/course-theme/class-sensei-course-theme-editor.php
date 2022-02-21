<?php
/**
 * File containing Sensei_Course_Theme_Editor class.
 *
 * @package sensei-lms
 * @since   3.15.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Add Sensei's block templates to the site editor.
 *
 * @since 3.15.0
 */
class Sensei_Course_Theme_Editor {

	/**
	 * Directory for the course theme.
	 */
	const THEME_PREFIX = Sensei_Course_Theme::THEME_NAME;

	/**
	 * Instance of class.
	 *
	 * @var self
	 */
	private static $instance;

	/**
	 * Sensei_Course_Theme constructor. Prevents other instances from being created outside of `self::instance()`.
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
	 * Initializes the Course Theme Editor.
	 */
	public function init() {
		add_action( 'setup_theme', [ $this, 'maybe_add_site_editor_hooks' ], 1 );
		add_action( 'setup_theme', [ $this, 'maybe_override_lesson_theme' ], 1 );
		add_action( 'rest_api_init', [ $this, 'maybe_add_site_editor_hooks' ] );
		add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_site_editor_assets' ] );

		add_action( 'admin_menu', [ $this, 'add_admin_menu_site_editor_item' ], 20 );

	}

	/**
	 * Adds the Appearance -> Editor menu item, unless it's already there because the active theme is a block theme.
	 *
	 * @access private
	 */
	public function add_admin_menu_site_editor_item() {

		if ( ! function_exists( 'wp_is_block_theme' ) || wp_is_block_theme() ) {
			return;
		}

		add_theme_page(
			__( 'Editor', 'sensei-lms' ),
			sprintf(
			/* translators: %s: "beta" label */
				__( 'Editor %s', 'sensei-lms' ),
				'<span class="awaiting-mod">' . __( 'beta', 'sensei-lms' ) . '</span>'
			),
			'edit_theme_options',
			'site-editor.php?postType=wp_template'
		);

	}

	/**
	 * Load the course theme for the lesson editor if it has Learning Mode enabled.
	 */
	public function maybe_override_lesson_theme() {

		$uri            = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
		$is_post_editor = preg_match( '#/wp-admin/post.php#i', $uri );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Safe handling of post ID.
		$post_id = isset( $_GET['post'] ) ? absint( wp_unslash( $_GET['post'] ) ) : null;

		if ( ! $is_post_editor || empty( $post_id ) ) {
			return;
		}

		if ( $this->lesson_has_learning_mode( get_post( $post_id ) ) ) {
			$this->add_editor_styles();

			if ( Sensei_Course_Theme_Option::should_override_theme() ) {
				Sensei_Course_Theme::instance()->override_theme();
			}
		}

	}

	/**
	 * Add template editing hooks for site editor and related API requests.
	 *
	 * @access private
	 */
	public function maybe_add_site_editor_hooks() {

		$uri = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';

		$is_site_editor      = preg_match( '#/wp-admin/site-editor.php#i', $uri ) || preg_match( '#/wp-admin/themes.php\?.*page=gutenberg-edit-site#i', $uri );
		$is_site_editor_rest = preg_match( '#/wp-json/.*/' . self::THEME_PREFIX . '#i', $uri ) || preg_match( '#/wp-json/wp/v2/templates#i', $uri );

		if ( $is_site_editor || $is_site_editor_rest ) {

			$this->add_site_editor_hooks();

			if ( ! function_exists( 'wp_is_block_theme' ) || ! wp_is_block_theme() && ! Sensei_Course_Theme::instance()->is_active() ) {
				Sensei_Course_Theme::instance()->override_theme();
			}
		}

	}

	/**
	 * Add template editing hooks.
	 *
	 * @access private
	 */
	public function add_site_editor_hooks() {

		register_theme_directory( Sensei()->plugin_path() . 'themes' );

		add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_site_editor_assets' ] );
		add_action( 'admin_init', [ $this, 'add_editor_styles' ] );
	}

	/**
	 * Enqueue course theme blocks and styles.
	 *
	 * @access private
	 */
	public function enqueue_site_editor_assets() {

		if ( $this->lesson_has_learning_mode() || $this->is_site_editor() ) {
			Sensei()->assets->enqueue( Sensei_Course_Theme::THEME_NAME . '-blocks', 'course-theme/blocks/blocks.js', [ 'sensei-shared-blocks' ] );
			Sensei()->assets->enqueue_style( 'sensei-shared-blocks-editor-style' );
			Sensei_Course_Theme::instance()->enqueue_fonts();

			if ( Sensei_Course_Theme_Option::should_override_theme() ) {
				Sensei()->assets->enqueue( Sensei_Course_Theme::THEME_NAME . '-editor', 'course-theme/course-theme.editor.js' );
			}
		}
	}

	/**
	 * Register course theme styles as editor styles.
	 *
	 * @access private
	 */
	public function add_editor_styles() {

		add_editor_style( Sensei()->assets->asset_url( 'css/learning-mode.css' ) );
		add_editor_style( Sensei()->assets->asset_url( 'css/learning-mode.editor.css' ) );
		add_editor_style( Sensei()->assets->asset_url( 'css/frontend.css' ) );

	}

	/**
	 * Check if the post being edited is a lesson with Learning Mode enabled.
	 *
	 * @param WP_Post? $post
	 *
	 * @return bool
	 */
	private function lesson_has_learning_mode( $post = null ) {

		$post = $post ?? get_post();

		if ( empty( $post ) || 'lesson' !== $post->post_type ) {
			return false;
		}

		$course_id = Sensei()->lesson->get_course_id( $post->ID );

		return Sensei_Course_Theme_Option::has_learning_mode_enabled( $course_id );
	}

	/**
	 * Check if the current screen is a site or widgets editor.
	 *
	 * @return bool
	 */
	private function is_site_editor() {

		$screen = get_current_screen();

		return ! empty( $screen ) && in_array( $screen->id, [ 'widgets', 'site-editor', 'customize', 'appearance_page_gutenberg-edit-site' ], true );
	}

}
