<?php
/**
 * Plugin Name: Sensei Certificates
 * Plugin URI: http://www.woothemes.com/products/sensei-certifcates
 * Description: Sensei extension that allows students to download PDF certificates after they complete courses
 * Version: 1.0.5
 * Author: WooThemes
 * Author URI: http://www.woothemes.com
 * License: GPLv3
 */

/**
 * TABLE OF CONTENTS
 *
 * - Required functions
 * - Plugin Updates
 * - Actions and Filters
 * - init_certificates_textdomain()
 * - init_sensei_certificates()
 * - sensei_certificates_install()
 * - sensei_certificates_updates_list()
 * - sensei_update_users_certificate_data()
 * - sensei_create_master_certificate_template()
 * - is_sensei_active()
 */

/**
 * Required functions
 */
if ( ! function_exists( 'woothemes_queue_update' ) )
	require_once( 'woo-includes/woo-functions.php' );


/**
 * Plugin updates
 */
woothemes_queue_update( plugin_basename( __FILE__ ), '625ee5fe1bf36b4c741ab07507ba2ffd', '247548' );


/**
 * Actions and Filters
 */
add_action('plugins_loaded', 'init_certificates_textdomain');
add_action( 'plugins_loaded', 'init_sensei_certificates', 0 );
add_filter( 'sensei_upgrade_functions', 'sensei_certificates_updates_list', 10, 1);
register_activation_hook( __FILE__, 'sensei_certificates_install' );


/**
 * init_certificates_textdomain localization
 * @since  1.0.0
 * @return void
 */
function init_certificates_textdomain() {
	load_plugin_textdomain( 'sensei-certificates', false, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );
} // End init_certificates_textdomain()


/**
 * init_sensei_certificates function.
 * @since  1.0.0
 * @return void
 */
function init_sensei_certificates() {

	if ( is_sensei_active() ) {
		require_once( 'classes/class-woothemes-sensei-certificates.php' );
		$GLOBALS['woothemes_sensei_certificates'] = new WooThemes_Sensei_Certificates( __FILE__ );
		require_once( 'classes/class-woothemes-sensei-certificate-templates.php' );
		$GLOBALS['woothemes_sensei_certificate_templates'] = new WooThemes_Sensei_Certificate_Templates( __FILE__ );
	}

} // End init_sensei_extension()


/**
 * install function to generate cert hashes
 * @since  1.0.0
 * @return string
 */
function sensei_certificates_install() {

	global $woothemes_sensei;

	// Check if the installer has already been run
	$sensei_certificates_user_data_installed = get_option( 'sensei_certificate_user_data_installer', false );
	$sensei_certificate_templates_installed = get_option( 'sensei_certificate_templates_installer', false );
	$user_data_installed = false;
	$template_installed = false;

	$user_count = count_users();
	$total_users = intval( $user_count['total_users'] );

	if ( !$sensei_certificates_user_data_installed && 1000 >= $total_users ) {

		// Create the example Certificate Template
		$user_data_installed = sensei_update_users_certificate_data( $total_users, 0 );
		update_option( 'sensei_certificate_user_data_installer', $user_data_installed );

	} // End If Statement

	if ( !$sensei_certificate_templates_installed ) {

		// Create the example Certificate Template
		$template_installed = sensei_create_master_certificate_template();
		update_option( 'sensei_certificate_templates_installer', $template_installed );

	} // End If Statement

	// Register post types, so we can flush the rewrite rules. This should be refactored to all happen in the main class, at a later stage.
	$args = array(
	    'public' => true,
	    'publicly_queryable' => true,
	    'query_var' => true,
	    'rewrite' => array( 'slug' => esc_attr( apply_filters( 'sensei_certificates_slug', 'certificate' ) ) , 'with_front' => true, 'feeds' => true, 'pages' => true ),
	    'has_archive' => false
	);

	register_post_type( 'certificate', $args );

	$args = array(
	    'public' => true,
	    'publicly_queryable' => true,
	    'query_var' => true,
	    'rewrite' => array( 'slug' => esc_attr( apply_filters( 'sensei_certificate_templates_slug', 'certificate-template' ) ) , 'with_front' => true, 'feeds' => true, 'pages' => true ),
	    'has_archive' => false
	);

	register_post_type( 'certificate_template', $args );

	flush_rewrite_rules();

} // End sensei_certificates_install()


/**
 * sensei_certificates_updates_list add sensei certificates updates to sensei list
 * @since  1.0.0
 * @param  array $updates list of existing updates
 * @return array $updates list of existing and new updates
 */
function sensei_certificates_updates_list( $updates ) {

	$updates['1.0.0'] = array( 	'auto'	=> array(),
								'manual' => array( 'sensei_update_users_certificate_data' => array(
																								'title' => 'Create Certificates',
																								'desc' => 'Creates certificates for learners who have already completed Courses.',
																								'product' => 'Sensei Certificates' ),
													'sensei_create_master_certificate_template' => array(
																								'title' => 'Create Master Certificate Template',
																								'desc' => 'Creates the master Certificate Template for all Courses.',
																								'product' => 'Sensei Certificates'
																									) )
												);

	return $updates;

} // End sensei_certificates_updates_list()

/**
 * sensei_update_users_certificate_data install user certificate data
 * @since  1.0.0
 * @param  int $n number of items to iterate through
 * @param  int $offeset number to offset iteration by
 * @return boolean
 */
function sensei_update_users_certificate_data( $n = 5, $offset = 0 ) {

	global $woothemes_sensei;

	$loop_ran = false;

	// Calculate if this is the last page
	if ( 0 == $offset ) {
		$current_page = 1;
	} else {
		$current_page = intval( $offset / $n );
	} // End If Statement

	$args_array = array(
			'number' => $n,
			'offset' => $offset,
			'orderby' => 'ID',
			'order' => 'DESC',
			'fields' => 'all_with_meta'
		);
	$wp_user_update = new WP_User_Query( $args_array );
	$users = $wp_user_update->get_results();

	$user_count = count_users();
	$total_items = $user_count['total_users'];

	$query_total = $wp_user_update->get_total();

	$total_pages = intval( $total_items / $n );

	foreach ( $users as $user_key => $user_item ) {

		$course_ids = WooThemes_Sensei_Utils::sensei_activity_ids( array( 'user_id' => $user_item->ID, 'type' => 'sensei_course_start' ) );
		$posts_array = array();
		if ( 0 < intval( count( $course_ids ) ) ) {
			$posts_array = $woothemes_sensei->post_types->course->course_query( -1, 'usercourses', $course_ids );
		} // End If Statement

		foreach ( $posts_array as $course_item ) {

			$course_end_date = WooThemes_Sensei_Utils::sensei_get_activity_value( array( 'post_id' => $course_item->ID, 'user_id' => $user_item->ID, 'type' => 'sensei_course_end', 'field' => 'comment_date' ) );

			if ( isset( $course_end_date ) && '' != $course_end_date ) {

				$args = array(
					'post_type' => 'certificate',
					'author' => $user_item->ID,
					'meta_key' => 'course_id',
					'meta_value' => $course_item->ID
				);
				$query = new WP_Query( $args );

				if ( ! $query->have_posts() ) {

					// Insert custom post type
					$cert_args = array(
						'post_author' => intval( $user_item->ID ),
						'post_title' => esc_html( substr( md5( $course_item->ID . $user_item->ID ), -8 ) ),
						'post_name' => esc_html( substr( md5( $course_item->ID . $user_item->ID ), -8 ) ),
						'post_type' => 'certificate',
						'post_status'   => 'publish'
					);
					$post_id = wp_insert_post( $cert_args, $wp_error = false );

					if ( ! is_wp_error( $post_id ) ) {
						add_post_meta( $post_id, 'course_id', intval( $course_item->ID ) );
						add_post_meta( $post_id, 'learner_id', intval( $user_item->ID ) );
						add_post_meta( $post_id, 'certificate_hash',esc_html( substr( md5( $course_item->ID . $user_item->ID ), -8 ) ) );
						$loop_ran = true;
					} // End If Statement

				} // End If Statement

				wp_reset_query();

			} // End If Statement

		} // End For Loop

	} // End For Loop

	if ( $current_page >= $total_pages ) {
		return true;
	} else {
		return false;
	} // End If Statement

} // End sensei_update_users_certificate_data()


/**
 * sensei_create_master_certificate_template Creates the example Certificate Template and assigns to every Course
 * @since  1.0.0
 * @return boolean
 */
function sensei_create_master_certificate_template() {

	// Register Post Data
	$post = array();
	$post['post_status']   = 'private';
	$post['post_type']     = 'certificate_template';
	$post['post_title']    = __( 'Example Template', 'sensei-certificates' );
	$post['post_content']  = '';

	// Create Post
	$post_id = wp_insert_post( $post );

	$url = trailingslashit( plugins_url( '', __FILE__ ) ) . 'assets/images/sensei_certificate_nograde.png';

	$tmp = download_url( $url );
	$post_id = $post_id;
	$desc = __( 'Sensei Certificate Template Example', 'sensei-certificates' );

	// Set variables for storage
	// fix file filename for query strings
	preg_match('/[^\?]+\.(jpg|JPG|jpe|JPE|jpeg|JPEG|gif|GIF|png|PNG)/', $url, $matches);
	$file_array['name'] = basename($matches[0]);
	$file_array['tmp_name'] = $tmp;

	// If error storing temporarily, unlink
	if ( is_wp_error( $tmp ) ) {
		@unlink($file_array['tmp_name']);
		$file_array['tmp_name'] = '';
		error_log('An error occurred while uploading the image');
	} // End If Statement

	// do the validation and storage stuff
	$image_id = media_handle_sideload( $file_array, $post_id, $desc );

	// If error storing permanently, unlink
	if ( is_wp_error($image_id) ) {
		@unlink($file_array['tmp_name']);
		error_log('An error occurred while uploading the image');
	} // End If Statement

	$src = wp_get_attachment_url( $image_id );

	$defaults = array(	'_certificate_font_color' => '#000000',
						'_certificate_font_size' => '12',
						'_certificate_font_family' => 'Helvetica',
						'_certificate_font_style' => '',
						'_certificate_heading' => '',
						'_certificate_heading_pos' => '114,11,989,57',
						'_certificate_heading_font_color' => '#595959',
						'_certificate_heading_font_size' => '25',
						'_certificate_heading_font_family' => 'Helvetica',
						'_certificate_heading_font_style' => 'C',
						'_certificate_heading_text' => __( 'Certificate of Completion', 'sensei-certificates' ),
						'_certificate_message' => '',
						'_certificate_message_pos' => '110,306,996,167',
						'_certificate_message_font_color' => '#000000',
						'_certificate_message_font_size' => '36',
						'_certificate_message_font_family' => 'Helvetica',
						'_certificate_message_font_style' => 'BC',
						'_certificate_message_text' => __( 'This is to certify that', 'sensei-certificates' ) . " \r\n\r\n" . '{{learner}}' . " \r\n\r\n" . __( 'has completed the course', 'sensei-certificates' ),
						'_certificate_course' => '',
						'_certificate_course_pos' => '186,88,838,116',
						'_certificate_course_font_color' => '#000000',
						'_certificate_course_font_size' => '48',
						'_certificate_course_font_family' => 'Helvetica',
						'_certificate_course_font_style' => 'BCO',
						'_certificate_course_text' => __( '{{course_title}}', 'sensei-certificates' ),
						'_certificate_completion' => '',
						'_certificate_completion_pos' => '108,599,998,48',
						'_certificate_completion_font_color' => '#9e9e9e',
						'_certificate_completion_font_size' => '20',
						'_certificate_completion_font_family' => 'Helvetica',
						'_certificate_completion_font_style' => 'C',
						'_certificate_completion_text' => __( '{{completion_date}} at {{course_place}}', 'sensei-certificates' ),
						'_certificate_place' => '',
						'_certificate_place_pos' => '',
						'_certificate_place_font_color' => '#9e9e9e',
						'_certificate_place_font_size' => '20',
						'_certificate_place_font_family' => 'Helvetica',
						'_certificate_place_font_style' => '',
						'_certificate_place_text' => __( '{{course_place}}', 'sensei-certificates' )
						);


	// certificate template font defaults
	update_post_meta( $post_id, '_certificate_font_color',  $defaults['_certificate_font_color'] );
	update_post_meta( $post_id, '_certificate_font_size',   $defaults['_certificate_font_size'] );
	update_post_meta( $post_id, '_certificate_font_family', $defaults['_certificate_font_family'] );
	update_post_meta( $post_id, '_certificate_font_style',  $defaults['_certificate_font_style'] );

	// create the certificate template fields data structure
	$fields = array();
	foreach ( array( '_certificate_heading', '_certificate_message', '_certificate_course', '_certificate_completion', '_certificate_place' ) as $i => $field_name ) {
		// set the field defaults
		$field = array(
			'type'      => 'property',
			'font'     => array( 'family' => '', 'size' => '', 'style' => '', 'color' => '' ),
			'position' => array(),
			'order'    => $i,
		);

		// get the field position (if set)
		if ( $defaults[ $field_name . '_pos' ] ) {
			$position = explode( ',', $defaults[ $field_name . '_pos' ] );
			$field['position'] = array( 'x1' => $position[0], 'y1' => $position[1], 'width' => $position[2], 'height' => $position[3] );
		}

		if ( $defaults[ $field_name . '_text' ] ) {
			$field['text'] = $defaults[ $field_name . '_text' ] ? $defaults[ $field_name . '_text' ] : '';
		}

		// get the field font settings (if any)
		if ( $defaults[ $field_name . '_font_family' ] )  $field['font']['family'] = $defaults[ $field_name . '_font_family' ];
		if ( $defaults[ $field_name . '_font_size' ] )    $field['font']['size']   = $defaults[ $field_name . '_font_size' ];
		if ( $defaults[ $field_name . '_font_style' ] )    $field['font']['style']   = $defaults[ $field_name . '_font_style' ];
		if ( $defaults[ $field_name . '_font_color' ] )   $field['font']['color']  = $defaults[ $field_name . '_font_color' ];

		// cut off the leading '_' to create the field name
		$fields[ ltrim( $field_name, '_' ) ] = $field;
	} // End For Loop

	update_post_meta( $post_id, '_certificate_template_fields', $fields );

	// Test attachment upload
	$image_ids = array();
	$image_ids[] = $image_id;
	update_post_meta( $post_id, '_image_ids', $image_ids );

	if ( $image_ids[0] ) {
		set_post_thumbnail( $post_id, $image_ids[0] );
	} // End If Statement

	// Set all courses to the default template
	$query_args['posts_per_page'] = -1;
	$query_args['post_status'] = 'any';
	$query_args['post_type'] = 'course';
	$the_query = new WP_Query($query_args);

	if ($the_query->have_posts()) {

		$count = 0;

		while ($the_query->have_posts()) {

			$the_query->the_post();

			update_post_meta( get_the_id(), '_course_certificate_template', $post_id );

		} // End While Loop

	} // End If Statement

	wp_reset_postdata();

	if ( 0 < $post_id ) {
		return true;
	} else {
		return false;
	} // End If Statement

} // End sensei_create_master_certificate_template()


/**
 * Functions used by plugins
 */
if ( ! class_exists( 'WooThemes_Sensei_Dependencies' ) )
  require_once 'woo-includes/class-woothemes-sensei-dependencies.php';


/**
 * Sensei Detection
 */
if ( ! function_exists( 'is_sensei_active' ) ) {
  function is_sensei_active() {
    return WooThemes_Sensei_Dependencies::sensei_active_check();
  } // End is_sensei_active()
} // End If Statement
