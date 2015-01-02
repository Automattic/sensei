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

$date_format = 'j F Y \a\t H:i';
$timezone_now = current_time( 'timestamp' ); // Adjusts to timezone

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
$max_attempts = !empty( $woothemes_sensei->frontend->data->reset_quiz_attempts ) ? $woothemes_sensei->frontend->data->reset_quiz_attempts : 0;

// Check if the user has started the course
$lesson_course_id = absint( get_post_meta( $quiz_lesson, '_lesson_course', true ) );
$has_user_start_the_course = WooThemes_Sensei_Utils::user_started_course( $lesson_course_id, $current_user->ID );

// Get the meta info
$quiz_passmark = absint( get_post_meta( $post->ID, '_quiz_passmark', true ) );
$quiz_passmark_float = (float) $quiz_passmark;

$lesson_start_date = get_post_meta( $quiz_lesson, '_lesson_start_date', true );
$lesson_close_date = get_post_meta( $quiz_lesson, '_lesson_close_date', true );
// $lesson_block_completion is auto checked and correctly updated upon loading of a Lesson or Quiz
$lesson_block_completion = ( 'on' == get_post_meta( $quiz_lesson, '_lesson_block_completion', true ) ) ? true : false;
?>
<div class="lesson-meta">
	<?php

	// Quiz hasn't opened yet, so block all content from showing
	if ( !empty($lesson_start_date) && $lesson_start_date > $timezone_now ) {
		echo '<div class="sensei-message info">';
		printf( __( 'This activity will be accessible from %s', 'imperial' ), date($date_format, $lesson_start_date) );
		echo '</div>';
	}

	// Quiz content now visible
	else {
		// Warn users that the content has expired
		if ( empty($lesson_close_date) && $lesson_block_completion ){
			echo '<div class="sensei-message info">';
			_e( 'This activity has been closed. You can still view the content, but you will not be able to complete the activity/quiz.', 'imperial' );
			echo '</div>';
		}
		// Warn users that the content expired automatically
		elseif ( $lesson_close_date && $lesson_block_completion ) {
			echo '<div class="sensei-message info">';
			printf( __( 'This activity closed on %s. You can still view the content, but you will not be able to complete the activity/quiz.', 'imperial' ), date($date_format, $lesson_close_date) );
			echo '</div>';
		}
		// Warn users that the content will expire automatically
		elseif ( $lesson_close_date && !$lesson_block_completion ) {
			echo '<div class="sensei-message alert">';
			printf( __( 'This activity will close on %s. You will still be able to view the content after this time, but you will not be able to complete the activity/quiz.', 'imperial' ), date($date_format, $lesson_close_date) );
			echo '</div>';
		}

		// Display user's quiz status
		$status = WooThemes_Sensei_Utils::sensei_user_quiz_status_message( $quiz_lesson, $current_user->ID );
		if ( !empty($status) ) {
			echo '<div class="sensei-message ' . $status['box_class'] . '">' . $status['message'] . '</div>';
		}
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

				// As long as the completion isn't blocked, and if we have a time constraint lets get the time data we will need
				if ( !$lesson_block_completion && !empty( $woothemes_sensei->frontend->data->quiz_time_limit ) && 1 <= $woothemes_sensei->frontend->data->quiz_time_limit ) {
					$quiz_time_limit = $woothemes_sensei->frontend->data->quiz_time_limit;
					$quiz_start_time = $woothemes_sensei->frontend->data->quiz_start_time;
					$quiz_end_time = $quiz_start_time + $quiz_time_limit;
					$now = current_time('timestamp', 1); // Using GMT timezone (BAD!)
					$mins_warning = apply_filters( 'sensei_quiz_time_limit_warning', 2 );
	//				$time_remaining = 0; // Set later

					// Has the deadline already passed?
					$deadline_class = ( $quiz_end_time <= $now ) ? '' : $deadline_class = 'hide';
					// Are we within the x minute warning?
					$warning_class = ( $quiz_end_time > $now && $quiz_end_time < $now + ( $mins_warning * 60 ) ) ? '' : 'hide';
					$warning_message = _n_noop('Warning, you have <span class="time">%s minute</span> left.', 'Warning, you have <span class="time">%s minutes</span> left.', 'imperial');
					?>
					<div id="sticky-anchor"></div>
					<div id="quiz-time-deadline-passed" class="sensei-message alert sticky <?php echo $deadline_class; ?>">
						<?php _e( 'The submission deadline has passed.', 'imperial' ); ?>
						<?php _e( 'Further changes have been disabled, please submit your answers by selecting the "Complete Quiz" button.', 'imperial' ); ?>
					</div>
					<div id="quiz-time-warning" class="sensei-message alert sticky <?php echo $warning_class; ?> ">
						<?php printf( translate_nooped_plural( $warning_message, $mins_warning), $mins_warning ); ?>
						<?php _e( 'Please ensure you finish all the questions <strong>before</strong> the time limit expires.', 'imperial' ); ?>
					</div>
					<?php
					// Still have time left
					if ( $quiz_end_time > $now ) {
	//					$time_remaining = $quiz_end_time - $quiz_start_time;
					?>
					<div class="sensei-message icon icon-activity" id="quiz-time-remaining">
						<?php printf( __( 'Time Remaining: <span class="time">%s</span> ','imperial' ), human_time_diff( $now, $quiz_end_time ) ); ?>
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
					<?php 
					// If not blocked, show the submission buttons
					if ( !$lesson_block_completion ) {
						do_action( 'sensei_quiz_action_buttons' ); 
					}
					?>
					</div>
				</form>
<script>
( function( $ ) {
	// Change the background colour of incorrect Latex images to match the highlight colour
	$('#sensei-quiz-list li.user_wrong .latex').each( function() {
		var ls = $(this).attr('src').replace('bg=ffffff', 'bg=ffd9c8');
		$(this).attr('src', ls);
	});
	// Change the 'text' colour of correct Latex images to match the text of other answers
	$('#sensei-quiz-list li.right_answer .latex').each( function() {
		var ls = $(this).attr('src').replace('fg=000', 'fg=008000');
		$(this).attr('src', ls);
	});

} )( jQuery );
</script>
				<?php 
				} // Password check
			} 
			else { ?>
			<div class="sensei-message alert"><?php _e( 'There are no questions for this Quiz yet. Check back soon.', 'woothemes-sensei' ); ?></div>
		<?php } // End If Statement ?>
	<?php } // End If content visible check ?>
</div>

<?php do_action( 'sensei_quiz_back_link', $quiz_lesson  ); ?>