<?php
/**
 * The Template for displaying all single courses.
 *
 * Override this template by copying it to yourtheme/sensei/single-course.php
 *
 * @author 		WooThemes
 * @package 	Sensei/Templates
 * @version     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

get_header();

/**
 * sensei_before_main_content hook
 *
 * @hooked sensei_output_content_wrapper - 10 (outputs opening divs for the content)
 */
do_action( 'sensei_before_main_content' );

/**
 * sensei_single_main_content hook
 *
 * @hooked Sensei->Modules->single_course_modules - 9
 * @hooked sensei_single_main_content - 10 (outputs main content)
 */
do_action( 'sensei_single_main_content' );

/**
 * sensei_after_main_content hook
 *
 * @hooked sensei_output_content_wrapper_end - 10 (outputs closing divs for the content)
 */
do_action( 'sensei_after_main_content' );

/**
 * sensei_sidebar hook
 *
 * @hooked sensei_get_sidebar - 10
 */
do_action( 'sensei_sidebar' );

get_footer(); ?>