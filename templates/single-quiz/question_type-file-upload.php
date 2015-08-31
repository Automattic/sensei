<?php
/**
 * The Template for displaying File Upload Questions.
 *
 * Override this template by copying it to yourtheme/sensei/single-quiz/question_type-file-upload.php
 *
 * @author      WooThemes
 * @package     Sensei/Templates
 * @version     1.5.0
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

// Question Meta
$question_id = $question_item->ID;
$question_right_answer = get_post_meta( $question_id, '_question_right_answer', true );
$question_wrong_answers = get_post_meta( $question_id, '_question_wrong_answers', true );
$question_helptext = '';
if( isset( $question_wrong_answers[0] ) ) {
	$question_helptext = $question_wrong_answers[0];
}
$question_grade = $woothemes_sensei->question->get_question_grade( $question_id );

// retrieve users stored data.
$user_answer_entry = $woothemes_sensei->quiz->get_user_question_answer( $lesson_id, $question_id, $current_user->ID );
$user_question_grade = $woothemes_sensei->quiz->get_user_question_grade( $lesson_id, $question_id, $current_user->ID );

// Get uploaded file
$attachment_id = $user_answer_entry;
$answer_media_url = $answer_media_filename = '';
if( 0 < intval( $attachment_id ) ) {
	$answer_media_url = wp_get_attachment_url( $attachment_id );
	$answer_media_filename = basename( $answer_media_url );
}

// Get max upload file size, formatted for display
// Code copied from wp-admin/includes/media.php:1515
$upload_size_unit = $max_upload_size = wp_max_upload_size();
$sizes = array( 'KB', 'MB', 'GB' );
for ( $u = -1; $upload_size_unit > 1024 && $u < count( $sizes ) - 1; $u++ ) {
	$upload_size_unit /= 1024;
}
if ( $u < 0 ) {
	$upload_size_unit = 0;
	$u = 0;
} else {
	$upload_size_unit = (int) $upload_size_unit;
}
$max_upload_size = sprintf( __( 'Maximum upload file size: %d%s' ), esc_html( $upload_size_unit ), esc_html( $sizes[ $u ] ) );

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

$question_text = get_the_title( $question_item );
$question_description = apply_filters( 'the_content', $question_item->post_content );

$answer_message = false;
$answer_notes = false;
if( ( $lesson_complete && $user_quiz_grade != '' ) || ( $lesson_complete && ! $reset_quiz_allowed && $user_quiz_grade != '' ) ) {
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
<li class="file-upload">
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
	<?php if( $question_helptext ) { ?>
		<?php echo apply_filters( 'the_content', $question_helptext ); ?>
	<?php } ?>
	<?php if ( $answer_media_url && $answer_media_filename ) { ?>
		<p class="submitted_file"><?php printf( __( 'Submitted file: %1$s', 'woothemes-sensei' ), '<a href="' . esc_url( $answer_media_url ) . '" target="_blank">' . esc_html( $answer_media_filename ) . '</a>' ); ?></p>
		<?php if( ! $lesson_complete ) { ?>
			<aside class="reupload_notice"><?php _e( 'Uploading a new file will replace your existing one:', 'woothemes-sensei' ); ?></aside>
		<?php } ?>
	<?php } ?>
	<?php if( ! $lesson_complete ) { ?>
		<input type="file" name="file_upload_<?php echo $question_id; ?>" />
		<input type="hidden" name="sensei_question[<?php echo $question_id; ?>]" value="<?php echo esc_attr( $user_answer_entry ); ?>" />
		<aside class="max_upload_size"><?php echo $max_upload_size; ?></aside>
	<?php } ?>
	<?php if( $answer_notes ) { ?>
		<div class="sensei-message info info-special"><?php echo apply_filters( 'the_content', $answer_notes ); ?></div>
	<?php } ?>
</li>