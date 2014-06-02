<?php
/**
 * The Template for displaying all single course meta information.
 *
 * Override this template by copying it to yourtheme/sensei/single-course/course-lessons.php
 *
 * @author 		WooThemes
 * @package 	Sensei/Templates
 * @version     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

global $post, $woothemes_sensei, $current_user;

$html = '';
// Get Course Lessons
$lessons_completed = 0;
$course_lessons = $woothemes_sensei->frontend->course->course_lessons( $post->ID );
$total_lessons = count( $course_lessons );
// Check if the user is taking the course
$is_user_taking_course = WooThemes_Sensei_Utils::sensei_check_for_activity( array( 'post_id' => $post->ID, 'user_id' => $current_user->ID, 'type' => 'sensei_course_start' ) );

// Get User Meta
get_currentuserinfo();

if ( 0 < $total_lessons ) {

    $html .= '<section class="course-lessons">';

    	$html .= '<header>';
    	  $html .= '<h2>' . __( 'Lessons', 'woothemes-sensei' ) . '</h2>';
    	  if ( is_user_logged_in() && $is_user_taking_course ) {

    	  		$html .= '<span class="course-completion-rate">' . sprintf( __( 'Currently completed %1$s of %2$s in total', 'woothemes-sensei' ), '######', $total_lessons ) . '</span>';
    	  		$html .= '<div class="meter+++++"><span style="width: @@@@@%">@@@@@%</span></div>';

    	  } // End If Statement
    	$html .= '</header>';

    	$lesson_count = 1;
    	$lessons_completed = 0;
        $show_lesson_numbers = false;
        $post_classes = array( 'course', 'post' );
    	foreach ( $course_lessons as $lesson_item ){
            $single_lesson_complete = false;
            $user_lesson_end = '';
    	    if ( is_user_logged_in() ) {
    	    	// Check if Lesson is complete
    	    	$user_lesson_end =  WooThemes_Sensei_Utils::sensei_get_activity_value( array( 'post_id' => $lesson_item->ID, 'user_id' => $current_user->ID, 'type' => 'sensei_lesson_end', 'field' => 'comment_content' ) );
				if ( '' != $user_lesson_end ) {
					//Check for Passed or Completed Setting
                    $course_completion = $woothemes_sensei->settings->settings[ 'course_completion' ];
                    if ( 'passed' == $course_completion ) {
                        // If Setting is Passed -> Check for Quiz Grades
                        $lesson_quizzes = $woothemes_sensei->post_types->lesson->lesson_quizzes( $lesson_item->ID );
                        // Get Quiz ID
                        if ( is_array( $lesson_quizzes ) || is_object( $lesson_quizzes ) ) {
                            foreach ($lesson_quizzes as $quiz_item) {
                                $lesson_quiz_id = $quiz_item->ID;
                            } // End For Loop
                            // Quiz Grade
                            $lesson_grade =  WooThemes_Sensei_Utils::sensei_get_activity_value( array( 'post_id' => $lesson_quiz_id, 'user_id' => $current_user->ID, 'type' => 'sensei_quiz_grade', 'field' => 'comment_content' ) ); // Check for wrapper
                            // Check if Grade is bigger than pass percentage
                            $lesson_prerequisite = abs( round( doubleval( get_post_meta( $lesson_quiz_id, '_quiz_passmark', true ) ), 2 ) );
                            if ( $lesson_prerequisite <= intval( $lesson_grade ) ) {
                                $lessons_completed++;
                                $single_lesson_complete = true;
                                $post_classes[] = 'lesson-completed';
                            } // End If Statement
                        } // End If Statement
                    } else {
                        $lessons_completed++;
                        $single_lesson_complete = true;
                        $post_classes[] = 'lesson-completed';
                    } // End If Statement;
				} // End If Statement
			} // End If Statement
    	    // Get Lesson data
    	    $complexity_array = $woothemes_sensei->frontend->lesson->lesson_complexities();
    	    $lesson_length = get_post_meta( $lesson_item->ID, '_lesson_length', true );
    	    $lesson_complexity = get_post_meta( $lesson_item->ID, '_lesson_complexity', true );
    	    if ( '' != $lesson_complexity ) { $lesson_complexity = $complexity_array[$lesson_complexity]; }
    	    $user_info = get_userdata( absint( $lesson_item->post_author ) );
            $is_preview = WooThemes_Sensei_Utils::is_preview_lesson( $lesson_item->ID );
            $preview_label = '';
            if ( $is_preview && !$is_user_taking_course ) {
                $preview_label = $woothemes_sensei->frontend->sensei_lesson_preview_title_text( $post->ID );
                $preview_label = '<span class="preview-heading">' . $preview_label . '</span>';
                $post_classes[] = 'lesson-preview';
            }

    	    $html .= '<article class="' . esc_attr( join( ' ', get_post_class( $post_classes, $lesson_item->ID ) ) ) . '">';

    			$html .= '<header>';

    	    		$html .= '<h2><a href="' . esc_url( get_permalink( $lesson_item->ID ) ) . '" title="' . esc_attr( sprintf( __( 'Start %s', 'woothemes-sensei' ), $lesson_item->post_title ) ) . '">';

                    if( apply_filters( 'sensei_show_lesson_numbers', $show_lesson_numbers ) ) {
                        $html .= '<span class="lesson-number">' . $lesson_count . '. </span>';
                    }

                    $html .= esc_html( sprintf( __( '%s', 'woothemes-sensei' ), $lesson_item->post_title ) ) . $preview_label . '</a></h2>';

    	    		$html .= '<p class="lesson-meta">';

    	   		 		if ( '' != $lesson_length ) { $html .= '<span class="lesson-length">' . apply_filters( 'sensei_length_text', __( 'Length: ', 'woothemes-sensei' ) ) . $lesson_length . __( ' minutes', 'woothemes-sensei' ) . '</span>'; }
    	   		 		if ( isset( $woothemes_sensei->settings->settings[ 'lesson_author' ] ) && ( $woothemes_sensei->settings->settings[ 'lesson_author' ] ) ) {
    	   		 			$html .= '<span class="lesson-author">' . apply_filters( 'sensei_author_text', __( 'Author: ', 'woothemes-sensei' ) ) . '<a href="' . get_author_posts_url( absint( $lesson_item->post_author ) ) . '" title="' . esc_attr( $user_info->display_name ) . '">' . esc_html( $user_info->display_name ) . '</a></span>';
    	   		 		} // End If Statement
    	   		 		if ( '' != $lesson_complexity ) { $html .= '<span class="lesson-complexity">' . apply_filters( 'sensei_complexity_text', __( 'Complexity: ', 'woothemes-sensei' ) ) . $lesson_complexity .'</span>'; }
    	   		 	    if ( '' != $user_lesson_end && $single_lesson_complete ) {
                            $html .= '<span class="lesson-status complete">' . apply_filters( 'sensei_complete_text', __( 'Complete', 'woothemes-sensei' ) ) .'</span>';
                        } else {
                            // Get Lesson Status
                            $lesson_quizzes = $woothemes_sensei->frontend->lesson->lesson_quizzes( $lesson_item->ID );
                            if ( 0 < count($lesson_quizzes) )  {
                                // Check if user has started the lesson and has saved answers
                                $user_lesson_start =  WooThemes_Sensei_Utils::sensei_get_activity_value( array( 'post_id' => $lesson_item->ID, 'user_id' => $current_user->ID, 'type' => 'sensei_lesson_start', 'field' => 'comment_date' ) );
                                if ( '' != $user_lesson_start ) {
                                    $html .= '<span class="lesson-status in-progress">' . apply_filters( 'sensei_in_progress_text', __( 'In Progress', 'woothemes-sensei' ) ) .'</span>';
                                } // End If Statement
                            } // End If Statement
                        }

    	   		 	$html .= '</p>';

    			$html .= '</header>';

    			// Image
    			$html .=  $woothemes_sensei->post_types->lesson->lesson_image( $lesson_item->ID );

    			$html .= '<section class="entry">';

                    $html .= WooThemes_Sensei_Lesson::lesson_excerpt( $lesson_item );

    	   		$html .= '</section>';

    	    $html .= '</article>';

    	    $lesson_count++;

    	} // End For Loop

    	if ( is_user_logged_in() && $is_user_taking_course ) {
    		// Add dynamic data to the output
    		$html = str_replace( '######', $lessons_completed, $html );
    		$progress_percentage = abs( round( ( doubleval( $lessons_completed ) * 100 ) / ( $total_lessons ), 0 ) );
    		/* if ( 0 == $progress_percentage ) { $progress_percentage = 5; } */
    		$html = str_replace( '@@@@@', $progress_percentage, $html );
    		if ( 50 < $progress_percentage ) { $class = ' green'; } elseif ( 25 <= $progress_percentage && 50 >= $progress_percentage ) { $class = ' orange'; } else { $class = ' red'; }
    		$html = str_replace( '+++++', $class, $html );
    	} // End If Statement

    $html .= '</section>';

} // End If Statement
// Output the HTML
echo $html; ?>