<?php
/**
 * File containing the Sensei_Logo class.
 *
 * @package sensei
 * @since   4.0.0
 */

namespace Sensei\Blocks\Course_Theme;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use \Sensei_Blocks;

/**
 * Exit course link, to leave Learning Mode and open the course page.
 */
class Sensei_Logo {
	/**
	 * Block JSON file.
	 */
	const BLOCK_JSON_FILE = '/sensei-logo-block/block.json';

	/**
	 * Exit_Course constructor.
	 */
	public function __construct() {
		$block_json_path = Sensei()->assets->src_path( 'course-theme/blocks' ) . self::BLOCK_JSON_FILE;

		Sensei_Blocks::register_sensei_block(
			'sensei-lms/sensei-logo',
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
		$icon = Sensei()->assets->get_icon(
			'sensei-logo-tree',
			'',
			[
				'width'  => '85',
				'height' => '40',
			]
		);

		return '<a href="https://www.senseilms.com/" target="_blank">' . $icon . '</a>';
	}
}
