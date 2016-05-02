<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * The Template for displaying Multiple Choice Questions.
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

<ul class="answers">

<?php
$count = 0;
foreach( $question_data[ 'answer_options' ] as $id => $option ) {

    $count++;

    ?>

    <li class="<?php echo esc_attr( $option[ 'option_class' ] ); ?>">
        <input type="<?php echo $option[ 'type' ]; ?>"
               id="<?php echo esc_attr( 'question_' . $question_data['ID'] ) . '-option-' . $count; ?>"
               name="<?php echo esc_attr( 'sensei_question[' . $question_data['ID'] . ']' ); ?>[]"
               value="<?php echo esc_attr( $option['answer'] ); ?>" <?php echo $option['checked']; ?>
                <?php echo is_user_logged_in() ? '' : ' disabled'; ?>
            />

        <label for="<?php echo esc_attr( 'question_' . $question_data['ID'] ) . '-option-' . $count; ?>">

            <?php echo apply_filters( 'sensei_answer_text', $option['answer'] ); ?>

        </label>

    </li>
<?php } // End For Loop ?>

</ul>



