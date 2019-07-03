<?php
/**
 * The Template for displaying Single Line Questions.
 *
 * Override this template by copying it to yourtheme/sensei/single-quiz/question_type-single-line.php
 *
 * @author      Automattic
 * @package     Sensei
 * @category    Templates
 * @version     2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get the question data with the current quiz id
 * All data is loaded in this array to keep the template clean.
 */
$question_data = Sensei_Question::get_template_data( sensei_get_the_question_id(), get_the_ID() );

?>

<div class="answer">

	<label for="<?php echo esc_attr( 'question_' . $question_data['ID'] ); ?>">
		<?php esc_html_e( 'Answer:', 'sensei-lms' ); ?>
	</label>

	<input type="text" id="<?php echo esc_attr( 'question_' . $question_data['ID'] ); ?>"
		   name="<?php echo esc_attr( 'sensei_question[' . $question_data['ID'] . ']' ); ?>"
		   value="<?php echo esc_attr( $question_data['user_answer_entry'] ); ?>" />

</div>
