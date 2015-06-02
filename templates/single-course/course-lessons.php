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
$course_lessons = Sensei()->course->course_lessons( $post->ID );
$total_lessons = count( $course_lessons );

// Check if the user is taking the course
$is_user_taking_course = WooThemes_Sensei_Utils::user_started_course( $post->ID, $current_user->ID );

// Get User Meta
get_currentuserinfo();

// exit if no lessons exist
if (  ! ( $total_lessons  > 0 ) ) {
    return;
}
$html .= '<section class="course-lessons">';
$html .= '<header>';
$html .= '<h2>' . apply_filters( 'sensei_lessons_text', __( 'Lessons', 'woothemes-sensei' ) ) . '</h2>';
$html .= '</header>';

$lesson_count = 1;

$lessons_completed = count( Sensei()->course->get_completed_lesson_ids( $post->ID, $current_user->ID ));
$show_lesson_numbers = false;

foreach ( $course_lessons as $lesson_item ){

    //skip lesson that are already in the modules
    if( false != Sensei()->modules->get_lesson_module( $lesson_item->ID ) ){
        continue;
    }

    $single_lesson_complete = false;
    $post_classes = array( 'course', 'post' );
    $user_lesson_status = false;
    if ( is_user_logged_in() ) {
        // Check if Lesson is complete
        $single_lesson_complete = WooThemes_Sensei_Utils::user_completed_lesson( $lesson_item->ID, $current_user->ID );
        if ( $single_lesson_complete ) {
            $post_classes[] = 'lesson-completed';
        } // End If Statement
    } // End If Statement

    // Get Lesson data
    $complexity_array = $woothemes_sensei->post_types->lesson->lesson_complexities();
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

                if ( $single_lesson_complete ) {
                    $html .= '<span class="lesson-status complete">' . apply_filters( 'sensei_complete_text', __( 'Complete', 'woothemes-sensei' ) ) .'</span>';
                }
                elseif ( $user_lesson_status ) {
                    $html .= '<span class="lesson-status in-progress">' . apply_filters( 'sensei_in_progress_text', __( 'In Progress', 'woothemes-sensei' ) ) .'</span>';
                } // End If Statement

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

$html .= '</section>';

// Output the HTML
echo $html;
