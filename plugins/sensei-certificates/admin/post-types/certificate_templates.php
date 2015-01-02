<?php
/**
 * Sensei Certificates Templates
 *
 * All functionality pertaining to the Certificate Templates functionality in Sensei.
 *
 * @package WordPress
 * @subpackage Sensei
 * @category Extension
 * @author WooThemes
 * @since 1.0.0
 *
 */

/**
 * TABLE OF CONTENTS
 *
 * - Requires
 * - Actions and Filters
 * - certificate_template_edit_certificate_bulk_actions()
 * - certificate_template_edit_certificate_views()
 * - certificate_template_edit_certificate_columns()
 * - certificate_template_custom_certificate_columns()
 */

/**
 * Admin functions for the certificate_template post type
 *
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Actions and Filters
 */
add_filter( 'bulk_actions-edit-certificate_template', 'certificate_template_edit_certificate_bulk_actions' );
add_filter( 'views_edit-certificate_template', 'certificate_template_edit_certificate_views' );
add_filter( 'manage_edit-certificate_template_columns', 'certificate_template_edit_certificate_columns' );
add_action( 'manage_certificate_template_posts_custom_column', 'certificate_template_custom_certificate_columns', 2 );

/**
 * Remove the bulk edit action for certificate templates
 *
 * @since 1.0.0
 * @param array $actions associative array of action identifier to name
 * @return array associative array of action identifier to name
 */
function certificate_template_edit_certificate_bulk_actions( $actions ) {

	unset( $actions['edit'] );

	return $actions;

} // End certificate_template_edit_certificate_bulk_actions()


/**
 * Modify the 'views' links, ie All (3) | Publish (1) | Draft (1) | Private (2) | Trash (3)
 * shown above the certificate templates list table, to hide the publish/private states,
 * which are not important and confusing for certificate objects.
 *
 * @since 1.0.0
 * @param array $views associative-array of view state name to link
 * @return array associative array of view state name to link
 */
function certificate_template_edit_certificate_views( $views ) {

	// publish and private are not important distinctions for certificate templates
	unset( $views['publish'], $views['private'] );

	return $views;

} // End certificate_template_edit_certificate_views()


/**
 * Columns for certificate templates page
 *
 * @since 1.0.0
 * @param array $columns associative-array of column identifier to header names
 *
 * @return array associative-array of column identifier to header names for the certificate tempaltes page
 */
function certificate_template_edit_certificate_columns( $columns ){

	$columns = array();

	$columns['cb']             = '<input type="checkbox" />';
	$columns['name']           = __( 'Name', 'sensei-certificates' );
	$columns['thumb']          = __( 'Image', 'sensei-certificates' );

	return $columns;

} // End certificate_template_edit_certificate_columns()


/**
 * Custom Column values for certificate templates page
 *
 * @since 1.0.0
 * @param string $column column identifier
 */
function certificate_template_custom_certificate_columns( $column ) {

	global $post;

	switch ( $column ) {
		case 'thumb':
			
			$edit_link = get_edit_post_link( $post->ID );
			if ( has_post_thumbnail( $post->ID ) ) {
				$image = get_the_post_thumbnail( $post->ID, 'thumb' );
				echo '<a href="' . $edit_link . '">' . $image . '</a>';
			} // End If Statement

		break;

		case 'name':
			
			$edit_link = get_edit_post_link( $post->ID );
			$title = _draft_or_post_title();

			$post_type_object = get_post_type_object( $post->post_type );
			$can_edit_post = current_user_can( $post_type_object->cap->edit_post, $post->ID );

			echo '<strong><a class="row-title" href="' . $edit_link . '">' . $title . '</a>';

			// display post states a little more selectively than _post_states( $post );
			if ( 'draft' == $post->post_status ) {
				echo " - <span class='post-state'>" . __( 'Draft', 'sensei-certificates' ) . '</span>';
			} // End If Statement

			echo '</strong>';

			// Get actions
			$actions = array();

			$actions['id'] = 'ID: ' . $post->ID;

			if ( current_user_can( $post_type_object->cap->delete_post, $post->ID ) ) {
				if ( 'trash' == $post->post_status )
					$actions['untrash'] = "<a title='" . esc_attr( __( 'Restore this item from the Trash', 'sensei-certificates' ) ) . "' href='" . wp_nonce_url( admin_url( sprintf( $post_type_object->_edit_link . '&amp;action=untrash', $post->ID ) ), 'untrash-' . $post->post_type . '_' . $post->ID ) . "'>" . __( 'Restore', 'sensei-certificates' ) . "</a>";
				elseif ( EMPTY_TRASH_DAYS )
					$actions['trash'] = "<a class='submitdelete' title='" . esc_attr( __( 'Move this item to the Trash', 'sensei-certificates' ) ) . "' href='" . get_delete_post_link( $post->ID ) . "'>" . __( 'Trash', 'sensei-certificates' ) . "</a>";
				if ( 'trash' == $post->post_status || ! EMPTY_TRASH_DAYS )
					$actions['delete'] = "<a class='submitdelete' title='" . esc_attr( __( 'Delete this item permanently', 'sensei-certificates' ) ) . "' href='" . get_delete_post_link( $post->ID, '', true ) . "'>" . __( 'Delete Permanently', 'sensei-certificates' ) . "</a>";
			} // End If Statement

			$actions = apply_filters( 'post_row_actions', $actions, $post );

			echo '<div class="row-actions">';

			$i = 0;
			$action_count = count( $actions );

			foreach ( $actions as $action => $link ) {
				( $action_count - 1 == $i ) ? $sep = '' : $sep = ' | ';
				echo '<span class="' . $action . '">' . $link . $sep . '</span>';
				$i++;
			} // End For Loop
			echo '</div>';
		break;

	} // End Switch Statement

} // End certificate_template_custom_certificate_columns()