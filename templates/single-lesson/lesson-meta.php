<?php
/**
 * The Template for displaying all single lesson meta data.
 *
 * Override this template by copying it to yourtheme/sensei/single-lesson/lesson-meta.php
 *
 * @author 		WooThemes
 * @package 	Sensei/Templates
 * @version     1.0.0
 */

global $post, $woothemes_sensei, $current_user;
    
// Get the meta info
$lesson_video_embed = get_post_meta( $post->ID, '_lesson_video_embed', true );
$lesson_course_id = get_post_meta( $post->ID, '_lesson_course', true );
$lesson_prerequisite = get_post_meta( $post->ID, '_lesson_prerequisite', true );

// Get User Meta
get_currentuserinfo();
// Check the lesson is complete
$user_lesson_end =  WooThemes_Sensei_Utils::sensei_get_activity_value( array( 'post_id' => $post->ID, 'user_id' => $current_user->ID, 'type' => 'sensei_lesson_end', 'field' => 'comment_content' ) );
$user_lesson_complete = false;
if ( '' != $user_lesson_end ) {
	$user_lesson_complete = true;
} // End If Statement
// Check for prerequisite lesson completions
$user_prerequisite_lesson_end =  WooThemes_Sensei_Utils::sensei_get_activity_value( array( 'post_id' => $lesson_prerequisite, 'user_id' => $current_user->ID, 'type' => 'sensei_lesson_end', 'field' => 'comment_content' ) );
$user_lesson_prerequisite_complete = false;
if ( '' != $user_prerequisite_lesson_end ) {
	$user_lesson_prerequisite_complete = true;
} // End If Statement

$html = '';
// Check that the course has been started
if ( ! WooThemes_Sensei_Utils::sensei_check_for_activity( array( 'post_id' => $lesson_course_id, 'user_id' => $current_user->ID, 'type' => 'sensei_course_start' ) ) ) { ?>
    <section class="lesson-meta">
    	
    	<header>
    		
    		<a href="<?php echo esc_url( get_permalink( $lesson_course_id->ID ) ); ?>" title="<?php echo esc_attr( __( 'Sign Up', 'woothemes-sensei' ) ); ?>"><?php _e( 'Please Sign Up for the course before starting the lesson.', 'woothemes-sensei' ); ?></a>
    		
    	</header>
    	
    </section>
    
<?php } else {
    
    if ( 'http' == substr( $lesson_video_embed, 0, 4) ) {
        // V2 - make width and height a setting for video embed
        $lesson_video_embed = wp_oembed_get( esc_url( $lesson_video_embed )/*, array( 'width' => 100 , 'height' => 100)*/ );
    } // End If Statement
    ?>
    <section class="lesson-meta">
        
        <div class="video"><?php echo html_entity_decode($lesson_video_embed); ?></div>
        <?php
        // Lesson Quiz Meta
        $lesson_quizzes = $woothemes_sensei->frontend->lesson->lesson_quizzes( $post->ID );
        if ( 0 < count($lesson_quizzes) )  { ?>
        	<header>
            <?php $no_quiz_count = 0; ?>
        	<?php foreach ($lesson_quizzes as $quiz_item){
                // Check quiz grade
        		$user_quiz_grade =  WooThemes_Sensei_Utils::sensei_get_activity_value( array( 'post_id' => $quiz_item->ID, 'user_id' => $current_user->ID, 'type' => 'sensei_quiz_grade', 'field' => 'comment_content' ) );
				if ( '' == $user_quiz_grade ) {
					$user_quiz_grade = '';
				} // End If Statement
        		// Check if Lesson is complete
        	    if ( isset( $user_lesson_complete ) && $user_lesson_complete ) { ?>
        	    	<div class="woo-sc-box tick"><?php echo sprintf( __( 'You have completed this Lesson Quiz with a grade of %d%%', 'woothemes-sensei' ), round( $user_quiz_grade ) ); ?> <a href="<?php echo esc_url( get_permalink( $quiz_item->ID ) ); ?>" title="<?php echo esc_attr( __( 'View the Lesson Quiz', 'woothemes-sensei' ) ); ?>" class="view-quiz"><?php _e( 'View the Lesson Quiz', 'woothemes-sensei' ); ?></a></div>
        	    <?php } else {
                    $question_count = 0;
                    if ( 0 < $quiz_item->ID ) {
                        $question_args = array( 'post_type'         => 'question',
                                                'numberposts'       => -1,
                                                'orderby'           => 'ID',
                                                'order'             => 'ASC',
                                                'meta_key'          => '_quiz_id',
                                                'meta_value'        => $quiz_item->ID,
                                                'post_status'       => 'any',
                                                'suppress_filters'  => 0 
                                            );
                        $questions_array = get_posts( $question_args );
                        $question_count = count( $questions_array );
                    } // End If Statement
                    if ( 0 < $question_count ) {
                        if ( $lesson_prerequisite > 0) {
                            if ( isset( $user_lesson_prerequisite_complete ) && $user_lesson_prerequisite_complete ) { ?>
                                <a class="button" href="<?php echo esc_url( get_permalink( $quiz_item->ID ) ); ?>" title="<?php echo esc_attr( __( 'Take the Lesson Quiz', 'woothemes-sensei' ) ); ?>"><?php _e( 'Take the Lesson Quiz',    'woothemes-sensei' ); ?></a>
                            <?php } else {
                                echo sprintf( __( 'You must first complete %1$s before taking this Lesson\'s Quiz', 'woothemes-sensei' ), '<a href="' . esc_url( get_permalink( $lesson_prerequisite ) ) . '" title="' . esc_attr(  sprintf( __( 'You must first complete: %1$s', 'woothemes-sensei' ), get_the_title( $lesson_prerequisite ) ) ) . '">' . get_the_title( $lesson_prerequisite ). '</a>' );
                            } // End If Statement
                        } else { ?>
                            <a href="<?php echo esc_url( get_permalink( $quiz_item->ID ) ); ?>" title="<?php echo esc_attr( __( 'Take the Lesson Quiz', 'woothemes-sensei' ) ); ?>"><?php _e( 'Take the Lesson Quiz', 'woothemes-sensei'    ); ?></a>
                        <?php } // End If Statement
                    } else {
                        if ( $no_quiz_count == 0 ) { ?><div class="woo-sc-box alert"><?php _e( 'There is no Quiz for this Lesson yet. Check back soon.', 'woothemes-sensei' ); ?></div><?php $no_quiz_count++; }
                    } // End If Statement
        	    } // End If Statement
        	} // End For Loop ?>
        	</header>
        <?php } // End If Statement ?>
    </section>
    
    <section class="lesson-course">
    	<?php _e( 'Back to ', 'woothemes-sensei' ); ?><a href="<?php echo esc_url( get_permalink( $lesson_course_id ) ); ?>" title="<?php echo esc_attr( __( 'Back to the course', 'woothemes-sensei' ) ); ?>"><?php echo get_the_title( $lesson_course_id ); ?></a>
    </section>
<?php } // End If Statement ?>