<?php
/**
 * The Template for outputting Lesson Archive items
 *
 * Override this template by copying it to yourtheme/sensei/loop-lesson.php
 *
 * @author 		WooThemes
 * @package 	Sensei/Templates
 * @version     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

global $woothemes_sensei, $post, $wp_query, $current_user;

wp_get_current_user();
$lesson_count = 1;
?>

    <?php if ( have_posts() ) { ?>
		<section id="main-course" class="course-container">
            <section class="module-lessons">

        	    <?php do_action( 'sensei_lesson_archive_header' ); ?>

        	    <?php while ( have_posts() ) { the_post();
        			// Meta data
        			$post_id = get_the_ID(); ?>

                    <?php
                    $single_lesson_complete = false;
                    $user_lesson_end = '';
                    if ( is_user_logged_in() ) {
                        // Check if Lesson is complete
                        $user_lesson_end =  WooThemes_Sensei_Utils::sensei_get_activity_value( array( 'post_id' => $post_id, 'user_id' => $current_user->ID, 'type' => 'sensei_lesson_end', 'field' => 'comment_content' ) );
                        if ( '' != $user_lesson_end ) {
                            //Check for Passed or Completed Setting
                            $course_completion = $woothemes_sensei->settings->settings[ 'course_completion' ];
                            if ( 'passed' == $course_completion ) {
                                // If Setting is Passed -> Check for Quiz Grades
                                $lesson_quizzes = $woothemes_sensei->post_types->lesson->lesson_quizzes( $post_id );
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
                                        $single_lesson_complete = true;
                                    } // End If Statement
                                } // End If Statement
                            } else {
                                $single_lesson_complete = true;
                            } // End If Statement;
                        } // End If Statement
                    } // End If Statement
                    // Get Lesson data
                    $complexity_array = $woothemes_sensei->frontend->lesson->lesson_complexities();
                    $lesson_length = get_post_meta( $post_id, '_lesson_length', true );
                    $lesson_complexity = get_post_meta( $post_id, '_lesson_complexity', true );
                    if ( '' != $lesson_complexity ) { $lesson_complexity = $complexity_array[$lesson_complexity]; }
                    $user_info = get_userdata( absint( get_the_author_meta( 'ID' ) ) );

                    $html = '<article class="' . esc_attr( join( ' ', get_post_class( array( 'lesson', 'course', 'post' ), $post_id ) ) ) . '">';

                        $html .= '<header>';

                            $html .= '<h2><a href="' . esc_url( get_permalink( $post_id ) ) . '" title="' . esc_attr( sprintf( __( 'Start %s', 'woothemes-sensei' ), get_the_title() ) ) . '">';

                            if( apply_filters( 'sensei_show_lesson_numbers', false ) ) {
                                $html .= '<span class="lesson-number">' . $lesson_count . '. </span>';
                            }

                            $html .= esc_html( sprintf( __( '%s', 'woothemes-sensei' ), get_the_title() ) ) . '</a></h2>';

                            $html .= '<p class="lesson-meta">';

                                if ( '' != $lesson_length ) { $html .= '<span class="lesson-length">' . apply_filters( 'sensei_length_text', __( 'Length: ', 'woothemes-sensei' ) ) . $lesson_length . __( ' minutes', 'woothemes-sensei' ) . '</span>'; }
                                if ( isset( $woothemes_sensei->settings->settings[ 'lesson_author' ] ) && ( $woothemes_sensei->settings->settings[ 'lesson_author' ] ) ) {
                                    $html .= '<span class="lesson-author">' . apply_filters( 'sensei_author_text', __( 'Author: ', 'woothemes-sensei' ) ) . '<a href="' . get_author_posts_url( absint( get_the_author_meta( 'ID' ) ) ) . '" title="' . esc_attr( $user_info->display_name ) . '">' . esc_html( $user_info->display_name ) . '</a></span>';
                                } // End If Statement
                                if ( '' != $lesson_complexity ) { $html .= '<span class="lesson-complexity">' . apply_filters( 'sensei_complexity_text', __( 'Complexity: ', 'woothemes-sensei' ) ) . $lesson_complexity .'</span>'; }
                                if ( '' != $user_lesson_end && $single_lesson_complete ) {
                                    $html .= '<span class="lesson-status complete">' . apply_filters( 'sensei_complete_text', __( 'Complete', 'woothemes-sensei' ) ) .'</span>';
                                } else {
                                    // Get Lesson Status
                                    $lesson_quizzes = $woothemes_sensei->frontend->lesson->lesson_quizzes( $post_id );
                                    if ( 0 < count($lesson_quizzes) )  {
                                        // Check if user has started the lesson and has saved answers
                                        $user_lesson_start =  WooThemes_Sensei_Utils::sensei_get_activity_value( array( 'post_id' => $post_id, 'user_id' => $current_user->ID, 'type' => 'sensei_lesson_start', 'field' => 'comment_date' ) );
                                        if ( '' != $user_lesson_start ) {
                                            $html .= '<span class="lesson-status in-progress">' . apply_filters( 'sensei_in_progress_text', __( 'In Progress', 'woothemes-sensei' ) ) .'</span>';
                                        } // End If Statement
                                    } // End If Statement
                                }

                            $html .= '</p>';

                        $html .= '</header>';

                        // Image
                        $html .=  $woothemes_sensei->post_types->lesson->lesson_image( $post_id );

                        $html .= '<section class="entry">';

                            $html .= Woothemes_Sensei_Lesson::lesson_excerpt( $post );

                        $html .= '</section>';

                    $html .= '</article>';

                    echo $html;

                    ?>

        		<?php } // End While Loop ?>

        	</section>
        </section>
    <?php } ?>