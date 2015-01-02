<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Sensei Certificates Main Class
 *
 * All functionality pertaining to the Certificates functionality in Sensei.
 *
 * @package WordPress
 * @subpackage Sensei
 * @category Extension
 * @author WooThemes
 * @since 1.0.0
 *
 * TABLE OF CONTENTS
 *
 * - __construct()
 * - plugin_path()
 * - certificates_settings_tabs()
 * - certificates_settings_fields()
 * - setup_certificates_post_type()
 * - post_type_custom_column_headings()
 * - post_type_custom_column_content()
 * - generate_certificate_number()
 * - can_view_certificate()
 * - download_certificate()
 * - certificate_text()
 * - certificate_backgroudn()
 * - get_certificate_font_settings()
 * - certificate_link()
 * - enqueue_styles()
 * - create_columns()
 * - populate_columns()
 * - add_inline_js()
 * - output_inline_js()
 * - include_sensei_scripts()
 * - reset_course_certificate()
 * - certificates_user_settings_form()
 * - certificcates_user_settings_save()
 * - certificates_user_settings_messages()
 */

class WooThemes_Sensei_Certificates {

	/**
	 * @var string url link to plugin files
	 */
	public $plugin_url;

	/**
	 * @var string path to the plugin files
	 */
	public $plugin_path;

	/**
	 * @var string inline js code
	 */
	public $_inline_js;

	/**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 * @since 1.0.0
	 */
	public function __construct( $file ) {

		global $woothemes_sensei;

		// Defaults
		$this->plugin_url = trailingslashit( plugins_url( '', $file ) );
		$this->plugin_path = plugin_dir_path( $file );

		// Hook onto Sensei settings and load a new tab with settings for extension
		add_filter( 'sensei_settings_tabs', array( $this, 'certificates_settings_tabs' ) );
		add_filter( 'sensei_settings_fields', array( $this, 'certificates_settings_fields' ) );

		// Setup post type
		add_action( 'init', array( $this, 'setup_certificates_post_type' ), 110 );
		add_filter( 'manage_edit-certificate_columns', array( $this, 'post_type_custom_column_headings' ) );
		add_action( 'manage_certificate_posts_custom_column', array( $this, 'post_type_custom_column_content' ), 10, 2 );

		/**
		 * FRONTEND
		 */
		add_filter( 'sensei_user_course_status_passed', array( $this, 'certificate_link' ), 10, 1 );

		// Remove in future version
		if( version_compare( $woothemes_sensei->version, '1.6', '<' ) ) {
		    add_filter( 'sensei_view_results_text', array( $this, 'certificate_link' ), 10, 1 );
		}
		add_filter( 'sensei_results_links', array( $this, 'certificate_link' ), 10, 1 );
		add_action( 'sensei_additional_styles', array( $this, 'enqueue_styles' ) );
		add_action( 'sensei_user_lesson_reset', array( $this, 'reset_lesson_course_certificate' ), 10, 2 );
		add_action( 'sensei_user_course_reset', array( $this, 'reset_course_certificate' ), 10, 2 );

		// Create certificate endpoint and handle generation of pdf certificate
		add_action( 'template_redirect', array( $this, 'download_certificate' ) );

		// User settings output and save handling
		add_action( 'sensei_learner_profile_info', array( $this, 'certificates_user_settings_form' ), 10, 1 );
		add_action( 'sensei_complete_course', array( $this, 'certificates_user_settings_save' ), 10 );
		add_action( 'sensei_frontend_messages', array( $this, 'certificates_user_settings_messages' ), 10 );

		/**
		 * BACKEND
		 */
		if ( is_admin() ) {
			// Add Certificates Menu
			add_action( 'sensei_analysis_course_columns', array( $this, 'create_columns' ), 10, 2 );
			add_action( 'sensei_analysis_course_column_data', array( $this, 'populate_columns' ), 10, 3 );
			add_action( 'admin_footer', array( $this, 'output_inline_js' ), 25 );
			add_filter( 'sensei_scripts_allowed_post_types', array( $this, 'include_sensei_scripts' ), 10, 1 );

			// We don't need a WordPress SEO meta box for certificates and certificate templates. Hide it.
			add_filter( 'option_wpseo_titles', array( $this, 'force_hide_wpseo_meta_box' ) );
		}

		// Generate certificate hash when course is completed.
		add_action( 'sensei_log_activity_after', array( $this, 'generate_certificate_number' ), 10, 2 );
		// Background Image to display on certificate
		add_action( 'sensei_certificates_set_background_image', array( $this, 'certificate_background' ), 10, 1 );
		// Text to display on certificate
		add_action( 'sensei_certificates_before_pdf_output', array( $this, 'certificate_text' ), 10, 2 );

	} // End __construct()

	/**
	 * Force the WordPress SEO meta box to be turned off for the "certificate" and "certificate_template" post types.
	 * @access  public
	 * @since   1.0.1
	 * @param   array $value WordPress SEO wpseo_titles option.
	 * @return  array        Modified array.
	 */
	public function force_hide_wpseo_meta_box ( $value ) {
		if ( is_array( $value ) ) {
			$value['hideeditbox-certificate'] = 'on';
			$value['hideeditbox-certificate_template'] = 'on';
		}

		return $value;
	} // End force_hide_wpseo_meta_box()

	/**
	 * plugin_path function
	 *
	 * @access public
	 * @since  1.0.0
	 * @return string
	 */
	public function plugin_path() {

		if ( $this->plugin_path ) return $this->plugin_path;

		return $this->plugin_path = untrailingslashit( plugin_dir_path( dirname( __FILE__ ) ) );

	} // End plugin_path()


	/**
	 * certificates_settings_tabs function for settings tabs
	 *
	 * @access public
	 * @param  $sections array
	 * @since  1.0.0
	 * @return $sections array
	 */
	public function certificates_settings_tabs( $sections ) {

		$sections['certificate-settings'] = array(
			'name' 			=> __( 'Certificate Settings', 'sensei-certificates' ),
			'description'	=> __( 'Options for the Certificate Extension.', 'sensei-certificates' )
		);

		return $sections;

	} // End certificates_settings_tabs()

	/**
	 * certificates_settings_fields function for settings fields
	 *
	 * @access public
	 * @param  $fields array
	 * @since  1.0.0
	 * @return $fields array
	 */
	public function certificates_settings_fields( $fields ) {

		$fields['certificates_view_courses'] = array(
			'name' 			=> __( 'View in Courses', 'sensei-certificates' ),
			'description' 	=> __( 'Show a View Certificate link in the single Course page and the My Courses page.', 'sensei-certificates' ),
			'type' 			=> 'checkbox',
			'default' 		=> true,
			'section' 		=> 'certificate-settings'
		);

		$fields['certificates_view_profile'] = array(
			'name' 			=> __( 'View in Learner Profile', 'sensei-certificates' ),
			'description' 	=> __( 'Show a View Certificate link in the Learner Profile page.', 'sensei-certificates' ),
			'type' 			=> 'checkbox',
			'default' 		=> true,
			'section' 		=> 'certificate-settings'
		);

		$fields['certificates_public_viewable'] = array(
			'name' 			=> __( 'Public Certificate', 'sensei-certificates' ),
			'description' 	=> __( 'Allow the Learner to share their Certificate with the public.', 'sensei-certificates' ),
			'type' 			=> 'checkbox',
			'default' 		=> true,
			'section' 		=> 'certificate-settings'
		);

		return $fields;

	} // End certificates_settings_fields()

	/**
	 * Setup the certificate post type, it's admin menu item and the appropriate labels and permissions.
	 *
	 * @access public
	 * @since  1.0.0
	 * @uses  global $woothemes_sensei
	 * @return void
	 */
	public function setup_certificates_post_type () {

		global $woothemes_sensei;

		$args = array(
		    'labels' => array(
			    'name' => sprintf( _x( '%s', 'post type general name', 'sensei-certificates' ), 'Certificates' ),
			    'singular_name' => sprintf( _x( '%s', 'post type singular name', 'sensei-certificates' ), 'Certificate' ),
			    'add_new' => sprintf( _x( 'Add New %s', 'post type add_new', 'sensei-certificates' ), 'Certificate' ),
			    'add_new_item' => sprintf( __( 'Add New %s', 'sensei-certificates' ), 'Certificate' ),
			    'edit_item' => sprintf( __( 'Edit %s', 'sensei-certificates' ), 'Certificate' ),
			    'new_item' => sprintf( __( 'New %s', 'sensei-certificates' ), 'Certificate' ),
			    'all_items' => sprintf( __( '%s', 'sensei-certificates' ), 'Certificates' ),
			    'view_item' => sprintf( __( 'View %s', 'sensei-certificates' ), 'Certificate' ),
			    'search_items' => sprintf( __( 'Search %s', 'sensei-certificates' ), 'Certificates' ),
			    'not_found' =>  sprintf( __( 'No %s found', 'sensei-certificates' ), strtolower( 'Certificates' ) ),
			    'not_found_in_trash' => sprintf( __( 'No %s found in Trash', 'sensei-certificates' ), strtolower( 'Certificates' ) ),
			    'parent_item_colon' => '',
			    'menu_name' => sprintf( __( '%s', 'sensei-certificates' ), 'Certificates' )
			),
		    'public' => true,
		    'publicly_queryable' => true,
		    'show_ui' => true,
		    'show_in_menu' => 'edit.php?post_type=lesson',
		    'query_var' => true,
		    'rewrite' => array( 'slug' => esc_attr( apply_filters( 'sensei_certificates_slug', 'certificate' ) ) , 'with_front' => true, 'feeds' => true, 'pages' => true ),
		    'map_meta_cap' => true,
		    'has_archive' => false,
		    'hierarchical' => false,
		    'menu_icon' => esc_url( $woothemes_sensei->plugin_url . 'assets/images/certificate.png' ),
		    'supports' => array( 'title', 'custom-fields' )
		);

		register_post_type( 'certificate', $args );

	} // End setup_certificates_post_type()


	/**
	 * post_type_custom_column_headings function.
	 *
	 * @access public
	 * @since  1.0.0
	 * @param  array $defaults default values
	 * @return array $defaults modified values
	 */
	public function post_type_custom_column_headings( $defaults ) {

		unset( $defaults['date'] );
		$defaults['learner'] = __( 'Learner', 'sensei-certificates' );
		$defaults['course'] = __( 'Course', 'sensei-certificates' );
		$defaults['date_completed'] = __( 'Date Completed', 'sensei-certificates' );
		$defaults['actions'] = __( 'Actions', 'sensei-certificates' );

    	return $defaults;

	} // End post_type_custom_column_headings()


	/**
	 * post_type_custom_column_content function.
	 *
	 * @access public
	 * @since  1.0.0
	 * @param  string  $column_name
	 * @param  int $post_ID post id
	 * @return void
	 */
	public function post_type_custom_column_content( $column_name, $post_ID ) {

		$user_id = get_post_meta( $post_ID, $key = 'learner_id', true );
		$course_id = get_post_meta( $post_ID, $key = 'course_id', true );
		$user = get_userdata( $user_id );
		$course = get_post( $course_id );
		$course_end_date = WooThemes_Sensei_Utils::sensei_get_activity_value( array( 'post_id' => intval( $course_id ), 'user_id' => intval( $user_id ), 'type' => 'sensei_course_end', 'field' => 'comment_date' ) );
		$certificate_hash = esc_html( substr( md5( $course_id . $user_id ), -8 ) );

		switch ( $column_name ) {
			case "learner" :
				echo '<a href="' . add_query_arg( array( 'page' => 'sensei_analysis', 'user' => intval( $user_id ), 'course_id' => intval( $course_id ) ), admin_url( 'edit.php?post_type=lesson' ) ) . '">'.$user->user_login.'</a>';
				break;
			case "course" :
				echo '<a href="' . add_query_arg( array( 'page' => 'sensei_analysis', 'course_id' => intval( $course_id ) ), admin_url( 'edit.php?post_type=lesson' ) ) . '">'.$course->post_title.'</a>';
				break;
			case "date_completed" :
				echo $course_end_date;
				break;
			case "actions" :
				echo '<a href="' . get_permalink( $post_ID ) . '" target="_blank">'. __( 'View Certificate', 'sensei-certificates' ) . '</a>';
				break;
		} // End Switch Statement

	} // End post_type_custom_column_content()


	/**
	 * Generate unique certificate hash and save as comment.
	 *
	 * @access public
	 * @since  1.0.0
	 * @param  array $args arguments for queries
	 * @param  array $data data to post
	 * @return void
	 */
	public function generate_certificate_number( $args, $data ) {

		if ( isset( $args['type'] ) && $args['type'] == 'sensei_course_end' ) {
			$cert_args = array(
				'post_id' => $args['post_id'],
				'username' => $args['username'],
				'user_email' => $args['user_email'],
				'user_url' => $args['user_url'],
				'data' => substr( md5( $args['post_id'] . $args['user_id'] ), -8 ), // Use last 8 chars of hash only
				'type' => 'sensei_certificate', /* FIELD SIZE 20 */
				'parent' => 0,
				'user_id' => $args['user_id'],
				'action' => 'update'
			);

			$time = current_time('mysql');
			$data = array(
				'comment_post_ID' => intval( $args['post_id'] ),
				'comment_author' => sanitize_user( $args['username'] ),
				'comment_author_email' => sanitize_email( $args['user_email'] ),
				'comment_author_url' => esc_url( $args['user_url'] ),
				'comment_content' => esc_html( substr( md5( $args['post_id'] . $args['user_id'] ), -8 ) ),
				'comment_type' => 'sensei_certificate',
				'comment_parent' => 0,
				'user_id' => intval( $args['user_id'] ),
				'comment_date' => $time,
				'comment_approved' => 1,
			);
			//$activity_logged = WooThemes_Sensei_Utils::sensei_log_activity( $cert_args );

			// custom post type
			$cert_args = array(
				'post_author' => intval( $args['user_id'] ),
				'post_title' => esc_html( substr( md5( $args['post_id'] . $args['user_id'] ), -8 ) ),
				'post_name' => esc_html( substr( md5( $args['post_id'] . $args['user_id'] ), -8 ) ),
				'post_type' => 'certificate',
				'post_status'   => 'publish'
			);
			$post_id = wp_insert_post( $cert_args, $wp_error = false );

			if ( ! is_wp_error( $post_id ) ) {

				add_post_meta( $post_id, 'course_id', intval( $args['post_id'] ) );
				add_post_meta( $post_id, 'learner_id', intval( $args['user_id'] ) );
				add_post_meta( $post_id, 'certificate_hash', esc_html( substr( md5( $args['post_id'] . $args['user_id'] ), -8 ) ) );

			} // End If Statement

		} // End If Statement

	} // End generate_certificate_number()


	/**
	 * Check if certificate is viewable
	 *
	 * @access public
	 * @since  1.0.0
	 * @return boolean
	 */
	public function can_view_certificate( $certificate_id = 0 ) {

		global $woothemes_sensei, $post, $current_user;
		get_currentuserinfo();

		$response = false;

		if ( 0 >= intval( $certificate_id ) ) return false; // We require a certificate ID value.

		$learner_id = get_post_meta( intval( $certificate_id ), 'learner_id', true );

		// Check if student can only view certificate
		$grant_access = $woothemes_sensei->settings->settings['certificates_public_viewable'];
		$grant_access_user = get_user_option( 'sensei_certificates_view_by_public', $learner_id );

		// If we can view certificates, get out.
		if ( true == (bool)$grant_access_user || ( false == (bool)$grant_access && true == (bool)$grant_access_user ) || current_user_can( 'manage_options' ) ) return true;

		if ( isset( $current_user->ID ) && ( intval( $current_user->ID ) === intval( $learner_id ) ) )  {
			$response = true;
		}

		return $response;

	} // End can_view_certificate()


	/**
	 * Download the certificate
	 *
	 * @access public
	 * @since  1.0.0
	 * @return void
	 */
	public function download_certificate() {

		global $woothemes_sensei, $post;

		if ( ! is_singular() || 'certificate' != get_post_type() ) return;

		if ( $this->can_view_certificate( get_the_ID() ) ) {

			$hash = $post->post_slug;
			$hash_meta = get_post_meta( get_the_ID(), 'certificate_hash', true );
			if ( ! empty( $hash_meta ) && 8 >= strlen( $hash_meta ) ) $hash = $hash_meta;

			// Generate the certificate here
			require_once( 'class-woothemes-sensei-pdf-certificate.php' );
			$pdf = new WooThemes_Sensei_PDF_Certificate( $hash );
			$pdf->generate_pdf();
			exit;

		} else {

			wp_die( __( 'You are not allowed to view this Certificate.', 'sensei-certificates' ), __( 'Certificate Error', 'sensei-certificates' ) );

		} // End If Statement

	} // End generate_certificate()


	/**
	 * Add text to the certificate
	 *
	 * @access public
	 * @since  1.0.0
	 * @return void
	 */
	public function certificate_text( $pdf_certificate, $fpdf ) {

		global $woothemes_sensei;

		$show_border = apply_filters( 'woothemes_sensei_certificates_show_border', 0 );
		$start_position = 200;

		// Find certificate based on hash
		$args = array(
			'post_type' => 'certificate',
			'meta_key' => 'certificate_hash',
			'meta_value' => $pdf_certificate->hash
		);

		$query = new WP_Query( $args );
		$certificate_id = 0;

		if ( $query->have_posts() ) {

			$query->the_post();
			$certificate_id = $query->posts[0]->ID;

		} // End If Statement

		wp_reset_query();

		if ( 0 < intval( $certificate_id ) ) {

			// Get Student Data
			$user_id = get_post_meta( $certificate_id, 'learner_id', true );
			$student = get_userdata( $user_id );
			$student_name = $student->display_name;

			// Get Course Data
			$course_id = get_post_meta( $certificate_id, 'course_id', true );
			$course = $woothemes_sensei->post_types->course->course_query( -1, 'usercourses', $course_id );
			$course = $course[0];
			$course_end_date = $course_end_date = WooThemes_Sensei_Utils::sensei_get_activity_value( array( 'post_id' => $course_id, 'user_id' => $user_id, 'type' => 'sensei_course_end', 'field' => 'comment_date' ) );

			// Get the certificate template
			$certificate_template_id = get_post_meta( $course_id, '_course_certificate_template', true );

			$certificate_template_custom_fields = get_post_custom( $certificate_template_id );

			// Define the data we're going to load: Key => Default value
			$load_data = array(
				'certificate_font_style'	=> array(),
				'certificate_font_color'	=> array(),
				'certificate_font_size'	=> array(),
				'certificate_font_family'	=> array(),
				'image_ids'            => array(),
				'certificate_template_fields'       => array(),
			);

			// Load the data from the custom fields
			foreach ( $load_data as $key => $default ) {

				// set value from db (unserialized if needed) or use default
				$this->$key = ( isset( $certificate_template_custom_fields[ '_' . $key ][0] ) && '' !== $certificate_template_custom_fields[ '_' . $key ][0] ) ? ( is_array( $default ) ? maybe_unserialize( $certificate_template_custom_fields[ '_' . $key ][0] ) : $certificate_template_custom_fields[ '_' . $key ][0] ) : $default;

			} // End For Loop

			// Set default fonts
			if ( isset( $this->certificate_font_color ) && '' != $this->certificate_font_color ) { $pdf_certificate->certificate_pdf_data['font_color'] = $this->certificate_font_color; }
			if ( isset( $this->certificate_font_size ) && '' != $this->certificate_font_size ) { $pdf_certificate->certificate_pdf_data['font_size'] = $this->certificate_font_size; }
			if ( isset( $this->certificate_font_family ) && '' != $this->certificate_font_family ) { $pdf_certificate->certificate_pdf_data['font_family'] = $this->certificate_font_family; }
			if ( isset( $this->certificate_font_style ) && '' != $this->certificate_font_style ) { $pdf_certificate->certificate_pdf_data['font_style'] = $this->certificate_font_style; }

			$certificate_heading = __( 'Certificate of Completion', 'sensei-certificates' ); // Certificate of Completion
			if ( isset( $this->certificate_template_fields['certificate_heading']['text'] ) && '' != $this->certificate_template_fields['certificate_heading']['text'] ) {

				$certificate_heading = $this->certificate_template_fields['certificate_heading']['text'];
				$certificate_heading = str_replace( array( '{{learner}}', '{{course_title}}', '{{completion_date}}', '{{course_place}}'  ), array( $student_name, $course->post_title, date( 'jS F Y', strtotime( $course_end_date ) ), get_bloginfo( 'name' ) ) , $certificate_heading );
			} // End If Statement

			$certificate_message = __( 'This is to certify that', 'sensei-certificates' ) . " \r\n\r\n" . $student_name . " \r\n\r\n" . __( 'has completed the course', 'sensei-certificates' ); // This is to certify that {{learner}} has completed the course
			if ( isset( $this->certificate_template_fields['certificate_message']['text'] ) && '' != $this->certificate_template_fields['certificate_message']['text'] ) {

				$certificate_message = $this->certificate_template_fields['certificate_message']['text'];
				$certificate_message = str_replace( array( '{{learner}}', '{{course_title}}', '{{completion_date}}', '{{course_place}}'  ), array( $student_name, $course->post_title, date( 'jS F Y', strtotime( $course_end_date ) ), get_bloginfo( 'name' ) ) , $certificate_message );

			} // End If Statement

			$certificate_course = $course->post_title; // {{course_title}}
			if ( isset( $this->certificate_template_fields['certificate_course']['text'] ) && '' != $this->certificate_template_fields['certificate_course']['text'] ) {

				$certificate_course = $this->certificate_template_fields['certificate_course']['text'];
				$certificate_course = str_replace( array( '{{learner}}', '{{course_title}}', '{{completion_date}}', '{{course_place}}'  ), array( $student_name, $course->post_title, date( 'jS F Y', strtotime( $course_end_date ) ), get_bloginfo( 'name' ) ) , $certificate_course );

			} // End If Statement

			$certificate_completion = date( 'jS F Y', strtotime( $course_end_date ) ); // {{completion_date}}
			if ( isset( $this->certificate_template_fields['certificate_completion']['text'] ) && '' != $this->certificate_template_fields['certificate_completion']['text'] ) {

				$certificate_completion = $this->certificate_template_fields['certificate_completion']['text'];
				$certificate_completion = str_replace( array( '{{learner}}', '{{course_title}}', '{{completion_date}}', '{{course_place}}'  ), array( $student_name, $course->post_title, date( 'jS F Y', strtotime( $course_end_date ) ), get_bloginfo( 'name' ) ) , $certificate_completion );

			} // End If Statement

			$certificate_place = sprintf( __( 'At %s', 'sensei-certificates' ), get_bloginfo( 'name' ) ); // At {{course_place}}
			if ( isset( $this->certificate_template_fields['certificate_place']['text'] ) && '' != $this->certificate_template_fields['certificate_place']['text'] ) {

				$certificate_place = $this->certificate_template_fields['certificate_place']['text'];
				$certificate_place = str_replace( array( '{{learner}}', '{{course_title}}', '{{completion_date}}', '{{course_place}}'  ), array( $student_name, $course->post_title, date( 'jS F Y', strtotime( $course_end_date ) ), get_bloginfo( 'name' ) ) , $certificate_place );

			} // End If Statement

			$output_fields = array(	'certificate_heading' 		=> 'text_field',
									'certificate_message' 		=> 'textarea_field',
									'certificate_course'		=> 'text_field',
									'certificate_completion' 	=> 'text_field',
									'certificate_place' 		=> 'text_field',
								 );

			foreach ( $output_fields as $meta_key => $function_name ) {

				// Check if the field has a set position
				if ( isset( $this->certificate_template_fields[$meta_key]['position']['x1'] ) ) {

					$font_settings = $this->get_certificate_font_settings( $meta_key );

					call_user_func_array(array($pdf_certificate, $function_name), array( $fpdf, $$meta_key, $show_border, array( $this->certificate_template_fields[$meta_key]['position']['x1'], $this->certificate_template_fields[$meta_key]['position']['y1'], $this->certificate_template_fields[$meta_key]['position']['width'], $this->certificate_template_fields[$meta_key]['position']['height'] ), $font_settings ));

				} // End If Statement

			} // End For Loop

		} else {

			wp_die( __( 'The certificate you are searching for does not exist.', 'sensei-certificates' ), __( 'Certificate Error', 'sensei-certificates' ) );

		} // End If Statement

	} // End certificate_text()


	/**
	 * Add background to the certificate
	 *
	 * @access public
	 * @since  1.0.0
	 * @return void
	 */
	public function certificate_background( $pdf_certificate ) {

		global $woothemes_sensei;

		$start_position = 200;

		// Find certificate based on hash
		$args = array(
			'post_type' => 'certificate',
			'meta_key' => 'certificate_hash',
			'meta_value' => $pdf_certificate->hash
		);

		$query = new WP_Query( $args );
		if ( $query->have_posts() ) {

			$query->the_post();
			$certificate_id = $query->posts[0]->ID;

		} // End If Statement

		wp_reset_query();

		// Get Course Data
		$course_id = get_post_meta( $certificate_id, 'course_id', true );

		// Get the certificate template
		$certificate_template_id = get_post_meta( $course_id, '_course_certificate_template', true );

		$certificate_template_custom_fields = get_post_custom( $certificate_template_id );

		// Define the data we're going to load: Key => Default value
		$load_data = array(
			'image_ids'            => array(),
		);

		// Load the data from the custom fields
		foreach ( $load_data as $key => $default ) {

			// set value from db (unserialized if needed) or use default
			$this->$key = ( isset( $certificate_template_custom_fields[ '_' . $key ][0] ) && '' !== $certificate_template_custom_fields[ '_' . $key ][0] ) ? ( is_array( $default ) ? maybe_unserialize( $certificate_template_custom_fields[ '_' . $key ][0] ) : $certificate_template_custom_fields[ '_' . $key ][0] ) : $default;

		} // End For Loop

		// set the certificate main template image, if any
		if ( count( $this->image_ids ) > 0 ) {
			$this->image_id = $this->image_ids[0];
		} // End If Statement

		// Logo image
		if ( isset( $this->image_id ) && 0 < intval( $this->image_id ) ) {
			$image_src = wp_get_attachment_url( $this->image_id );

			// Use image path instead of URL
			$uploads = wp_upload_dir();
			$file_path = str_replace( $uploads['baseurl'], $uploads['basedir'], $image_src );

			$pdf_certificate->bg_image_src = $file_path;
		} // End If Statement

	} // End certificate_background()


	/**
	 * Returns font settings for the certificate template
	 *
	 * @access public
	 * @since  1.0.0
	 * @param string $field_key
	 * @return array $return_array
	 */
	public function get_certificate_font_settings( $field_key = '' ) {

		$return_array = array();

		if ( isset( $this->certificate_template_fields[$field_key]['font']['color'] ) && '' != $this->certificate_template_fields[$field_key]['font']['color'] ) {
			$return_array['font_color'] = $this->certificate_template_fields[$field_key]['font']['color'];
		} // End If Statement

		if ( isset( $this->certificate_template_fields[$field_key]['font']['family'] ) && '' != $this->certificate_template_fields[$field_key]['font']['family'] ) {
			$return_array['font_family'] = $this->certificate_template_fields[$field_key]['font']['family'];
		} // End If Statement

		if ( isset( $this->certificate_template_fields[$field_key]['font']['style'] ) && '' != $this->certificate_template_fields[$field_key]['font']['style'] ) {
			$return_array['font_style'] = $this->certificate_template_fields[$field_key]['font']['style'];
		} // End If Statement

		if ( isset( $this->certificate_template_fields[$field_key]['font']['size'] ) && '' != $this->certificate_template_fields[$field_key]['font']['size'] ) {
			$return_array['font_size'] = $this->certificate_template_fields[$field_key]['font']['size'];
		} // End If Statement

		return $return_array;

	} // End get_certificate_font_settings()

	/**
	 * certificate_link frontend output function for certificate link
	 *
	 * @access public
	 * @since  1.0.0
	 * @param  string $message html
	 * @return string $message html
	 */
	public function certificate_link( $message ) {
		global $current_user, $course, $woothemes_sensei, $wp_query, $post;

		if( isset( $course->ID ) ) {
			$course_id = $course->ID;
		} else {
			$course_id = $post->ID;
		}

		$certificate_template_id = get_post_meta( $course_id, '_course_certificate_template', true );

		if( ! $certificate_template_id ) return $message;

		$my_account_page_id = intval( $woothemes_sensei->settings->settings[ 'my_course_page' ] );
		$view_link_courses = $woothemes_sensei->settings->settings[ 'certificates_view_courses' ];
		$view_link_profile = $woothemes_sensei->settings->settings[ 'certificates_view_profile' ];
		$is_viewable = false;

		if ( ( is_page( $my_account_page_id ) || is_singular( 'course' ) || isset( $wp_query->query_vars['course_results'] ) ) && $view_link_courses ) {

			$is_viewable = true;

		} elseif( isset( $wp_query->query_vars['learner_profile'] ) && $view_link_profile ) {

			$is_viewable = true;

		} // End If Statement

		if ( $is_viewable ) {

			// Get User Meta
			get_currentuserinfo();

			if ( is_singular( 'course' ) ) {

				$certificate_url = $this->get_certificate_url( $post->ID, $current_user->ID );

			} else {

				$certificate_url = $this->get_certificate_url( $course->ID, $current_user->ID );

			} // End If Statement

			if ( '' != $certificate_url ) {

				$classes = '';

				if ( is_page( $my_account_page_id ) || isset( $wp_query->query_vars['learner_profile'] ) ) {

					$classes = 'button ';

				} // End If Statement

				$message = $message . '<a href="' . $certificate_url . '" class="' . $classes . 'sensei-certificate-link" title="' . esc_attr( __( 'View Certificate', 'sensei-certificates' ) ) . '">'. __( 'View Certificate', 'sensei-certificates' ) . '</a>';

			} // End If Statement

		} // End If Statement

		return $message;

	} // End certificate_link()


	/**
	 * get_certificate_url gets url for certificate
	 *
	 * @access private
	 * @since  1.0.0
	 * @param  int $course_id course post id
	 * @param  int $user_id   course learner user id
	 * @return string $certificate_url certificate link
	 */
	private function get_certificate_url( $course_id, $user_id ) {

		$certificate_url = '';

		$args = array(
			'post_type' => 'certificate',
			'author' => $user_id,
			'meta_key' => 'course_id',
			'meta_value' => $course_id
		);

		$query = new WP_Query( $args );
		if ( $query->have_posts() ) {

			$count = 0;
			while ($query->have_posts()) {

				$query->the_post();
				$certificate_url = get_permalink();

			} // End While Loop

		} // End If Statement

		wp_reset_postdata();

		return $certificate_url;

	} // End get_certificate_url()


	/**
	 * enqueue_styles loads frontend styles
	 *
	 * @access public
	 * @since  1.0.0
	 * @return void
	 */
	public function enqueue_styles() {

		$this->token = 'sensei-certificates';
		wp_register_style( $this->token . '-frontend', $this->plugin_url . 'assets/css/frontend.css', '', '1.0.0', 'screen' );
		wp_enqueue_style( $this->token . '-frontend' );

	} // End enqueue_styles()


	/**
	 * create_columns adds columns for certificates
	 *
	 * @access public
	 * @since  1.0.0
	 * @param  array $columns existing columns
	 * @return array $columns existing and new columns
	 */
	public function create_columns( $columns, $analysis ) {

		if ( 'user' == $analysis->view ) {
			$columns['certificates_link'] = __( 'Certificate', 'sensei-certificates' );
		}

		return $columns;

	} // End create_columns()


	/**
	 * populate_columns outputs column data
	 *
	 * @access public
	 * @since  1.0.0
	 * @param  array $content output
	 * @param  int $course_id course post id
	 * @param  int $user_id  course learner user id
	 * @return array $content modified output
	 */
	public function populate_columns( $content, $item, $analysis ) {

		if ( 'user' == $analysis->view ) {
			$certificate_url = $this->get_certificate_url( $analysis->course_id, $item->user_id );
			$output = '';

			if ( '' != $certificate_url ) {

				$output = '<a href="' . $certificate_url . '" class="sensei-certificate-link" title="' . esc_attr( __( 'View Certificate', 'sensei-certificates' ) ) . '">'. __( 'View Certificate', 'sensei-certificates' ) . '</a>';

			} // End If Statement

			$content['certificates_link'] = $output;
		}
		return $content;

	} // End populate_columns()


	/**
	 * Add some JavaScript inline to be output in the footer.
	 *
	 * @access public
	 * @since  1.0.0
	 * @param string $code
	 * @return void
	 */
	public function add_inline_js( $code ) {

		$this->_inline_js .= "\n" . $code . "\n";

	} // End add_inline_js()


	/**
	 * Output any queued inline JS.
	 *
	 * @access public
	 * @since  1.0.0
	 * @return void
	 */
	public function output_inline_js() {

		if ( $this->_inline_js ) {

			echo "<!-- Sensei Certificates JavaScript-->\n<script type=\"text/javascript\">\njQuery(document).ready(function($) {";

			// Sanitize
			$this->_inline_js = wp_check_invalid_utf8( $this->_inline_js );
			$this->_inline_js = preg_replace( '/&#(x)?0*(?(1)27|39);?/i', "'", $this->_inline_js );
			$this->_inline_js = str_replace( "\r", '', $this->_inline_js );

			// Output
			echo $this->_inline_js;

			echo "});\n</script>\n";

			$this->_inline_js = '';

		} // End If Statement

	} // End output_inline_js()


	/**
	 * include_sensei_scripts includes Sensei scripts and styles on Certificates pages
	 *
	 * @access public
	 * @since  1.0.0
	 * @param  array $allowed_post_types array of existing post types
	 * @return array $allowed_post_types array of additional post types
	 */
	public function include_sensei_scripts( $allowed_post_types ) {

		array_push( $allowed_post_types, 'certificate' );
		array_push( $allowed_post_types, 'certificate_template' );

		return $allowed_post_types;

	} // End include_sensei_scripts()


	/**
	 * reset_course_certificate deletes existing course certificate when the user resets a lesson
	 *
	 * @access public
	 * @since  1.0.5
	 * @param  int $user_id   User ID
	 * @param  int $lesson_id Lesson Post ID
	 * @return void
	 */
	public function reset_lesson_course_certificate( $user_id = 0, $lesson_id = 0 ) {

		if ( 0 < $user_id && 0 < $lesson_id ) {
			$course_id = get_post_meta( $lesson_id, '_lesson_course' ,true );
			if ( $course_id ) {
				return $this->reset_course_certificate( $user_id, $course_id );
			}
		}
	}

	/**
	 * reset_course_certificate deletes existing course certificate when the user resets the course
	 *
	 * @access public
	 * @since  1.0.0
	 * @param  int $user_id   User ID
	 * @param  int $course_id Course Post ID
	 * @return void
	 */
	public function reset_course_certificate( $user_id = 0, $course_id = 0 ) {

		if ( 0 < $user_id && 0 < $course_id ) {

			// Get a list of all Certificates for the Course for the User
			$certificates_array = array();

			$certificate_args = array(	'post_type' 		=> 'certificate',
										'numberposts' 		=> -1,
			    						'meta_query' => array(
																'relation' => 'AND',
																array(
																	'key' => 'course_id',
																	'value' => $course_id,
																	'compare' => '='
																),
																array(
																	'key' => 'learner_id',
																	'value' => $user_id,
																	'compare' => '='
																)
															),
			    						'post_status'       => 'any',
										'suppress_filters' 	=> true,
										'fields'			=> 'ids'
										);
			$certificates_array = get_posts( $certificate_args );

			if ( is_array( $certificates_array ) && !empty( $certificates_array ) ) {

				// Loop and delete all existing certificates
				foreach ($certificates_array as $key => $certificate_id ) {

					$dataset_changes = wp_delete_post( $certificate_id, true );

				} // End For Loop

			} // End If Statement

		} // End If Statement

	} // End reset_course_certificate()


	/**
	 * certificates_user_settings_form form output
	 *
	 * @access public
	 * @since  1.0.0
	 * @param  Object $user WordPress User object
	 * @return html
	 */
	public function certificates_user_settings_form( $user ) {

		if ( is_user_logged_in() ) {

			$view_setting = get_user_option( 'sensei_certificates_view_by_public', $user->ID );
			?>
			<div id="certificates_user_settings">
				<form class="certificates_user_meta" method="POST" action="">
		            <input type="hidden" name="<?php echo esc_attr( 'woothemes_sensei_certificates_user_meta_save_noonce' ); ?>" id="<?php echo esc_attr( 'woothemes_sensei_certificates_user_meta_save_noonce' ); ?>" value="<?php echo esc_attr( wp_create_nonce( 'woothemes_sensei_certificates_user_meta_save_noonce' ) ); ?>" />
		            <p>
		            	 <input type="checkbox" value="yes" name="certificates_user_public_view" <?php checked( $view_setting, true ); ?>/> <?php _e( 'Allow my Certificates to be publicly viewed', 'sensei-certificates' ); ?> <input type="submit" name="certificates_user_meta_save" class="certificates-submit complete" value="<?php echo apply_filters( 'sensei_certificates_save_meta_button', __( 'Save', 'sensei-certificates' ) ); ?>"/>
		            </p>
		        </form>
	    	</div>
		<?php } // End If Statement

	} // End certificates_user_settings_form()


	/**
	 * certificates_user_settings_save handles the save from the user meta form
	 *
	 * @access public
	 * @since  1.0.0
	 * @return void
	 */
	public function certificates_user_settings_save() {

		global $current_user;

		if ( is_user_logged_in() && isset( $_POST['certificates_user_meta_save'] ) && wp_verify_nonce( $_POST[ 'woothemes_sensei_certificates_user_meta_save_noonce' ], 'woothemes_sensei_certificates_user_meta_save_noonce' ) ) {

			// Update the user meta with the setting
			$current_user = wp_get_current_user();
			$current_user_id = intval( $current_user->ID );

			if ( 0  < $current_user_id ) {

				$view_setting = false;
				if ( isset( $_POST['certificates_user_public_view'] ) && 'yes' == esc_html( $_POST['certificates_user_public_view'] ) ) {
					$view_setting = true;
				} // End If Statement

				$update_success = update_user_option( $current_user_id, 'sensei_certificates_view_by_public', $view_setting );

				$this->messages = '<div class="sensei-message tick">' . apply_filters( 'sensei_certificates_user_settings_save', __( 'Your Certificates Public View Settings Saved Successfully.', 'sensei-certificates' ) ) . '</div>';

			} // End If Statement

		} // End If Statement

	} // End certificates_user_settings_save()


	/**
	 * certificates_user_settings_messages frontend notification messages
	 *
	 * @access public
	 * @since  1.0.0
	 * @return void
	 */
	public function certificates_user_settings_messages() {

		if ( isset( $this->messages ) && '' != $this->messages ) {
			echo $this->messages;
		} // End If Statement

	} // End certificates_user_settings_message()

} // End Class