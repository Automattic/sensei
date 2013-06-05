<?php
/**
 * The Template for displaying the my course page data.
 *
 * Override this template by copying it to yourtheme/sensei/user/my-courses.php
 *
 * @author 		WooThemes
 * @package 	Sensei/Templates
 * @version     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

global $woothemes_sensei, $post, $current_user, $wp_query;

// Get User Meta
get_currentuserinfo();

// Check if the user is logged in
if ( is_user_logged_in() ) {
	// Handle completion of a course
	do_action( 'sensei_complete_course' );
	?>
	<section id="main-course" class="course-container">
	<?php do_action( 'sensei_frontend_messages' ); ?>
	<?php
	// Logic for Active and Completed Courses
	if ( isset( $woothemes_sensei->settings->settings[ 'my_course_amount' ] ) && ( 0 < absint( $woothemes_sensei->settings->settings[ 'my_course_amount' ] ) ) ) {
		$amount = absint( $woothemes_sensei->settings->settings[ 'my_course_amount' ] );
	} else {
		$amount = $wp_query->get( 'posts_per_page' );
	} // End If Statement
	$course_ids = WooThemes_Sensei_Utils::sensei_activity_ids( array( 'user_id' => $current_user->ID, 'type' => 'sensei_course_start' ) );
	$posts_array = array();
	if ( 0 < intval( count( $course_ids ) ) ) {
		$posts_array = $woothemes_sensei->post_types->course->course_query( $amount, 'usercourses', $course_ids );
	} // End If Statement
	$lesson_count = 1;

	// Build Output HTML
	$complete_html = '';
	$active_html = '';

	// MAIN LOOP
	foreach ($posts_array as $course_item){
	    $course_lessons = $woothemes_sensei->frontend->course->course_lessons( $course_item->ID );
	    $lessons_completed = 0;
	    foreach ($course_lessons as $lesson_item){
	    	$single_lesson_complete = false;
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
                        } // End If Statement
                    } // End If Statement
                } else {
                    $lessons_completed++;
                    $single_lesson_complete = true;
                } // End If Statement;
			} // End If Statement
	    } // End For Loop
	    // Get Course Categories
	    $category_output = get_the_term_list( $course_item->ID, 'course-category', '', ', ', '' );
	    // Build Output
	    if ( absint( $lessons_completed ) == absint( count( $course_lessons ) ) && ( 0 < absint( count( $course_lessons ) ) ) && ( 0 < absint( $lessons_completed ) ) ) {
	    	// Course is complete
	    	$complete_html .= '<article class="' . join( ' ', get_post_class( array( 'course', 'post' ), $course_item->ID ) ) . '">';

	    	    // Image
	    		$complete_html .= $woothemes_sensei->post_types->course->course_image( absint( $course_item->ID ) );

	    		// Title
	    		$complete_html .= '<header>';

	    		    $complete_html .= '<h2><a href="' . esc_url( get_permalink( absint( $course_item->ID ) ) ) . '" title="' . esc_attr( $course_item->post_title ) . '">' . esc_html( $course_item->post_title ) . '</a></h2>';

	    		$complete_html .= '</header>';

	    		$complete_html .= '<section class="entry">';

	    			$complete_html .= '<p class="sensei-course-meta">';

	    		    	// Author
	    		    	$user_info = get_userdata( absint( $course_item->post_author ) );
	    		    	if ( isset( $woothemes_sensei->settings->settings[ 'course_author' ] ) && ( $woothemes_sensei->settings->settings[ 'course_author' ] ) ) {
	    		    		$complete_html .= '<span class="course-author">' . __( 'by ', 'woothemes-sensei' ) . '<a href="' . esc_url( get_author_posts_url( absint( $course_item->post_author ) ) ) . '" title="' . esc_attr( $user_info->display_name ) . '">' . esc_html( $user_info->display_name ) . '</a></span>';
	    		    	} // End If Statement

	    		    	// Lesson count for this author
	    		    	$complete_html .= '<span class="course-lesson-count">' . $woothemes_sensei->post_types->course->course_author_lesson_count( $course_item->post_author, absint( $course_item->ID ) ) . '&nbsp;' . __( 'Lectures', 'woothemes-sensei' ) . '</span>';
	    		    	// Course Categories
	    		    	if ( '' != $category_output ) {
	    		    		$complete_html .= '<span class="course-category">' . sprintf( __( 'in %s', 'woothemes-sensei' ), $category_output ) . '</span>';
	    		    	} // End If Statement

					$complete_html .= '</p>';

					$complete_html .= '<p>' . apply_filters( 'get_the_excerpt', $course_item->post_excerpt ) . '</p>';

					$complete_html .= '<div class="meter green"><span style="width: 100%">100%</span></div>';

	    		$complete_html .= '</section>';

	    	$complete_html .= '</article>';

	    	$complete_html .= '<div class="fix"></div>';

	    } else {

	    	$active_html .= '<article class="' . esc_attr( join( ' ', get_post_class( array( 'course', 'post' ), $course_item->ID ) ) ) . '">';

	    	    // Image
	    		$active_html .= $woothemes_sensei->post_types->course->course_image( absint( $course_item->ID ) );

	    		// Title
	    		$active_html .= '<header>';

	    		    $active_html .= '<h2><a href="' . esc_url( get_permalink( absint( $course_item->ID ) ) ) . '" title="' . esc_attr( $course_item->post_title ) . '">' . esc_html( $course_item->post_title ) . '</a></h2>';

	    		$active_html .= '</header>';

	    		$active_html .= '<section class="entry">';

	    			$active_html .= '<p class="sensei-course-meta">';

	    		    	// Author
	    		    	$user_info = get_userdata( absint( $course_item->post_author ) );
	    		    	if ( isset( $woothemes_sensei->settings->settings[ 'course_author' ] ) && ( $woothemes_sensei->settings->settings[ 'course_author' ] ) ) {
	    		    		$active_html .= '<span class="course-author"><a href="' . esc_url( get_author_posts_url( absint( $course_item->post_author ) ) ) . '" title="' . esc_attr( $user_info->display_name ) . '">' . __( 'by ', 'woothemes-sensei' ) . esc_html( $user_info->display_name ) . '</a></span>';
	    		    	} // End If Statement
	    		    	// Lesson count for this author
	    		    	$lesson_count = $woothemes_sensei->post_types->course->course_author_lesson_count( $course_item->post_author, absint( $course_item->ID ) );
	    		    	// Handle Division by Zero
						if ( 0 == $lesson_count ) {
							$lesson_count = 1;
						} // End If Statement
	    		    	$active_html .= '<span class="course-lesson-count">' . $lesson_count . '&nbsp;' . __( 'Lectures', 'woothemes-sensei' ) . '</span>';
	    		    	// Course Categories
	    		    	if ( '' != $category_output ) {
	    		    		$active_html .= '<span class="course-category">' . sprintf( __( 'in %s', 'woothemes-sensei' ), $category_output ) . '</span>';
	    		    	} // End If Statement
						$active_html .= '<span class="course-lesson-progress">' . sprintf( __( '%1$d of %2$d Chapters completed', 'woothemes-sensei' ) , $lessons_completed, $lesson_count  ) . '</span>';

	    		    $active_html .= '</p>';

	    		    $active_html .= '<p>' . apply_filters( 'get_the_excerpt', $course_item->post_excerpt ) . '</p>';

	    		   	$progress_percentage = abs( round( ( doubleval( $lessons_completed ) * 100 ) / ( $lesson_count ), 0 ) );

	    		   	if ( 50 < $progress_percentage ) { $class = ' green'; } elseif ( 25 <= $progress_percentage && 50 >= $progress_percentage ) { $class = ' orange'; } else { $class = ' red'; }

	    		   	/* if ( 0 == $progress_percentage ) { $progress_percentage = 5; } */

	    		   	$active_html .= '<div class="meter' . esc_attr( $class ) . '"><span style="width: ' . $progress_percentage . '%">' . $progress_percentage . '%</span></div>';

	    		$active_html .= '</section>';

	    		$active_html .= '<section class="entry-actions">';

	    			$active_html .= '<form method="POST" action="' . esc_url( get_permalink() ) . '">';

	    				$active_html .= '<input type="hidden" name="' . esc_attr( 'woothemes_sensei_complete_course_noonce' ) . '" id="' . esc_attr( 'woothemes_sensei_complete_course_noonce' ) . '" value="' . esc_attr( wp_create_nonce( 'woothemes_sensei_complete_course_noonce' ) ) . '" />';

	    				$active_html .= '<input type="hidden" name="course_complete_id" id="course-complete-id" value="' . esc_attr( absint( $course_item->ID ) ) . '" />';

	    				if ( 0 < absint( count( $course_lessons ) ) ) {
	    					$active_html .= '<span><input name="course_complete" type="submit" class="course-complete" value="' . __( 'Mark as Complete', 'woothemes-sensei' ) . '"/></span>';
	    				} // End If Statement

	    				$course_purchased = false;
	    				if ( WooThemes_Sensei_Utils::sensei_is_woocommerce_activated() ) {
	    					// Get the product ID
	    					$wc_post_id = get_post_meta( absint( $course_item->ID ), '_course_woocommerce_product', true );
	    					if ( 0 < $wc_post_id ) {
	    						$course_purchased = WooThemes_Sensei_Utils::sensei_customer_bought_product( $current_user->user_email, $current_user->ID, $wc_post_id );
	    					} // End If Statement
	    				} // End If Statement

	    				if ( !$course_purchased ) {
	    					$active_html .= '<span><input name="course_complete" type="submit" class="course-delete" value="' . __( 'Delete Course', 'woothemes-sensei' ) . '"/></span>';
	    				} // End If Statement

	    			$active_html .= '</form>';

	    		$active_html .= '</section>';

	    	$active_html .= '</article>';

	    	$active_html .= '<div class="fix"></div>';

	    } // End If Statement
	} // End For Loop
	?>
	<div id="my-courses">

	    <ul>
	    	<li><a href="#active-courses"><?php _e( 'Active Courses', 'woothemes-sensei' ); ?></a></li>
	    	<li><a href="#completed-courses"><?php _e( 'Completed Courses', 'woothemes-sensei' ); ?></a></li>
	    </ul>

	    <div id="active-courses">

	    	<?php if ( '' != $active_html ) {
	    		echo $active_html;
	    	} else { ?>
	    		<div class="woo-sc-box info"><?php _e( 'You have no active courses.', 'woothemes-sensei' ); ?> <a href="<?php echo get_post_type_archive_link( 'course' ); ?>"><?php _e( 'Start a Course!', 'woothemes-sensei' ); ?></a></div>
	    	<?php } // End If Statement ?>

	    </div>

	    <div id="completed-courses">

	    	<?php if ( '' != $complete_html ) {
	    		echo $complete_html;
	    	} else { ?>
	    		<div class="woo-sc-box info"><?php _e( 'You have not completed any courses yet.', 'woothemes-sensei' ); ?></div>
	    	<?php } // End If Statement ?>

	    </div>

	</div>

	</section>
<?php } else { 
	do_action( 'sensei_login_form' );
} // End If Statement ?>