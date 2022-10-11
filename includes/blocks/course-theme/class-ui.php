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
				/**
				'style'           => 'sensei-learning-mode',
				'editorStyle'     => 'sensei-learning-mode-editor',
				'render_callback' => [ $this, 'render' ],
				 */
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

		// Add variation-specific rendering here.
		switch ( $element_class ) {
			default:
				return $content;
		}
	}
}
