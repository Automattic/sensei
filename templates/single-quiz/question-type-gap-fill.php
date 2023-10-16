<?php
/**
 * The Template for displaying Gap Fill Line Questions.
 *
 * Override this template by copying it to yourtheme/sensei/single-quiz/question_type-gap-fill.php
 *
 * @author      Automattic
 * @package     Sensei
 * @category    Templates
 * @version     4.17.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get the question data with the current quiz id
 * All data is loaded in this array to keep the template clean.
 */
$question_data = Sensei_Question::get_template_data( sensei_get_the_question_id(), get_the_ID() );

$sensei_is_quiz_view_only_mode = $question_data['quiz_is_completed'] || ! Sensei_Quiz::is_quiz_available();
?>


<p class="gapfill-answer">
	<span class="gapfill-answer-pre">
		<?php
		/**
		 * Filter user answer text.
		 *
		 * @hook sensei_answer_text
		 *
		 * @param {string} Answer text.
		 * @return {string} Filtered answer text.
		 */
		echo wp_kses_post( apply_filters( 'sensei_answer_text', esc_html( $question_data['gapfill_pre'] ) ) );
		?>
		<?php if ( $sensei_is_quiz_view_only_mode ) { ?>
			<span class="wp-block-sensei-lms-question-answers__answer">
				<?php echo wp_kses_post( $question_data['user_answer_entry'] ); ?>
			</span>
		<?php } else { ?>
			<input type="text" id="<?php echo esc_attr( 'question_' . $question_data['ID'] ); ?>"
				name="<?php echo esc_attr( 'sensei_question[' . $question_data['ID'] . ']' ); ?>"
				value="<?php echo esc_attr( $question_data['user_answer_entry'] ); ?>"
				class="gapfill-answer-gap"
				<?php echo $question_data['quiz_is_completed'] || ! Sensei_Quiz::is_quiz_available() ? 'disabled' : ''; ?>
			/>
		<?php } ?>
		<span class="gapfill-answer-post">
			<?php
			/**
			 * Filter user answer text.
			 *
			 * @hook sensei_answer_text
			 *
			 * @param {string} Answer text.
			 * @return {string} Filtered answer text.
			 */
			echo wp_kses_post( apply_filters( 'sensei_answer_text', esc_html( $question_data['gapfill_post'] ) ) );
			?>
		</span>
	</span>
</p>
