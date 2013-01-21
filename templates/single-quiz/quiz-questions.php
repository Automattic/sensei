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

global $post, $woothemes_sensei, $current_user;

// Get Quiz Questions
$lesson_quiz_questions = $woothemes_sensei->frontend->lesson->lesson_quiz_questions( $post->ID );
$grade = 0;

// Setup Action Messages
$messages = '';

// Get User Meta
get_currentuserinfo();

// Get Reset Settings
$reset_quiz_allowed = $woothemes_sensei->settings->settings[ 'quiz_reset_allowed' ];

// Get Answers and Grade
$user_quizzes = unserialize( base64_decode( WooThemes_Sensei_Utils::sensei_get_activity_value( array( 'post_id' => $post->ID, 'user_id' => $current_user->ID, 'type' => 'sensei_quiz_answers', 'field' => 'comment_content' ) ) ) );
$user_quiz_grade =  WooThemes_Sensei_Utils::sensei_get_activity_value( array( 'post_id' => $post->ID, 'user_id' => $current_user->ID, 'type' => 'sensei_quiz_grade', 'field' => 'comment_content' ) );
if ( '' == $user_quiz_grade ) {
	$user_quiz_grade = '';
} // End If Statement

if ( ! is_array($user_quizzes) ) { $user_quizzes = array(); }

// Check if the lesson is complete
$quiz_lesson = absint( get_post_meta( $post->ID, '_quiz_lesson', true ) );
$user_lesson_end =  WooThemes_Sensei_Utils::sensei_get_activity_value( array( 'post_id' => $quiz_lesson, 'user_id' => $current_user->ID, 'type' => 'sensei_lesson_end', 'field' => 'comment_content' ) );
$user_lesson_complete = false;
if ( '' != $user_lesson_end ) {
	$user_lesson_complete = true;
} // End If Statement

// Handle Quiz Completion
if ( isset( $_POST['quiz_complete'] ) && wp_verify_nonce( $_POST[ 'woothemes_sensei_complete_quiz_noonce' ], 'woothemes_sensei_complete_quiz_noonce' ) ) {

    $sanitized_submit = esc_html( $_POST['quiz_complete'] );
    
    if ( ! is_array($user_quizzes) ) {
    	$user_quizzes = array();
    } // End If Statement	
    
    $answers_array = array();
    		
    switch ($sanitized_submit) {
    	case __( 'Complete Quiz', 'woothemes-sensei' ):
    		// Add to quizzes array
    		$correct_answers = 0;
    		for ( $i = 1; $i <= count($lesson_quiz_questions); $i++ ) {
    			$answers_array[ $i ] = $_POST[ 'question_' . $i ];
    			// Grade if number of questions submitted matches question count
    			$question_id = absint( $_POST[ 'question_id_' . $i ] );
    			$right_answer = get_post_meta( $question_id, '_question_right_answer', true );
    			if ( 0 == strcmp( $right_answer, $_POST[ 'question_' . $i ] ) ) {
    				// Answer is correct
    				$correct_answers++;
    			} // End If Statement ;
    		} // End For Loop
    		
    		// Calculate Grade
    		$grade = abs( round( ( doubleval( $correct_answers ) * 100 ) / ( count( $lesson_quiz_questions ) ), 2 ) );
    		
    		// Save Quiz Answers
    		$args = array(
							    'post_id' => $post->ID,
							    'username' => $current_user->user_login,
							    'user_email' => $current_user->user_email,
							    'user_url' => $current_user->user_url,
							    'data' => base64_encode( serialize( $answers_array ) ),
							    'type' => 'sensei_quiz_answers', /* FIELD SIZE 20 */
							    'parent' => 0,
							    'user_id' => $current_user->ID,
							    'action' => 'update'
							);
			
			$activity_logged = WooThemes_Sensei_Utils::sensei_log_activity( $args );
			
			if ( $activity_logged ) {
				// Save Quiz Grade
    			$args = array(
								    'post_id' => $post->ID,
								    'username' => $current_user->user_login,
								    'user_email' => $current_user->user_email,
								    'user_url' => $current_user->user_url,
								    'data' => $grade,
								    'type' => 'sensei_quiz_grade', /* FIELD SIZE 20 */
								    'parent' => 0,
								    'user_id' => $current_user->ID,
								    'action' => 'update'
								);
				$activity_logged = WooThemes_Sensei_Utils::sensei_log_activity( $args );
				// Get Lesson Grading Setting
				if ( $activity_logged && 'passed' == $woothemes_sensei->settings->settings[ 'lesson_completion' ] ) {
					$lesson_prerequisite = abs( round( doubleval( get_post_meta( $post->ID, '_quiz_passmark', true ) ), 2 ) );
					if ( $lesson_prerequisite <= $grade ) {
						// Student has reached the pass mark and lesson is complete
						$args = array(
										    'post_id' => $quiz_lesson,
										    'username' => $current_user->user_login,
										    'user_email' => $current_user->user_email,
										    'user_url' => $current_user->user_url,
										    'data' => 'Lesson completed and passed by the user',
										    'type' => 'sensei_lesson_end', /* FIELD SIZE 20 */
										    'parent' => 0,
										    'user_id' => $current_user->ID
										);
						$activity_logged = WooThemes_Sensei_Utils::sensei_log_activity( $args );
					} // End If Statement
				} elseif ($activity_logged) {
					// Mark lesson as complete
					$args = array(
					    			    'post_id' => $quiz_lesson,
					    			    'username' => $current_user->user_login,
					    			    'user_email' => $current_user->user_email,
					    			    'user_url' => $current_user->user_url,
					    			    'data' => 'Lesson completed by the user',
					    			    'type' => 'sensei_lesson_end', /* FIELD SIZE 20 */
					    			    'parent' => 0,
					    			    'user_id' => $current_user->ID
					    			);
					$activity_logged = WooThemes_Sensei_Utils::sensei_log_activity( $args );
				} // End If Statement
			} else {
				// Something broke
			} // End If Statement
			
			break;
    	case __( 'Save Quiz', 'woothemes-sensei' ):
    		
    		// Add to quizzes array
    		for ( $i = 1; $i <= count($lesson_quiz_questions); $i++ ) {
    			if ( isset( $_POST[ 'question_' . $i ] ) ) {
    				$answers_array[ $i ] = $_POST[ 'question_' . $i ];
    			} // End If Statement
    		} // End For Loop
    		
    		// Save Quiz Answers
    		$args = array(
							    'post_id' => $post->ID,
							    'username' => $current_user->user_login,
							    'user_email' => $current_user->user_email,
							    'user_url' => $current_user->user_url,
							    'data' => base64_encode( serialize( $answers_array ) ),
							    'type' => 'sensei_quiz_answers', /* FIELD SIZE 20 */
							    'parent' => 0,
							    'user_id' => $current_user->ID,
							    'action' => 'update'
							);
			$activity_logged = WooThemes_Sensei_Utils::sensei_log_activity( $args );
			$messages = '<div class="woo-sc-box note">' . __( 'Quiz Saved Successfully.', 'woothemes-sensei' ) . '</div>';
			break;
    	case __( 'Reset Quiz', 'woothemes-sensei' ):
    		// Remove existing user quiz meta
    		$grade = '';
    		$answers_array = array();
    		// Check for quiz grade
    		$delete_grades = WooThemes_Sensei_Utils::sensei_delete_activities( array( 'post_id' => $post->ID, 'user_id' => $current_user->ID, 'type' => 'sensei_quiz_grade' ) );
    		// Check for quiz answers
    		$delete_answers = WooThemes_Sensei_Utils::sensei_delete_activities( array( 'post_id' => $post->ID, 'user_id' => $current_user->ID, 'type' => 'sensei_quiz_answers' ) ); 
    		// Check for lesson complete
    		$delete_lesson_completion = WooThemes_Sensei_Utils::sensei_delete_activities( array( 'post_id' => $quiz_lesson, 'user_id' => $current_user->ID, 'type' => 'sensei_lesson_end' ) ); 
    		// Check for course complete
    		$course_id = get_post_meta( $quiz_lesson, '_lesson_course' ,true );
    		$delete_course_completion = WooThemes_Sensei_Utils::sensei_delete_activities( array( 'post_id' => $course_id, 'user_id' => $current_user->ID, 'type' => 'sensei_course_end' ) ); 
    		$messages = '<div class="woo-sc-box note">' . __( 'Quiz Reset Successfully.', 'woothemes-sensei' ) . '</div>';
    		break;
    	default:
    		// Nothing
    		break;
    	
    } // End Switch Statement
	
	// Get latest quiz answers and grades
	$user_quizzes = unserialize( base64_decode( WooThemes_Sensei_Utils::sensei_get_activity_value( array( 'post_id' => $post->ID, 'user_id' => $current_user->ID, 'type' => 'sensei_quiz_answers', 'field' => 'comment_content' ) ) ) );
    $user_quiz_grade =  WooThemes_Sensei_Utils::sensei_get_activity_value( array( 'post_id' => $post->ID, 'user_id' => $current_user->ID, 'type' => 'sensei_quiz_grade', 'field' => 'comment_content' ) );
	if ( '' == $user_quiz_grade ) {
		$user_quiz_grade = '';
	} // End If Statement
	
	if ( ! is_array($user_quizzes) ) { $user_quizzes = array(); }
	
	// Check again that the lesson is complete
	$quiz_lesson = absint( get_post_meta( $post->ID, '_quiz_lesson', true ) );
	$user_lesson_end =  WooThemes_Sensei_Utils::sensei_get_activity_value( array( 'post_id' => $quiz_lesson, 'user_id' => $current_user->ID, 'type' => 'sensei_lesson_end', 'field' => 'comment_content' ) );
	$user_lesson_complete = false;
	if ( '' != $user_lesson_end ) {
		$user_lesson_complete = true;
	} // End If Statement

} // End If Statement
// Get the meta info
$quiz_passmark = absint( get_post_meta( $post->ID, '_quiz_passmark', true ) ); 
?>
<div class="lesson-meta">    
    <?php if ( 0 < $quiz_passmark && 0 < count( $lesson_quiz_questions ) ) { ?>
    	<p>
           <?php echo $messages; ?>
    	   <?php if ( isset( $user_quiz_grade ) && abs( $user_quiz_grade ) >= 0 && isset( $user_lesson_complete ) && $user_lesson_complete ) {
    			$quiz_passmark_float = (float) $quiz_passmark;
    			if ( $user_quiz_grade >= abs( round( $quiz_passmark_float, 2 ) ) ) { ?>
    				<div class="woo-sc-box tick"><?php echo sprintf( __( 'Congratulations! You have passed this Quiz achieving %d%%', 'woothemes-sensei' ), round( $user_quiz_grade ) ); ?></div>	
    			<?php } else { ?>
    				<div class="woo-sc-box alert"><?php if ( $user_lesson_complete ) { echo sprintf( __( 'You require %1$d%% to pass this Quiz. Your grade is %2$d%%', 'woothemes-sensei' ), round( $quiz_passmark ), round( $user_quiz_grade ) ); } ?></div>
    			<?php } // End If Statement
    		} else { ?>
    			<div class="woo-sc-box info"><?php echo sprintf( __( 'You require %1$d%% to pass this Quiz.', 'woothemes-sensei' ), round( $quiz_passmark ) ); ?></div>
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
    				// Question Meta
    				$question_right_answer = get_post_meta( $question_item->ID, '_question_right_answer', true );
    				$question_wrong_answers = get_post_meta( $question_item->ID, '_question_wrong_answers', true );
    				// Merge right and wrong answers and randomize
    				array_push( $question_wrong_answers, $question_right_answer );
    				shuffle($question_wrong_answers);
    				$question_text = $question_item->post_title;
    				?>
    				<li>
    					<span><?php echo esc_html( stripslashes( $question_text ) ); ?></span>
    					<input type="hidden" name="<?php echo esc_attr( 'question_id_' . $question_count ); ?>" value="<?php echo esc_attr( $question_item->ID ); ?>" />
    				
    				
    				<ul>	
    					<?php $count = 0; ?>
    					<?php foreach( $question_wrong_answers as $question ) {
    						$checked = '';
    						$count++;
    						if ( isset( $user_quizzes[$question_count] ) && ( '' != $user_quizzes[$question_count] ) ) {
    							$checked = checked( $question, $user_quizzes[$question_count], false );
    						} // End If Statement ?>
    						<li><input type="radio" id="<?php echo esc_attr( 'question_' . $question_count ) . '-option-' . $count; ?>" name="<?php echo esc_attr( 'question_' . $question_count ); ?>" value="<?php echo esc_attr( stripslashes( $question ) ); ?>" <?php echo $checked; ?>>&nbsp;<label for="<?php echo esc_attr( 'question_' . $question_count ) . '-option-' . $count; ?>"><?php echo esc_html( stripslashes( $question ) ); ?></label></li>
    					<?php } // End For Loop ?>	
    				</ul>
    				</li>
    				<?php
    				$question_count++;
    			} // End For Loop ?>
    			
    			</ol>
    			
    				<input type="hidden" name="<?php echo esc_attr( 'woothemes_sensei_complete_quiz_noonce' ); ?>" id="<?php echo esc_attr( 'woothemes_sensei_complete_quiz_noonce' ); ?>" value="<?php echo esc_attr( wp_create_nonce( 'woothemes_sensei_complete_quiz_noonce' ) ); ?>" />
				<?php if ( ( isset( $user_lesson_complete ) && !$user_lesson_complete ) ) { ?>
    				<span><input type="submit" name="quiz_complete" class="quiz-submit complete" value="<?php _e( 'Complete Quiz', 'woothemes-sensei' ); ?>"/></span>
    				<span><input type="submit" name="quiz_complete" class="quiz-submit save" value="<?php _e( 'Save Quiz', 'woothemes-sensei' ); ?>"/></span>
    			<?php } // End If Statement ?>
    			<?php if ( isset( $reset_quiz_allowed ) && $reset_quiz_allowed ) { ?>
    				<span><input type="submit" name="quiz_complete" class="quiz-submit reset" value="<?php _e( 'Reset Quiz', 'woothemes-sensei' ); ?>"/></span>
    			<?php } ?>
    	</form>
    <?php } else { ?>
    	<div class="woo-sc-box alert"><?php _e( 'There are no questions for this Quiz yet. Check back soon.', 'woothemes-sensei' ); ?></div>
    <?php } // End If Statement ?>
</div>

<section class="lesson-course">
   	<?php _e( 'Back to ', 'woothemes-sensei' ); ?><a href="<?php echo esc_url( get_permalink( $quiz_lesson ) ); ?>" title="<?php echo esc_attr( __( 'Back to the lesson', 'woothemes-sensei' ) ); ?>"><?php echo get_the_title( $quiz_lesson ); ?></a>
</section>