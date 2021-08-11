<?php
/**
 * File containing the class Sensei_Blocks.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Sensei_Blocks
 */
class Sensei_Blocks {
	/**
	 * Course blocks.
	 *
	 * @var Sensei_Course_Blocks
	 */
	public $course;

	/**
	 * Course blocks.
	 *
	 * @var Sensei_Lesson_Blocks
	 */
	private $lesson;

	/**
	 * Quiz blocks.
	 *
	 * @var Sensei_Quiz_Blocks
	 */
	public $quiz;

	/**
	 * Page blocks.
	 *
	 * @var Sensei_Page_Blocks
	 */
	public $page;

	/**
	 * Sensei_Blocks constructor.
	 *
	 * @param Sensei_Main $sensei Sensei instance.
	 */
	public function __construct( Sensei_Main $sensei ) {
		// Skip if Gutenberg is not available.
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		// Register generic blocks assets.
		add_action( 'init', [ $this, 'register_generic_assets' ] );

		if ( is_wp_version_compatible( '5.8' ) ) {
			add_filter( 'block_categories_all', [ $this, 'sensei_block_categories' ], 10, 2 );
		} else {
			add_filter( 'block_categories', [ $this, 'sensei_block_categories' ], 10, 2 );
		}

		// Init blocks.
		$this->course = new Sensei_Course_Blocks();
		$this->lesson = new Sensei_Lesson_Blocks();
		$this->quiz   = new Sensei_Quiz_Blocks();
		$this->page   = new Sensei_Page_Blocks();
	}

	/**
	 * Register generic assets.
	 *
	 * @access private
	 */
	public function register_generic_assets() {
		Sensei()->assets->register( 'sensei-shared-blocks', 'blocks/shared.js', [], true );
		Sensei()->assets->register( 'sensei-shared-blocks-style', 'blocks/shared-style.css' );
		Sensei()->assets->register( 'sensei-shared-blocks-editor-style', 'blocks/shared-style-editor.css' );

		Sensei()->assets->register( 'sensei-editor-components-style', 'blocks/editor-components/editor-components-style.css' );

		Sensei()->assets->register( 'sensei-blocks-frontend', 'blocks/frontend.js', [], true );
	}

	/**
	 * Add Sensei LMS block category.
	 *
	 * @access private
	 *
	 * @param array                           $categories Current categories.
	 * @param WP_Post|WP_Block_Editor_Context $context    Either the WP Post (pre-WP 5.8) or the context object.
	 *
	 * @return array Filtered categories.
	 */
	public function sensei_block_categories( $categories, $context ) {
		$post = null;
		if ( class_exists( 'WP_Block_Editor_Context' ) && $context instanceof WP_Block_Editor_Context ) {
			$post = $context->post;
		} elseif ( $context instanceof WP_Post ) {
			$post = $context;
		}

		if ( ! $post || ! in_array( $post->post_type, [ 'course', 'lesson', 'question', 'page' ], true ) ) {
			return $categories;
		}

		return array_merge(
			[
				[
					'slug'  => 'sensei-lms',
					'title' => __( 'Sensei LMS', 'sensei-lms' ),
				],
			],
			$categories
		);
	}

	/**
	 * Register Sensei block. It's a wrapper for `register_block_type` or
	 * `register_block_type_from_metadata`, allowing to filter the args.
	 *
	 * @param string $block_name     Block name.
	 * @param array  $block_args     Block arguments.
	 * @param string $file_or_folder Path to the JSON file with metadata definition for
	 *                               the block or path to the folder where the `block.json`
	 *                               file is located. The block will be registered using
	 *                               `register_block_type_from_metadata` if it's defined.
	 */
	public static function register_sensei_block( $block_name, $block_args, $file_or_folder = null ) {
		if ( WP_Block_Type_Registry::get_instance()->is_registered( $block_name ) ) {
			return;
		}

		/**
		 * Filter the args of the Sensei blocks.
		 *
		 * In WordPress versions 5.5 and later, block type arguments can be filtered by using register_block_type_args
		 * instead. This filter exists to support earlier versions only.
		 *
		 * Notice that for blocks being registered using the `$file_or_folder`, this filter runs before the
		 * `register_block_type_from_metadata`.
		 *
		 * @since 3.6.0
		 * @hook sensei_block_type_args
		 * @see register_block_type
		 * @see register_block_type_from_metadata
		 * @see includes/blocks/compat.php
		 *
		 * @param {array}  $block_args The block arguments as defined by register_block_type.
		 * @param {string} $block_name Block name.
		 *
		 * @return {array} Block args.
		 */
		$block_args = apply_filters( 'sensei_block_type_args', $block_args, $block_name );

		if ( null === $file_or_folder ) {
			register_block_type( $block_name, $block_args );
		} else {
			register_block_type_from_metadata( $file_or_folder, $block_args );
		}
	}

	/**
	 * Check if the current post has any Sensei blocks.
	 *
	 * @param int|WP_Post|null $post
	 *
	 * @return bool
	 */
	public function has_sensei_blocks( $post = null ) {
		if ( ! is_string( $post ) ) {
			$wp_post = get_post( $post );
			if ( $wp_post instanceof WP_Post ) {
				$post = $wp_post->post_content;
			}
		}

		return false !== strpos( (string) $post, '<!-- wp:sensei-lms/' );
	}

	/**
	 * Update the URL of a button block.
	 *
	 * @param string $block_content The block content about to be appended.
	 * @param array  $block         The full block, including name and attributes.
	 * @param string $class_name    The CSS class name used to identify the correct block.
	 * @param string $url           The URL to navigate to when the button is clicked.
	 *
	 * @return string Block HTML.
	 */
	public static function update_button_block_url( $block_content, $block, $class_name, $url ): string {
		if (
			! isset( $block['blockName'] )
			|| 'core/button' !== $block['blockName']
			|| ! isset( $block['attrs']['className'] )
			|| false === strpos( $block['attrs']['className'], $class_name )
		) {
			return $block_content;
		}

		if ( ! $url ) {
			return $block_content;
		}

		$dom = new DomDocument();
		$dom->loadHTML( $block_content );
		$parent_node = $dom->getElementsByTagName( 'div' )->length > 0 ? $dom->getElementsByTagName( 'div' )[0] : '';

		if ( ! $parent_node || ! $parent_node->hasAttributes() ) {
			return $block_content;
		}

		// Get anchor node.
		$anchor_node = $parent_node->getElementsByTagName( 'a' )->length > 0 ? $parent_node->getElementsByTagName( 'a' )[0] : '';

		// Open the appropriate page when the button is clicked.
		if ( $anchor_node ) {
			$anchor_node->setAttribute( 'href', $url );
			$block_content = $dom->saveHTML( $parent_node );
		}

		return $block_content;
	}
}
