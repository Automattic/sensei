<?php
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
do_action( 'sensei_course_meta' );

/**
 * sensei_course_meta_video hook
 *
 * @hooked sensei_course_meta_video - 10 (outputs the video for course)
 */
do_action( 'sensei_course_meta_video' );