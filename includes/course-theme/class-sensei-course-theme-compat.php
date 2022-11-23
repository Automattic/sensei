<?php
/**
 * Sensei Course Theme compatibility functions. Used for WordPress 5.7 and 5.8  support.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sensei Course Theme compatibility.
 *
 * @since 4.0.2
 */
class Sensei_Course_Theme_Compat {

	/**
	 * Instance of class.
	 *
	 * @var self
	 */
	private static $instance;

	/**
	 * Sensei_Course_Theme_Compat constructor. Prevents other instances from being created outside of `self::instance()`.
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
	 * Initialize theme compatibility hooks.
	 *
	 * @return void
	 */
	public function load_theme() {
		add_filter( 'template_include', [ $this, 'get_wrapper_template' ] );
		add_filter( 'theme_mod_custom_logo', [ $this, 'theme_mod_custom_logo' ], 60 );

	}

	/**
	 * Load the layout and render its blocks.
	 *
	 * @access private
	 */
	public function the_course_theme_layout() {

		$template = \Sensei_Course_Theme_Templates::instance()->should_use_quiz_template() ? 'quiz' : 'lesson';
		$content  = $this->load_block_template( $template );

		//phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Theme function.
		echo $content;
	}

	/**
	 * Load the wrapper template, unless the core block template canvas is already being used.
	 *
	 * @access private
	 *
	 * @param string $template Current template.
	 *
	 * @return string The wrapper template path.
	 */
	public function get_wrapper_template( $template ) {
		// Fix compatibility issue with Divi builder.
		if (
			class_exists( 'ET_GB_Block_Layout' )
			&& method_exists( 'ET_GB_Block_Layout', 'is_layout_block_preview' )
			&& ET_GB_Block_Layout::is_layout_block_preview()
		) {
			return $template;
		}

		if ( ! preg_match( '/template-canvas.php$/', $template ) ) {
			return Sensei_Course_Theme::instance()->get_course_theme_root() . '/index.php';
		}

		return $template;
	}

	/**
	 * Load and render a block template file.
	 *
	 * @param string $template Template name.
	 *
	 * @return string
	 */
	private function load_block_template( $template ) {
		$template_content = Sensei_Course_Theme_Templates::instance()->get_template_content( $template );
		return $this->get_the_block_template_html( $template_content );
	}

	/**
	 * Render a block template.
	 * Replicates WordPress core get_the_block_template_html.
	 *
	 * @param string $template_content Block template content.
	 *
	 * @return string
	 */
	private function get_the_block_template_html( $template_content ) {
		global $wp_embed;
		$content = $wp_embed->run_shortcode( $template_content );
		$content = $wp_embed->autoembed( $content );
		$content = do_blocks( $content );
		$content = wptexturize( $content );
		$content = wp_filter_content_tags( $content );
		$content = str_replace( ']]>', ']]&gt;', $content );

		// Wrap block template in .wp-site-blocks to allow for specific descendant styles
		// (e.g. `.wp-site-blocks > *`).
		return '<div class="wp-site-blocks">' . $content . '</div>';

	}

	/**
	 * Get custom logo from the original theme's customize settings if it was not found already.
	 *
	 * @param string $custom_logo Custom logo.
	 *
	 * @return string
	 */
	public function theme_mod_custom_logo( $custom_logo ) {

		if ( $custom_logo ) {
			return $custom_logo;
		}

		$theme_mods = get_option( 'theme_mods_' . \Sensei_Course_Theme::instance()->get_original_theme() );

		if ( ! empty( $theme_mods['custom_logo'] ) ) {
			return $theme_mods['custom_logo'];
		}

		return $custom_logo;

	}
}
