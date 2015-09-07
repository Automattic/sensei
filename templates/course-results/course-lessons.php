<?php
/**
 * The Template for displaying all course lessons on the course results page.
 *
 * Override this template by copying it to yourtheme/sensei/course-results/course-lessons.php
 *
 * @author 		WooThemes
 * @package 	Sensei/Templates
 * @version     1.6.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

global $course, $woothemes_sensei, $current_user;

// Get User Meta
get_currentuserinfo();

if ( is_user_logged_in() ) {

	// WooThemes_Sensei_Utils::sensei_course_user_grade() loops through every Lesson to find it's grade and total for the Course
	// but then we re-loop every lesson below and do the same again, REFACTOR!
	$course_user_grade = WooThemes_Sensei_Utils::sensei_course_user_grade( $course->ID, $current_user->ID );

	$html = '';

	$html .= '<section class="course-results-lessons">';

		$html .= '<header>';
			$html .= '<h2>' . apply_filters( 'sensei_lessons_text', __( 'Lessons', 'woothemes-sensei' ) ) . '</h2>';
		$html .= '</header>';

		$html .= '<article class="' . esc_attr( join( ' ', get_post_class( array( 'course', 'post' ), $course->ID ) ) ) . '">';

		$displayed_lessons = array();

        $modules = Sensei()->modules->get_course_modules( intval( $course->ID ) );

        foreach( $modules as $module ) {

            $args = array(
                'post_type' => 'lesson',
                'post_status' => 'publish',
                'posts_per_page' => -1,
                'meta_query' => array(
                    array(
                        'key' => '_lesson_course',
                        'value' => intval( $course->ID ),
                        'compare' => '='
                    )
                ),
                'tax_query' => array(
                    array(
                        'taxonomy' => Sensei()->modules->taxonomy,
                        'field' => 'id',
                        'terms' => intval( $module->term_id )
                    )
                ),
                'meta_key' => '_order_module_' . $module->term_id,
                'orderby' => 'meta_value_num date',
                'order' => 'ASC',
                'suppress_filters' => 0
            );

            $lessons = get_posts( $args );

            if( count( $lessons ) > 0 ) {
                $html .= '<h3>' . $module->name . '</h3>' . "\n";

                $count = 0;
                foreach( $lessons as $lesson_item ) {

                    $lesson_grade = 'n/a';
                    $has_questions = get_post_meta( $lesson_item->ID, '_quiz_has_questions', true );
                    if ( $has_questions ) {
                        $lesson_status = WooThemes_Sensei_Utils::user_lesson_status( $lesson_item->ID, $current_user->ID );
                        // Get user quiz grade
                        $lesson_grade = get_comment_meta( $lesson_status->comment_ID, 'grade', true );
                        if ( $lesson_grade ) {
                            $lesson_grade .= '%';
                        }
                    }
                    $html .= '<h2><a href="' . esc_url( get_permalink( $lesson_item->ID ) ) . '" title="' . esc_attr( sprintf( __( 'Start %s', 'woothemes-sensei' ), $lesson_item->post_title ) ) . '">' . esc_html( sprintf( __( '%s', 'woothemes-sensei' ), $lesson_item->post_title ) ) . '</a> <span class="lesson-grade">' . $lesson_grade . '</span></h2>';

                    $displayed_lessons[] = $lesson_item->ID;
                }
            }
        }

		$args = array(
			'post_type' => 'lesson',
			'posts_per_page' => -1,
			'suppress_filters' => 0,
			'meta_key' => '_order_' . $course->ID,
			'orderby' => 'meta_value_num date',
			'order' => 'ASC',
			'meta_query' => array(
				array(
					'key' => '_lesson_course',
					'value' => intval( $course->ID ),
				),
			),
			'post__not_in' => $displayed_lessons,
		);

		$lessons = get_posts( $args );

		if(  0 < count( $lessons ) ) {
			$html .= '<h3>' . __( 'Other Lessons', 'woothemes-sensei' ) . '</h3>' . "\n";
		}

		foreach ( $lessons as $lesson_item ) {

			$lesson_grade = 'n/a';
			$has_questions = get_post_meta( $lesson_item->ID, '_quiz_has_questions', true );
			if ( $has_questions ) {
				$lesson_status = WooThemes_Sensei_Utils::user_lesson_status( $lesson_item->ID, $current_user->ID );
				// Get user quiz grade
				$lesson_grade = get_comment_meta( $lesson_status->comment_ID, 'grade', true );
				if ( $lesson_grade ) {
					$lesson_grade .= '%';
				}
			}

			$html .= '<h2><a href="' . esc_url( get_permalink( $lesson_item->ID ) ) . '" title="' . esc_attr( sprintf( __( 'Start %s', 'woothemes-sensei' ), $lesson_item->post_title ) ) . '">' . esc_html( sprintf( __( '%s', 'woothemes-sensei' ), $lesson_item->post_title ) ) . '</a> <span class="lesson-grade">' . $lesson_grade . '</span></h2>';

		} // End For Loop

		$html .= '<h2 class="total-grade">' . apply_filters( 'sensei_total_grade_text', __( 'Total Grade', 'woothemes-sensei' ) ) . '<span class="lesson-grade">' . $course_user_grade . '%</span></h2>';

		$html .= '</article>';

	$html .= '</section>';

	do_action( 'sensei_course_results_before_lessons', $course->ID );

	// Output the HTML
	echo $html;

	do_action( 'sensei_course_results_after_lessons', $course->ID );

} // End If Statement

