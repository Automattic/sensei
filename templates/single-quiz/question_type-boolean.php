<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * The Template for displaying True/False ( Boolean ) Question type.
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
    $boolean_options = array( 'true', 'false' );

?>

<ul class="answers options">

    <?php

    // setup the options the right answer set by the admin/teacher
    // will be compared to.
    $boolean_options = array( true, false );

    //loop through the 2 boolean options and compare them with
    // the selected right answer
    foreach ( $boolean_options as $option ){

        $answer_class = '';

        // Add classes to indicate correctness, only if there is a grade
        if( isset( $question_data[ 'user_correct' ] ) && 0 < $question_data['question_grade'] ) {

            if( $question_right_answer == $question_data[ 'question_right_answer' ] ) {

                if( $question_data[ 'user_correct' ] ) {

                    $answer_class = 'user_right';

                }

                $answer_class .= ' right_answer';

            } else {

                if( ! $question_data[ 'user_correct' ] ) {

                    $answer_class = 'user_wrong';

                }

            } // end if right answer == current booloean options

        }// end if $user_correct .. $question_grade

	    $option_value = $option ? 'true' : 'false';

    ?>

    <li class="<?php echo esc_attr( $answer_class ); ?>">

        <input type="radio"
               id="<?php echo esc_attr( 'question_' . $question_data[ 'ID' ]  ) . '-option-'. $option_value; ?>"
               name="<?php echo esc_attr( 'sensei_question[' . $question_data[ 'ID' ]  . ']' ); ?>"
               value="<?php echo $option_value; ?>"
            <?php echo checked( $question_data[ 'user_answer_entry' ], $option_value, false ); ?>
            <?php if ( !is_user_logged_in() ) { echo ' disabled'; } ?>
	    />
        <label for="<?php echo esc_attr( 'question_' . $question_data[ 'ID' ]  ) . '-option-' . $option_value; ?>">
            <?php

            if( 'true' == $option ){

                _e( 'True', 'woothemes-sensei' );

            }else{

                _e( 'False', 'woothemes-sensei' );

            }

            ?>


        </label>

    </li>

    <?php } ?>

</ul>
