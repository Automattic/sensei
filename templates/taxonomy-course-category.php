<?php
/**
 * The Template for displaying course archives for the course category taxonomy terms.
 *
 * Override this template by copying it to yourtheme/sensei/taxonomy-course-category.php
 *
 * @author 		WooThemes
 * @package 	Sensei/Templates
 * @version     1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

get_header();

/**
 * sensei_before_main_content hook
 *
 * @hooked sensei_output_content_wrapper - 10 (outputs opening divs for the content)
 */
do_action('sensei_before_main_content');

/**
 * sensei_course_category_main_content hook
 *
 * @hooked sensei_course_category_main_content - 10 (outputs main content loop)
 */
do_action('sensei_course_category_main_content');

/**
 * sensei_pagination hook
 *
 * @hooked sensei_pagination - 10 (outputs pagination)
 */
do_action('sensei_pagination');

/**
 * sensei_after_main_content hook
 *
 * @hooked sensei_output_content_wrapper_end - 10 (outputs closing divs for the content)
 */
do_action('sensei_after_main_content');

/**
 * sensei_sidebar hook
 *
 * @hooked sensei_get_sidebar - 10
 */
do_action('sensei_sidebar');

get_footer(); ?>