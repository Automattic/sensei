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
		add_action( 'init', [ $this, 'maybe_register_pattern_block_polyfill' ], 99 );
		add_action( 'init', [ $this, 'register_block_patterns_category' ] );
		add_action( 'init', [ $this, 'register_course_list_block_pattern' ] );
		add_action( 'current_screen', [ $this, 'register_block_patterns' ] );
		add_action( 'enqueue_block_assets', [ $this, 'enqueue_scripts' ] );
	}

	/**
	 * Sensei_Editor_Wizard constructor. Prevents other instances from being created outside of `self::instance()`.
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
	 * Temporary pattern for course list block because default patterns changes the Post Type to 'post' instead of 'course'.
	 *
	 * @access private
	 */
	public function register_course_list_block_pattern() {
		register_block_pattern(
			'dummy-course-list-query',
			[
				'title'      => __( 'Grid of courses', 'sensei-lms' ),
				'categories' => array( 'query' ),
				'blockTypes' => array( 'core/query' ),
				'content'    => '<!-- wp:query {"query":{"offset":0,"postType":"course","categoryIds":[],"tagIds":[],"order":"desc","orderBy":"date","author":"","search":"","sticky":"","perPage":4},"displayLayout":{"type":"flex","columns":3},"layout":{"inherit":false}} -->
						<div class="wp-block-query course-list-block"><!-- wp:post-template {"align":"wide"} -->
						<!-- wp:post-featured-image {"isLink":true,"width":"100%","height":"318px"} /-->

						<!-- wp:post-title {"isLink":true,"fontSize":"x-large"} /-->

						<!-- wp:post-excerpt /-->
						<!-- wp:sensei-lms/course-progress {"defaultBarColor":"primary"} /-->
						<!-- wp:post-date {"format":"F j, Y","isLink":true,"fontSize":"small"} /-->
						<!-- /wp:post-template -->

						<!-- wp:separator {"align":"wide","className":"is-style-wide"} -->
						<hr class="wp-block-separator alignwide is-style-wide"/>
						<!-- /wp:separator -->

						<!-- wp:query-pagination {"paginationArrow":"arrow","align":"wide","layout":{"type":"flex","justifyContent":"space-between"}} -->
						<!-- wp:query-pagination-previous {"fontSize":"small"} /-->

						<!-- wp:query-pagination-numbers /-->

						<!-- wp:query-pagination-next {"fontSize":"small"} /-->
						<!-- /wp:query-pagination --></div>
						<!-- /wp:query -->',
			]
		);
	}
	/**
	 * Register block patterns.
	 *
	 * @param WP_Screen $current_screen Current WP_Screen object.
	 *
	 * @access private
	 */
	public function register_block_patterns( $current_screen ) {
		$post_type      = $current_screen->post_type;
		$block_patterns = [];

		if ( 'course' === $post_type ) {
			$block_patterns = [
				'course-default',
				'video-hero',
				'long-sales-page',
				'life-coach',
			];
		} elseif ( 'lesson' === $post_type ) {
			$block_patterns = [
				'default',
				'default-with-quiz',
				'video-lesson',
				'files-to-download',
				'zoom-meeting',
			];

			if (
				WP_Block_Type_Registry::get_instance()->is_registered( 'core/comments-query-loop' )
				|| version_compare( get_bloginfo( 'version' ), '6.0', '>=' )
			) {
				$block_patterns[] = 'discussion-question';
			}
		}

		foreach ( $block_patterns as $block_pattern ) {
			register_block_pattern(
				'sensei-lms/' . $block_pattern,
				require __DIR__ . "/{$post_type}/{$block_pattern}.php"
			);
		}
	}

	/**
	 * Register pattern block polyfill if it's not registered.
	 *
	 * @access private
	 */
	public function maybe_register_pattern_block_polyfill() {
		if ( \WP_Block_Type_Registry::get_instance()->is_registered( 'core/pattern' ) ) {
			return;
		}

		// Register script.
		Sensei()->assets->register( 'sensei-core-pattern-polyfill-script', 'blocks/core-pattern-polyfill/core-pattern-polyfill.js', [], true );

		// Register dynamic block.
		Sensei_Blocks::register_sensei_block(
			'core/pattern',
			[
				'editor_script'   => 'sensei-core-pattern-polyfill-script',
				'render_callback' => function( $attributes ) {
					if ( empty( $attributes['slug'] ) ) {
						return '';
					}

					$slug     = $attributes['slug'];
					$registry = WP_Block_Patterns_Registry::get_instance();
					if ( ! $registry->is_registered( $slug ) ) {
						return '';
					}

					$pattern = $registry->get_registered( $slug );
					return do_blocks( $pattern['content'] );
				},
			],
			Sensei()->assets->src_path( 'blocks/core-pattern-polyfill' )
		);
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
