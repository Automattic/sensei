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
$quiz_grade_type = $woothemes_sensei->frontend->data->quiz_grade_type;
$user_lesson_end = $woothemes_sensei->frontend->data->user_lesson_end;
$user_lesson_complete = $woothemes_sensei->frontend->data->user_lesson_complete;
$lesson_quiz_questions = $woothemes_sensei->frontend->data->lesson_quiz_questions;

// Check if the user has started the course
$lesson_course_id = absint( get_post_meta( $quiz_lesson, '_lesson_course', true ) );
$has_user_start_the_course = sensei_has_user_started_course( $lesson_course_id, $current_user->ID );

// Get the meta info
$quiz_passmark = absint( get_post_meta( $post->ID, '_quiz_passmark', true ) );
$quiz_passmark_float = (float) $quiz_passmark;
?>
<div class="lesson-meta">
    <?php

    // Display user's quiz status
    $status = WooThemes_Sensei_Utils::sensei_user_quiz_status_message( $quiz_lesson, $current_user->ID );
    echo '<div class="sensei-message ' . $status['box_class'] . '">' . $status['message'] . '</div>';

    // Lesson Quiz Meta
    if ( 0 < count( $lesson_quiz_questions ) )  {
    	$question_count = 1;
    	?>
    	<form method="POST" action="<?php echo esc_url( get_permalink() ); ?>" enctype="multipart/form-data">
    		<ol id="sensei-quiz-list">
    			<?php foreach ($lesson_quiz_questions as $question_item) {

                    // Setup current Frontend Question
                    $woothemes_sensei->frontend->data->question_item = $question_item;
                    $woothemes_sensei->frontend->data->question_count = $question_count;
                    // Question Type
                    $question_type = 'multiple-choice';
                    $question_types_array = wp_get_post_terms( $question_item->ID, 'question-type', array( 'fields' => 'names' ) );
                    if ( isset( $question_types_array[0] ) && '' != $question_types_array[0] ) {
                        $question_type = $question_types_array[0];
                    } // End If Statement

                    echo '<input type="hidden" name="questions_asked[]" value="' . $question_item->ID . '" />';

    				do_action( 'sensei_quiz_question_type', $question_type );

                    $question_count++;

    			} // End For Loop ?>

    		</ol>
            <?php do_action( 'sensei_quiz_action_buttons' ); ?>
    	</form>
    <?php } else { ?>
    	<div class="sensei-message alert"><?php _e( 'There are no questions for this Quiz yet. Check back soon.', 'woothemes-sensei' ); ?></div>
    <?php } // End If Statement ?>
</div>

<?php do_action( 'sensei_quiz_back_link', $quiz_lesson  ); ?>