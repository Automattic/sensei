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
		<?php

		do_action( 'sensei_frontend_messages' );

		do_action( 'sensei_before_user_course_content', $current_user );

		echo $woothemes_sensei->course->load_user_courses_content( $current_user, true );

		do_action( 'sensei_after_user_course_content', $current_user );

		?>
	</section>
<?php } else {
	do_action( 'sensei_login_form' );
} // End If Statement ?>