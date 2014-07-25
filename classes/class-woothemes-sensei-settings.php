<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/*
 * Sensei Settings Class
 *
 * All functionality pertaining to the settings in Sensei.
 *
 * @package WordPress
 * @subpackage Sensei
 * @category Core
 * @author WooThemes
 * @since 1.0.0
 *
 * TABLE OF CONTENTS
 *
 * - __construct()
 * - init_sections()
 * - init_fields()
 * - get_duration_options()
 * - add_contextual_help()
 * - pages_array()
 */
class WooThemes_Sensei_Settings extends WooThemes_Sensei_Settings_API {

	/**
	 * Constructor.
	 * @access public
	 * @since  1.0.0
	 * @return void
	 */
	public function __construct () {
	    parent::__construct(); // Required in extended classes.
	    add_action( 'admin_head', array( $this, 'add_contextual_help' ) );
	} // End __construct()

	/**
	 * Register the settings screen within the WordPress admin.
	 * @access public
	 * @since  1.0.0
	 * @return void
	 */
	public function register_settings_screen () {
		global $woothemes_sensei;
		$this->settings_version = $woothemes_sensei->version; // Use the global plugin version on this settings screen.
		$hook = add_submenu_page( 'sensei', $this->name, $this->menu_label, 'manage_sensei', $this->page_slug, array( $this, 'settings_screen' ) );
		$this->hook = $hook;

		if ( isset( $_GET['page'] ) && ( $_GET['page'] == $this->page_slug ) ) {
			add_action( 'admin_notices', array( $this, 'settings_errors' ) );
			add_action( 'admin_print_scripts', array( $this, 'enqueue_scripts' ) );
			add_action( 'admin_print_styles', array( $this, 'enqueue_styles' ) );
		}
	} // End register_settings_screen()

	/**
	 * Add settings sections.
	 * @access public
	 * @since  1.0.0
	 * @return void
	 */
	public function init_sections () {
		$sections = array();

		$sections['default-settings'] = array(
					'name' 			=> __( 'General', 'woothemes-sensei' ),
					'description'	=> __( 'Settings that apply to the entire plugin.', 'woothemes-sensei' )
				);

		$sections['course-settings'] = array(
					'name' 			=> __( 'Courses', 'woothemes-sensei' ),
					'description'	=> __( 'Settings that apply to all Courses.', 'woothemes-sensei' )
				);

		$sections['lesson-settings'] = array(
					'name' 			=> __( 'Lessons', 'woothemes-sensei' ),
					'description'	=> __( 'Settings that apply to all Lessons.', 'woothemes-sensei' )
				);

		$sections['email-notification-settings'] = array(
					'name' 			=> __( 'Email Notifications', 'woothemes-sensei' ),
					'description'	=> __( 'Settings for email notifications sent from your site.', 'woothemes-sensei' )
				);

		$sections['learner-profile-settings'] = array(
					'name' 			=> __( 'Learner Profiles', 'woothemes-sensei' ),
					'description'	=> __( 'Settings for public Learner Profiles.', 'woothemes-sensei' )
				);

		if ( WooThemes_Sensei_Utils::sensei_is_woocommerce_present() ) {
			$sections['woocommerce-settings'] = array(
						'name' 			=> __( 'WooCommerce', 'woothemes-sensei' ),
						'description'	=> __( 'Optional settings for WooCommerce functions.', 'woothemes-sensei' )
					);
		} // End If Statement

		$this->sections = apply_filters( 'sensei_settings_tabs', $sections );
	} // End init_sections()

	/**
	 * Add settings fields.
	 * @access public
	 * @since  1.0.0
	 * @uses   Sensei_Utils::get_slider_types()
	 * @return void
	 */
	public function init_fields () {
		global $pagenow;

		$pages_array = $this->pages_array();
		$posts_per_page_array = array( '0' => '0', '1' => '1', '2' => '2', '3' => '3', '4' => '4', '5' => '5', '6' => '6', '7' => '7', '8' => '8', '9' => '9', '10' => '10', '11' => '11', '12' => '12', '13' => '13', '14' => '14', '15' => '15', '16' => '16', '17' => '17', '18' => '18', '19' => '19', '20' => '20' );
		$complete_settings = array( 'passed' => __( 'Once all the course lessons have been completed', 'woothemes-sensei' ), 'complete' => __( 'At any time (by clicking the \'Complete Course\' button)', 'woothemes-sensei' ) );
		$course_display_settings = array( 'excerpt' => __( 'Course Excerpt', 'woothemes_sensei' ), 'full' => __( 'Full Course Content', 'woothemes_sensei' ) );

	    $fields = array();

		$fields['access_permission'] = array(
								'name' => __( 'Access Permissions', 'woothemes-sensei' ),
								'description' => __( 'Users must be logged in to view Course, Lesson, and Quiz content.', 'woothemes-sensei' ),
								'type' => 'checkbox',
								'default' => true,
								'section' => 'default-settings'
								);

		$fields['messages_disable'] = array(
								'name' => __( 'Disable Private Messages', 'woothemes-sensei' ),
								'description' => __( 'Disable the private message functions between learners and teachers.', 'woothemes-sensei' ),
								'type' => 'checkbox',
								'default' => false,
								'section' => 'default-settings'
								);

		$fields['course_page'] = array(
								'name' => __( 'Course Archive Page', 'woothemes-sensei' ),
								'description' => __( 'The page to use to display courses. If you leave this blank the default custom post type archive will apply.', 'woothemes-sensei' ),
								'type' => 'select',
								'default' => get_option( 'woothemes-sensei_courses_page_id', 0 ),
								'section' => 'default-settings',
								'required' => 0,
								'options' => $pages_array
								);

		$fields['my_course_page'] = array(
								'name' => __( 'My Courses Page', 'woothemes-sensei' ),
								'description' => __( 'The page to use to display the courses that a user is currently taking as well as the courses a user has complete.', 'woothemes-sensei' ),
								'type' => 'select',
								'default' => get_option( 'woothemes-sensei_user_dashboard_page_id', 0 ),
								'section' => 'default-settings',
								'required' => 0,
								'options' => $pages_array
								);

		$fields['placeholder_images_enable'] = array(
								'name' => __( 'Use placeholder images', 'woothemes-sensei' ),
								'description' => __( 'Output a placeholder image when no featured image has been specified for Courses and Lessons.', 'woothemes-sensei' ),
								'type' => 'checkbox',
								'default' => false,
								'section' => 'default-settings'
								);

		$fields['styles_disable'] = array(
								'name' => __( 'Disable Sensei Styles', 'woothemes-sensei' ),
								'description' => __( 'Prevent the frontend stylesheets from loading. This will remove the default styles for all Sensei elements.', 'woothemes-sensei' ),
								'type' => 'checkbox',
								'default' => false,
								'section' => 'default-settings'
								);

		$fields['js_disable'] = array(
								'name' => __( 'Disable Sensei Javascript', 'woothemes-sensei' ),
								'description' => __( 'Prevent the frontend javascript from loading. This affects the progress bars and the My Courses tabs.', 'woothemes-sensei' ),
								'type' => 'checkbox',
								'default' => false,
								'section' => 'default-settings'
								);

    	// Course Settings

    	$fields['course_completion'] = array(
								'name' => __( 'Courses are complete:', 'woothemes-sensei' ),
								'description' => __( 'This will determine when courses are marked as complete.', 'woothemes-sensei' ),
								'type' => 'select',
								'default' => 'passed',
								'section' => 'course-settings',
								'required' => 0,
								'options' => $complete_settings
								);

    	$fields['course_author'] = array(
								'name' => __( 'Display Course Author', 'woothemes-sensei' ),
								'description' => __( 'Output the Course Author on Course archive and My Courses page.', 'woothemes-sensei' ),
								'type' => 'checkbox',
								'default' => true,
								'section' => 'course-settings'
								);

    	$fields['course_archive_amount'] = array(
								'name' => __( 'Course Archive Pagination', 'woothemes-sensei' ),
								'description' => __( 'The number of courses to output for the archive pages.', 'woothemes-sensei' ),
								'type' => 'range',
								'default' => '0',
								'section' => 'course-settings',
								'required' => 0,
								'options' => $posts_per_page_array
								);

		$fields['my_course_amount'] = array(
								'name' => __( 'My Courses Pagination', 'woothemes-sensei' ),
								'description' => __( 'The number of courses to output for the my courses page.', 'woothemes-sensei' ),
								'type' => 'range',
								'default' => '0',
								'section' => 'course-settings',
								'required' => 0,
								'options' => $posts_per_page_array
								);

		$fields['course_archive_image_enable'] = array(
								'name' => __( 'Course Archive Image', 'woothemes-sensei' ),
								'description' => __( 'Output the Course Image on the Course Archive Page.', 'woothemes-sensei' ),
								'type' => 'checkbox',
								'default' => true,
								'section' => 'course-settings'
								);

		$fields['course_archive_image_width'] = array(
								'name' => __( 'Image Width - Archive', 'woothemes-sensei' ),
								'description' => __( 'The width in pixels of the featured image for the Course Archive page.', 'woothemes-sensei' ),
								'type' => 'text',
								'default' => '100',
								'section' => 'course-settings',
								'required' => 0
								);

		$fields['course_archive_image_height'] = array(
								'name' => __( 'Image Height - Archive', 'woothemes-sensei' ),
								'description' => __( 'The height in pixels of the featured image for the Course Archive page.', 'woothemes-sensei' ),
								'type' => 'text',
								'default' => '100',
								'section' => 'course-settings',
								'required' => 0
								);

		$fields['course_archive_image_hard_crop'] = array(
								'name' => __( 'Image Hard Crop - Archive', 'woothemes-sensei' ),
								'description' => sprintf( __( 'After changing this setting you may need to <a href="%s">regenerate your thumbnails</a>.', 'woothemes-sensei' ), 'http://wordpress.org/extend/plugins/regenerate-thumbnails/' ),
								'type' => 'checkbox',
								'default' => false,
								'section' => 'course-settings'
								);

		$fields['course_single_image_enable'] = array(
								'name' => __( 'Single Course Image', 'woothemes-sensei' ),
								'description' => __( 'Output the Course Image on the Single Course Page.', 'woothemes-sensei' ),
								'type' => 'checkbox',
								'default' => false,
								'section' => 'course-settings'
								);

		$fields['course_single_image_width'] = array(
								'name' => __( 'Image Width - Single', 'woothemes-sensei' ),
								'description' => __( 'The width in pixels of the featured image for the Course single post page.', 'woothemes-sensei' ),
								'type' => 'text',
								'default' => '100',
								'section' => 'course-settings',
								'required' => 0
								);

		$fields['course_single_image_height'] = array(
								'name' => __( 'Image Height - Single', 'woothemes-sensei' ),
								'description' => __( 'The height in pixels of the featured image for the Course single post page.', 'woothemes-sensei' ),
								'type' => 'text',
								'default' => '100',
								'section' => 'course-settings',
								'required' => 0
								);

		$fields['course_single_image_hard_crop'] = array(
								'name' => __( 'Image Hard Crop - Single', 'woothemes-sensei' ),
								'description' => sprintf( __( 'After changing this setting you may need to <a href="%s">regenerate your thumbnails</a>.', 'woothemes-sensei' ), 'http://wordpress.org/extend/plugins/regenerate-thumbnails/' ),
								'type' => 'checkbox',
								'default' => false,
								'section' => 'course-settings'
								);

		$fields['course_single_content_display'] = array(
								'name' => __( 'Single Course page displays:', 'woothemes-sensei' ),
								'description' => __( 'Determines what content to display on the single course page.', 'woothemes-sensei' ),
								'type' => 'select',
								'default' => 'excerpt',
								'section' => 'course-settings',
								'required' => 0,
								'options' => $course_display_settings
								);

		$fields['course_archive_featured_enable'] = array(
								'name' => __( 'Featured Courses Panel', 'woothemes-sensei' ),
								'description' => __( 'Output the Featured Courses Panel on the Course Archive Page.', 'woothemes-sensei' ),
								'type' => 'checkbox',
								'default' => true,
								'section' => 'course-settings'
								);

		$fields['course_archive_more_link_text'] = array(
								'name' => __( 'More link text', 'woothemes-sensei' ),
								'description' => __( 'The text that will be displayed on the Course Archive for the more courses link.', 'woothemes-sensei' ),
								'type' => 'text',
								'default' => __ ( 'More', 'woothemes-sensei' ),
								'section' => 'course-settings',
								'required' => 0
								);

		// Lesson Settings

		$fields['lesson_comments'] = array(
								'name' => __( 'Allow Comments for Lessons', 'woothemes-sensei' ),
								'description' => __( 'This will allow learners to post comments on the single Lesson page, only learner who have access to the Lesson will be allowed to comment.', 'woothemes-sensei' ),
								'type' => 'checkbox',
								'default' => true,
								'section' => 'lesson-settings'
								);

		$fields['lesson_author'] = array(
								'name' => __( 'Display Lesson Author', 'woothemes-sensei' ),
								'description' => __( 'Output the Lesson Author on Course single page & Lesson archive page.', 'woothemes-sensei' ),
								'type' => 'checkbox',
								'default' => true,
								'section' => 'lesson-settings'
								);

		$fields['course_lesson_image_enable'] = array(
								'name' => __( 'Course Lesson Images', 'woothemes-sensei' ),
								'description' => __( 'Output the Lesson Image on the Single Course Page.', 'woothemes-sensei' ),
								'type' => 'checkbox',
								'default' => false,
								'section' => 'lesson-settings'
								);

		$fields['lesson_archive_image_width'] = array(
								'name' => __( 'Image Width - Course Lessons', 'woothemes-sensei' ),
								'description' => __( 'The width in pixels of the featured image for the Lessons on the Course Single page.', 'woothemes-sensei' ),
								'type' => 'text',
								'default' => '100',
								'section' => 'lesson-settings',
								'required' => 0
								);

		$fields['lesson_archive_image_height'] = array(
								'name' => __( 'Image Height - Course Lessons', 'woothemes-sensei' ),
								'description' => __( 'The height in pixels of the featured image for the Lessons on the Course Single page.', 'woothemes-sensei' ),
								'type' => 'text',
								'default' => '100',
								'section' => 'lesson-settings',
								'required' => 0
								);

		$fields['lesson_archive_image_hard_crop'] = array(
								'name' => __( 'Image Hard Crop - Course Lessons', 'woothemes-sensei' ),
								'description' => sprintf( __( 'After changing this setting you may need to <a href="%s">regenerate your thumbnails</a>.', 'woothemes-sensei' ), 'http://wordpress.org/extend/plugins/regenerate-thumbnails/' ),
								'type' => 'checkbox',
								'default' => false,
								'section' => 'lesson-settings'
								);

		$fields['lesson_single_image_enable'] = array(
								'name' => __( 'Single Lesson Images', 'woothemes-sensei' ),
								'description' => __( 'Output the Lesson Image on the Single Lesson Page.', 'woothemes-sensei' ),
								'type' => 'checkbox',
								'default' => false,
								'section' => 'lesson-settings'
								);

		$fields['lesson_single_image_width'] = array(
								'name' => __( 'Image Width - Single', 'woothemes-sensei' ),
								'description' => __( 'The width in pixels of the featured image for the Lessons single post page.', 'woothemes-sensei' ),
								'type' => 'text',
								'default' => '100',
								'section' => 'lesson-settings',
								'required' => 0
								);

		$fields['lesson_single_image_height'] = array(
								'name' => __( 'Image Height - Single', 'woothemes-sensei' ),
								'description' => __( 'The height in pixels of the featured image for the Lessons single post page.', 'woothemes-sensei' ),
								'type' => 'text',
								'default' => '100',
								'section' => 'lesson-settings',
								'required' => 0
								);

		$fields['lesson_single_image_hard_crop'] = array(
								'name' => __( 'Image Hard Crop - Single', 'woothemes-sensei' ),
								'description' => sprintf( __( 'After changing this setting you may need to <a href="%s">regenerate your thumbnails</a>.', 'woothemes-sensei' ), 'http://wordpress.org/extend/plugins/regenerate-thumbnails/' ),
								'type' => 'checkbox',
								'default' => false,
								'section' => 'lesson-settings'
								);

		// Learner Profile settings

		$profile_url_base = apply_filters( 'sensei_learner_profiles_url_base', __( 'learner', 'woothemes-sensei') );
		$profile_url_example = trailingslashit( get_site_url() ) . $profile_url_base . '/%username%';
		$fields['learner_profile_enable'] = array(
							'name' => __( 'Public learner profiles', 'woothemes-sensei' ),
							'description' => sprintf( __( 'Enable public learner profiles that will be accassible to everyone. Profile URL format: %s', 'woothemes-sensei' ), $profile_url_example ),
							'type' => 'checkbox',
							'default' => true,
							'section' => 'learner-profile-settings'
							);

		$fields['learner_profile_show_courses'] = array(
							'name' => __( 'Show learner\'s courses', 'woothemes-sensei' ),
							'description' => __( 'Display the learner\'s active and completed courses on their profile.', 'woothemes-sensei' ),
							'type' => 'checkbox',
							'default' => true,
							'section' => 'learner-profile-settings'
							);

		// Email notifications

		$learner_email_options = array(
			'learner-graded-quiz' => __( 'Their quiz is graded (auto and manual grading)', 'woothemes-sensei' ),
			'learner-completed-course' => __( 'They complete a course', 'woothemes-sensei' ),
		);

		$teacher_email_options = array(
			'teacher-completed-course' => __( 'A learner completes their course', 'woothemes-sensei' ),
			'teacher-started-course' => __( 'A learner starts their course', 'woothemes-sensei' ),
			'teacher-quiz-submitted' => __( 'A learner submits a quiz for grading', 'woothemes-sensei' ),
			'teacher-new-message' => __( 'A learner sends a private message to a teacher', 'woothemes-sensei' ),
		);

		$global_email_options = array(
			'new-message-reply' => __( 'They receive a reply to their private message', 'woothemes-sensei' ),
		);

		$fields['email_learners'] = array(
								'name' => __( 'Emails Sent to Learners', 'woothemes-sensei' ),
								'description' => __( 'Select the notifications that will be sent to learners.', 'woothemes-sensei' ),
								'type' => 'multicheck',
								'options' => $learner_email_options,
								'defaults' => array( 'learner-graded-quiz', 'learner-completed-course' ),
								'section' => 'email-notification-settings'
								);

		$fields['email_teachers'] = array(
								'name' => __( 'Emails Sent to Teachers', 'woothemes-sensei' ),
								'description' => __( 'Select the notifications that will be sent to teachers.', 'woothemes-sensei' ),
								'type' => 'multicheck',
								'options' => $teacher_email_options,
								'defaults' => array( 'teacher-completed-course', 'teacher-started-course', 'teacher-quiz-submitted', 'teacher-new-message' ),
								'section' => 'email-notification-settings'
								);

		$fields['email_global'] = array(
								'name' => __( 'Emails Sent to All Users', 'woothemes-sensei' ),
								'description' => __( 'Select the notifications that will be sent to all users.', 'woothemes-sensei' ),
								'type' => 'multicheck',
								'options' => $global_email_options,
								'defaults' => array( 'new-message-reply' ),
								'section' => 'email-notification-settings'
								);

		$fields['email_from_name'] = array(
								'name' => __( '"From" Name', 'woothemes-sensei' ),
								'description' => __( 'The name from which all emails will be sent.', 'woothemes-sensei' ),
								'type' => 'text',
								'default' => get_bloginfo( 'name' ),
								'section' => 'email-notification-settings',
								'required' => 1
								);

		$fields['email_from_address'] = array(
								'name' => __( '"From" Address', 'woothemes-sensei' ),
								'description' => __( 'The address from which all emails will be sent.', 'woothemes-sensei' ),
								'type' => 'text',
								'default' => get_bloginfo( 'admin_email' ),
								'section' => 'email-notification-settings',
								'required' => 1
								);

		$fields['email_header_image'] = array(
								'name' => __( 'Header Image', 'woothemes-sensei' ),
								'description' => sprintf( __( 'Enter a URL to an image you want to show in the email\'s header. Upload your image using the %1$smedia uploader%2$s.', 'woothemes-sensei' ), '<a href="' . admin_url( 'media-new.php' ) . '">', '</a>' ),
								'type' => 'text',
								'default' => '',
								'section' => 'email-notification-settings',
								'required' => 0
								);

		$fields['email_footer_text'] = array(
								'name' => __( 'Email Footer Text', 'woothemes-sensei' ),
								'description' => __( 'The text to appear in the footer of Sensei emails.', 'woothemes-sensei' ),
								'type' => 'textarea',
								'default' => sprintf( __( '%1$s - Powered by Sensei', 'woothemes-sensei' ), get_bloginfo( 'name' ) ),
								'section' => 'email-notification-settings',
								'required' => 0
								);

		$fields['email_base_color'] = array(
								'name' => __( 'Base Colour', 'woothemes-sensei' ),
								'description' => sprintf( __( 'The base colour for Sensei email templates. Default %1$s#557da1%2$s.', 'woothemes-sensei' ), '<code>', '</code>' ),
								'type' => 'color',
								'default' => '#557da1',
								'section' => 'email-notification-settings',
								'required' => 1
								);

		$fields['email_background_color'] = array(
								'name' => __( 'Background Colour', 'woothemes-sensei' ),
								'description' => sprintf( __( 'The background colour for Sensei email templates. Default %1$s#f5f5f5%2$s.', 'woothemes-sensei' ), '<code>', '</code>' ),
								'type' => 'color',
								'default' => '#f5f5f5',
								'section' => 'email-notification-settings',
								'required' => 1
								);

		$fields['email_body_background_color'] = array(
								'name' => __( 'Body Background Colour', 'woothemes-sensei' ),
								'description' => sprintf( __( 'The main body background colour for Sensei email templates. Default %1$s#fdfdfd%2$s.', 'woothemes-sensei' ), '<code>', '</code>' ),
								'type' => 'color',
								'default' => '#fdfdfd',
								'section' => 'email-notification-settings',
								'required' => 1
								);

		$fields['email_text_color'] = array(
								'name' => __( 'Body Text Colour', 'woothemes-sensei' ),
								'description' => sprintf( __( 'The main body text colour for Sensei email templates. Default %1$s#505050%2$s.', 'woothemes-sensei' ), '<code>', '</code>' ),
								'type' => 'color',
								'default' => '#505050',
								'section' => 'email-notification-settings',
								'required' => 1
								);

		if ( WooThemes_Sensei_Utils::sensei_is_woocommerce_present() ) {
			// WooCommerce Settings
    		$fields['woocommerce_enabled'] = array(
									'name' => __( 'Enable WooCommerce Courses', 'woothemes-sensei' ),
									'description' => __( 'Use WooCommerce to sell Courses by linking a Product to a Course.', 'woothemes-sensei' ),
									'type' => 'checkbox',
									'default' => true,
									'section' => 'woocommerce-settings'
									);

			$fields['course_archive_free_enable'] = array(
									'name' => __( 'Free Courses Panel', 'woothemes-sensei' ),
									'description' => __( 'Output the Free Courses Panel on the Course Archive Page.', 'woothemes-sensei' ),
									'type' => 'checkbox',
									'default' => true,
									'section' => 'woocommerce-settings'
									);

			$fields['course_archive_paid_enable'] = array(
									'name' => __( 'Paid Courses Panel', 'woothemes-sensei' ),
									'description' => __( 'Output the Paid Courses Panel on the Course Archive Page.', 'woothemes-sensei' ),
									'type' => 'checkbox',
									'default' => true,
									'section' => 'woocommerce-settings'
									);

		} // End If Statement

		$this->fields = apply_filters( 'sensei_settings_fields', $fields );

	} // End init_fields()

	/**
	 * Get options for the duration fields.
	 * @since  1.0.0
	 * @param  $include_milliseconds (default: true) Whether or not to include milliseconds between 0 and 1.
	 * @return array Options between 0.1 and 10 seconds.
	 */
	private function get_duration_options ( $include_milliseconds = true ) {
		$numbers = array( '1.0', '1.5', '2.0', '2.5', '3.0', '3.5', '4.0', '4.5', '5.0', '5.5', '6.0', '6.5', '7.0', '7.5', '8.0', '8.5', '9.0', '9.5', '10.0' );
		$options = array();

		if ( true == (bool)$include_milliseconds ) {
			$milliseconds = array( '0.1', '0.2', '0.3', '0.4', '0.5', '0.6', '0.7', '0.8', '0.9' );
			foreach ( $milliseconds as $k => $v ) {
				$options[$v] = $v;
			}
		} else {
			$options['0.5'] = '0.5';
		}

		foreach ( $numbers as $k => $v ) {
			$options[$v] = $v;
		}

		return $options;
	} // End get_duration_options()

	/**
	 * Add contextual help to the settings screen.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function add_contextual_help () {
		// TODO
	}  // End add_contextual_help()


	/**
	 * Return an array of pages.
	 * @access private
	 * @since  1.0.0
	 * @return void
	 */
	private function pages_array() {
		// REFACTOR - Transform this into a field type instead.
		// Setup an array of portfolio gallery terms for a dropdown.
		$args = array( 'echo' => 0, 'hierarchical' => 1, 'sort_column' => 'post_title', 'sort_order' => 'ASC' );
		$pages_dropdown = wp_dropdown_pages( $args );
		$page_items = array();

		// Quick string hack to make sure we get the pages with the indents.
		$pages_dropdown = str_replace( "<select class='chosen_select' name='page_id' id='page_id'>", '', $pages_dropdown );
		$pages_dropdown = str_replace( '</select>', '', $pages_dropdown );
		$pages_split = explode( '</option>', $pages_dropdown );

		$page_items[] = __( 'Select a Page:', 'woothemes-sensei' );

		foreach ( $pages_split as $k => $v ) {
		    $id = '';
		    // Get the ID value.
		    preg_match( '/value="(.*?)"/i', $v, $matches );

		    if ( isset( $matches[1] ) ) {
		        $id = $matches[1];
		        $page_items[$id] = trim( strip_tags( $v ) );
		    } // End If Statement
		} // End For Loop

		$pages_array = $page_items;

		return $pages_array;
	} // End pages_array()
} // End Class
?>