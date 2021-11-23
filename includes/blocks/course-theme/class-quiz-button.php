<?php
/**
 * File containing the Quiz_Button class.
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
 * Class Quiz_Button is responsible for rendering the Quiz button block.
 */
class Quiz_Button {
	/**
	 * Quiz_Button constructor.
	 */
	public function __construct() {
		Sensei_Blocks::register_sensei_block(
			'sensei-lms/course-theme-quiz-button',
			[
				'render_callback' => [ $this, 'render' ],
			]
		);
	}

	/**
	 * Renders the block.
	 *
	 * @param array $attributes The block attributes.
	 *
	 * @access private
	 *
	 * @return string The block HTML.
	 */
	public function render( array $attributes = [] ) : string {
		$lesson_id = get_the_ID();
		$user_id   = wp_get_current_user()->ID;

		if ( empty( $lesson_id ) || empty( $user_id ) || ! Sensei()->access_settings() ) {
			return '';
		}

		$quiz_permalink = Sensei()->lesson->get_quiz_permalink( $lesson_id );

		if ( empty( $quiz_permalink ) ) {
			return '';
		}

		if ( Sensei()->lesson->is_quiz_submitted( $lesson_id, $user_id ) ) {
			return '';
		}

		return '<a href="' . esc_url( $quiz_permalink ) . '" class="">' . esc_html__( 'Take quiz', 'sensei-lms' ) . '</a>';
	}
}
