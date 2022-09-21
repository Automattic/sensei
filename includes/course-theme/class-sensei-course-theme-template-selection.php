<?php
/**
 * The class file for Sensei_Course_Theme_Template_Selection.
 *
 * @author      Automattic
 * @package     Sensei
 * @version     $$next-version$$
 */

/**
 * Class Sensei_Course_Theme_Template_Selection
 */
class Sensei_Course_Theme_Template_Selection {
	/**
	 * The default Learning Mode block template name.
	 *
	 * @var string
	 */
	const DEFAULT_TEMPLATE_NAME = 'default';

	/**
	 * The seperator used when appending the template name to
	 * post_name.
	 *
	 * @var string
	 */
	const TEMPLATE_NAME_SEPERATOR = '___';

	/**
	 * Sensei_Course_Theme constructor. Prevents other instances from being created outside of `self::instance()`.
	 */
	private function __construct() {

	}

	/**
	 * Instance of class.
	 *
	 * @var self
	 */
	private static $instance;

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
	 * Initialize the class.
	 */
	public function init() {
		add_action( 'save_post', [ $this, 'maybe_set_block_template_name' ], 10, 3 );
	}

	/**
	 * Returns the templates info.
	 *
	 * @return Sensei_Course_Theme_Template[]
	 */
	public static function get_templates(): array {
		$base_path = Sensei_Course_Theme::instance()->get_course_theme_root() . '/templates';
		$base_url  = Sensei_Course_Theme::instance()->get_course_theme_root_url() . '/templates';
		$quiz_path = "$base_path/quiz.html";
		$upsell    = [
			'title' => __( 'Upgrade to Pro', 'sensei-lms' ),
			'tag'   => __( 'Premium', 'sensei-lms' ),
			'url'   => 'https://senseilms.com/sensei-pro/?utm_source=plugin_sensei&utm_medium=upsell&utm_campaign=learning-mode-themes',
		];

		$templates = [

			'default'    => new Sensei_Course_Theme_Template(
				[
					'name'        => 'default',
					'title'       => __( 'Default', 'sensei-lms' ),
					'content'     => [
						'lesson' => "$base_path/default/lesson.html",
						'quiz'   => $quiz_path,
					],
					'version'     => '1.0.0',
					'styles'      => [ Sensei()->assets->asset_url( 'course-theme/themes/default-theme.css' ) ],
					'scripts'     => [],
					'screenshots' => [
						'thumbnail' => "$base_url/default/img-thumb.jpg",
						'full'      => "$base_url/default/img-full.jpg",
					],
				]
			),

			'modern'     => new Sensei_Course_Theme_Template(
				[
					'name'        => 'modern',
					'title'       => __( 'Modern', 'sensei-lms' ),
					'content'     => [
						'lesson' => '',
						'quiz'   => $quiz_path,
					],
					'version'     => '1.0.0',
					'styles'      => [],
					'scripts'     => [],
					'screenshots' => [
						'thumbnail' => "$base_url/modern/img-thumb.jpg",
						'full'      => "$base_url/modern/img-full.jpg",
					],
					'upsell'      => $upsell,
				]
			),

			'video'      => new Sensei_Course_Theme_Template(
				[
					'name'        => 'video',
					'title'       => __( 'Video', 'sensei-lms' ),
					'content'     => [
						'lesson' => '',
						'quiz'   => $quiz_path,
					],
					'version'     => '1.0.0',
					'styles'      => [],
					'scripts'     => [],
					'screenshots' => [
						'thumbnail' => "$base_url/video/img-thumb.jpg",
						'full'      => "$base_url/video/img-full.jpg",
					],
					'upsell'      => $upsell,
				]
			),

			'video-full' => new Sensei_Course_Theme_Template(
				[
					'name'        => 'video-full',
					'title'       => __( 'Large Video', 'sensei-lms' ),
					'content'     => [
						'lesson' => '',
						'quiz'   => $quiz_path,
					],
					'version'     => '1.0.0',
					'styles'      => [],
					'scripts'     => [],
					'screenshots' => [
						'thumbnail' => "$base_url/video-full/img-thumb.jpg",
						'full'      => "$base_url/video-full/img-full.jpg",
					],
					'upsell'      => $upsell,
				]
			),

		];

		/**
		 * Filters the Learning Mode block templates list. Allows to add additional ones too.
		 *
		 * @since $$next-version$$
		 * @hook  sensei_learning_mode_block_templates
		 *
		 * @param Sensei_Course_Theme_Template[] $templates {
		 *     The list of Learning Mode block templates. If adding a new template then it's key
		 *     should be the template name.
		 *
		 * @return Sensei_Course_Theme_Template[] The list of extra learning mode block templates.
		 */
		return apply_filters( 'sensei_learning_mode_block_templates', $templates );
	}

	/**
	 * Retrieves the block template data that is currently activated in the settings.
	 */
	public static function get_active_template(): Sensei_Course_Theme_Template {
		$active_template  = \Sensei()->settings->get( 'sensei_learning_mode_template' );
		$templates        = self::get_templates();
		$default_template = $templates[ self::DEFAULT_TEMPLATE_NAME ];
		if ( isset( $templates[ $active_template ] ) ) {
			$template = $templates[ $active_template ];

			// In case the selected template does not have the template contents somehow
			// supply the default template contents.
			if ( ! isset( $template->content ) ) {
				$template->content = [];
			}

			// Make sure the lesson content is not empty.
			if ( empty( $template->content['lesson'] ) ) {
				$template->content['lesson'] = $default_template->content['lesson'];
			}

			// Make sure the quiz content is not empty.
			if ( empty( $template->content['quiz'] ) ) {
				$template->content['quiz'] = $default_template->content['quiz'];
			}
		} else {
			$template = $default_template;
		}

		return $template;
	}

	/**
	 * Get active LM template name.
	 */
	public static function get_active_template_name(): string {
		return \Sensei()->settings->get( 'sensei_learning_mode_template' ) ?? self::DEFAULT_TEMPLATE_NAME;
	}

	/**
	 * Sets the LM template name as a post_name suffix for the custom LM template in the db.
	 *
	 * @param int     $post_id The id of the post saved.
	 * @param WP_Post $post The instance of the post that was saved.
	 * @param boolean $update Whether this was an update of the existing post or not.
	 */
	public function maybe_set_block_template_name( $post_id, $post, $update ) {
		if (
			// We set the template name meta only for wp_template post types.
			'wp_template' !== $post->post_type ||

			// We set the template name meta only for templates for lesson and quiz posts.
			! in_array( $post->post_name, [ 'lesson', 'quiz' ], true )
		) {
			return;
		}

		$active_template_name = self::get_active_template_name();
		$post->post_name      = $post->post_name . self::TEMPLATE_NAME_SEPERATOR . $active_template_name;

		wp_update_post( $post );
	}

}
