<?php
/**
 * File containing the class Sensei_Page_Blocks.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Sensei_Page_Blocks
 */
class Sensei_Page_Blocks extends Sensei_Blocks_Initializer {
	/**
	 * Sensei_Page_Blocks constructor.
	 */
	public function __construct() {
		parent::__construct( [ 'page' ] );

		add_filter( 'render_block', array( $this, 'add_course_featured_badge' ), 11, 2 );
	}

	/**
	 * Initialize blocks that are used in page post types.
	 */
	public function initialize_blocks() {
		new Sensei_Block_Take_Course();
		new Sensei_Block_View_Results();
		new Sensei_Continue_Course_Block();
		new Sensei_Course_Completed_Actions_Block();
		new Sensei_Course_Progress_Block();
		new Sensei_Course_Results_Block();
		new Sensei_Learner_Courses_Block();
		new Sensei_Learner_Messages_Button_Block();
		new Sensei_Course_Categories_Block();
		new Sensei_Course_Featured_Block();
	}

	/**
	 * Enqueue frontend and editor assets.
	 *
	 * @access private
	 */
	public function enqueue_block_assets() {

		Sensei()->assets->disable_frontend_styles();
		Sensei()->assets->enqueue(
			'sensei-single-page-blocks-style',
			'blocks/single-page-style.css'
		);
		Sensei()->assets->enqueue(
			'sensei-shared-blocks-style',
			'blocks/shared-style.css'
		);
	}

	/**
	 * Enqueue editor assets.
	 *
	 * @access private
	 */
	public function enqueue_block_editor_assets() {
		Sensei()->assets->enqueue( 'sensei-single-page-blocks', 'blocks/single-page.js', [], true );
		Sensei()->assets->enqueue(
			'sensei-single-page-blocks-editor-style',
			'blocks/single-page-style-editor.css'
		);
	}


	/**
	 * A function to add a course featured badge to the course list.
	 *
	 * @access public
	 * @since $$next-version$$
	 *
	 * @param string $block_content This is block content.
	 * @param object $block_parent This is block parent.
	 *
	 * @return string $block_content block content.
	 */
	public function add_course_featured_badge( $block_content, $block_parent ): string {
		if ( empty( $block_content ) ) {
			return $block_content;
		}
		// Add featured course badge to a featured image block.
		if ( 'core/post-featured-image' === $block_parent['blockName'] ) {
			if ( ! empty( $block_content ) ) {
				return '<div class="featured-image-wrapper">' . $block_content . '<div class="featured-badge">Featured</div></div>';
			}
		}

		// Add featured course badge to a course category block.
		if ( 'sensei-lms/course-categories' === $block_parent['blockName'] ) {
			return '<div class="featured-category-wrapper"><div class="featured-badge">Featured</div>' . $block_content . '</div>';
		}

		// Check if parent block.
		if ( 'core/null' === $block_parent['blockName'] ) {

			// If the course is not featured don't do anything.
			if ( ! str_contains( $block_content, 'class-course-featured' ) ) {
				return $block_content;
			}

			if ( str_contains( $block_content, 'wp-block-post-featured-image' ) ) {
				return '<div class="featured-course-with-image-wrapper">' . $block_content . '</div>';
			} else {
				return '<div class="featured-course-no-image-wrapper">' . $block_content . '</div>';
			}
		}
		return $block_content;
	}
}
