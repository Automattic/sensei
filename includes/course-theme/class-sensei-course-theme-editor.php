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
	const THEME_NAME = 'sensei-course-theme';

	/**
	 * Instance of class.
	 *
	 * @var self
	 */
	private static $instance;

	/**
	 * Block templates.
	 *
	 * @var array[]
	 */
	private $templates;

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
	 *
	 * @param Sensei_Main $sensei Sensei object.
	 */
	public function init( $sensei ) {

		if ( ! $sensei->feature_flags->is_enabled( 'course_theme' ) ) {
			return;
		}

		register_theme_directory( $sensei->plugin_path() . 'themes' );

		add_action( 'admin_init', [ $this, 'ensure_site_editor_block_theme' ], 0, 10 );
		add_action( 'rest_api_init', [ $this, 'ensure_site_editor_block_theme' ], 0, 10 );

		add_action( 'admin_menu', [ $this, 'add_admin_menu_site_editor_item' ], 0, 20 );
		add_filter( 'default_template_types', [ $this, 'extend_template_types' ], 10, 1 );

	}

	/**
	 * Set up template data.
	 */
	private function init_templates() {

		if ( ! empty( $this->templates ) ) {
			return;
		}

		$base_path = Sensei_Course_Theme::instance()->get_course_theme_root() . '/templates/';

		$this->templates = [
			'lesson' => [
				'title'          => __( 'Lesson (Learning Mode)', 'sensei-lms' ),
				'description'    => __( 'Displays course content.', 'sensei-lms' ),
				'slug'           => 'lesson',
				'id'             => 'sensei-course-theme//lesson',
				'path'           => $base_path . 'lesson.html',
				'content'        => file_get_contents( $base_path . 'lesson.html' ),
				'type'           => 'wp_template',
				'theme'          => 'Sensei LMS',
				'source'         => 'plugin',
				'origin'         => 'plugin',
				'is_custom'      => false,
				'has_theme_file' => true,
				'status'         => 'publish',
				'post_types'     => [ 'lesson' ],
			],
			'quiz'   => [
				'title'          => __( 'Quiz (Learning Mode)', 'sensei-lms' ),
				'description'    => __( 'Displays a lesson quiz.', 'sensei-lms' ),
				'slug'           => 'quiz',
				'id'             => 'sensei-course-theme//quiz',
				'path'           => $base_path . 'quiz.html',
				'content'        => file_get_contents( $base_path . 'quiz.html' ),
				'type'           => 'wp_template',
				'theme'          => 'Sensei LMS',
				'source'         => 'plugin',
				'origin'         => 'plugin',
				'is_custom'      => false,
				'has_theme_file' => true,
				'status'         => 'publish',
				'post_types'     => [ 'quiz' ],
			],
		];
	}

	/**
	 * Add strings for course theme templates.
	 *
	 * @param array $templates
	 *
	 * @access private
	 *
	 * @return array
	 */
	public function extend_template_types( $templates ) {

		$this->init_templates();

		foreach ( $this->templates as $name => $template ) {

			$templates[ $name ] = [
				'title'       => $template['title'],
				'description' => $template['description'],
			];
		}

		return $templates;
	}

	/**
	 * Add Course Theme block templates.
	 *
	 * @param array $templates
	 * @param array $query
	 * @param array $template_type
	 *
	 * @access private
	 *
	 * @return array
	 */
	public function add_block_templates( $templates, $query, $template_type ) {

		$this->init_templates();

		if ( 'wp_template' !== $template_type ) {
			return $templates;
		}

		foreach ( $this->templates as $template ) {
			$templates[] = (object) $template;
		}

		return $templates;
	}

	/**
	 * Adds the Appearance -> Editor menu item, unless it's already there because the active theme is a block theme.
	 *
	 * @access private
	 */
	public function add_admin_menu_site_editor_item() {

		if ( function_exists( 'wp_is_block_theme' ) && wp_is_block_theme() ) {
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
	 * Enable theme override for site editor if the active theme is not a block theme.
	 *
	 * @access private
	 */
	public function ensure_site_editor_block_theme() {

		remove_action( 'admin_init', [ $this, 'ensure_site_editor_block_theme' ], 0, 10 );
		remove_action( 'rest_api_init', [ $this, 'ensure_site_editor_block_theme' ], 0, 10 );

		if ( Sensei_Course_Theme::instance()->is_active() ) {
			return;
		}

		$uri = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';

		$is_site_editor      = preg_match( '#/wp-admin/site-editor.php#i', $uri );
		$is_site_editor_rest = preg_match( '#/wp-json/.*/sensei-course-theme#i', $uri );

		if ( $is_site_editor || $is_site_editor_rest ) {


			if ( ! function_exists( 'wp_is_block_theme' ) || ! wp_is_block_theme() ) {
				Sensei_Course_Theme::instance()->override_theme();
			} else {
				$this->register_plugin_templates();
			}
		}

	}

	/**
	 * Register block templates as plugin-provided templates when a block theme is already active.
	 *
	 * @access private
	 */
	public function register_plugin_templates() {
		add_filter( 'get_block_templates', [ $this, 'add_block_templates' ], 10, 3 );
	}

}
