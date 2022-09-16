<?php
/**
 * File containing the Template_Style class.
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
 * Allows to embed css styles into the block templates. Used for Learning Mode block templates.
 */
class Template_Style {
	/**
	 * Block JSON file.
	 */
	const BLOCK_JSON_FILE = '/template-style/template-style.block.json';

	/**
	 * Style constructor.
	 */
	public function __construct() {
		$block_json_path = Sensei()->assets->src_path( 'course-theme/blocks' ) . self::BLOCK_JSON_FILE;
		Sensei_Blocks::register_sensei_block(
			'sensei-lms/template-style',
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
	 * @param string $content The block content.
	 *
	 * @access private
	 *
	 * @return string The block HTML.
	 */
	public function render( array $attributes = [], string $content ): string {
		return $content;
	}

	/**
	 * Returns a string that could be embedded into block template as a block.
	 *
	 * @param string $content The css content of the Template_Style block.
	 */
	public static function render_embed( string $content = '' ): string {
		return "
            <!-- wp:sensei-lms/template-style -->
            <style>
                {$content}
            </style>
            <!-- /wp:sensei-lms/template-style -->
		";
	}
}
