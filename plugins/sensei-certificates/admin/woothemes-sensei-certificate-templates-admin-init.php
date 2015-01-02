<?php
/**
 * WooThemes Sensei Certificates Templates Admin
 *
 * @package   woothemes-sensei-certificates/Admin
 * @author    WooThemes
 * @copyright Copyright (c) 2012-2013, WooThemes, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

/**
 * TABLE OF CONTENTS
 *
 * - Requires
 * - Actions and Filters
 * - sensei_certificate_template_admin_menu_highlight()
 * - sensei_certificate_template_admin_init()
 * - sensei_certificate_template_admin_help_tab()
 * - sensei_certificate_template_certificate_help_tab_content()
 * - sensei_certificate_template_how_to_help_tab_content()
 * - sensei_certificate_template_admin_enqueue_scripts()
 * - sensei_certificate_template_item_updated_messages()
 */

/**
 * Main admin file which loads all Template panels
 *
 * @since 1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Requires
 */
include_once( 'post-types/certificate_templates.php' );


/**
 * Actions and Filters
 */
add_action( 'admin_head', 'sensei_certificate_template_admin_menu_highlight' );
add_action( 'admin_init', 'sensei_certificate_template_admin_init' );
add_action( 'admin_enqueue_scripts', 'sensei_certificate_template_admin_enqueue_scripts' );
add_filter( 'post_updated_messages', 'sensei_certificate_template_item_updated_messages' );


/**
 * Highlight the correct top level admin menu item for the certificate post type add screen
 *
 * @since 1.0.0
 */
function sensei_certificate_template_admin_menu_highlight() {

	global $menu, $submenu, $parent_file, $submenu_file, $self, $post_type, $taxonomy;

	if ( isset( $post_type ) && 'certificate_template' == $post_type ) {
		$submenu_file = 'edit.php?post_type=' . $post_type;
		$parent_file  = 'sensei';
	} // End If Statement

} // End sensei_certificate_template_admin_menu_highlight()


/**
 * Initialize the admin, adding actions to properly display and handle
 * the certificate custom post type add/edit page
 *
 * @since 1.0.0
 */
function sensei_certificate_template_admin_init() {
	global $pagenow;

	if ( 'post-new.php' == $pagenow || 'post.php' == $pagenow || 'edit.php' == $pagenow ) {

		include_once( 'post-types/writepanels/writepanels-init.php' );

		// add certificate list/edit pages contextual help
		add_action( 'admin_print_styles', 'sensei_certificate_template_admin_help_tab' );

	} // End If Statement

} // End sensei_certificate_template_admin_init()


/**
 * Adds the certificates Admin Help tab to the certificates admin screens
 *
 * @since 1.0.0
 */
function sensei_certificate_template_admin_help_tab() {

	$screen = get_current_screen();

	if ( 'edit-certificate_template' != $screen->id && 'certificate_template' != $screen->id ) return;

	$screen->add_help_tab( array(
		'id'      => 'sensei_certificate_template_overview_help_tab',
		'title'   => __( 'Overview', 'sensei-certificates' ),
		'content' => '<p>' . __( 'The Sensei Certificates extension allows you to create and configure customizable certificate templates which can be attached to Sensei Courses.  Your learners will earn a Certificate which they can download and share with others once they have completed a course.', 'sensei-certificates' ) . '</p>',
	) );

	$screen->add_help_tab( array(
		'id'       => 'sensei_certificate_template_certificate_help_tab',
		'title'    => __( 'Editing a Certificate', 'sensei-certificates' ),
		'callback' => 'sensei_certificate_template_certificate_help_tab_content',
	) );

	$screen->add_help_tab( array(
		'id'      => 'sensei_certificate_template_list_help_tab',
		'title'   => __( 'Certificates List', 'sensei-certificates' ),
		'content' => '<p>' . __( 'From the list view you can review all your certificate templates, quickly see the name, primary default image and its data, and trash a certificate template.', 'sensei-certificates' ) . '</p>'
	) );

	$screen->add_help_tab( array(
		'id'       => 'sensei_certificate_template_how_to_help_tab',
		'title'    => __( 'How To', 'sensei-certificates' ),
		'callback' => 'sensei_certificate_template_how_to_help_tab_content',
	) );

	$screen->set_help_sidebar(
		'<p><strong>' . __( 'For more information:', 'sensei-certificates' ) . '</strong></p>' .
		'<p><a href="http://docs.woothemes.com/document/sensei-certificates/" target="_blank">' . __( 'Certificates Docs', 'sensei-certificates' ) . '</a></p>'
	);

} // End sensei_certificate_template_admin_help_tab()


/**
 * Renders the certificate help tab content for the contextual help menu
 *
 * @since 1.0.0
 */
function sensei_certificate_template_certificate_help_tab_content() {
	
	?>
	<p><strong><?php _e( 'Certificate Name', 'sensei-certificates' ) ?></strong> - <?php _e( 'All certificate templates must be given a name.  This will be used to identify the certificate within the admin.', 'sensei-certificates' ) ?></p>
	<p><strong><?php _e( 'Certificate Background Image', 'sensei-certificates' ) ?></strong> - <?php _e( 'This is the main image for your certificate, and will be used to configure the layout of the various text fields defined in the Certificate Data panel.', 'sensei-certificates' ) ?></p>
	<p><strong><?php _e( 'Certificate Data', 'sensei-certificates' ) ?></strong> - <?php _e( 'These configuration options allow you to specify exactly where various text fields will be displayed on your certificate, as well as the font used.  For instance, if you want the message displayed on your certificate, click the "Set Position" button next to "Message Position".  Then select the area of the Certificate Image where you want the message to be displayed.', 'sensei-certificates' ) ?></p>
	<p><?php _e( 'You can define a default font, size, style and color to be used for the certificate text fields.  For each individual text field, you can override these defaults by setting a specific font/style, size or color.  Note that the default font style (Italic/Bold) will only be used if a font is not selected at the field level.', 'sensei-certificates' ) ?></p>
	<p><strong><?php _e( 'Previewing', 'sensei-certificates' ) ?></strong> - <?php _e( 'You must update the certificate template to see any changes in the Preview.', 'sensei-certificates' ) ?></p>
	<?php

} // End sensei_certificate_template_certificate_help_tab_content()


/**
 * Renders the "How To" help tab content for the contextual help menu
 *
 * @since 1.0.0
 */
function sensei_certificate_template_how_to_help_tab_content() {
	
	?>
	<p><strong><?php _e( 'How to Create Your First Certificate Template ', 'sensei-certificates' ) ?></strong></p>
	<ol>
		<li><?php _e( 'First go to Sensei &gt; Certificate Templates and click "Add Certificate Template" to add a template', 'sensei-certificates' ); ?></li>
		<li><?php _e( 'Set a Certificate Name, and Certificate Background Image.  Optionally configure and add some Certificate Data fields (see the "Editing a Certificate" section for more details)', 'sensei-certificates' ); ?></li>
		<li><?php _e( 'Next click "Publish" to save your certificate template.  You can also optionally "Preview" the certificate to check your work and field layout.', 'sensei-certificates' ); ?></li>
		<li><?php _e( 'Next go to Sensei &gt; All Courses and either create a new course or edit an existing one, and assigning the template you created to the course.', 'sensei-certificates' ); ?></li>
		<li><?php _e( 'Your learners can now earn a Certificate when they have completed a course! Your existing learners who have already completed a course will automatically have certificates generated for them when you installed the plugin.', 'sensei-certificates' ); ?></li>
	</ol>
	<?php

} // End sensei_certificate_template_how_to_help_tab_content()


/**
 * Enqueue the certificates admin scripts
 *
 * @since 1.0.0
 */
function sensei_certificate_template_admin_enqueue_scripts() {

	global $post, $woothemes_sensei_certificates, $wp_version;

	// Get admin screen id
	$screen = get_current_screen();

	// Certificate Template admin pages
	if ( 'certificate_template' == $screen->id ) {

		// color picker script/styles
		wp_enqueue_script( 'wp-color-picker' );
		wp_enqueue_style( 'wp-color-picker' );

		wp_enqueue_media();

		// image area select, for selecting the certificate fields
		wp_enqueue_script( 'imgareaselect' );
		wp_enqueue_style( 'imgareaselect' );

	} // End If Statement

	if ( in_array( $screen->id, array( 'certificate_template' ) ) ) {

		// default javascript params
		$sensei_certificate_templates_params = array( 'primary_image_width' => '', 'primary_image_height' => '' );

		if ( 'certificate_template' == $screen->id ) {
			
			// get the primary image dimensions (if any) which are needed for the page script
			$attachment = null;
			$image_ids = get_post_meta( $post->ID, '_image_ids', true );

			if ( is_array( $image_ids ) && isset( $image_ids[0] ) && $image_ids[0] ) {
				$attachment = wp_get_attachment_metadata( $image_ids[0] );
			} // End If Statement

			// pass parameters into the javascript file
			$sensei_certificate_templates_params = array(
				'done_label'           => __( 'Done', 'sensei-certificates' ),
				'set_position_label'   => __( 'Set Position', 'sensei-certificates' ),
				'post_id'              => $post->ID,
				'primary_image_width'  => isset( $attachment['width']  ) && $attachment['width']  ? $attachment['width']  : '0',
				'primary_image_height' => isset( $attachment['height'] ) && $attachment['height'] ? $attachment['height'] : '0',
			 );

		} // End If Statement

		wp_enqueue_script( 'sensei_certificate_templates_admin', $woothemes_sensei_certificates->plugin_url . 'assets/js/admin.js', array( 'jquery' ) );
		wp_localize_script( 'sensei_certificate_templates_admin', 'sensei_certificate_templates_params', $sensei_certificate_templates_params );

		wp_enqueue_style( 'sensei_certificate_templates_admin_styles', $woothemes_sensei_certificates->plugin_url . '/assets/css/admin.css' );

	} // End If Statement

	if ( in_array( $screen->id, array( 'course' ) ) ) {

		wp_enqueue_script( 'sensei_course_certificate_templates_admin', $woothemes_sensei_certificates->plugin_url . 'assets/js/course.js', array( 'jquery', 'woosensei-lesson-metadata', 'woosensei-lesson-chosen' ) );

	} // End If Statement

} // End sensei_certificate_template_admin_enqueue_scripts()


/**
 * Set the product updated messages so they're specific to the certificates
 *
 * @since 1.0.0
 */
function sensei_certificate_template_item_updated_messages( $messages ) {

	global $post, $post_ID;

	$messages['certificate_template'] = array(
		0 => '', // Unused. Messages start at index 1.
		1 => __( 'Certificate Template updated.', 'sensei-certificates' ),
		2 => __( 'Custom field updated.', 'sensei-certificates' ),
		3 => __( 'Custom field deleted.', 'sensei-certificates' ),
		4 => __( 'Certificate Template updated.', 'sensei-certificates'),
		5 => isset( $_GET['revision'] ) ? sprintf( __( 'Certificate Template restored to revision from %s', 'sensei-certificates' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
		6 => __( 'Certificate Template updated.', 'sensei-certificates' ),
		7 => __( 'Certificate Template saved.', 'sensei-certificates' ),
		8 => __( 'Certificate Template submitted.', 'sensei-certificates' ),
		9 => sprintf( __( 'Certificate Template scheduled for: <strong>%1$s</strong>.', 'sensei-certificates' ),
		  date_i18n( __( 'M j, Y @ G:i', 'sensei-certificates' ), strtotime( $post->post_date ) ) ),
		10 => __( 'Certificate Template draft updated.', 'sensei-certificates'),
	);

	return $messages;

} // End sensei_certificate_template_item_updated_messages()
