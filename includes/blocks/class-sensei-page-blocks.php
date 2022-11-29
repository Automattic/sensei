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
		global $wp_version;

		parent::__construct( [ 'page' ] );

		$version = str_replace( '-src', '', $wp_version );

		if ( ! version_compare( $version, '5.9', '<' ) ) {
			add_filter( 'render_block', array( $this, 'add_course_featured_badge' ), 11, 3 );
			add_filter( 'render_block_core/post-featured-image', [ $this, 'add_badge' ], 10, 3 );
		}
	}

	/**
	 * Add featured label to the featured image block.
	 *
	 * @access private
	 * @since 4.6.4
	 *
	 * @param string   $block_content Block content.
	 * @param array    $block Block.
	 * @param WP_Block $instance Block instance.
	 *
	 * @return string
	 */
	public function add_badge( string $block_content, array $block, WP_Block $instance ) {
		if ( ! isset( $instance->context['postId'] ) ) {
			return $block_content;
		}

		if ( empty( $block_content ) || 'featured' !== get_post_meta( $instance->context['postId'], '_course_featured', true ) ) {
			return $block_content;
		}

		return '<div class="sensei-lms-course-list-featured-label__image-wrapper">' .
			'<span class="sensei-lms-course-list-featured-label__text">' .
				__( 'Featured', 'sensei-lms' ) .
			'</span>' .
			$block_content .
		'</div>';
	}

	/**
	 * Add featured label to the course categories block.
	 *
	 * @access private
	 * @since 4.6.4
	 *
	 * @param string   $block_content This is block content.
	 * @param object   $block_parent This is block parent.
	 * @param WP_Block $instance Block instance.
	 *
	 * @return string $block_content block content.
	 */
	public function add_course_featured_badge( $block_content, $block_parent, WP_Block $instance ): string {

		if ( ! isset( $instance->context['postId'] ) ) {
			return $block_content;
		}

		if ( 'sensei-lms/course-categories' !== $block_parent['blockName'] ) {
			return $block_content;
		}

		if ( 'featured' !== get_post_meta( $instance->context['postId'], '_course_featured', true ) ) {
			return $block_content;
		}

		if ( get_post_meta( $instance->context['postId'], '_thumbnail_id' ) ) {
			return $block_content;
		}

		return '<div class="sensei-lms-course-list-featured-label__meta-wrapper">' .
			'<span class="sensei-lms-course-list-featured-label__text">' .
				__( 'Featured', 'sensei-lms' ) .
			'</span>' .
			$block_content .
		'</div>';
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
}
