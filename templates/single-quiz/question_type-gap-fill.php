<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * The Template for displaying Gap Fill Line Questions.
 *
 * Override this template by copying it to yourtheme/sensei/single-quiz/question_type-gap-fill.php
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

<p class="gapfill-answer">

    <span class="gapfill-answer-pre">

        <?php echo apply_filters( 'sensei_answer_text', esc_html( $question_data[ 'gapfill_pre' ] ) ); ?>

        <input type="text" id="<?php echo esc_attr( 'question_' .  $question_data[ 'ID' ]  ); ?>"
               name="<?php echo esc_attr( 'sensei_question[' . $question_data[ 'ID' ] . ']' ); ?>"
               value="<?php echo esc_attr( $question_data[ 'user_answer_entry' ]  ); ?>"
               class="gapfill-answer-gap" />

        <span class="gapfill-answer-post">

            <?php echo apply_filters( 'sensei_answer_text', esc_html(  $question_data[ 'gapfill_post' ]  ) ); ?>

        </span>

</p>
