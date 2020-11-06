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
					'icon'  => 'wordpress',
				],
			]
		);
	}
}
