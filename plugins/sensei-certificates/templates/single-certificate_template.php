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
 * The template for displaying certificate template previews.  This isn't a page template in
 * the regular sense, instead it streams the certificate template PDF to the client.  The
 * certificate is created with default field data.  The background image at least must be set.
 *
 * @since 1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

global $post, $woothemes_sensei_certificate_templates;

if ( $woothemes_sensei_certificate_templates->get_image_id() ) {
	// stream the example certificate pdf
	$woothemes_sensei_certificate_templates->generate_pdf();
	exit;
} else {
	wp_die( __( 'You must set a certificate_template primary image before you can preview', 'sensei-certificates' ) );
}
