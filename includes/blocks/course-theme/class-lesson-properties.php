<?php
/**
 * File containing the Lesson_Properties class.
 *
 * @package sensei
 * @since 4.7.0
 */

namespace Sensei\Blocks\Course_Theme;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use \Sensei_Blocks;
use Sensei_Lesson_Properties_Block;

/**
 * Class Lesson_Properties
 */
class Lesson_Properties {
	/**
	 * Block JSON file.
	 */
	const BLOCK_JSON_FILE = '/lesson-blocks/learning-mode-lesson-properties.block.json';

	/**
	 * Lesson_Properties constructor.
	 */
	public function __construct() {
		$block_json_path = Sensei()->assets->src_path( 'course-theme/blocks' ) . self::BLOCK_JSON_FILE;
		Sensei_Blocks::register_sensei_block(
			'sensei-lms/learning-mode-lesson-properties',
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
	 * @param array  $attributes The block attributes.
	 * @param string $content    Inner block content.
	 *
	 * @access private
	 *
	 * @return string The block HTML.
	 */
	public function render( array $attributes = [], string $content = '' ) : string {
		return Sensei_Lesson_Properties_Block::render_content( $attributes, $content );
	}
}
