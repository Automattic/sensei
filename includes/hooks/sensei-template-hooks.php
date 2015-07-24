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
 * deprecate the archive content hook
 * @since 1.9.0
 */
add_action( 'sensei_archive_before_course_loop', array ( 'Sensei_Templates', 'deprecated_archive_hook' ), 10, 1 );

/**
 * This hook allow us to change the template WordPress loads for a given page/post_type
 *
 * @since 1.0.0
 */
add_filter( 'template_include', array ( 'Sensei_Templates', 'template_loader' ), 10, 1 );