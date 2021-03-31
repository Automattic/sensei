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

	const NAME = 'sensei-lms/learner-courses';

	/**
	 * Sensei_Learner_Courses_Block constructor.
	 */
	public function __construct() {
		Sensei_Blocks::register_sensei_block(
			self::NAME,
			[
				'render_callback' => [ $this, 'render' ],
			],
			Sensei()->assets->src_path( 'blocks/learner-courses-block' )
		);

		add_filter( 'body_class', array( $this, 'add_sensei_body_class' ), 10, 1 );
	}

	/**
	 * Renders learner courses block in the frontend.
	 *
	 * @param array  $attributes The block attributes.
	 * @param string $content    The inner block content.
	 *
	 * @return string The HTML of the block.
	 */
	public function render( $attributes, $content ): string {

		$shortcode = new Sensei_Shortcode_User_Courses( [ 'options' => $attributes['options'] ], null, null );

		return $shortcode->render();
	}

	/**
	 * Add sensei to body classes if block is used.
	 *
	 * @param array $classes
	 *
	 * @access private
	 * @return array
	 */
	public function add_sensei_body_class( $classes ) {

		if ( has_block( self::NAME ) ) {
			$classes[] = 'sensei';
		}
		return $classes;
	}
}
