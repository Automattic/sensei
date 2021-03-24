<?php
/**
 * File containing the Sensei_Learner_Courses_Block class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Sensei_Learner_Courses_Block
 */
class Sensei_Learner_Courses_Block {
	/**
	 * Sensei_Learner_Courses_Block constructor.
	 */
	public function __construct() {
		Sensei_Blocks::register_sensei_block(
			'sensei-lms/learner-courses',
			[
				'render_callback' => [ $this, 'render' ],
			],
			Sensei()->assets->src_path( 'blocks/learner-courses-block' )
		);
	}

	/**
	 * Renders learner courses block in the frontend.
	 *
	 * @param array  $attributes The block attributes.
	 * @param string $content    The inner block content.
	 *
	 * @return string The HTML of the block.
	 */
	public function render( $attributes, $content ) : string {
		return '<p>Learner Courses block content</p>';
	}
}
