<?php
// This file contains material which is the pre-existing property of Psycle Interactive Limited.
// Copyright (c) 2014 Psycle Interactive. All rights reserved.

// This file contains the primary changes to Sensei, the deep rooted aspects. More generic filters (and actions) are in the sensei_filters.php file

/**
 * Modifies the Sensei content types, changing menu positions and icons
 * 
 * @param type $post_type
 * @param type $args
 */
function imperial_modify_sensei_content_type( $post_type, $args ) {
	global $wp_post_types;
	if ( 'course' == $post_type ) {
//		$args = $wp_post_types[ 'course' ];
		// Adjust the args...
		$args->show_in_menu = true;
		$args->show_in_admin_bar = true;
		$args->menu_position = 52;
		$args->menu_icon = 'dashicons-book-alt';
		$args->has_archive = false;
//		error_log( print_r($args, true));
		// ... and re-save back
		$wp_post_types[ $post_type ] = $args;
	}
	elseif ( 'lesson' == $post_type ) {
		// Adjust the args...
		$args->menu_icon = 'dashicons-format-aside';
		$labels = get_object_vars( $args->labels );
		foreach ( $labels as $key => $label ) {
			$labels[$key] = str_replace( array('Lessons', 'lessons', 'Lesson\'s', 'lesson\'s', 'Lesson', 'lesson'), array('Activities', 'activities', 'Activities', 'activities', 'Activity', 'activity'), $label );
		}
		$args->labels = (object) $labels;
		$args->label = 'Activities';
		// ... and re-save back
		$wp_post_types[ $post_type ] = $args;
	}
} // END imperial_modify_sensei_content_type()
add_action( 'registered_post_type', 'imperial_modify_sensei_content_type', 10, 2 ); // Just after Sensei

/**
 * Customise the Sensei Settings page, remove (and add) options
 * 
 * @param type $fields
 * @return type
 */
function imperial_sensei_settings_fields( $fields ) {
	unset( $fields['course_page'] );
	return $fields;
}
//add_filter( 'sensei_settings_fields', 'imperial_sensei_settings_fields' );

/**
 * Modifies the Sensei taxonomies
 */
function imperial_modify_sensei_taxonomy( $taxonomy, $object_type, $args ) {
	global $wp_taxonomies;
	if ( 'course-category' == $taxonomy ) {
		// Adjust the args...
//		$args['show_ui'] = false;
		$args['show_in_menu'] = false;
		$args['show_in_nav_menus'] = false;
		// ... and re-save back
		$wp_taxonomies[ 'course-category' ] = (object) $args;
	}
	if ( 'lesson-tag' == $taxonomy ) {
		// Adjust the args...
		$labels = get_object_vars( $args['labels'] );
		foreach ( $labels as $key => $label ) {
			$labels[$key] = str_replace( array('Lessons', 'lessons', 'Lesson\'s', 'lesson\'s', 'Lesson', 'lesson'), array('Activities', 'activities', 'Activities', 'activities', 'Activity', 'activity'), $label );
		}
		$args['labels'] = (object) $labels;
		$args['label'] = 'Activity Tags';
		// ... and re-save back
		$wp_taxonomies[ $taxonomy ] = (object) $args;
	}
//	unset($wp_taxonomies[ 'course-category' ]);
} // END imperial_modify_sensei_taxonomy()
add_action( 'registered_taxonomy', 'imperial_modify_sensei_taxonomy', 10, 3 );


/**
 * Adjust various Sensei admin menus such as permissions, removing some...
 * 
 * @global type $woothemes_sensei
 */
function imperial_sensei_menu_removals() {
	global $woothemes_sensei, $menu;
	// Don't need this category
//	remove_action( 'init', array( $woothemes_sensei->post_types, 'setup_course_category_taxonomy' ), 100 );
	// Don't need to add Course Categories to the 'Lessons' menu
//	remove_action( 'admin_menu', array( $woothemes_sensei->post_types, 'sensei_admin_menu_items' ), 10 );
	// 'Courses' isn't part of 'Lessons' now
	remove_action( 'admin_head', array( $woothemes_sensei->admin, 'admin_menu_highlight' ) );
	
	// Allow lower level users to access the Sensei Analysis and Grading screens (remove now, re-add later)
//	remove_action( 'admin_menu', array( $woothemes_sensei->admin, 'admin_menu' ), 10 );
//	remove_action( 'admin_menu', array( $woothemes_sensei->analysis, 'analysis_admin_menu' ), 10);
//	remove_action( 'admin_menu', array( $woothemes_sensei->grading, 'grading_admin_menu' ), 10);
//	remove_action( 'admin_menu', array( $woothemes_sensei->learners, 'learners_admin_menu' ), 10);

}
add_action( 'init', 'imperial_sensei_menu_removals', 5 ); // Higher priority to remove later actions

/**
 * Adjust various Sensei admin menus such as permissions, adding some...
 * 
 * @global type $woothemes_sensei
 */
function imperial_sensei_menu_additions() {
	global $woothemes_sensei, $menu;
	if ( current_user_can( 'publish_courses' ) ) {
		// These 3 used to use 'manage_options' as the capability!?
		$menu[] = array( '', 'read', 'separator-sensei', '', 'wp-menu-separator sensei' );
		$main_page = add_menu_page( __( 'Sensei', 'woothemes-sensei' ), __( 'Sensei', 'woothemes-sensei' ), 'publish_courses', 'sensei' , array( $woothemes_sensei->analysis, 'analysis_page' ) , '', '50' );
		$analysis_page = add_submenu_page( 'sensei', __('Analysis', 'woothemes-sensei'),  __('Analysis', 'woothemes-sensei') , 'publish_courses', 'sensei_analysis', array( $woothemes_sensei->analysis, 'analysis_page' ) );
		$grading_page = add_submenu_page( 'sensei', __('Grading', 'woothemes-sensei'),  __('Grading', 'woothemes-sensei') , 'publish_courses', 'sensei_grading', array( $woothemes_sensei->grading, 'grading_page' ) );
	}

}
//add_action( 'admin_menu', 'imperial_sensei_menu_additions', 10 );

/**
 * Adjusts the CSS used for Sensei
 * 
 * @global type $woothemes_sensei
 */
function imperial_sensei_css() {
	global $woothemes_sensei;
	$imp = imperial();
	wp_enqueue_style( $woothemes_sensei->token . '-global-overrides', $imp->css_url( 'sensei-global.css' ), '', $woothemes_sensei->version, 'screen' );
}
add_action( 'admin_enqueue_scripts', 'imperial_sensei_css' );

/**
 * Filter the title of Quizzes stored against Lessons so that a duplicate 'quiz Quiz' doesn't appear
 * 
 * @param type $data
 * @param type $postarr
 * @return type
 */
function imperial_sensei_quiz_titles( $data, $postarr ) {
	if ( false !== stristr($data['post_title'], 'quiz quiz') ) {
		$data['post_title'] = str_ireplace( 'quiz quiz', 'Quiz', $data['post_title'] );
	}
	return $data;
}
add_filter( 'wp_insert_post_data', 'imperial_sensei_quiz_titles', 10, 2 );

