<?php
/**
 * The Template for displaying Multi Line Questions.
 *
 * Override this template by copying it to yourtheme/sensei/single-quiz/question_type-multi-line.php
 *
 * @author      WooThemes
 * @package     Sensei/Templates
 * @version     1.3.0
 */

global $post, $woothemes_sensei, $current_user;
// Get Frontend Data
$user_quizzes = $woothemes_sensei->frontend->data->user_quizzes;
$question_item = $woothemes_sensei->frontend->data->question_item;
$question_count = $woothemes_sensei->frontend->data->question_count;
// Question Meta
$question_right_answer = get_post_meta( $question_item->ID, '_question_right_answer', true );
$question_wrong_answers = get_post_meta( $question_item->ID, '_question_wrong_answers', true );
// Merge right and wrong answers and randomize
array_push( $question_wrong_answers, $question_right_answer );
shuffle($question_wrong_answers);
$question_text = $question_item->post_title;
?>
<li>
    <span><?php echo esc_html( stripslashes( $question_text ) ); ?></span>
    <input type="hidden" name="<?php echo esc_attr( 'question_id_' . $question_item->ID ); ?>" value="<?php echo esc_attr( $question_item->ID ); ?>" />
    <?php WooThemes_Sensei_Utils::sensei_text_editor( $user_quizzes[ $question_item->ID ], 'textquestion' . $question_item->ID, 'sensei_question[' . $question_item->ID . ']' ); ?>
</li>