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

/***************************
 *
 * TEMPLATE SYSTEM HOOKS
 *
 ***************************/

//This hook allow us to change the template WordPress loads for a given page/post_type @since 1.9.0
add_filter( 'template_include', array ( 'Sensei_Templates', 'template_loader' ), 10, 1 );


/***************************
 *
 * COURSE ARCHIVE HOOKS
 *
 ***************************/
// deprecate the archive content hook @since 1.9.0
add_action( 'sensei_archive_before_course_loop', array ( 'Sensei_Templates', 'deprecated_archive_hook' ), 10, 1 );

// Course archive title hook @since 1.9.0
add_action('sensei_archive_title', array( 'WooThemes_Sensei_Course', 'archive_header' ), 10, 0 );

// add the course image above the content
add_action('sensei_course_content_before', array( Sensei()->course, 'course_image' ) ,10, 1 );

// add course content title to the courses on the archive page
add_action('sensei_course_content_before', array( 'Sensei_Templates', 'the_title' ) ,11, 1 );

/***************************
 *
 * SINGLE COURSE HOOKS
 *
 ***************************/

// @1.9.0
// add deprecated action hooks for backwards compatibility sake
// hooks on single course page: sensei_course_image , sensei_course_single_title, sensei_course_single_meta
add_action('sensei_single_course_content_inside_before', array( 'Sensei_Templates', 'deprecated_single_course_inside_before_hooks' ), 80);

// @1.9.0
// hook the single course title on the single course page
add_action( 'sensei_single_course_content_inside_before', array( Sensei()->frontend, 'sensei_single_title' ), 10 );

// @1.9.0
//Add legacy hooks deprecated in 1.9.0
add_action( 'sensei_single_course_content_inside_before', 'course_single_meta', 10 );

// @1.9.0
// Filter the content and replace it with the excerpt if the user doesn't have full access
add_filter( 'the_content', array('WooThemes_Sensei_Course', 'single_course_content' ) );

// @1.9.0
// Deprecate lessons specific single course hooks
add_action( 'sensei_single_course_content_inside_after', array( 'Sensei_Templates','deprecate_sensei_course_single_lessons_hook' ) );

// @1.9.0
// Deprecate single course content hooks and in favor of simply calling the_content.
add_action( 'sensei_single_course_content_inside_after', array( 'Sensei_Templates', 'deprecated_single_course_single_main_content_hook') );