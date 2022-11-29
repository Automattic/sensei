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
	 *
	 * @return string
	 */
	public function render_block( array $attributes, string $content, WP_Block $block ): string {
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

		$wrapper = '<div class="wp-block-sensei-lms-course-categories">';
		$end     = '</div>';

		if ( preg_match( '/(<[a-z]+ *[^\/]*?>)(<\/.*>)/', $content, $matches ) ) {
			$wrapper = $matches[1];
			$end     = $matches[2];
		}

		$terms = get_the_term_list(
			$post_id,
			'course-category',
			$wrapper,
			'',
			$end
		);

		$pattern     = '/<a (.*?)>(.*?)<\/a>/i';
		$replacement = '<a $1>$2</a>';

		return preg_replace( $pattern, $replacement, $terms );
	}
}
