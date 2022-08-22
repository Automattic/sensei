<?php
/**
 * File containing the Sensei_Course_Categories_Block class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Sensei_Course_Categories_Block
 */
class Sensei_Course_Categories_Block {



	/**
	 * Rendered HTML output for the block.
	 *
	 * @var string
	 */
	private $block_content;

	/**
	 * Sensei_Course_Categories_Block constructor.
	 */
	public function __construct() {
		$this->register_block();
	}

	/**
	 * Register course results block.
	 */
	private function register_block() {
		Sensei_Blocks::register_sensei_block(
			'sensei-lms/course-categories',
			[
				'render_callback' => [ $this, 'render_block' ],
			],
			Sensei()->assets->src_path( 'blocks/course-categories-block' )
		);
	}


	/**
	 * Render the Course Categories block.
	 *
	 * @param Array    $attributes The block's attributes.
	 * @param string   $content    The block's content.
	 * @param WP_Block $block      The block instance.
	 * @return string
	 */
	public function render_block( $attributes, $content, WP_Block $block ): string {

		$css = Sensei_Block_Helpers::build_styles( $attributes );

		if ( ! isset( $block->context['postId'] ) ) {
			return '';
		}

		$post_id = $block->context['postId'];

		if ( 'course' !== get_post_type( $post_id ) ) {
			return '';
		}

		$post_terms = get_the_terms( $post_id, 'course-category' );

		if ( is_wp_error( $post_terms ) || empty( $post_terms ) ) {
			return '';
		}

		$wrapper_classes = 'taxonomy-course-category';

		if ( isset( $attributes['textAlign'] ) ) {
			$wrapper_classes .= ' has-text-align-' . $attributes['textAlign'];
		}

		$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => $wrapper_classes ) );

		$link_attributes = '<a ' . Sensei_Block_Helpers::render_style_attributes( [], $css );
		$terms           = get_the_term_list(
			$post_id,
			'course-category',
			"<div $wrapper_attributes>",
			'',
			'</div>'
		);

		return str_replace( '<a ', $link_attributes, $terms );
	}
}
