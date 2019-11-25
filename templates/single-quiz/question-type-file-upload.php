<?php
/**
 * The Template for displaying File Upload Questions.
 *
 * Override this template by copying it to yourtheme/sensei/single-quiz/question_type-file-upload.php
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

?>

<?php if ( $question_data['question_helptext'] ) { ?>

	<?php
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output escaped in called method (before `the_content` filter).
	echo apply_filters( 'the_content', esc_html( $question_data['question_helptext'] ) );
	?>

<?php } ?>

<?php if ( $question_data['answer_media_url'] && $question_data['answer_media_filename'] ) { ?>

	<p class="submitted_file">

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
	<?php if ( ! $question_data['lesson_complete'] ) { ?>

		<aside class="reupload_notice"><?php esc_html_e( 'Uploading a new file will replace your existing one:', 'sensei-lms' ); ?></aside>

	<?php } ?>

<?php } ?>

<?php if ( ! $question_data['lesson_complete'] ) { ?>

	<input type="file" name="file_upload_<?php echo esc_attr( $question_data['ID'] ); ?>" />

	<input type="hidden" name="sensei_question[<?php echo esc_attr( $question_data['ID'] ); ?>]"
		   value="<?php echo esc_attr( $question_data['user_answer_entry'] ); ?>" />

	<aside class="max_upload_size"><?php echo esc_html( $question_data['max_upload_size'] ); ?></aside>

<?php } ?>
