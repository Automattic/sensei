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
	 *
	 * Transforms camel case properties to dashed case.
	 * Example:  backgroundColor -> background-color
	 *
	 * @param string $value value to be converted.
	 * @return string  Converted value.
	 */
	private function camel2dashed( string $value ):string {
		return strtolower( preg_replace( '/([^A-Z-])([A-Z])/', '$1-$2', $value ) );
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
	 * Undocumented function
	 *
	 * @param Array    $attributes     The block's attributes saved attributes.
	 * @param string   $content       The block's content.
	 * @param WP_Block $block   The block instance.
	 * @return string
	 */
	public function render_block( $attributes, $content, WP_Block $block ): string {

		if ( ! isset( $block->context['postId'] ) ) {
			return '';
		}

		$post_terms = get_the_terms( $block->context['postId'], 'course-category' );

		if ( is_wp_error( $post_terms ) || empty( $post_terms ) ) {
			return '';
		}

		$wrapper_classes = 'taxonomy-course-category';

		if ( isset( $attributes['textAlign'] ) ) {
			$wrapper_classes .= ' has-text-align-' . $attributes['textAlign'];
		}

		$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => $wrapper_classes ) );
		$link_classes       = implode( ' ', $attributes['categoryStyle']['classes'] );
		$styles             = $attributes['categoryStyle']['style'];

		$link_styles = implode(
			'; ',
			array_map(
				function ( $v, $k ) {
					return sprintf( '%s: %s', $this->camel2dashed( $k ), $v );
				},
				$styles,
				array_keys( $styles )
			)
		);

		$link_attributes = sprintf( '<a class="%s" style="%s" ', $link_classes, $link_styles );
		$terms           = get_the_term_list(
			$block->context['postId'],
			'course-category',
			"<div $wrapper_attributes>",
			'',
			'</div>'
		);

		return str_replace( '<a ', $link_attributes, $terms );
	}
}
