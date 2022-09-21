<?php
/**
 * File containing the Lesson_Video class.
 *
 * @package sensei
 * @since
 */

namespace Sensei\Blocks\Course_Theme;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use \Sensei_Blocks;
use \Sensei_Course;
use \Sensei_Utils;
use \Sensei_Frontend;

/**
 * Class Lesson_Video is responsible for rendering the Lesson video template block.
 */
class Lesson_Video {
	/**
	 * Block JSON file.
	 */
	const BLOCK_JSON_FILE = '/lesson-blocks/course-theme-lesson-video.block.json';

	/**
	 * Lesson_Actions constructor.
	 */
	public function __construct() {
		add_filter( 'the_content', [ $this, 'remove_featured_video_content' ], 1 );
		$block_json_path = Sensei()->assets->src_path( 'course-theme/blocks' ) . self::BLOCK_JSON_FILE;
		Sensei_Blocks::register_sensei_block(
			'sensei-lms/course-theme-lesson-video',
			[
				'render_callback' => [ $this, 'render' ],
				'style'           => 'sensei-theme-blocks',
			],
			$block_json_path
		);
	}

	/**
	 * Renders the block.
	 *
	 * @access private
	 *
	 * @return string The block HTML.
	 */
	public function render() : string {
		$lesson_id = Sensei_Utils::get_current_lesson();
		$user_id   = get_current_user_id();

		if ( empty( $lesson_id ) || empty( $user_id ) ) {
			return '';
		}

		$course_id = Sensei()->lesson->get_course_id( $lesson_id );

		if (
			! Sensei_Course::is_user_enrolled( $course_id )
			|| Sensei_Utils::user_completed_lesson( $lesson_id )
		) {
			return '';
		}

		$content = self::get_featured_video_html( $lesson_id ) ?? '';

		if ( empty( $content ) ) {
			return '';
		}

		$wrapper_attr = get_block_wrapper_attributes(
			[
				'class' => 'sensei-course-theme-lesson-video wp-block-video is-type-video wp-has-aspect-ratio',
			]
		);

		remove_action( 'sensei_lesson_video', [ Sensei_Frontend::class, 'sensei_lesson_video' ] );

		return sprintf(
			'<div %s>
				%s
				</div>',
			$wrapper_attr,
			$content
		);
	}

	/**
	 * Removes the featured video html from the content.
	 *
	 * @access private
	 *
	 * @param string $content The content of the post.
	 *
	 * @return string HTML
	 */
	public function remove_featured_video_content( $content ) {
		$active_template = \Sensei_Course_Theme_Template_Selection::get_active_template_name();
		if ( ! \Sensei_Course_Theme_Option::should_use_learning_mode() || in_array( $active_template, [ 'default', 'modern' ], true ) ) {
			return $content;
		}

		$blocks = parse_blocks( $content );

		$blocks = array_filter(
			$blocks,
			function ( $block ) {
				return 'sensei-lms/featured-video' !== $block['blockName'];
			}
		);

		return serialize_blocks( $blocks );
	}

	/**
	 * Gets the HTML content from the Featured Video for a post.
	 *
	 * @since $$next-version$$
	 *
	 * @param string $post_id the post ID.
	 *
	 * @return string The featured video HTML output.
	 */
	public static function get_featured_video_html( $post_id ) {
		global $wp_embed;
		if ( has_blocks( $post_id ) ) {
			$post   = get_post( $post_id );
			$blocks = parse_blocks( $post->post_content );
			foreach ( $blocks as $block ) {
				if ( 'sensei-lms/featured-video' === $block['blockName'] ) {
					$content = render_block( $block );
					$content = $wp_embed->run_shortcode( $content );
					return $wp_embed->autoembed( $content );
				}
			}
		} else {
			ob_start();
			Sensei()->frontend->sensei_lesson_video( $post_id );
			return trim( ob_get_clean() );
		}
	}
}
