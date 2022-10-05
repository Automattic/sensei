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

use \Sensei\Blocks\Course_Theme\Template_Style;

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

		// The below hooks enable block theme support and inject the learning mode templates.
		add_action( 'template_redirect', [ $this, 'maybe_use_course_theme_templates' ], 1 );
		add_action( 'admin_init', [ $this, 'maybe_add_theme_supports' ] );
		add_filter( 'get_block_templates', [ $this, 'add_course_theme_block_templates' ], 10, 3 );
		add_filter( 'pre_get_block_file_template', [ $this, 'get_single_block_template' ], 10, 3 );
		add_filter( 'theme_lesson_templates', [ $this, 'add_learning_mode_template' ], 10, 4 );
		add_filter( 'theme_quiz_templates', [ $this, 'add_learning_mode_template' ], 10, 4 );

	}


	/**
	 * Use course theme if it's enabled for the current lesson or quiz.
	 *
	 * @access private
	 */
	public function maybe_use_course_theme_templates() {
		if ( Sensei_Course_Theme_Option::should_use_learning_mode() ) {
			add_filter( 'sensei_use_sensei_template', '__return_false' );
			add_filter( 'single_template_hierarchy', [ $this, 'set_single_template_hierarchy' ] );
			add_theme_support( 'block-templates' );
			add_theme_support( 'align-wide' );
		}
	}

	/**
	 * Add block template supports in admin.
	 *
	 * @access private
	 */
	public function maybe_add_theme_supports() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Argument cast to int and used for comparison.
		if ( isset( $_GET['post'] ) && in_array( get_post_type( (int) $_GET['post'] ), [ 'lesson', 'quiz' ], true ) ) {
			add_theme_support( 'block-templates' );
		}
	}

	/**
	 * Add learning mode templates to theme templates for quizzes and lessons.
	 *
	 * @access private
	 *
	 * @param string[]     $post_templates Array of template header names keyed by the template file name.
	 * @param WP_Theme     $theme          The theme object.
	 * @param WP_Post|null $post           The post being edited, provided for context, or null.
	 * @param string       $post_type      Post type to get the templates for.
	 */
	public function add_learning_mode_template( $post_templates, $theme, $post, $post_type ) {
		if ( ! Sensei_Course_Theme_Option::should_use_learning_mode() ) {
			return $post_templates;
		}

		$this->load_file_templates();
		$post_templates[ $this->file_templates[ $post_type ]['slug'] ] = $this->file_templates[ $post_type ]['title'];

		return $post_templates;
	}

	/**
	 * Add course theme block templates to single template hierarchy.
	 *
	 * @param string[] $templates The list of template names.
	 *
	 * @return string[]
	 */
	public function set_single_template_hierarchy( $templates ) {

		// Don't change if a block template is already selected for the post.
		$is_default_template = count( $templates ) && preg_match( '/\.php$/', $templates[0] );

		if ( ! $is_default_template ) {
			return $templates;
		}

		if ( $this->should_use_quiz_template() ) {
			return array_merge( [ 'quiz', 'lesson' ], $templates );
		}

		if ( ! in_array( 'lesson', $templates, true ) ) {
			return array_merge( [ 'lesson' ], $templates );
		}

		return $templates;
	}

	/**
	 * Check whether to use the quiz layout.
	 *
	 * @return bool
	 */
	public function should_use_quiz_template() {
		$post = get_post();

		$lesson_id = \Sensei_Utils::get_current_lesson();
		$status    = \Sensei_Utils::user_lesson_status( $lesson_id );

		$has_submitted_quiz = $status && in_array( $status->comment_approved, [ 'ungraded', 'graded', 'passed', 'failed' ], true );

		if ( ! $post || 'quiz' !== $post->post_type || $has_submitted_quiz ) {
			return false;
		}

		return true;
	}

	/**
	 * Set up template data.
	 */
	private function load_file_templates() {

		if ( ! empty( $this->file_templates ) ) {
			return;
		}

		$template = Sensei_Course_Theme_Template_Selection::get_active_template();
		$title    = $template->title ?? $template->name;

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
					// translators: %1$s is the block template name.
					'title'       => sprintf( __( 'Lesson (Learning Mode - %1$s)', 'sensei-lms' ), $title ),
					'description' => __( 'Displays course content.', 'sensei-lms' ),
					'slug'        => 'lesson',
					'id'          => self::THEME_PREFIX . '//lesson',
					'content'     => $template->content['lesson'],
					'styles'      => $template->styles,
				]
			),
			'quiz'   => array_merge(
				$common_options,
				[
					// translators: %1$s is the block template name.
					'title'       => sprintf( __( 'Quiz (Learning Mode - %1$s)', 'sensei-lms' ), $title ),
					'description' => __( 'Displays a lesson quiz.', 'sensei-lms' ),
					'slug'        => 'quiz',
					'id'          => self::THEME_PREFIX . '//quiz',
					'content'     => $template->content['quiz'],
					'styles'      => $template->styles,
				]
			),
		];

		// Enqueue scripts of the current active template.
		if ( is_array( $template->scripts ) ) {
			foreach ( $template->scripts as $index => $script_url ) {
				wp_enqueue_script( self::THEME_PREFIX . '-' . $template->name . "-scripts-$index", $script_url, [], $template->version, true );
			}
		}
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

		if ( 'wp_template' !== $template_type || ! empty( $query['wp_id'] ) ) {
			return $templates;
		}

		if ( $this->should_hide_lesson_template( $query['post_type'] ?? null ) ) {
			return $templates;
		}

		$slugs = $query['slug__in'] ?? $query['post_type'] ?? null;
		if ( $slugs && ! is_array( $slugs ) ) {
			$slugs = [ $slugs ];
		}

		$supported_template_types = [ 'lesson', 'quiz' ];

		$is_site_editor_request   = Sensei_Course_Theme_Editor::is_site_editor_request();
		$is_course_theme_override = ! empty( $query['theme'] ) && ( Sensei_Course_Theme::THEME_NAME === $query['theme'] );
		$is_site_editor           = $is_site_editor_request || $is_course_theme_override;
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
		if ( $is_site_editor && empty( $templates ) ) {
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
				// Prefill the template contents from their content files.
				if ( ! empty( $template['content'] ) && file_exists( $template['content'] ) ) {
					// Get the block template html.
					// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local file usage.
					$html = file_get_contents( $template['content'] );

					// Get the block template styles.
					$css = '';
					if ( ! empty( $template['styles'] ) && is_array( $template['styles'] ) ) {
						foreach ( $template['styles'] as $template_style_url ) {
							$response = wp_remote_get( $template_style_url );
							if ( ! is_wp_error( $response ) && ! empty( $response['body'] ) ) {
								$css .= "\n\n";
								$css .= $response['body'];
							}
						}
					}

					$template_object->content = $html;

					if ( ! empty( $css ) ) {
						$template_object->content .= Template_Style::serialize_block( $css );
					}
				}
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
		$active_db_templates     = [];
		$active_template_name    = Sensei_Course_Theme_Template_Selection::get_active_template_name();
		$template_name_seperator = Sensei_Course_Theme_Template_Selection::TEMPLATE_NAME_SEPERATOR;
		$default_template_name   = Sensei_Course_Theme_Template_Selection::DEFAULT_TEMPLATE_NAME;
		$db_templates            = self::get_db_templates();

		// Collect only those templates that correspond to the template that is set
		// in the Sensei Settings.
		foreach ( $db_templates as $db_template ) {
			$post_name = $db_template->post_name;

			// If the post_name does not have a template name suffix
			// then it is considered a default template.
			if ( strpos( $post_name, $template_name_seperator ) === false ) {
				$post_name .= "{$template_name_seperator}{$default_template_name}";
			}

			// Get only active templates.
			list( $post_type, $template_name ) = explode( $template_name_seperator, $post_name );
			if ( $template_name !== $active_template_name ) {
				continue;
			}

			// The post_name of the template should be the post type that
			// the template is related to.
			$db_template->post_name = $post_type;

			$active_db_templates[] = $db_template;
		}

		return array_column( $active_db_templates, null, 'post_name' );
	}

	/**
	 * Retrieves the Learning Mode templates that are stored in the db.
	 */
	public static function get_db_templates(): array {
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

		return $db_templates_query->posts ?? [];
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

	/**
	 * Gets the html content of the active block template by type.
	 *
	 * @param string $type The type of the template. Accepts 'lesson', 'quiz'.
	 */
	public function get_template_content( string $type ): string {
		$templates = $this->get_block_templates();
		return $templates[ $type ]->content;
	}

	/**
	 * Hides the lesson template in the post editor if the lesson does not have learning mode enabled.
	 *
	 * @param string $post_type The query post type.
	 *
	 * @return bool True if template should be hidden.
	 */
	private function should_hide_lesson_template( $post_type ) {
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST && 'lesson' === $post_type ) {

			$referer = isset( $_SERVER['HTTP_REFERER'] ) ? esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '';

			// Hide the lesson only in the post editor.
			if ( ! preg_match( '#.*/wp-admin/post.php\?.*post=(\d+)#', $referer, $matches ) ) {
				return false;
			}

			$course_id = Sensei()->lesson->get_course_id( $matches[1] );

			return ! $course_id || ! Sensei_Course_Theme_Option::has_learning_mode_enabled( $course_id );
		}

		return false;
	}


}
