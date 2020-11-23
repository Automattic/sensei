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
	 * Course outline block.
	 *
	 * @var Sensei_Course_Blocks
	 */
	public $course;

	/**
	 * Sensei_Blocks constructor .
	 *
	 * @param Sensei_Main $sensei
	 */
	public function __construct( $sensei ) {
		// Skip if Gutenberg is not available.
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		add_filter( 'block_categories', [ $this, 'sensei_block_categories' ], 10, 2 );

		// Init blocks.
		$this->course = new Sensei_Course_Blocks( $sensei );
	}

	/**
	 * Add Sensei LMS block category.
	 *
	 * @access private
	 *
	 * @param array   $categories Current categories.
	 * @param WP_Post $post       Filtered post.
	 *
	 * @return array Filtered categories.
	 */
	public function sensei_block_categories( $categories, $post ) {
		if ( 'course' !== $post->post_type ) {
			return $categories;
		}

		return array_merge(
			$categories,
			[
				[
					'slug'  => 'sensei-lms',
					'title' => __( 'Sensei LMS', 'sensei-lms' ),
				],
			]
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
}
