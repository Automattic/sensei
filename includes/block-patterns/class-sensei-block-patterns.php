<?php
/**
 * Sensei Block Patterns.
 *
 * @package sensei-lms
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Sensei Block Patterns class.
 */
class Sensei_Block_Patterns {
	/**
	 * Instance of class.
	 *
	 * @var self
	 */
	private static $instance;

	/**
	 * Fetches the instance of the class.
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
	 * Initializes the class.
	 */
	public function init() {
		add_action( 'init', [ $this, 'register_block_patterns_category' ] );
		add_action( 'init', [ $this, 'register_course_list_block_patterns' ] );
		add_action( 'current_screen', [ $this, 'register_block_patterns' ] );
		add_action( 'enqueue_block_assets', [ $this, 'enqueue_scripts' ] );
	}

	/**
	 * Sensei_Block_Patterns constructor. Prevents other instances from being created outside of `self::instance()`.
	 */
	private function __construct() {
	}

	/**
	 * Register Sensei block patterns category.
	 *
	 * @access private
	 */
	public function register_block_patterns_category() {
		register_block_pattern_category(
			self::get_patterns_category_name(),
			[ 'label' => __( 'Sensei LMS', 'sensei-lms' ) ]
		);
	}

	/**
	 * Register patterns for course list block.
	 *
	 * @access private
	 */
	public function register_course_list_block_patterns() {
		( new Sensei_Course_List_Block_Patterns() )->register_course_list_block_patterns();
	}

	/**
	 * Register block patterns.
	 *
	 * @param WP_Screen $current_screen Current WP_Screen object.
	 *
	 * @access private
	 */
	public function register_block_patterns( $current_screen ) {
		$post_type = (string) $current_screen->post_type;

		foreach ( self::get_pattern_names( $post_type ) as $block_pattern ) {
			register_block_pattern(
				'sensei-lms/' . $block_pattern,
				require __DIR__ . "/{$post_type}/{$block_pattern}.php"
			);
		}
	}

	/**
	 * Get the names of the patterns Sensei provides for a post type.
	 *
	 * @since $$next-version$$
	 *
	 * @param string $post_type Post type (`course`, `lesson` or `page`).
	 *
	 * @return string[] Pattern names without the `sensei-lms/` prefix.
	 */
	private static function get_pattern_names( string $post_type ): array {
		if ( 'course' === $post_type ) {
			return array(
				'course-default',
				'video-hero',
				'long-sales-page',
				'life-coach',
			);
		}

		if ( 'lesson' === $post_type ) {
			$block_patterns = array(
				'default',
				'video-lesson',
				'default-with-quiz',
				'zoom-meeting',
				'files-to-download',
			);

			if (
				WP_Block_Type_Registry::get_instance()->is_registered( 'core/comments-query-loop' )
				|| version_compare( get_bloginfo( 'version' ), '6.0', '>=' )
			) {
				$block_patterns[] = 'discussion-question';
			}

			return $block_patterns;
		}

		if ( 'page' === $post_type ) {
			return array(
				'landing-page',
			);
		}

		return array();
	}

	/**
	 * Get the block markup of a Sensei pattern.
	 *
	 * Patterns are only registered on `current_screen`, so code that runs outside the
	 * editor (REST, WP-CLI, abilities) reads the pattern file instead of the registry.
	 *
	 * @since $$next-version$$
	 *
	 * @param string $post_type Post type the pattern belongs to (`course`, `lesson` or `page`).
	 * @param string $name      Pattern name without the `sensei-lms/` prefix.
	 *
	 * @return string|null Block markup, or null when Sensei has no such pattern.
	 */
	public static function get_pattern_content( string $post_type, string $name ): ?string {
		if ( ! in_array( $name, self::get_pattern_names( $post_type ), true ) ) {
			return null;
		}

		$pattern = require __DIR__ . "/{$post_type}/{$name}.php";

		return $pattern['content'] ?? null;
	}

	/**
	 * Fill placeholder paragraphs in pattern markup.
	 *
	 * Server-side counterpart of `replacePlaceholders` in the editor wizard: every block
	 * without inner blocks whose className contains a key of `$replaces` gets that value
	 * as its content. Values are inserted as-is, so the caller escapes them.
	 *
	 * @since $$next-version$$
	 *
	 * @param string $content  Block markup.
	 * @param array  $replaces Map of className placeholder to escaped content.
	 *
	 * @return string Block markup with the placeholders filled.
	 */
	public static function replace_placeholders( string $content, array $replaces ): string {
		$blocks = self::replace_placeholders_in_blocks( parse_blocks( $content ), $replaces );

		return serialize_blocks( $blocks );
	}

	/**
	 * Fill placeholders in parsed blocks, recursing into inner blocks.
	 *
	 * @param array $blocks   Parsed blocks.
	 * @param array $replaces Map of className placeholder to escaped content.
	 *
	 * @return array Parsed blocks with the placeholders filled.
	 */
	private static function replace_placeholders_in_blocks( array $blocks, array $replaces ): array {
		foreach ( $blocks as &$block ) {
			if ( ! empty( $block['innerBlocks'] ) ) {
				$block['innerBlocks'] = self::replace_placeholders_in_blocks( $block['innerBlocks'], $replaces );
				continue;
			}

			$class_name = $block['attrs']['className'] ?? '';
			if ( ! is_string( $class_name ) || '' === $class_name ) {
				continue;
			}

			foreach ( $replaces as $placeholder => $value ) {
				if ( false === strpos( $class_name, $placeholder ) ) {
					continue;
				}

				$html                  = '<p class="' . esc_attr( $class_name ) . '">' . $value . '</p>';
				$block['innerHTML']    = $html;
				$block['innerContent'] = array( $html );
			}
		}
		unset( $block );

		return $blocks;
	}

	/**
	 * Enqueue scripts.
	 *
	 * @access private
	 */
	public function enqueue_scripts() {
		$post_type  = get_post_type();
		$post_types = [ 'course', 'lesson' ];

		if ( in_array( $post_type, $post_types, true ) ) {
			Sensei()->assets->enqueue( 'sensei-block-patterns-style', 'css/block-patterns.css' );
		}

		if ( 'page' === $post_type ) {
			Sensei()->assets->enqueue( 'sensei-page-block-patterns-style', 'css/page-block-patterns.css' );
		}
	}

	/**
	 * Get patterns category name.
	 */
	public static function get_patterns_category_name() {
		return 'sensei-lms';
	}

	/**
	 * Get post content block type name.
	 */
	public static function get_post_content_block_type_name() {
		return 'sensei-lms/post-content';
	}
}
