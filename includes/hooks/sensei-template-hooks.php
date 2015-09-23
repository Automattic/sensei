<?php
/**
 * Sensei Template Hooks
 *
 * Action/filter hooks used for Sensei functionality hooked into Sensei Templates
 *
 * @author 		WooThemes
 * @package 	Sensei
 * @category 	Hooks
 * @version     1.9.0
 */

/**
 *
 * Course Archive hooks
 *
 */
// deprecate the archive content hook @since 1.9.0
add_action( 'sensei_archive_before_course_loop', array ( 'Sensei_Templates', 'deprecated_archive_hook' ), 10, 1 );

// Course archive title hook @since 1.9.0
add_action('sensei_archive_title', array( 'WooThemes_Sensei_Course', 'archive_header' ), 10, 0 );

// add the course image above the content
add_action('sensei_course_content_before', array( Sensei()->course, 'course_image' ) ,10, 1 );

// add course content title to the courses on the archive page
add_action('sensei_course_content_before', array( 'Sensei_Templates', 'the_title' ) ,11, 1 );

/**
 * This hook allow us to change the template WordPress loads for a given page/post_type
 *
 * @since 1.0.0
 */
add_filter( 'template_include', array ( 'Sensei_Templates', 'template_loader' ), 10, 1 );