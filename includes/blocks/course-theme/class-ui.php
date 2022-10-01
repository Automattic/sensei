<?php
/**
 * Learning Mode Ui block.
 *
 * @package sensei
 * @since
 */

namespace Sensei\Blocks\Course_Theme;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use \Sensei_Blocks;

/**
 * User interface block for Learning mode layout elements.
 */
class Ui {
	/**
	 * Block JSON file.
	 */
	const BLOCK_JSON_FILE = '/ui/ui.block.json';

	/**
	 * Course_Title constructor.
	 */
	public function __construct() {
		$block_json_path = Sensei()->assets->src_path( 'course-theme/blocks' ) . self::BLOCK_JSON_FILE;
		Sensei_Blocks::register_sensei_block(
			'sensei-lms/ui',
			[
				'style'           => 'sensei-learning-mode',
				'editorStyle'     => 'sensei-learning-mode-editor',
				'render_callback' => [ $this, 'render' ],
			],
			$block_json_path
		);
	}

	/**
	 * Renders the UI block.
	 *
	 * @param array  $attributes The block attributes.
	 * @param string $content The block content.
	 */
	public function render( array $attributes, string $content ): string {
		$element_class = $attributes['elementClass'] ?? '';

		switch ( $element_class ) {
			case 'sensei-course-theme__video-container':
				return $this->render_video_container( $attributes, $content );
			default:
				return $content;
		}
	}

	/**
	 * Renders the video container variation of the UI block.
	 *
	 * @param array  $attributes The block attributes.
	 * @param string $content The block content.
	 */
	public function render_video_container( array $attributes, string $content ) {
		$post = get_post();

		if ( ! has_block( 'sensei-lms/featured-video', $post ) ) {
			$content = str_replace( 'sensei-course-theme__video-container', 'sensei-course-theme__video-container no-video', $content );
		}

		return $content;
	}
}
