<?php
/**
 * The Template for displaying True/False Questions.
 *
 * Override this template by copying it to yourtheme/sensei/single-quiz/question_type-boolean.php
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
$quiz_passmark = $woothemes_sensei->frontend->data->quiz_passmark;
$user_quiz_grade = $woothemes_sensei->frontend->data->user_quiz_grade;
$lesson_complete = $woothemes_sensei->frontend->data->user_lesson_complete;

// Question Meta
$question_id = $question_item->ID;
$question_right_answer = get_post_meta( $question_id, '_question_right_answer', true );
$question_wrong_answers = get_post_meta( $question_id, '_question_wrong_answers', true );
$question_grade = get_post_meta( $question_id, '_question_grade', true );
if( ! $question_grade || $question_grade == '' ) {
    $question_grade = 1;
}
$user_question_grade = WooThemes_Sensei_Utils::sensei_get_user_question_grade( $question_id, $current_user->ID );

// Merge right and wrong answers and randomize
array_push( $question_wrong_answers, $question_right_answer );
shuffle($question_wrong_answers);
$question_text = $question_item->post_title;

$answer_message = false;
$answer_notes = false;
if( $lesson_complete && $user_quiz_grade && $user_quiz_grade != '' ) {
    $user_correct = false;
    $answer_message = __( 'Incorrect', 'woothemes-sensei' );
    $answer_message_class = 'user_wrong';
    if( $user_question_grade > 0 ) {
        $user_correct = true;
        $answer_message = sprintf( __( 'Grade: %d', 'woothemes-sensei' ), $user_question_grade );
        $answer_message_class = 'user_right';
    }
    $answer_notes = base64_decode( WooThemes_Sensei_Utils::sensei_get_activity_value( array( 'post_id' => $question_id, 'user_id' => $current_user->ID, 'type' => 'sensei_answer_notes', 'field' => 'comment_content' ) ) );
    if( $answer_notes ) {
        $answer_message_class .= ' has_notes';
    }
}

?>
<li class="boolean">
    <span><?php echo esc_html( stripslashes( $question_text ) ); ?> <span>[<?php echo $question_grade; ?>]</span></span>
    <?php if( $answer_message ) { ?>
        <div class="answer_message <?php esc_attr_e( $answer_message_class ); ?>">
            <span><?php echo $answer_message; ?></span>
            <?php if( $answer_notes ) { ?>
                <div class="notes"><?php echo $answer_notes; ?></div>
            <?php } ?>
        </div>
    <?php } ?>
    <input type="hidden" name="<?php echo esc_attr( 'question_id_' . $question_id ); ?>" value="<?php echo esc_attr( $question_id ); ?>" />
    <ul>
    	<?php
    	$answer_class = '';
        if( isset( $user_correct ) ) {
            if( $question_right_answer == 'true' ) {
            	if( $user_correct ) {
	                $answer_class = 'user_right';
	            }
                $answer_class .= ' right_answer';
            }
        }
        ?>
        <li class="<?php esc_attr_e( $answer_class ); ?>"><input type="radio" id="<?php echo esc_attr( 'question_' . $question_id ) . '-option-' . $count . '-true'; ?>" name="<?php echo esc_attr( 'sensei_question[' . $question_id . ']' ); ?>" value="true" <?php echo checked( $user_quizzes[ $question_id ], 'true', false ); ?><?php if ( !is_user_logged_in() ) { echo ' disabled'; } ?>>&nbsp;<label for="<?php echo esc_attr( 'question_' . $question_id ) . '-option-' . $count . '-true'; ?>"><?php _e( 'True', 'woothemes-sensei' ); ?></label></li>
        <?php
        $answer_class = '';
        if( isset( $user_correct ) ) {
            if( $question_right_answer == 'false' ) {
            	if( $user_correct ) {
	                $answer_class = 'user_right';
	            }
                $answer_class .= ' right_answer';
            }
        }
        ?>
        <li class="<?php esc_attr_e( $answer_class ); ?>"><input type="radio" id="<?php echo esc_attr( 'question_' . $question_id ) . '-option-' . $count . '-false'; ?>" name="<?php echo esc_attr( 'sensei_question[' . $question_id . ']' ); ?>" value="false" <?php echo checked( $user_quizzes[ $question_id ], 'false', false ); ?><?php if ( !is_user_logged_in() ) { echo ' disabled'; } ?>>&nbsp;<label for="<?php echo esc_attr( 'question_' . $question_id ) . '-option-' . $count . '-false'; ?>"><?php _e( 'False', 'woothemes-sensei' ); ?></label></li>
    </ul>
</li>