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

		if ( empty( $lesson_id ) ) {
			return '';
		}

		$quiz_permalink = Sensei()->lesson->get_quiz_permalink( $lesson_id );

		if ( ! $quiz_permalink || ! Sensei()->access_settings() ) {
			return '';
		}

		$user_id            = wp_get_current_user()->ID;
		$user_lesson_status = \Sensei_Utils::user_lesson_status( $lesson_id, $user_id );
		$quiz_submitted     = $user_lesson_status && in_array( $user_lesson_status->comment_approved, [ 'ungraded', 'passed', 'failed', 'graded' ], true );

		if ( 0 === $user_id || $quiz_submitted ) {
			return '<a href="' . esc_url( $quiz_permalink ) . '" class="">' . esc_html__( 'View quiz', 'sensei-lms' ) . '</a>';
		}

		return '<a href="' . esc_url( $quiz_permalink ) . '" class="">' . esc_html__( 'Take quiz', 'sensei-lms' ) . '</a>';
	}
}
