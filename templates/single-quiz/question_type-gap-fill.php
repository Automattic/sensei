<?php
/**
 * The Template for displaying Gap Fill Line Questions.
 *
 * Override this template by copying it to yourtheme/sensei/single-quiz/question_type-gap-fill.php
 *
 * @author      WooThemes
 * @package     Sensei/Templates
 * @version     1.3.0
 */

global $post, $woothemes_sensei, $current_user;
/// Get Frontend Data
$user_quizzes = $woothemes_sensei->frontend->data->user_quizzes;
$question_item = $woothemes_sensei->frontend->data->question_item;
$question_count = $woothemes_sensei->frontend->data->question_count;
// Question Meta
$question_right_answer = get_post_meta( $question_item->ID, '_question_right_answer', true );
// Gap Fill data
$question_text = $question_item->post_title;
$gapfill_array = explode( '|', $question_right_answer );
if ( isset( $gapfill_array[0] ) ) { $gapfill_pre = $gapfill_array[0]; } else { $gapfill_pre = ''; }
if ( isset( $gapfill_array[1] ) ) { $gapfill_gap = $gapfill_array[1]; } else { $gapfill_gap = ''; }
if ( isset( $gapfill_array[2] ) ) { $gapfill_post = $gapfill_array[2]; } else { $gapfill_post = ''; }
?>
<li>
    <span><?php echo esc_html( stripslashes( $question_text ) ); ?></span>
    <input type="hidden" name="<?php echo esc_attr( 'question_id_' . $question_item->ID ); ?>" value="<?php echo esc_attr( $question_item->ID ); ?>" />
    <p class="gapfill-answer">
    	<span class="gapfill-answer-pre"><?php echo esc_html( $gapfill_pre ); ?></span>&nbsp;<input type="text" id="<?php echo esc_attr( 'question_' . $question_item->ID ); ?>" name="<?php echo esc_attr( 'sensei_question[' . $question_item->ID . ']' ); ?>" value="<?php echo esc_attr( $user_quizzes[ $question_item->ID ] ); ?>" class="gapfill-answer-gap" />&nbsp;<span class="gapfill-answer-post"><?php echo esc_html( $gapfill_post ); ?></span>
    </p>
</li>