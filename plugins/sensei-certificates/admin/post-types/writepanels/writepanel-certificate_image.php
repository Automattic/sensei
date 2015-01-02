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
 * - certificate_template_image_meta_box()
 * - certificate_template_process_images_meta()
 */

/**
 * Functions for displaying the certificate primary image meta box
 *
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Actions and Filters
 */
add_action( 'sensei_process_certificate_template_meta', 'certificate_template_process_images_meta', 10, 2 );

/**
 * Display the certificate image meta box
 * Fluid image reference: http://unstoppablerobotninja.com/entry/fluid-images
 *
 * @since 1.0.0
 */
function certificate_template_image_meta_box() {

	global $post, $woocommerce;

	$image_src = '';
	$image_id  = '';

	$image_ids = get_post_meta( $post->ID, '_image_ids', true );

	if ( is_array( $image_ids ) && count( $image_ids ) > 0 ) {
		$image_id = $image_ids[0];
		$image_src = wp_get_attachment_url( $image_id );
	} // End If Statement

	$attachment = wp_get_attachment_metadata( $image_id );

	?>
	<div id="certificate_image_wrapper" style="position:relative;">
		<img id="certificate_image_0" src="<?php echo $image_src ?>" style="max-width:100%;" />
	</div>
	<input type="hidden" name="upload_image_id[0]" id="upload_image_id_0" value="<?php echo $image_id; ?>" />
	<p>
		<a title="<?php esc_attr_e( 'Set certificate image', 'sensei-certificates' ) ?>" href="#" id="set-certificate-image"><?php _e( 'Set certificate image', 'sensei-certificates' ) ?></a>
		<a title="<?php esc_attr_e( 'Remove certificate image', 'sensei-certificates' ) ?>" href="#" id="remove-certificate-image" style="<?php echo ( ! $image_id ? 'display:none;' : '' ); ?>"><?php _e( 'Remove certificate image', 'sensei-certificates' ) ?></a>
	</p>
	<?php

} // End certificate_template_image_meta_box()


/**
 * Certificate Templates Images Data Save
 *
 * Function for processing and storing certificate template images
 *
 * @since 1.0.0
 * @param int $post_id the certificate template id
 * @param object $post the certificate template post object
 */
function certificate_template_process_images_meta( $post_id, $post ) {

	// handle the image_ids meta, which will always have at least an index 0 for the main template image, even if the value is empty
	$image_ids = array();
	foreach ( $_POST['upload_image_id'] as $i => $image_id ) {

		if ( 0 == $i || $image_id ) {
			$image_ids[] = $image_id;
		} // End If Statement

	} // End For Loop

	update_post_meta( $post_id, '_image_ids', $image_ids );

	if ( $image_ids[0] )
		set_post_thumbnail( $post_id, $image_ids[0] );
	else
		delete_post_thumbnail( $post_id );

} // End certificate_template_process_images_meta()
