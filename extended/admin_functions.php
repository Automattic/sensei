<?php
// This file contains material which is the pre-existing property of Psycle Interactive Limited.
// Copyright (c) 2014 Psycle Interactive. All rights reserved.

/***************************************************
 * SHARED FUNCTIONS BETWEEN PROGRAMME/COURSE/POST/GROUP ETC
 ***************************************************/

/**
 * Auto create a Forum for published Programmes and Courses
 * 
 * @param type $post_ID
 * @param type $post
 * @param type $update
 */
function imperial_auto_create_forum( $post_ID, $post, $update ) {
	if ( ( 'programme' == $post->post_type || 'course' == $post->post_type ) && 'publish' == $post->post_status && !empty($post->post_name) ) {
		// Check via P2P for Forum(s)...
		$forums = p2p_type( $post->post_type . '_forum' )->get_connected( $post_ID );
		if ( !$forums->have_posts() ) {
			$code = strtoupper( $post->__get($post->post_type . '_code') );
			$year = strtoupper( $post->__get($post->post_type . '_year') );
			// Only auto create if we have to necessary minimal info...
			if ( empty($code) || empty($year) ) {
				return;
			}
			// ...check the Forum doesn't already exist...
			$title = sprintf( '%s - %s - %s', $code, $year, $post->post_title );
			// ...(of course if the post_name variable changes...) 
			$forum = get_page_by_path( sanitize_title( $title ), OBJECT, 'forum' );
			if ( $forum ) {
				$forum_id = $forum->ID;
			}
			else {
				// Reset the data from the form submission otherwise Forum will 'gain' unneeded meta data
				$_POST = array();
				// ...if none found then create one
				$data = array(
					'post_title' => $title,
//					'post_name' => $post->post_name,
					'post_content' => 'If you have a question or comment about ' . $post->post_title . ', please post it here. We will aim to respond within 48 hours.',
//					'post_status' => bbp_get_private_status_id()
				);
				$forum_id = bbp_insert_forum( $data );
				// Ensure auto-forums support 'Support' topics
				update_post_meta( $forum_id, '_bpbbpst_forum_settings', 1 );
				// Allow anonymous posting to forum.
				update_post_meta( $forum_id, '_imp_allow_anonymous_posting', 1 );
			}
			// ...don't forget to attach to the Programme/Course
			p2p_type( $post->post_type . '_forum' )->connect( $post_ID, $forum_id );
		}
	}
}
add_action( 'wp_insert_post', 'imperial_auto_create_forum', 10, 3 );

/**
 * Auto create a Group for published Programmes and Courses
 * 
 * @param type $post_ID
 * @param type $post
 * @param type $update
 */
function imperial_auto_create_group( $post_ID, $post, $update ) {
	if ( ( 'programme' == $post->post_type || 'course' == $post->post_type ) && 'publish' == $post->post_status && !empty($post->post_name) ) {
		// We require minimal info to check
		$code = strtoupper( $post->__get($post->post_type . '_code') );
		$year = strtoupper( $post->__get($post->post_type . '_year') );
		// Minimal data check...
		if ( empty($code) || empty($year) ) {
			return;
		}
		// ...check that the Group doesn't already exist
		$slug = sprintf( '%s - %s - group', $code, $year );
		$group_id = BP_Groups_Group::get_id_from_slug( sanitize_title( $slug ) );
		// ...no group exists (AFAIK) so create one
		if ( !$group_id ) {
			// Title is likely to change, but the slug *shouldn't*
			$title = sprintf( '%s - %s - %s', $code, $year, $post->post_title );
			$creator_id = get_current_user_id();
			// Check for psycle user...
			if ( 1 == $creator_id ) {
				$user = get_user_by( 'login', 'mwells' );
				$creator_id = $user->ID; // ...change to Marc :)
			}
			$id = groups_create_group( array(
				'creator_id'   => $creator_id,
				'name'         => $title,
				'description'  => 'This is the group discussion area for the '.$post->post_title.' course. Please use this to communicate with your fellow class mates and tutor(s).',
				'slug'         => groups_check_slug( sanitize_title( $slug ) ),
				'enable_forum' => 1,
				'status'       => 'private',
			) );
			// Set the invite status, 'mods' = "Group admins and mods only"
			groups_update_groupmeta( $id, 'invite_status', 'mods' );
			// Assign forum to group, if group was created.
			$forums = p2p_type( $post->post_type . '_forum' )->get_connected( $post_ID );
			if ( $forums->have_posts() ) {
				$forum = $forums->next_post();
				bbp_update_group_forum_ids( $id, (array) $forum->ID );
				bbp_update_forum_group_ids( $forum->ID, (array) $id  );
				// Track where we came from, this could be used elsewhere
				groups_update_groupmeta( $id, $post->post_type . '_id', $post_ID );
			}
		}
	}
}
add_action( 'wp_insert_post', 'imperial_auto_create_group', 10, 3 );

/**
 * P2P Checks for the 'edit_{posttype}' capability of the destination connection before showing the meta box. 
 * As we have users who can edit Courses but not Programmes, filter the permissions dynamically to fake it.
 * 
 * @param type $post_type
 */
function imperial_allow_programme_assignment_by_non_admins( $post_type ) {
	// When editing a Course...
	if ( 'course' == $post_type ) {
		// Filter all user capability checks... (continued in imperial_course_add_edit_programmes_capability() )
		add_filter( 'user_has_cap', 'imperial_course_add_edit_programmes_capability', 10, 4 );
	}
	elseif ( 'post' == $post_type ) {
//		add_filter( 'user_has_cap', 'imperial_post_add_edit_programmes_capability', 10, 4 );
		add_filter( 'user_has_cap', 'imperial_post_add_edit_courses_capability', 10, 4 );
	}
}
add_action( 'add_meta_boxes', 'imperial_allow_programme_assignment_by_non_admins', 9, 2 );

/**
 * P2P Checks for the 'edit_{posttype}' capability of the destination connection before showing the meta box. 
 * As we have users who can edit Courses but not Programmes, filter the permissions dynamically to fake it.
 * 
 * @param type $allcaps
 * @param type $caps
 * @param type $args
 * @param type $user
 * @return $allcaps
 */
function imperial_course_add_edit_programmes_capability( $allcaps, $caps, $args, $user ) {
	// ...if the request is for Programmes and the User can edit Courses...
	if ( ( 'edit_programmes' == $caps[0] || 'edit_programme' == $caps[0] ) &&
			!empty($allcaps['edit_courses']) ) {
		// ...grant access to allow P2P meta box to show
		$allcaps['edit_programme'] = 1;
		$allcaps['edit_programmes'] = 1;
		// Don't need this now
		remove_filter( 'user_has_cap', 'imperial_course_add_edit_programmes_capability', 10, 4 );
	}
	return $allcaps;
}

/**
 * P2P Checks for the 'edit_{posttype}' capability of the destination connection before showing the meta box. 
 * As we have users who can edit Courses but not Programmes, filter the permissions dynamically to fake it.
 * 
 * @param type $allcaps
 * @param type $caps
 * @param type $args
 * @param type $user
 * @return $allcaps
 */
function imperial_post_add_edit_programmes_capability( $allcaps, $caps, $args, $user ) {
	// ...if the request is for Programmes and the User can edit Courses...
	if ( ( 'edit_programmes' == $caps[0] || 'edit_programme' == $caps[0] ) &&
			!empty($allcaps['edit_posts']) ) {
		// ...grant access to allow P2P meta box to show
		$allcaps['edit_programme'] = 1;
		$allcaps['edit_programmes'] = 1;
		// Don't need this now
		remove_filter( 'user_has_cap', 'imperial_post_add_edit_programmes_capability', 10, 4 );
	}
	return $allcaps;
}
/**
 * P2P Checks for the 'edit_{posttype}' capability of the destination connection before showing the meta box. 
 * As we have users who can edit Courses but not Programmes, filter the permissions dynamically to fake it.
 * 
 * @param type $allcaps
 * @param type $caps
 * @param type $args
 * @param type $user
 * @return $allcaps
 */
function imperial_post_add_edit_courses_capability( $allcaps, $caps, $args, $user ) {
	// ...if the request is for Programmes and the User can edit Courses...
	if ( ( 'edit_courses' == $caps[0] || 'edit_course' == $caps[0] ) &&
			!empty($allcaps['edit_posts']) ) {
		// ...grant access to allow P2P meta box to show
		$allcaps['edit_course'] = 1;
		$allcaps['edit_courses'] = 1;
		// Don't need this now
		remove_filter( 'user_has_cap', 'imperial_post_add_edit_courses_capability', 10, 4 );
	}
	return $allcaps;
}

/***************************************************
 * MISC FUNCTIONS
 ***************************************************/

function imperial_queue_admin_css() {
	$imp = imperial();
	wp_enqueue_style( 'imperial_admin', $imp->css_url( 'admin.css' ), false, $imp->version );
}
add_action( 'admin_enqueue_scripts', 'imperial_queue_admin_css', 11 );

/**
 * Remove those rules that cause problems, leaving the nice ones behind
 */
function imperial_remove_unnessary_rewrite_rules( $rules ) {
	foreach ( $rules as $k => $v ) {
		if ( false !== strpos( $v, 'attachment' ) ||
				false !== strpos( $v, 'cpage' ) ||
				false !== strpos( $v, 'paged' ) ||
				false !== strpos( $v, 'feed' ) ||
				false !== strpos( $k, 'attachment' ) ||
				false !== strpos( $k, 'trackback' ) ) {
			unset( $rules[$k] );
		}
	}
	return $rules;
}

/**
 * Shift the Enhanced Media Library menu to relocate under normal Settings screen
 */
function imperial_move_eml_menu() {
	remove_menu_page( 'eml-taxonomies-options' );
	remove_menu_page( 'eml-mimetype-options' );

	$eml_taxonomies_options_suffix = add_options_page(
		__('Media Taxonomies','eml'),
		__('Media Taxonomies','eml'),
		'manage_options',
		'eml-taxonomies-options'
	);

	$eml_mimetype_options_suffix = add_options_page(
		__('MIME Types','eml'),
		__('MIME Types','eml'),
		'manage_options',
		'eml-mimetype-options',
		'wpuxss_eml_print_mimetypes_options'
	);

	add_action('admin_print_scripts-' . $eml_taxonomies_options_suffix, 'wpuxss_eml_admin_settings_pages_scripts');
	add_action('admin_print_scripts-' . $eml_mimetype_options_suffix, 'wpuxss_eml_admin_settings_pages_scripts');
}
add_action('admin_menu', 'imperial_move_eml_menu');
