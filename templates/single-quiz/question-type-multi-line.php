<?php
/**
 * The Template for displaying Multi Line Questions.
 *
 * Override this template by copying it to yourtheme/sensei/single-quiz/question_type-multi-line.php
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

Sensei_Utils::sensei_text_editor(
	$question_data['user_answer_entry'],
	'textquestion' . $question_data['ID'],
	'sensei_question[' . $question_data['ID'] . ']'
);

