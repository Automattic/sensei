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
$quiz_passmark = $woothemes_sensei->frontend->data->quiz_passmark;
$user_quiz_grade = $woothemes_sensei->frontend->data->user_quiz_grade;
$lesson_complete = $woothemes_sensei->frontend->data->user_lesson_complete;
$reset_quiz_allowed = $woothemes_sensei->frontend->data->reset_quiz_allowed;

// Question Meta
$question_id = $question_item->ID;
$question_right_answer = get_post_meta( $question_id, '_question_right_answer', true );
$question_grade = get_post_meta( $question_id, '_question_grade', true );
if( ! $question_grade || $question_grade == '' ) {
    $question_grade = 1;
}
$user_question_grade = WooThemes_Sensei_Utils::sensei_get_user_question_grade( $question_id, $current_user->ID );

// Question media
$question_media = get_post_meta( $question_id, '_question_media', true );
$question_media_type = $question_media_thumb = $question_media_link = $question_media_title = $question_media_description = '';
if( 0 < intval( $question_media ) ) {
    $mimetype = get_post_mime_type( $question_media );
    if( $mimetype ) {
        $mimetype_array = explode( '/', $mimetype);
        if( isset( $mimetype_array[0] ) && $mimetype_array[0] ) {
            $question_media_type = $mimetype_array[0];
            $question_media_url = wp_get_attachment_url( $question_media );
            $attachment = get_post( $question_media );
            $question_media_title = $attachment->post_title;
            $question_media_description = $attachment->post_content;
            switch( $question_media_type ) {
                case 'image':
                    $image_size = apply_filters( 'sensei_question_image_size', 'medium', $question_id );
                    $attachment_src = wp_get_attachment_image_src( $question_media, $image_size );
                    $question_media_link = '<a class="' . esc_attr( $question_media_type ) . '" title="' . esc_attr( $question_media_title ) . '" href="' . esc_url( $question_media_url ) . '" target="_blank"><img src="' . $attachment_src[0] . '" width="' . $attachment_src[1] . '" height="' . $attachment_src[2] . '" /></a>';
                break;

                case 'audio':
                    $question_media_link = wp_audio_shortcode( array( 'src' => $question_media_url ) );
                break;

                case 'video':
                    $question_media_link = wp_video_shortcode( array( 'src' => $question_media_url ) );
                break;

                default:
                    $question_media_filename = basename( $question_media_url );
                    $question_media_link = '<a class="' . esc_attr( $question_media_type ) . '" title="' . esc_attr( $question_media_title ) . '" href="' . esc_url( $question_media_url ) . '" target="_blank">' . $question_media_filename . '</a>';
                break;
            }
        }
    }
}

// Gap Fill data
$question_text = $question_item->post_title;
$gapfill_array = explode( '|', $question_right_answer );
if ( isset( $gapfill_array[0] ) ) { $gapfill_pre = $gapfill_array[0]; } else { $gapfill_pre = ''; }
if ( isset( $gapfill_array[1] ) ) { $gapfill_gap = $gapfill_array[1]; } else { $gapfill_gap = ''; }
if ( isset( $gapfill_array[2] ) ) { $gapfill_post = $gapfill_array[2]; } else { $gapfill_post = ''; }

$answer_message = false;
$answer_notes = false;
if( ( $lesson_complete && $user_quiz_grade != '' ) || ( $lesson_complete && ! $reset_quiz_allowed && $user_quiz_grade != '' ) ) {
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
<li class="gap-fill">
    <span><?php echo esc_html( stripslashes( $question_text ) ); ?> <span>[<?php echo $question_grade; ?>]</span></span>
    <?php if( $question_media_link ) { ?>
        <div class="question_media_display">
            <?php echo $question_media_link; ?>
            <dl>
                <?php if( $question_media_title ) { ?>
                    <dt><?php echo $question_media_title; ?></dt>
                <?php } ?>
                <?php if( $question_media_description ) { ?>
                    <?php echo '<dd>' . $question_media_description . '</dd>'; ?>
                <?php } ?>
            </dl>
        </div>
    <?php } ?>
    <?php if( $answer_message ) { ?>
        <div class="answer_message <?php esc_attr_e( $answer_message_class ); ?>">
            <span><?php echo $answer_message; ?></span>
        </div>
    <?php } ?>
    <input type="hidden" name="<?php echo esc_attr( 'question_id_' . $question_id ); ?>" value="<?php echo esc_attr( $question_id ); ?>" />
    <p class="gapfill-answer">
    	<span class="gapfill-answer-pre"><?php echo esc_html( $gapfill_pre ); ?></span>&nbsp;<input type="text" id="<?php echo esc_attr( 'question_' . $question_id ); ?>" name="<?php echo esc_attr( 'sensei_question[' . $question_id . ']' ); ?>" value="<?php echo esc_attr( $user_quizzes[ $question_id ] ); ?>" class="gapfill-answer-gap" />&nbsp;<span class="gapfill-answer-post"><?php echo esc_html( $gapfill_post ); ?></span>
    </p>
    <?php if( $answer_notes ) { ?>
        <div class="sensei-message info info-special"><?php echo $answer_notes; ?></div>
    <?php } ?>
</li>