<?php
/**
 * File containing the Sensei_Block_Quiz_Progress class.
 *
 * @package sensei
 * @since   4.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render quiz progress with a progress bar.
 */
class Sensei_Block_Quiz_Progress {
	/**
	 * Quiz_Actions constructor.
	 */
	public function __construct() {
		Sensei_Blocks::register_sensei_block(
			'sensei-lms/quiz-progress',
			[
				'render_callback' => [ $this, 'render' ],
			]
		);
	}

	/**
	 * Renders the block.
	 *
	 * @access private
	 *
	 * @param array $attributes Block attributes.
	 *
	 * @return string The block HTML.
	 */
	public function render( $attributes ): string {

		if ( ! sensei_can_user_view_lesson() || 'quiz' !== get_post_type() ) {
			return '';
		}

		$quiz_id = get_the_ID();

		$questions     = (int) get_post_meta( $quiz_id, '_show_questions', true );
		$total         = $questions ? $questions : count( Sensei()->quiz->get_questions( $quiz_id ) );
		$user_id       = get_current_user_id();
		$lesson_id     = Sensei()->quiz->get_lesson_id( $quiz_id );
		$answers       = Sensei()->quiz->get_user_answers( $lesson_id, $user_id );
		$answers_count = is_array( $answers ) ? count( array_filter( $answers ) ) : 0;

		$completed  = $answers_count;
		$percentage = Sensei_Utils::quotient_as_absolute_rounded_percentage( $completed, $total, 2 );

		// translators: %1$d number of questions completed, %2$d number of total questions, %3$s percentage.
		$label = sprintf( __( '%1$d of %2$d questions completed (%3$s)', 'sensei-lms' ), $completed, $total, $percentage . '%' );

		$attributes = array_merge(
			$attributes,
			[
				'percentage' => $percentage,
				'label'      => $label,
			]
		);

		return \Sensei\Blocks\Shared\Progress_Bar::render( $attributes );
	}

}
