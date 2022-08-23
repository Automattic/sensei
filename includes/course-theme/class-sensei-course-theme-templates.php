<?php
/**
 * File containing Sensei_Course_Theme_Templates class.
 *
 * @package sensei-lms
 * @since   4.0.2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Sensei's block templates.
 *
 * @since 4.0.2
 */
class Sensei_Course_Theme_Templates {

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
	 * Block templates from the theme.
	 *
	 * @var array[]
	 */
	private $file_templates = [];

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

		add_action( 'template_redirect', [ $this, 'maybe_use_course_theme_templates' ], 1 );
		add_filter( 'get_block_templates', [ $this, 'add_course_theme_block_templates' ], 10, 3 );
		add_filter( 'pre_get_block_file_template', array( $this, 'get_single_block_template' ), 10, 3 );

	}


	/**
	 * Use course theme if it's enabled for the current lesson or quiz.
	 */
	public function maybe_use_course_theme_templates() {
		if ( Sensei_Course_Theme_Option::should_use_learning_mode() ) {
			add_filter( 'sensei_use_sensei_template', '__return_false' );
			add_filter( 'single_template_hierarchy', [ $this, 'set_single_template_hierarchy' ] );
		}
	}

	/**
	 * Add course theme block templates to single template hierarchy.
	 *
	 * @param string[] $templates The list of template names.
	 *
	 * @return string[]
	 */
	public function set_single_template_hierarchy( $templates ) {
		if ( $this->should_use_quiz_template() ) {
			return array_merge( [ 'quiz', 'lesson' ], $templates );
		}
		return array_merge( [ 'lesson', 'quiz' ], $templates );
	}

	/**
	 * Check whether to use the quiz layout.
	 *
	 * @return bool
	 */
	public function should_use_quiz_template() {
		$post = get_post();

		if ( $post && 'quiz' === $post->post_type ) {
			$lesson_id = \Sensei_Utils::get_current_lesson();
			$status    = \Sensei_Utils::user_lesson_status( $lesson_id );
			if ( $status && 'in-progress' === $status->comment_approved ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Set up template data.
	 */
	private function load_file_templates() {

		if ( ! empty( $this->file_templates ) ) {
			return;
		}

		$base_path = Sensei_Course_Theme::instance()->get_course_theme_root() . '/templates/';

		$common_options = [
			'type'           => 'wp_template',
			'theme'          => self::THEME_PREFIX,
			'source'         => 'theme',
			'origin'         => 'theme',
			'is_custom'      => false,
			'has_theme_file' => true,
			'status'         => 'publish',
		];

		$this->file_templates = [
			'lesson' => array_merge(
				$common_options,
				[
					'title'       => __( 'Lesson (Learning Mode)', 'sensei-lms' ),
					'description' => __( 'Displays course content.', 'sensei-lms' ),
					'slug'        => 'lesson',
					'id'          => self::THEME_PREFIX . '//lesson',
					// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local file usage.
					'content'     => file_get_contents( $base_path . 'lesson.html' ),
				]
			),
			'quiz'   => array_merge(
				$common_options,
				[
					'title'       => __( 'Quiz (Learning Mode)', 'sensei-lms' ),
					'description' => __( 'Displays a lesson quiz.', 'sensei-lms' ),
					'slug'        => 'quiz',
					'id'          => self::THEME_PREFIX . '//quiz',
					// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local file usage.
					'content'     => file_get_contents( $base_path . 'quiz.html' ),
				]
			),
		];
	}

	/**
	 * Add Course Theme block templates.
	 *
	 * @param array $templates     List of WP templates.
	 * @param array $query         The query arguments to retrieve templates.
	 * @param array $template_type The type of the template.
	 *
	 * @access private
	 *
	 * @return array
	 */
	public function add_course_theme_block_templates( $templates, $query, $template_type ) {

		if ( 'wp_template' !== $template_type ) {
			return $templates;
		}

		$slugs = $query['slug__in'] ?? $query['post_type'] ?? null;
		if ( $slugs && ! is_array( $slugs ) ) {
			$slugs = [ $slugs ];
		}

		$supported_template_types = [ 'lesson', 'quiz' ];

		$is_course_theme_override = ! empty( $query['theme'] ) && ( Sensei_Course_Theme::THEME_NAME === $query['theme'] );
		$is_site_editor           = empty( $query ) || $is_course_theme_override;
		$is_supported_template    = $slugs && ! empty( array_intersect( $supported_template_types, $slugs ) );

		// Remove file templates picked up from the sensei-course-theme theme directory when the theme override is active.
		$templates = array_filter(
			$templates,
			function( $template ) {
				$is_sensei_template = preg_match( '#^' . Sensei_Course_Theme::THEME_NAME . '//.*$#', $template->id );
				return ! ( $is_sensei_template && $template->has_theme_file );
			}
		);

		if ( ! $is_site_editor && ! $is_supported_template ) {
			return $templates;
		}

		$course_theme_templates = $this->get_block_templates();
		$extra_templates        = array_values( $course_theme_templates );

		// Only show templates matching the queried slug or post type.
		if ( $slugs ) {
			$extra_templates = array_filter(
				$extra_templates,
				function( $template ) use ( $slugs ) {
					return in_array( $template->slug, $slugs, true );
				}
			);
		}

		$templates = array_merge( $extra_templates, $templates );

		// Return the lesson template as the default when there are no theme templates in the site editor.
		if ( $is_course_theme_override && empty( $templates ) ) {
			return [ $course_theme_templates['lesson'] ];
		}

		return $templates;
	}

	/**
	 * Get block templates, including user-customized overrides.
	 *
	 * @return array
	 */
	public function get_block_templates() {

		$this->load_file_templates();

		$db_templates = $this->get_custom_templates();
		$templates    = [];

		foreach ( $this->file_templates as $name => $template ) {
			$db_template     = $db_templates[ $name ] ?? null;
			$template_object = (object) $template;

			if ( ! empty( $db_template ) ) {
				$template_object = $this->build_template_from_post( $db_template );
			} else {
				$template_object->wp_id  = null;
				$template_object->author = null;
			}
			$templates[ $name ] = $template_object;
		}

		return $templates;
	}

	/**
	 * Get templates customized by the user.
	 *
	 * @return array
	 */
	private function get_custom_templates() {

		$db_templates_query = new \WP_Query(
			[
				'post_type'      => 'wp_template',
				'posts_per_page' => -1,
				'no_found_rows'  => true,
				'tax_query'      => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
					[
						'taxonomy' => 'wp_theme',
						'field'    => 'name',
						'terms'    => [ self::THEME_PREFIX ],
					],
				],
			]
		);

		$db_templates = $db_templates_query->posts ?? [];

		return array_column( $db_templates, null, 'post_name' );
	}

	/**
	 * Return a block template for the course theme.
	 *
	 * @access private
	 *
	 * @param \WP_Block_Template|null $template      Return a block template object to short-circuit the default query,
	 *                                               or null to allow WP to run its normal queries.
	 * @param string                  $id            Template unique identifier (example: theme_slug//template_slug).
	 * @param array                   $template_type wp_template or wp_template_part.
	 *
	 * @return mixed|\WP_Block_Template|\WP_Error
	 */
	public function get_single_block_template( $template, $id, $template_type ) {

		if (
			'wp_template' !== $template_type || ! $id || 0 !== strpos( $id, self::THEME_PREFIX . '//' )
		) {
			return $template;
		}

		$templates = $this->get_block_templates();

		list( , $slug ) = explode( '//', $id );

		return $templates[ $slug ] ?? $template;
	}

	/**
	 * Build a template object from a post.
	 *
	 * @param WP_Post $post The post.
	 *
	 * @return WP_Block_Template
	 */
	private function build_template_from_post( $post ) {
		$template                 = new WP_Block_Template();
		$template->wp_id          = $post->ID;
		$template->id             = self::THEME_PREFIX . '//' . $post->post_name;
		$template->theme          = self::THEME_PREFIX;
		$template->content        = $post->post_content;
		$template->slug           = $post->post_name;
		$template->source         = 'custom';
		$template->origin         = 'theme';
		$template->type           = $post->post_type;
		$template->description    = $post->post_excerpt;
		$template->title          = $post->post_title;
		$template->status         = $post->post_status;
		$template->has_theme_file = true;
		$template->is_custom      = true;
		$template->author         = $post->post_author;

		return $template;

	}

}
