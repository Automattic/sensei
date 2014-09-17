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
$reset_allowed = $woothemes_sensei->frontend->data->reset_quiz_allowed;
$max_attempts = $woothemes_sensei->frontend->data->reset_quiz_attempts;

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
    if ( 0 < count( $lesson_quiz_questions ) ) {
    	$question_count = 1;

		//Check to see if this quiz requires the user to enter a password. if so show the form
		if( imperial_sensei_quiz_password_required( $post ) ) {
			echo imperial_sensei_get_the_password_form( $post );
		}
		else {
			// Limit the maximum number of attempts allowed
			if( $reset_allowed && 1 <= $max_attempts ) {
				$attempts_used = $woothemes_sensei->frontend->data->reset_quiz_attempts_used;
				$attempts_remaining = $max_attempts - $attempts_used;
				if ( $attempts_remaining < 0 ) {
					$attempts_remaining = 0;
				}
				if( !$attempts_remaining ) { ?>
					<div class="sensei-message alert"><?php _e( 'You have no more attempts left.', 'imperial' ); ?></div>
				<?php 
				} else { ?>
					<div class="sensei-message info"><?php printf( __( 'You have have %s attempt(s) remaining.', 'imperial' ), $attempts_remaining ); ?></div>
				<?php } 
			} // quiz attempt constraint 

			do_action( 'sensei_quiz_header' );

			$quiz_time_limit = $woothemes_sensei->frontend->data->quiz_time_limit;
			// If we have a time constraint lets get the time data we will need
			if ( !empty( $quiz_time_limit ) && 1 <= $quiz_time_limit ) {
				$quiz_start_time = $woothemes_sensei->frontend->data->quiz_start_time;
				$quiz_end_time = $quiz_start_time + $quiz_time_limit;
				$now = current_time('timestamp', 1); // Using GMT timezone (BAD!)
				$mins_warning = apply_filters( 'sensei_quiz_time_limit_warning', 2 );
//				$time_remaining = 0; // Set later

				// Has the deadline already passed?
				$deadline_class = ( $quiz_end_time <= $now ) ? '' : $deadline_class = 'hide';
				// Are we within the x minute warning?
				$warning_class = ( $quiz_end_time > $now && $quiz_end_time < $now + ( $mins_warning * 60 ) ) ? '' : 'hide';
				$warning_message = _n_noop('Warning, you have %s minute left.', 'Warning, you have %s minutes left.', 'imperial');
				?>
				<div id="sticky-anchor"></div>
				<div id="quiz-time-deadline-passed" class="sensei-message alert sticky <?php echo $deadline_class; ?>">
					<?php _e( 'The submission deadline has passed.', 'imperial' ); ?>
				</div>
				<div id="quiz-time-warning" class="sensei-message alert sticky <?php echo $warning_class; ?> ">
					<?php printf( translate_nooped_plural( $warning_message, $mins_warning), $mins_warning ); ?>
				</div>
				<?php
				// Still have time left
				if ( $quiz_end_time > $now ) {
//					$time_remaining = $quiz_end_time - $quiz_start_time;
				?>
				<div class="sensei-message icon icon-activity" id="quiz-time-remaining">
					<?php printf( __( 'Time Remaining: %s ','imperial' ), human_time_diff( $now, $quiz_end_time ) ); ?>
				</div>
				<?php }
			} // Quiz time limits

			?>
			<form id="quiz-form" method="POST" action="<?php echo esc_url( get_permalink() ); ?>" enctype="multipart/form-data">
				<?php if ( !empty( $quiz_time_limit ) && $quiz_end_time ) { ?>
				<input type="hidden" name="quiz_end_time" value="<?php echo $quiz_end_time; ?>" id="quiz_end_time" />
				<?php } ?>
				<ol id="sensei-quiz-list">
					<?php foreach ($lesson_quiz_questions as $question_item) {

						// Setup current Frontend Question
						$woothemes_sensei->frontend->data->question_item = $question_item;
						$woothemes_sensei->frontend->data->question_count = $question_count;
						// Question Type
						$question_type = 'choice';
						$question_types_array = wp_get_post_terms( $question_item->ID, 'question-type', array( 'fields' => 'names' ) );
						if ( isset( $question_types_array[0] ) && '' != $question_types_array[0] ) {
							$question_type = $question_types_array[0];
						} // End If Statement

						echo '<input type="hidden" name="questions_asked[]" value="' . $question_item->ID . '" />';

						do_action( 'sensei_quiz_question_type', $question_type );

						$question_count++;

					} // End For Loop ?>

				</ol>
				<div id="quiz-form-buttons">
				<?php do_action( 'sensei_quiz_action_buttons' ); ?>
				</div>
			</form>
			<?php 
			} // Password check
		} 
		else { ?>
		<div class="sensei-message alert"><?php _e( 'There are no questions for this Quiz yet. Check back soon.', 'woothemes-sensei' ); ?></div>
	<?php } // End If Statement ?>
</div>

<?php do_action( 'sensei_quiz_back_link', $quiz_lesson  ); ?>