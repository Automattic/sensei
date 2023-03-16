<?php
/**
 * File containing the Sensei_Continue_Course_Block class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Sensei_Continue_Course_Block
 */
class Sensei_Continue_Course_Block {

	/**
	 * Sensei_Continue_Course_Block constructor.
	 */
	public function __construct() {
		Sensei_Blocks::register_sensei_block(
			'sensei-lms/button-continue-course',
			[
				'render_callback' => [ $this, 'render' ],
			]
		);
	}

	/**
	 * Renders the `sensei-lms/button-continue-course` block on the server.
	 *
	 * @param array  $attributes Block attributes.
	 * @param string $content    Block default content.
	 *
	 * @access private
	 *
	 * @return string Returns a Continue button that links to the course page.
	 */
	public function render( array $attributes, string $content ) : string {
		$course_id = get_the_ID();
		$user_id   = get_current_user_id();

		/**
		 * Whether to render the Continue Course block.
		 *
		 * @since x.x.x
		 *
		 * @param {boolean} $render     Whether to render the Continue Course block.
		 * @param {array}   $attributes Block attributes.
		 * @param {string}  $content    Block content.
		 *
		 * @return {boolean} Whether to render the Continue Course block.
		 */
		$render = apply_filters(
			'sensei_render_continue_course_block',
			Sensei()->course::is_user_enrolled( $course_id, $user_id ) && ! Sensei_Utils::user_completed_course( $course_id, $user_id ),
			$attributes,
			$content
		);

		if ( ! $render ) {
			return '';
		}

		$target_post_id = Sensei_Utils::get_target_page_post_id_for_continue_url( $course_id, $user_id );

		return '<form action="' . esc_url( get_permalink( absint( $target_post_id ?? $course_id ) ) ) . '" method="get" class="sensei-block-wrapper sensei-cta">' .
			preg_replace(
				'/<a(.*)>/',
				'<button type="submit" $1>',
				$content,
				1
			) .
		'</form>';
	}
}
