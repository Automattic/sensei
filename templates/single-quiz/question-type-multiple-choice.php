<?php
/**
 * The Template for displaying Multiple Choice Questions.
 *
 * @author      Automattic
 * @package     Sensei
 * @category    Templates
 * @version     1.12.2
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

<ul class="answers">

<?php
$count = 0;
foreach ( $question_data['answer_options'] as $option ) {

	$count++;

	?>

	<li class="<?php echo esc_attr( $option['option_class'] ); ?>">
		<input type="<?php echo esc_attr( $option['type'] ); ?>"
			   id="<?php echo esc_attr( 'question_' . $question_data['ID'] . '-option-' . $count ); ?>"
			   name="<?php echo esc_attr( 'sensei_question[' . $question_data['ID'] . ']' ); ?>[]"
			   value="<?php echo esc_attr( $option['answer'] ); ?>" <?php echo esc_attr( $option['checked'] ); ?>
				<?php echo is_user_logged_in() ? '' : ' disabled'; ?>
			/>

		<label for="<?php echo esc_attr( 'question_' . $question_data['ID'] . '-option-' . $count ); ?>">

			<?php echo wp_kses_post( apply_filters( 'sensei_answer_text', $option['answer'] ) ); ?>

		</label>

	</li>
<?php } ?>

</ul>



