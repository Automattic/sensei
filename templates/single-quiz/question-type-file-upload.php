<?php
/**
 * The Template for displaying File Upload Questions.
 *
 * Override this template by copying it to yourtheme/sensei/single-quiz/question_type-file-upload.php
 *
 * @author      Automattic
 * @package     Sensei
 * @category    Templates
 * @version     4.17.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

Sensei()->assets->enqueue( 'sensei-file-upload-question-type', 'js/file-upload-question-type.js', [], true );

/**
 * Get the question data with the current quiz id
 * All data is loaded in this array to keep the template clean.
 */
$question_data = Sensei_Question::get_template_data( sensei_get_the_question_id(), get_the_ID() );

$sensei_is_quiz_view_only_mode = $question_data['quiz_is_completed'] || ! Sensei_Quiz::is_quiz_available();
?>

<?php if ( $question_data['question_helptext'] ) { ?>

	<?php
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output escaped in called method (before `the_content` filter).
	echo apply_filters( 'the_content', esc_html( $question_data['question_helptext'] ) );
	?>

<?php } ?>

<?php if ( $question_data['answer_media_url'] && $question_data['answer_media_filename'] ) { ?>

	<p class="wp-block-sensei-lms-question-answers__filename">

		<?php
		printf(
			// translators: Placeholder %1$s is a link to the submitted file.
			esc_html__( 'Submitted file: %1$s', 'sensei-lms' ),
			'<a href="' . esc_url( $question_data['answer_media_url'] )
			. '" target="_blank">'
			. esc_html( $question_data['answer_media_filename'] ) . '</a>'
		);
		?>

	</p>

	<?php if ( $sensei_is_quiz_view_only_mode && getimagesize( $question_data['answer_media_url'] ) ) { ?>
		<img src="<?php echo esc_url( $question_data['answer_media_url'] ); ?>" class="wp-block-sensei-lms-question-answers__preview" />
	<?php } ?>

	<?php if ( ! $question_data['quiz_is_completed'] ) { ?>

		<aside class="reupload_notice"><?php esc_html_e( 'Uploading a new file will replace your existing one:', 'sensei-lms' ); ?></aside>

	<?php } ?>

<?php } ?>

<?php if ( ! $question_data['quiz_is_completed'] ) { ?>

	<label for="file-upload-<?php echo esc_attr( $question_data['ID'] ); ?>" class="wp-block-button is-style-outline sensei-lms-question-block__file-upload">
		<input id="file-upload-<?php echo esc_attr( $question_data['ID'] ); ?>" type="file" class="sensei-lms-question-block__file-input" name="file_upload_<?php echo esc_attr( $question_data['ID'] ); ?>" />
		<span type="button" class="wp-block-button__link wp-element-button is-secondary sensei-course-theme__button sensei-lms-question-block__file-upload-button">
			<?php echo esc_html__( 'Choose File', 'sensei-lms' ); ?>
		</span>
	</label>
	<span class="sensei-lms-question-block__file-upload-name"></span>

	<input type="hidden" name="sensei_question[<?php echo esc_attr( $question_data['ID'] ); ?>]"
		value="<?php echo esc_attr( $question_data['user_answer_entry'] ); ?>" />

	<aside class="max_upload_size"><?php echo esc_html( $question_data['max_upload_size'] ); ?></aside>

<?php } ?>
