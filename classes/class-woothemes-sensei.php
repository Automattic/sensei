<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Sensei Class
 *
 * Base class for Sensei.
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
 * - register_widgets()
 * - load_localisation()
 * - load_plugin_textdomain()
 * - activation()
 * - install()
 * - activate_sensei()
 * - register_plugin_version()
 * - ensure_post_thumbnails_support()
 * - template_loader()
 * - plugin_path()
 * - get_page_id()
 * - woocommerce_course_update()
 * - check_user_permissions()
 * - access_settings()
 */
class WooThemes_Sensei {
	public $admin;
	public $frontend;
	public $post_types;
	public $token = 'woothemes-sensei';
	public $plugin_url;
	public $plugin_path;
	public $slider_count = 1;
	public $version;
	public $permissions_message;
	private $file;
	
	/**
	 * Constructor.
	 * @param string $file The base file of the plugin.
	 * @since  1.0.0
	 * @return  void
	 */
	public function __construct ( $file ) {
		// Setup object data
		$this->file = $file;
		$this->plugin_url = trailingslashit( plugins_url( '', $plugin = $file ) );
		$this->plugin_path = trailingslashit( dirname( $file ) );
		$this->template_url	= apply_filters( 'sensei_template_url', 'sensei/' );
		$this->permissions_message = array( 'title' => __( 'Permission Denied', 'woothemes-sensei' ), 'message' => 'Unfortunately you do not have permissions to access this page.' );
		// Localisation
		$this->load_plugin_textdomain();
		add_action( 'init', array( &$this, 'load_localisation' ), 0 );
		// Installation
		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) $this->install();
		// Run this on activation.
		register_activation_hook( $this->file, array( &$this, 'activation' ) );
		// Load the Utils class.
		require_once( 'class-woothemes-sensei-utils.php' );
		// Setup post types.
		require_once( 'class-woothemes-sensei-posttypes.php' );
		$this->post_types = new WooThemes_Sensei_PostTypes();
		$this->post_types->token = 'woothemes-sensei-posttypes';
		// Setup settings screen.
		require_once( 'class-woothemes-sensei-settings-api.php' );
		require_once( 'class-woothemes-sensei-settings.php' );
		$this->settings = new WooThemes_Sensei_Settings();
		$this->settings->token = 'woothemes-sensei-settings';
		// Setup Admin Settings data
		if ( is_admin() ) {
			$this->settings->has_tabs 	= true;
			$this->settings->name 		= __( 'Sensei Settings', 'woothemes-sensei' );
			$this->settings->menu_label	= __( 'Settings', 'woothemes-sensei' );
			$this->settings->page_slug	= 'woothemes-sensei-settings';
		} // End If Statement
		$this->settings->setup_settings();
		// Differentiate between administration and frontend logic.
		if ( is_admin() ) {
			// Load Admin Class
			require_once( 'class-woothemes-sensei-admin.php' );
			$this->admin = new WooThemes_Sensei_Admin( $file );
			$this->admin->token = $this->token;
			// Load Analysis Reports
			require_once( 'class-woothemes-sensei-analysis.php' );
			$this->analysis = new WooThemes_Sensei_Analysis( $file );
			$this->analysis->token = $this->token;
		} else {
			// Load Frontend Class
			require_once( 'class-woothemes-sensei-frontend.php' );
			$this->frontend = new WooThemes_Sensei_Frontend();
			$this->frontend->token = $this->token;
			$this->frontend->init();
			// Frontend Hooks
			add_filter( 'template_include', array( &$this, 'template_loader' ) );
		}
		add_action( 'widgets_init', array( &$this, 'register_widgets' ) );
		add_action( 'after_setup_theme', array( &$this, 'ensure_post_thumbnails_support' ) );
	} // End __construct()

	/**
	 * Register the widgets.
	 * @return [type] [description]
	 */
	public function register_widgets () {
		// Course Component Widget
		require_once( $this->plugin_path . 'widgets/widget-woothemes-sensei-course-component.php' );
		register_widget( 'WooThemes_Sensei_Course_Component_Widget' );
		
		// Lesson Component Widget
		require_once( $this->plugin_path . 'widgets/widget-woothemes-sensei-lesson-component.php' );
		register_widget( 'WooThemes_Sensei_Lesson_Component_Widget' );
	} // End register_widgets()

	/**
	 * Load the plugin's localisation file.
	 * @access public
	 * @since 1.0.0
	 * @return void
	 */
	public function load_localisation () {
		load_plugin_textdomain( 'woothemes-sensei', false, dirname( plugin_basename( $this->file ) ) . '/lang/' );
	} // End load_localisation()

	/**
	 * Load the plugin textdomain from the main WordPress "languages" folder.
	 * @since  1.0.0
	 * @return  void
	 */
	public function load_plugin_textdomain () {
	    $domain = 'woothemes-sensei';
	    // The "plugin_locale" filter is also used in load_plugin_textdomain()
	    $locale = apply_filters( 'plugin_locale', get_locale(), $domain );
	 	load_textdomain( $domain, WP_LANG_DIR . '/' . $domain . '/' . $domain . '-' . $locale . '.mo' );
	    load_plugin_textdomain( $domain, FALSE, dirname( plugin_basename( $this->file ) ) . '/lang/' );
	} // End load_plugin_textdomain()

	/**
	 * Run on activation.
	 * @access public
	 * @since 1.0.0
	 * @return void
	 */
	public function activation () {
		$this->register_plugin_version();
	} // End activation()
	
	
	/**
	 * install function.
	 * 
	 * @access public
	 * @return void
	 */
	public function install () {
		register_activation_hook( $this->file, array( &$this, 'activate_sensei' ) );
		register_activation_hook( $this->file, 'flush_rewrite_rules' );
	} // End install()
	
	
	/**
	 * activate_sensei function.
	 * 
	 * @access public
	 * @return void
	 */
	public function activate_sensei () {
		update_option( 'skip_install_sensei_pages', 0 );
		update_option( 'sensei_installed', 1 );
	} // End activate_sensei()
	
	/**
	 * Register the plugin's version.
	 * @access public
	 * @since 1.0.0
	 * @return void
	 */
	private function register_plugin_version () {
		if ( $this->version != '' ) {
			update_option( 'woothemes-sensei' . '-version', $this->version );
		}
	} // End register_plugin_version()

	/**
	 * Ensure that "post-thumbnails" support is available for those themes that don't register it.
	 * @since  1.0.1
	 * @return  void
	 */
	public function ensure_post_thumbnails_support () {
		if ( ! current_theme_supports( 'post-thumbnails' ) ) { add_theme_support( 'post-thumbnails' ); }
	} // End ensure_post_thumbnails_support()
	
	
	/**
	 * template_loader function.
	 * 
	 * @access public
	 * @param mixed $template
	 * @return void
	 */
	public function template_loader ( $template ) {
		global $post;
		
		$find = array( 'woothemes-sensei.php' );
		$file = '';
				
		if ( is_single() && get_post_type() == 'course' ) {
		
		    if ( $this->check_user_permissions( 'course-single' ) ) {
				$file 	= 'single-course.php';
		    	$find[] = $file;
		    	$find[] = $this->template_url . $file;
			} else {
				// No Permissions Page
				$file 	= 'no-permissions.php';
				$find[] = $file;
				$find[] = $this->template_url . $file;
			} // End If Statement
			
		} elseif ( is_single() && get_post_type() == 'lesson' ) {
			
			if ( $this->check_user_permissions( 'lesson-single' ) ) {
				$file 	= 'single-lesson.php';
		    	$find[] = $file;
		    	$find[] = $this->template_url . $file;
			} else {
				// No Permissions Page
				$file 	= 'no-permissions.php';
				$find[] = $file;
				$find[] = $this->template_url . $file;
			} // End If Statement
		
		} elseif ( is_single() && get_post_type() == 'quiz' ) {
		
		    if ( $this->check_user_permissions( 'quiz-single' ) ) {
				$file 	= 'single-quiz.php';
		    	$find[] = $file;
		    	$find[] = $this->template_url . $file;
			} else {
				// No Permissions Page
				$file 	= 'no-permissions.php';
				$find[] = $file;
				$find[] = $this->template_url . $file;
			} // End If Statement
		
		} elseif ( is_tax( 'product_cat' ) || is_tax( 'product_tag' ) ) {
		
		    $term = get_queried_object();
		
		    $file 		= 'taxonomy-' . $term->taxonomy . '.php';
		    $find[] 	= 'taxonomy-' . $term->taxonomy . '-' . $term->slug . '.php';
		    $find[] 	= $this->template_url . 'taxonomy-' . $term->taxonomy . '-' . $term->slug . '.php';
		    $find[] 	= $file;
		    $find[] 	= $this->template_url . $file;
		
		} elseif ( is_post_type_archive( 'course' ) || is_page( $this->get_page_id( 'courses' ) ) ) {
		
		    $file 	= 'archive-course.php';
		    $find[] = $file;
		    $find[] = $this->template_url . $file;
		
		} elseif ( is_post_type_archive( 'lesson' ) ) {
		
		    $file 	= 'archive-lesson.php';
		    $find[] = $file;
		    $find[] = $this->template_url . $file;
		
		} // End If Statement

		// Load the template file
		if ( $file ) {
			$template = locate_template( $find );
			if ( ! $template ) $template = $this->plugin_path() . '/templates/' . $file;
		} // End If Statement

		return $template;
	} // End template_loader()
	
	
	/**
	 * plugin_path function.
	 * 
	 * @access public
	 * @return void
	 */
	public function plugin_path () {
		if ( $this->plugin_path ) return $this->plugin_path;

		return $this->plugin_path = untrailingslashit( plugin_dir_path( __FILE__ ) );
	} // End plugin_path()

	
	/**
	 * get_page_id function.
	 * 
	 * @access public
	 * @param mixed $page
	 * @return void
	 */
	public function get_page_id ( $page ) {
		$page = apply_filters( 'sensei_get_' . $page . '_page_id', get_option( 'sensei_' . $page . '_page_id' ) );
		return ( $page ) ? $page : -1;
	} // End get_page_id()
	
	
	/**
	 * woocommerce_course_update function.
	 * 
	 * @access public
	 * @param int $course_id (default: 0)
	 * @return void
	 */
	public function woocommerce_course_update ( $course_id = 0  ) {
		
		global $current_user;
		
		$data_update = false;
		
		// Get the product ID
	 	$wc_post_id = get_post_meta( $course_id, '_course_woocommerce_product', true );
	 	
	 	$is_user_taking_course = WooThemes_Sensei_Utils::sensei_check_for_activity( array( 'post_id' => $course_id, 'user_id' => $current_user->ID, 'type' => 'sensei_course_start' ) );
	 	    	
		if ( WooThemes_Sensei_Utils::sensei_is_woocommerce_activated() && sensei_customer_bought_product( $current_user->user_email, $current_user->ID, $wc_post_id ) && ( 0 < $wc_post_id ) && !$is_user_taking_course ) {
			
			$args = array(
							    'post_id' => $course_id,
							    'username' => $current_user->user_login,
							    'user_email' => $current_user->user_email,
							    'user_url' => $current_user->user_url,
							    'data' => 'Course started by the user',
							    'type' => 'sensei_course_start', /* FIELD SIZE 20 */
							    'parent' => 0,
							    'user_id' => $current_user->ID
							);
			
			$activity_logged = WooThemes_Sensei_Utils::sensei_log_activity( $args );
			
			$is_user_taking_course = false;
			if ( $activity_logged ) {
				$is_user_taking_course = true;
			} // End If Statement
			
		} // End If Statement
		
		return $is_user_taking_course;
		
	} // End woocommerce_course_update()
	
	
	/**
	 * check_user_permissions function.
	 * 
	 * @access public
	 * @param string $page (default: '')
	 * @param array $data (default: array())
	 * @return void
	 */
	public function check_user_permissions ( $page = '', $data = array() ) {
		global $current_user, $post;
		// Get User Meta
	 	get_currentuserinfo();
      	
      	$user_allowed = false;
		
		switch ( $page ) {
			case 'course-single':
				
					// check for prerequisite course or lesson,
					$course_prerequisite_id = get_post_meta( $post->ID, '_course_prerequisite', true);
					$update_course = $this->woocommerce_course_update( $post->ID  );
					// Count completed lessons
					$lessons_completed = 0;
					$course_lessons = $this->frontend->course->course_lessons( $course_prerequisite_id );
		    		$lessons_completed = 0;
		    		foreach ($course_lessons as $lesson_item){
		    			// Check if Lesson is complete
    					$user_lesson_end =  WooThemes_Sensei_Utils::sensei_get_activity_value( array( 'post_id' => $lesson_item->ID, 'user_id' => $current_user->ID, 'type' => 'sensei_lesson_end', 'field' => 'comment_content' ) );
						if ( '' != $user_lesson_end ) {
							$lessons_completed++;
						} // End If Statement
		    		} // End For Loop
		    		// Check prerequisites
		    		$prerequisite_complete = false;
					if ( absint( $lessons_completed ) == absint( count( $course_lessons ) ) && ( 0 < absint( count( $course_lessons ) ) ) && ( 0 < absint( $lessons_completed ) ) ) {
						$prerequisite_complete = true;
					} // End If Statement
					// Handles restrictions
					if ( !$prerequisite_complete && 0 < absint( $course_prerequisite_id ) ) { 
						$this->permissions_message['title'] = get_the_title( $post->ID ) . ': ' . __('Restricted Access', 'woothemes-sensei' );
						$course_link = '<a href="' . get_permalink( $course_prerequisite_id ) . '">' . __( 'course', 'woothemes-sensei' ) . '</a>';
						$this->permissions_message['message'] = sprintf( __('Please complete the previous %1$s before taking this course.', 'woothemes-sensei' ), $course_link );
					} else {
						$user_allowed = true;	
					} // End If Statement
				break;
			case 'lesson-single':
				// Check for WC purchase
				$lesson_course_id = get_post_meta( $post->ID, '_lesson_course',true );
				$update_course = $this->woocommerce_course_update( $lesson_course_id  );
				if ( $this->access_settings() && WooThemes_Sensei_Utils::sensei_check_for_activity( array( 'post_id' => $lesson_course_id, 'user_id' => $current_user->ID, 'type' => 'sensei_course_start' ) ) ) {
					$user_allowed = true;
				} else {
					$this->permissions_message['title'] = get_the_title( $post->ID ) . ': ' . __('Restricted Access', 'woothemes-sensei' );
					$course_link = '<a href="' . get_permalink( $lesson_course_id ) . '">' . __( 'course', 'woothemes-sensei' ) . '</a>';
					$wc_post_id = get_post_meta( $lesson_course_id, '_course_woocommerce_product',true );
					if ( WooThemes_Sensei_Utils::sensei_is_woocommerce_activated() && ( 0 < $wc_post_id ) ) { 
						$this->permissions_message['message'] = sprintf( __('Please purchase the %1$s before starting this Lesson.', 'woothemes-sensei' ), $course_link );
					} else {
						$this->permissions_message['message'] = sprintf( __('Please sign up for the %1$s before starting this Lesson.', 'woothemes-sensei' ), $course_link );	
					} // End If Statement
				} // End If Statement
				break;
			case 'quiz-single':
				$lesson_id = get_post_meta( $post->ID, '_quiz_lesson',true );
				$lesson_course_id = get_post_meta( $lesson_id, '_lesson_course',true );
				$update_course = $this->woocommerce_course_update( $lesson_course_id  );
				if ( $this->access_settings() && WooThemes_Sensei_Utils::sensei_check_for_activity( array( 'post_id' => $lesson_course_id, 'user_id' => $current_user->ID, 'type' => 'sensei_course_start' ) ) ) {
					// Check for prerequisite lesson for this quiz
					$lesson_prerequisite_id = get_post_meta( $lesson_id, '_lesson_prerequisite', true);
					$user_lesson_prerequisite_end =  WooThemes_Sensei_Utils::sensei_get_activity_value( array( 'post_id' => $lesson_prerequisite_id, 'user_id' => $current_user->ID, 'type' => 'sensei_lesson_end', 'field' => 'comment_content' ) );
					$user_lesson_prerequisite_complete = false;
					if ( '' != $user_lesson_prerequisite_end ) {
						$user_lesson_prerequisite_complete = true;
					} // End If Statement
					// Handle restrictions
					if ( 0 < absint( $lesson_prerequisite_id ) && ( !$user_lesson_prerequisite_complete ) ) { 
						$this->permissions_message['title'] = get_the_title( $post->ID ) . ': ' . __('Restricted Access', 'woothemes-sensei' );
						$lesson_link = '<a href="' . get_permalink( $lesson_prerequisite_id ) . '">' . __( 'lesson', 'woothemes-sensei' ) . '</a>';
						$this->permissions_message['message'] = sprintf( __('Please complete the previous %1$s before taking this Quiz.', 'woothemes-sensei' ), $lesson_link );
					} else {
						$user_allowed = true;	
					} // End If Statement
				} else {
					$this->permissions_message['title'] = get_the_title( $post->ID ) . ': ' . __('Restricted Access', 'woothemes-sensei' );
					$course_link = '<a href="' . get_permalink( get_post_meta( get_post_meta( $post->ID, '_quiz_lesson', true ), '_lesson_course', true ) ) . '">' . __( 'course', 'woothemes-sensei' ) . '</a>';
					$this->permissions_message['message'] = sprintf( __('Please sign up for the %1$s before taking this Quiz.', 'woothemes-sensei' ), $course_link );
				} // End If Statement
				break;
			default:
				$user_allowed = true;
				break;
			
		} // End Switch Statement
		return $user_allowed;
	} // End get_placeholder_image()

	
	/**
	 * access_settings function.
	 * 
	 * @access public
	 * @return void
	 */
	public function access_settings () {
		if ( isset( $this->settings->settings['access_permission'] ) && $this->settings->settings['access_permission'] ) {
        	if ( is_user_logged_in() ) {
        		return true;
        	} else {
        		return false;
        	} // End If Statement
        } else {
        	return true;
        } // End If Statement
	} // End access_settings()
} // End Class
?>