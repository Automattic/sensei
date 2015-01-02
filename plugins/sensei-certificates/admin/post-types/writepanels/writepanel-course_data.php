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
 * - course_certificate_template_data_meta_box()
 * - course_certificate_templates_process_meta()
 */

/**
 * Functions for displaying the course certificates templates data meta box
 *
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Actions and Filters
 */
add_action( 'sensei_process_course_certificate_template_meta', 'course_certificate_templates_process_meta', 10, 2 );

/**
 * Certificates data meta box
 *
 * Displays the meta box
 *
 * @since 1.0.0
 */
function course_certificate_template_data_meta_box( $post ) {

	global $post;

		$select_certificate_template = get_post_meta( $post->ID, '_course_certificate_template', true );

		$post_args = array(	'post_type' 		=> 'certificate_template',
							'post_status' 		=> 'private',
							'numberposts' 		=> -1,
							'orderby'         	=> 'title',
    						'order'           	=> 'DESC',
    						'exclude' 			=> $post->ID,
							'suppress_filters' 	=> 0
							);
		$posts_array = get_posts( $post_args );

		$html = '';

		$html .= '<input type="hidden" name="' . esc_attr( 'woo_course_noonce' ) . '" id="' . esc_attr( 'woo_course_noonce' ) . '" value="' . esc_attr( wp_create_nonce( plugin_basename(__FILE__) ) ) . '" />';

		if ( count( $posts_array ) > 0 ) {
			$html .= '<select id="course-certificate-template-options" name="course_certificate_template" class="widefat">' . "\n";
			$html .= '<option value="">' . __( 'None', 'sensei-certificates' ) . '</option>';
				foreach ($posts_array as $post_item){
					$html .= '<option value="' . esc_attr( absint( $post_item->ID ) ) . '"' . selected( $post_item->ID, $select_certificate_template, false ) . '>' . esc_html( $post_item->post_title ) . '</option>' . "\n";
				} // End For Loop
			$html .= '</select>' . "\n";
		} else {
			$html .= '<p>' . esc_html( __( 'No certificate template exist yet. Please add some first.', 'sensei-certificates' ) ) . '</p>';
		} // End If Statement

		echo $html;

} // End course_certificate_templates_data_meta_box()


/**
 * Course Certificate Template Data Save
 *
 * Function for processing and storing all course certificate data.
 *
 * @since 1.0.0
 * @param int $post_id the certificate id
 * @param object $post the certificate post object
 */
function course_certificate_templates_process_meta( $post_id ) {

	global $woothemes_sensei_certificate_templates;

	/* Verify the nonce before proceeding. */
	if ( ( get_post_type() != 'course' ) ) {
		return $post_id;
	} // End If Statement

	$woothemes_sensei_certificate_templates->save_post_meta( 'course_certificate_template', $post_id );

} // End course_certificate_templates_process_meta()