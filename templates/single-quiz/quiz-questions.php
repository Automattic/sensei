<?php
/**
 * The Template for displaying all Quiz Questions.
 *
 * Override this template by copying it to yourtheme/sensei/single-quiz/quiz-questions.php
 *
 * @author 		WooThemes
 * @package 	Sensei/Templates
 * @version     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

global $post, $woothemes_sensei, $current_user;

// Get User Meta
get_currentuserinfo();

// Handle Quiz Completion
do_action( 'sensei_complete_quiz' );

// Get Frontend data
$user_quizzes = $woothemes_sensei->frontend->data->user_quizzes;
$user_quiz_grade = $woothemes_sensei->frontend->data->user_quiz_grade;
$quiz_lesson = $woothemes_sensei->frontend->data->quiz_lesson;
$user_lesson_end = $woothemes_sensei->frontend->data->user_lesson_end;
$user_lesson_complete = $woothemes_sensei->frontend->data->user_lesson_complete;
$lesson_quiz_questions = $woothemes_sensei->frontend->data->lesson_quiz_questions;

// Get the meta info
$quiz_passmark = absint( get_post_meta( $post->ID, '_quiz_passmark', true ) );
?>
<div class="lesson-meta">
    <?php if ( 0 < $quiz_passmark && 0 < count( $lesson_quiz_questions ) ) { ?>
    	<p>
           <?php do_action( 'sensei_frontend_messages' ); ?>
    	   <?php if ( !is_user_logged_in() ) { ?>
                <div class="woo-sc-box info"><?php echo sprintf( __( 'You must be logged in to take this Quiz.', 'woothemes-sensei' ), round( $quiz_passmark ) ); ?></div>
            <?php } elseif ( isset( $user_quiz_grade ) && abs( $user_quiz_grade ) >= 0 && isset( $user_lesson_complete ) && $user_lesson_complete ) {
    			$quiz_passmark_float = (float) $quiz_passmark;
    			if ( $user_quiz_grade >= abs( round( $quiz_passmark_float, 2 ) ) ) { ?>
    				<div class="woo-sc-box tick"><?php echo sprintf( __( 'Congratulations! You have passed this Quiz achieving %d%%', 'woothemes-sensei' ), round( $user_quiz_grade ) ); ?></div>
    			<?php } else { ?>
    				<div class="woo-sc-box alert"><?php if ( $user_lesson_complete ) { echo sprintf( __( 'You require %1$d%% to pass this Quiz. Your grade is %2$d%%', 'woothemes-sensei' ), round( $quiz_passmark ), round( $user_quiz_grade ) ); } ?></div>
    			<?php } // End If Statement
    		} else {
                $quiz_passmark_float = (float) $quiz_passmark;
                if ( isset( $user_quiz_grade ) &&  '' != $user_quiz_grade && $user_quiz_grade < abs( round( $quiz_passmark_float, 2 ) ) ) { ?>
                    <div class="woo-sc-box alert"><?php echo sprintf( __( 'You require %1$d%% to pass this Quiz. Your grade is %2$d%%', 'woothemes-sensei' ), round( $quiz_passmark ), round( $user_quiz_grade ) ); ?></div>
                <?php } else { ?>
    	           <div class="woo-sc-box info"><?php echo sprintf( __( 'You require %1$d%% to pass this Quiz.', 'woothemes-sensei' ), round( $quiz_passmark ) ); ?></div>
                <?php } ?>
    		<?php } // End If Statement ?>
    	</p>
    <?php } // End If Statement
    // Lesson Quiz Meta
    if ( 0 < count( $lesson_quiz_questions ) )  {
    	$question_count = 1;
    	?>
    	<form method="POST" action="<?php echo esc_url( get_permalink() ); ?>">
    		<ol>
    			<?php foreach ($lesson_quiz_questions as $question_item) {
                    // Setup current Frontend Question
                    $woothemes_sensei->frontend->data->question_item = $question_item;
                    // Question Type
                    $question_type = 'multiple-choice';
                    $question_types_array = wp_get_post_terms( $question_item->ID, 'question-type', array( 'fields' => 'names' ) );
                    if ( isset( $question_types_array[0] ) && '' != $question_types_array[0] ) {
                        $question_type = $question_types_array[0];
                    } // End If Statement

    				do_action( 'sensei_quiz_question_type', $question_type );

                    $question_count++;
    			} // End For Loop ?>

    		</ol>
            <?php do_action( 'sensei_quiz_action_buttons' ); ?>
    	</form>
    <?php } else { ?>
    	<div class="woo-sc-box alert"><?php _e( 'There are no questions for this Quiz yet. Check back soon.', 'woothemes-sensei' ); ?></div>
    <?php } // End If Statement ?>
</div>

<?php do_action( 'sensei_quiz_back_link', $quiz_lesson  ); ?>