<?php
/**
 * File containing the Sensei\Blocks\Course_Theme\Course_Content class.
 *
 * @package sensei
 * @since   4.0.0
 */

namespace Sensei\Blocks\Course_Theme;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Block to render the content for the current lesson or quiz page.
 */
class Course_Content {

	/**
	 * Content constructor.
	 */
	public function __construct() {
		add_filter( 'the_content', [ $this, 'render_content' ] );

		if ( ! \WP_Block_Type_Registry::get_instance()->is_registered( 'core/post-content' ) ) {
			register_block_type(
				'core/post-content',
				[
					'render_callback' => [ $this, 'render_content_block' ],
					'style'           => 'sensei-theme-blocks',
				]
			);
		}
	}

	/**
	 * Content block fallback.
	 *
	 * @return string
	 */
	public function render_content_block() {

		// Make sure post is set up correctly.
		if ( ! in_the_loop() && have_posts() ) {
			the_post();
		}

		ob_start();
		the_content();
		$content = ob_get_clean();

		$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => 'entry-content' ) );

		return (
			'<div ' . $wrapper_attributes . '>' .
			$content .
			'</div>'
		);
	}

	/**
	 * Render content for the current page.
	 *
	 * @access private
	 *
	 * @param string $content The content of the post.
	 *
	 * @return string HTML
	 */
	public function render_content( $content ) {

		if ( ! \Sensei_Course_Theme_Option::should_use_learning_mode() ) {
			return $content;
		}

		remove_filter( 'the_content', [ $this, 'render_content' ] );

		$type = get_post_type();

		switch ( $type ) {
			case 'quiz':
				$content = Quiz_Content::render_quiz();
				break;
			case 'lesson':
				$content = $this->render_lesson_content( $content );
				break;
			default:
				$content = '';
		}

		add_filter( 'the_content', [ $this, 'render_content' ] );

		return $content;

	}

	/**
	 * Render the current lesson page's content.
	 *
	 * @param string $content Content of the post.
	 *
	 * @return string
	 */
	private function render_lesson_content( $content ) {
		global $_wp_current_template_content;

		if ( ! in_the_loop() && have_posts() ) {
			the_post();
		}

		if ( sensei_can_user_view_lesson() ) {
			return $content;
		}

		if ( empty( $_wp_current_template_content ) || ! has_block( 'core/post-excerpt', $_wp_current_template_content ) ) {
			return apply_filters( 'the_excerpt', get_the_excerpt() ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Intended.
		}

		return '';
	}

}
