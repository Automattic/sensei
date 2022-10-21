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
	public function render(): string {

		$video = sensei_can_user_view_lesson() ? Sensei_Utils::get_featured_video_html() : null;

		if ( ! empty( $video ) ) {
			add_filter(
				'body_class',
				function( $classes ) {
					return array_merge( $classes, [ 'sensei-video-lesson' ] );
				}
			);
		} else {
			return '';
		}

		$wrapper_attr = get_block_wrapper_attributes(
			[
				'class' => 'sensei-course-theme-lesson-video',
			]
		);

		remove_action( 'sensei_lesson_video', [ Sensei_Frontend::class, 'sensei_lesson_video' ] );
		remove_action( 'the_content', [ \Sensei_Course_Theme::instance(), 'add_lesson_video_to_content' ], 80 );

		global $sensei_template_has_lesson_video_block;
		$sensei_template_has_lesson_video_block = true;

		return sprintf(
			'<div %1s>%2s</div>',
			$wrapper_attr,
			trim( $video )
		);
	}
}
