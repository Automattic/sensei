<?php
// This file contains material which is the pre-existing property of Psycle Interactive Limited.
// Copyright (c) 2014 Psycle Interactive. All rights reserved.

/**
 * The Template for displaying all single course meta information.
 *
 * Override this template by copying it to yourtheme/sensei/single-course/course-meta.php
 *
 * @author 		WooThemes
 * @package 	Sensei/Templates
 * @version     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

global $post, $current_user, $woocommerce;

// Get User Meta
get_currentuserinfo();

/**
 * sensei_course_start hook
 *
 * @hooked sensei_course_start - 10 (handles logic to start a course)
 */
do_action( 'sensei_course_start' );

/**
 * sensei_woocommerce_in_cart_message hook
 *
 * @hooked sensei_woocommerce_in_cart_message - 10 (outputs message to user if the course is already in the cart)
 */
do_action( 'sensei_woocommerce_in_cart_message' );

/**
 * sensei_course_meta hook
 *
 * @hooked sensei_course_meta - 10 (outputs the main course meta information)
 */
$imp = imperial();
$user_course_ids = $imp->get_user_course_ids( wp_get_current_user(), $imp->programme, $student_only = true );
$course_programmes = $imp->get_programmes_by_course( $imp->course, 'ids' );
// Check if the Student is either on the Course, or the Course is floating (or it's us!)
if ( 1 == $current_user->ID || in_array( $imp->course->ID, $user_course_ids ) || empty($course_programmes) ) {
	// Capture content, detect start course form and remove before showing on an 'onground' course
	ob_start();
	do_action( 'sensei_course_meta' );
	$course_meta = ob_get_clean();
	// Check for 'onground' (don't hide for us!)
	if ( 'onground' == ( $course_type = get_post_meta( $imp->course->ID, 'course_type', true ) ) && 1 != $current_user->ID ) {
		// Remove "start course" form
		$course_meta = preg_replace( '|<form.*woothemes_sensei_start_course_noonce.*<\/form>|Us', '', $course_meta );
		// Remove 'status' element
		$course_meta = preg_replace( '|<div.*class="status.*<\/div>|Us', '', $course_meta );
	}
	$pdf_link = do_shortcode( '[pdf-download-link]' );
	$course_meta = str_replace( '</section>', $pdf_link . '</section>', $course_meta );
	echo $course_meta;
}

/**
 * sensei_course_meta_video hook
 *
 * @hooked sensei_course_meta_video - 10 (outputs the video for course)
 */
do_action( 'sensei_course_meta_video' );
?>