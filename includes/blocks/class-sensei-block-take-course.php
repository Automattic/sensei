<?php
/**
 * File containing the Sensei_Block_Take_Course class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Block for Take course button.
 */
class Sensei_Block_Take_Course {

	/**
	 * Sensei_Contact_Teacher_Block constructor.
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'register_block' ] );
	}


	/**
	 * Register progress bar block.
	 *
	 * @access private
	 */
	public function register_block() {
		register_block_type(
			'sensei-lms/button-take-course',
			[
				'render_callback' => [ $this, 'render_take_course_block' ],
			]
		);
	}

	/**
	 * Render the take course button. Wraps block HTML within a form.
	 *
	 * @param array  $attributes Block attributes.
	 * @param string $content    Block HTML.
	 *
	 * @return string Form to start the course, with the block content as the submit button.
	 */
	public function render_take_course_block( $attributes, $content ): string {
		global $post;
		$course_id = $post->ID;

		$nonce = wp_nonce_field( 'woothemes_sensei_start_course_noonce', 'woothemes_sensei_start_course_noonce', false, false );
		return ( '
			<form method="POST" action="' . esc_url( get_permalink( $course_id ) ) . '">
			<input type="hidden" name="course_start" value="1" />
			' . $nonce . '
			' . $content . '
			</form>
			' );
	}
}
