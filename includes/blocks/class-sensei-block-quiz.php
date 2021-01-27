<?php
/**
 * File containing the Sensei_Block_Quiz class.
 *
 * @package sensei
 * @since 3.9.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Sensei_Block_Quiz is responsible for handling the 'Quiz' block.
 */
class Sensei_Block_Quiz {

	/**
	 * Sensei_Block_Quiz constructor.
	 */
	public function __construct() {
		Sensei_Blocks::register_sensei_block(
			'sensei-lms/quiz',
			[
				'render_callback' => [ $this, 'render_quiz' ],
			],
			Sensei()->assets->src_path( 'blocks/quiz' )
		);
	}

	/**
	 * Renders the block as an empty string.
	 *
	 * @param array  $attributes The block attributes.
	 * @param string $content    The block content.
	 *
	 * @return string The block HTML.
	 */
	public function render_quiz( array $attributes, string $content ) : string {
		return '';
	}
}
