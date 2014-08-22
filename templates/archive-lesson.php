<?php
/**
 * The Template for displaying lesson archives.
 *
 * Override this template by copying it to yourtheme/sensei/archive-lesson.php
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
do_action('sensei_before_main_content');

/**
 * sensei_lesson_archive_main_content hook
 *
 * @hooked sensei_lesson_archive_main_content - 10 (outputs main lesson archive content loop)
 */
do_action( 'sensei_lesson_archive_main_content' );

/**
 * sensei_breadcrumb hook
 *
 * @hooked sensei_breadcrumb - 10 (outputs sensei breadcrumb trail)
 */
do_action('sensei_breadcrumb');

/**
 * sensei_pagination hook
 *
 * @hooked sensei_pagination - 10 (outputs archive pagination)
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