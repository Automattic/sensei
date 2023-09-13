<?php
/**
 * The Template for displaying Multi Line Questions.
 *
 * Override this template by copying it to yourtheme/sensei/single-quiz/question_type-multi-line.php
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

if ( $sensei_is_quiz_view_only_mode ) {
	?>
	<div class="wp-block-sensei-lms-question-answers__answer">
		<?php echo wp_kses_post( $question_data['user_answer_entry'] ); ?>
	</div>
	<?php
} else {
	Sensei_Utils::sensei_text_editor(
		$question_data['user_answer_entry'],
		'textquestion' . $question_data['ID'],
		'sensei_question[' . $question_data['ID'] . ']'
	);
}
