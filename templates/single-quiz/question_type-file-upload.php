<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * The Template for displaying File Upload Questions.
 *
 * Override this template by copying it to yourtheme/sensei/single-quiz/question_type-file-upload.php
 *
 * @author 		Automattic
 * @package 	Sensei
 * @category    Templates
 * @version     1.9.0
 */
?>

<?php

    /**
     * Get the question data with the current quiz id
     * All data is loaded in this array to keep the template clean.
     */
    $question_data = WooThemes_Sensei_Question::get_template_data( sensei_get_the_question_id(), get_the_ID() );

?>

<?php if( $question_data[ 'question_helptext' ] ) { ?>

    <?php echo apply_filters( 'the_content', $question_data[ 'question_helptext' ] ); ?>

<?php } ?>

<?php if ( $question_data[ 'answer_media_url' ]  &&  $question_data[ 'answer_media_filename' ]  ) { ?>

    <p class="submitted_file">

        <?php

        printf( __( 'Submitted file: %1$s', 'woothemes-sensei' ), '<a href="' . esc_url(  $question_data[ 'answer_media_url' ] )
            . '" target="_blank">'
            . esc_html(  $question_data[ 'answer_media_filename' ] ) . '</a>' );

        ?>

    </p>
    <?php if( !  $question_data[ 'lesson_complete' ]  ) { ?>

        <aside class="reupload_notice"><?php _e( 'Uploading a new file will replace your existing one:', 'woothemes-sensei' ); ?></aside>

    <?php } ?>

<?php } ?>

<?php if( ! $question_data[ 'lesson_complete' ]  ) { ?>

    <input type="file" name="file_upload_<?php echo  $question_data[ 'ID' ] ; ?>" />

    <input type="hidden" name="sensei_question[<?php echo  $question_data[ 'ID' ]; ?>]"
           value="<?php echo esc_attr(  $question_data[ 'user_answer_entry' ] ); ?>" />

    <aside class="max_upload_size"><?php echo  $question_data[ 'max_upload_size' ]; ?></aside>

<?php } ?>
