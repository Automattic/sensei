<?php
// This file contains material which is the pre-existing property of Psycle Interactive Limited.
// Copyright (c) 2014 Psycle Interactive. All rights reserved.

/**
 * The Template for displaying the my course page data.
 *
 * Override this template by copying it to yourtheme/sensei/user/my-courses.php
 *
 * @author 		WooThemes
 * @package 	Sensei/Templates
 * @version     1.0.0
 */
// Customised so that Users cannot remove themselves off Courses, change 'load_user_courses_content' to have false
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
global $woothemes_sensei, $post, $current_user, $wp_query;

// Switch user to show depending on new My Learning BP component...
if ( function_exists('imperial_bp_is_learning_component') && imperial_bp_is_learning_component() ) {
	$user_to_show = get_user_by( 'id', bp_displayed_user_id() );
}
// ...or old shortcode only method on global page
else {
	// Get User Meta
	get_currentuserinfo();
	$user_to_show = $current_user;
}
// Check if the user is logged in
if ( is_user_logged_in() ) {
	// Handle completion of a course
	do_action( 'sensei_complete_course' );
	?>
	<section id="main-course" class="course-container">
		<?php

		do_action( 'sensei_frontend_messages' );

		do_action( 'sensei_before_user_course_content', $user_to_show );

		echo imperial_sensei_load_user_courses_content( $user_to_show, false );

		do_action( 'sensei_after_user_course_content', $user_to_show );

		?>
	</section>
<?php } else {
	do_action( 'sensei_login_form' );
} // End If Statement 