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
	 * Instance of singleton.
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
	 * Sensei_Blocks constructor. Private so it can only be initialized internally.
	 */
	private function __construct() {}

	/**
	 * Initialize the class.
	 *
	 * @param Sensei_Main $sensei_main_instance Sensei main class instance.
	 */
	public function init( $sensei_main_instance ) {
		// Skip if Gutenberg is not available.
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		add_filter( 'block_categories', [ $this, 'sensei_block_categories' ], 10, 2 );

		// Init blocks.
		if ( $sensei_main_instance->feature_flags->is_enabled( 'course_outline' ) ) {
			new Sensei_Course_Outline_Block();
		}
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
