<?php
/**
 * The Template for displaying Multiple Choice Questions.
 *
 * Override this template by copying it to yourtheme/sensei/single-quiz/question_type-multiple-choice.php
 *
 * @author 		WooThemes
 * @package 	Sensei/Templates
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
$reset_quiz_allowed = $woothemes_sensei->frontend->data->reset_quiz_allowed;
$quiz_grade_type = $woothemes_sensei->frontend->data->quiz_grade_type;

// Question ID
$question_id = $question_item->ID;

// Question answers
$question_right_answer = get_post_meta( $question_id, '_question_right_answer', true );
$question_wrong_answers = get_post_meta( $question_id, '_question_wrong_answers', true );

// Merge right and wrong answers
array_push( $question_wrong_answers, $question_right_answer );

// Setup answer array
foreach( $question_wrong_answers as $answer ) {
    $answer_id = WooThemes_Sensei_Lesson::get_answer_id( $answer );
    $question_answers[ $answer_id ] = $answer;
}

$answers_sorted = array();
$random_order = get_post_meta( $question_id, '_random_order', true );
if( ! $random_order || ( $random_order && $random_order == 'yes' ) ) {
    $answers_sorted = $question_answers;
    shuffle( $answers_sorted );
} else {
    $answer_order = array();
    $answer_order_string = get_post_meta( $question_id, '_answer_order', true );
    if( $answer_order_string ) {
        $answer_order = array_filter( explode( ',', $answer_order_string ) );
    }

    if( count( $answer_order ) > 0 ) {
        foreach( $answer_order as $answer_id ) {
            if( $question_answers[ $answer_id ] ) {
                $answers_sorted[ $answer_id ] = $question_answers[ $answer_id ];
                unset( $question_answers[ $answer_id ] );
            }
        }

        if( count( $question_answers ) > 0 ) {
            foreach( $question_answers as $id => $answer ) {
                $answers_sorted[ $id ] = $answer;
            }
        }
    }
}

$question_grade = get_post_meta( $question_id, '_question_grade', true );
if( ! $question_grade || $question_grade == '' ) {
    $question_grade = 1;
}
$user_question_grade = WooThemes_Sensei_Utils::sensei_get_user_question_grade( $question_id, $current_user->ID );

$question_text = $question_item->post_title;

$answer_message = false;
$answer_notes = false;
if( ( $lesson_complete && $user_quiz_grade != '' ) || ( $lesson_complete && ! $reset_quiz_allowed && 'auto' == $quiz_grade_type ) || ( 'auto' == $quiz_grade_type && ! $reset_quiz_allowed && $user_quiz_grade != '' ) ) {
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
<li class="multiple-choice">
    <span><?php echo esc_html( stripslashes( $question_text ) ); ?> <span>[<?php echo $question_grade; ?>]</span></span>
    <?php if( $answer_message ) { ?>
        <div class="answer_message <?php esc_attr_e( $answer_message_class ); ?>">
            <span><?php echo $answer_message; ?></span>
            <?php if( $answer_notes ) { ?>
                <div class="notes"><p><?php echo $answer_notes; ?></p></div>
            <?php } ?>
        </div>
    <?php } ?>
    <input type="hidden" name="<?php echo esc_attr( 'question_id_' . $question_id ); ?>" value="<?php echo esc_attr( $question_id ); ?>" />
    <ul>
    <?php $count = 0; ?>
    <?php foreach( $answers_sorted as $id => $question ) {
        $checked = '';
        $count++;

        $answer_class = '';
        if( isset( $user_correct ) ) {
            if( $user_quizzes[ $question_id ] == $question ) {
                $answer_class = 'user_wrong';
                if( $user_correct ) {
                    $answer_class = 'user_right';
                }
            }
            if( $question_right_answer == $question ) {
                $answer_class .= ' right_answer';
            }
        }

        if ( isset( $user_quizzes[ $question_id ] ) && ( '' != $user_quizzes[ $question_id ] ) ) {
            $checked = checked( $question, $user_quizzes[ $question_id ], false );
        } // End If Statement ?>
        <li class="<?php esc_attr_e( $answer_class ); ?>">
            <input type="radio" id="<?php echo esc_attr( 'question_' . $question_id ) . '-option-' . $count; ?>" name="<?php echo esc_attr( 'sensei_question[' . $question_id . ']' ); ?>" value="<?php echo esc_attr( stripslashes( $question ) ); ?>" <?php echo $checked; ?><?php if ( !is_user_logged_in() ) { echo ' disabled'; } ?>>&nbsp;
            <label for="<?php echo esc_attr( 'question_' . $question_id ) . '-option-' . $count; ?>"><?php echo esc_html( stripslashes( $question ) ); ?></label>
        </li>
    <?php } // End For Loop ?>
    </ul>
</li>