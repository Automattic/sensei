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
$lesson_id = $woothemes_sensei->quiz->get_lesson_id( $post->ID );
$question_item = $woothemes_sensei->quiz->data->question_item;
$question_count = $woothemes_sensei->quiz->data->question_count;
$quiz_passmark = $woothemes_sensei->quiz->data->quiz_passmark;
$user_quiz_grade = $woothemes_sensei->quiz->data->user_quiz_grade;
$lesson_complete = $woothemes_sensei->quiz->data->user_lesson_complete;
$reset_quiz_allowed = $woothemes_sensei->quiz->data->reset_quiz_allowed;
$quiz_grade_type = $woothemes_sensei->quiz->data->quiz_grade_type;


// Question ID
$question_id = $question_item->ID;

// Question answers
$question_right_answer = get_post_meta( $question_id, '_question_right_answer', true );
$question_wrong_answers = get_post_meta( $question_id, '_question_wrong_answers', true );

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
$answer_type = 'radio';
// Merge right and wrong answers
if ( is_array($question_right_answer) ) {
	if ( 1 < count($question_right_answer) ) {
		$answer_type = 'checkbox';
	}
	$question_wrong_answers = array_merge( $question_wrong_answers, $question_right_answer );
}
else {
	array_push( $question_wrong_answers, $question_right_answer );
}

// Setup answer array
foreach( $question_wrong_answers as $answer ) {
	$answer_id = $woothemes_sensei->post_types->lesson->get_answer_id( $answer );
	$question_answers[ $answer_id ] = $answer;
}

$answers_sorted = array();
$random_order = get_post_meta( $question_id, '_random_order', true );
if(  $random_order && $random_order == 'yes' ) {

    $answers_sorted = $question_answers;
	shuffle( $answers_sorted );

} else {

	$answer_order = array();
	$answer_order_string = get_post_meta( $question_id, '_answer_order', true );
	if( $answer_order_string ) {

        $answer_order = array_filter( explode( ',', $answer_order_string ) );
		if( count( $answer_order ) > 0 ) {

            foreach( $answer_order as $answer_id ) {

				if( isset( $question_answers[ $answer_id ] ) ) {

					$answers_sorted[ $answer_id ] = $question_answers[ $answer_id ];
					unset( $question_answers[ $answer_id ] );

				}

			}

			if( count( $question_answers ) > 0 ) {
				foreach( $question_answers as $id => $answer ) {

                    $answers_sorted[ $id ] = $answer;

				}
			}

		}else{

            $answers_sorted = $question_answers;

        }

	} // end if $answer_order_string

}

$question_grade = $woothemes_sensei->question->get_question_grade( $question_id );

// retrieve users stored data.
$user_answer_entry = $woothemes_sensei->quiz->get_user_question_answer( $lesson_id, $question_id, $current_user->ID );
$user_question_grade = $woothemes_sensei->quiz->get_user_question_grade( $lesson_id, $question_id, $current_user->ID );

$question_text = get_the_title( $question_item );
$question_description = apply_filters( 'the_content', $question_item->post_content );

$answer_message = false;
$answer_notes = false;
if( ( $lesson_complete && $user_quiz_grade != '' ) || ( $lesson_complete && ! $reset_quiz_allowed && 'auto' == $quiz_grade_type ) || ( 'auto' == $quiz_grade_type && ! $reset_quiz_allowed && $user_quiz_grade != '' ) ) {
	$user_correct = false;
	$answer_message = __( 'Incorrect', 'woothemes-sensei' );
	$answer_message_class = 'user_wrong';
	// For zero grade mark as 'correct' but add no classes
	if ( 0 == $question_grade ) {
		$user_correct = true;
		$answer_message = '';
		$answer_message_class = '';
	}
	else if( $user_question_grade > 0 ) {
		$user_correct = true;
		$answer_message = sprintf( __( 'Grade: %d', 'woothemes-sensei' ), $user_question_grade );
		$answer_message_class = 'user_right';
	}

    $answer_notes = $woothemes_sensei->quiz->get_user_question_feedback( $lesson_id, $question_id, $current_user->ID );
	if( $answer_notes ) {
		$answer_message_class .= ' has_notes';
	}
}

?>
<li class="multiple-choice">
	<span class="question"><?php echo apply_filters( 'sensei_question_title', esc_html( $question_text ) ); ?> <span class="grade">[<?php echo $question_grade; ?>]</span></span>
	<?php echo $question_description; ?>
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
	<ul class="answers">
	<?php 
	$count = 0;
	foreach( $answers_sorted as $id => $answer ) {
		$checked = '';
		$count++;

		$answer_class = '';
		if( isset( $user_correct ) && 0 < $question_grade ) {
			if ( is_array($question_right_answer) && in_array($answer, $question_right_answer) ) {
				$answer_class .= ' right_answer';
			}
			elseif( !is_array($question_right_answer) && $question_right_answer == $answer ) {
				$answer_class .= ' right_answer';
			}
			elseif( ( is_array( $user_answer_entry  ) && in_array($answer, $user_answer_entry ) ) ||
					( !is_array( $user_answer_entry ) && $user_answer_entry == $answer ) ) {
				$answer_class = 'user_wrong';
				if( $user_correct ) {
					$answer_class = 'user_right';
				}
			}
		}

		if ( isset( $user_answer_entry ) && 0 < count( $user_answer_entry ) ) {
			if ( is_array( $user_answer_entry ) && in_array( $answer, $user_answer_entry ) ) {
				$checked = 'checked="checked"';
			}
			elseif ( !is_array( $user_answer_entry ) ) {
				$checked = checked( $answer, $user_answer_entry , false );
			}
		} // End If Statement ?>
		<li class="<?php esc_attr_e( $answer_class ); ?>">
			<input type="<?php echo $answer_type; ?>" id="<?php echo esc_attr( 'question_' . $question_id ) . '-option-' . $count; ?>" name="<?php echo esc_attr( 'sensei_question[' . $question_id . ']' ); ?>[]" value="<?php echo esc_attr( $answer ); ?>" <?php echo $checked; ?><?php if ( !is_user_logged_in() ) { echo ' disabled'; } ?>>&nbsp;
			<label for="<?php echo esc_attr( 'question_' . $question_id ) . '-option-' . $count; ?>"><?php echo apply_filters( 'sensei_answer_text', $answer ); ?></label>
		</li>
	<?php } // End For Loop ?>
	</ul>
	<?php if( $answer_notes ) { ?>
		<div class="sensei-message info info-special"><?php echo apply_filters( 'the_content', $answer_notes ); ?></div>
	<?php } ?>
</li>